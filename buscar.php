<?php
// buscar.php
session_name("GA");
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: /Pedidos_GA/Sesion/login.html");
    exit;
}

require_once __DIR__ . "/Conexiones/Conexion.php";
header('Content-Type: text/html; charset=UTF-8');

if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST['busqueda'])) {
    echo "Por favor, ingrese un término de búsqueda.";
    exit;
}

$busqueda = trim($_POST['busqueda'] ?? '');
if ($busqueda === '') {
    echo "Por favor, ingrese un término de búsqueda.";
    exit;
}

$rolSesion = $_SESSION["Rol"] ?? '';
$sucSesion = strtoupper($_SESSION["Sucursal"] ?? '');

// ---------- Sucursales visibles ----------
$sucursales_permitidas = [];
if ($rolSesion === 'Admin' && $sucSesion === 'TODAS') {
    // Admin con TODAS: sin filtro por sucursal
    $sucursales_permitidas = [];
} elseif ($rolSesion === 'JC' && $sucSesion === 'TAPATIA') {
    // JC Tapatía: Tapatía + Iluminación
    $sucursales_permitidas = ['TAPATIA', 'ILUMINACION'];
} else {
    // Resto: su sucursal de sesión (si no es TODAS)
    if ($sucSesion && $sucSesion !== 'TODAS') {
        $sucursales_permitidas = [$sucSesion];
    } else {
        // fallback prudente: no abrir todo si no es admin
        $sucursales_permitidas = [$sucSesion ?: '___NULO___'];
    }
}

// ---------- WHERE de búsqueda ----------
$cols = [
  'ID','VENDEDOR','ESTADO','FECHA_RECEPCION_FACTURA','CHOFER_ASIGNADO',
  'FACTURA','DIRECCION','NOMBRE_CLIENTE','CONTACTO','SUCURSAL','tipo_envio'
];

$likeParts = [];
$types = '';
$params = [];
$needle = "%{$busqueda}%";

foreach ($cols as $c) {
    $likeParts[] = "$c LIKE ?";
    $types .= 's';
    $params[] = $needle;
}
$where = [];
$where[] = '(' . implode(' OR ', $likeParts) . ')';

if (!empty($sucursales_permitidas)) {
    $place_suc = implode(',', array_fill(0, count($sucursales_permitidas), '?'));
    $where[] = "SUCURSAL IN ($place_suc)";
    $types  .= str_repeat('s', count($sucursales_permitidas));
    foreach ($sucursales_permitidas as $s) { $params[] = $s; }
}

$whereSql = implode(' AND ', $where);

// ---------- Consulta segura ----------
$sql = "SELECT * FROM pedidos
        WHERE $whereSql
        ORDER BY FECHA_RECEPCION_FACTURA DESC
        LIMIT 500";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// ---------- Render ----------
if ($result && $result->num_rows > 0) {
    echo "<table class='mi-tabla' border='1'>";
    echo "<tr>
            <th>N°</th>
            <th>Factura (caja)</th>
            <th>Estado</th>
            <th>Tipo de Envío</th>
            <th>Sucursal</th>
            <th>Fecha Recepción Factura</th>
            <th>Chofer Asignado</th>
            <th>Vendedor</th>
            <th>Factura</th>
            <th>Dirección</th>
            <th>Nombre Cliente</th>
            <th>Contacto</th>
            <th>Acción</th>
          </tr>";

    while ($row = $result->fetch_assoc()) {
        // Estado -> color
        $estado = $row["ESTADO"] ?? '';
        $colorEstado = "#FFFFFF";
        switch (strtoupper($estado)) {
            case "CANCELADO":    $colorEstado = "#FFCCCC"; break;
            case "EN TIENDA":    $colorEstado = "#FFFFCC"; break;
            case "REPROGRAMADO": $colorEstado = "#E6CCFF"; break;
            case "ACTIVO":       $colorEstado = "#CCE5FF"; break;
            case "EN RUTA":      $colorEstado = "#FFD699"; break;
            case "ENTREGADO":    $colorEstado = "#CCFFCC"; break;
        }

        // Tipo envío -> color
        $tipoEnvio = $row["tipo_envio"] ?? '';
        $colorTipo = "#FFFFFF";
        switch (strtolower($tipoEnvio)) {
            case "domicilio":   $colorTipo = "#e0ffd9ff"; break;
            case "programado":  $colorTipo = "#e0ffd9ff"; break;
            case "paqueteria":
            case "paquetería":  $colorTipo = "#edc6ffff"; break;
        }

        // Badge factura caja
        $estadoFactura = intval($row["estado_factura_caja"] ?? 0);
        $badge = '';
        $accionHtml = '';
        switch ($estadoFactura) {
            case 0:
                $badge = "<span class='badge badge-azul'>En Caja</span>";
                if (in_array($_SESSION["Rol"], ["Admin","JC"])) {
                    $accionHtml = "<button type='button' class='btn btn-sm btn-primary accion-factura' data-id='".htmlspecialchars($row["ID"])."' data-accion='entregar_jefe'>Entregar a Jefe</button>";
                }
                break;
            case 1:
                $badge = "<span class='badge badge-amarillo'>Con Jefe de choferes</span>";
                if (in_array($_SESSION["Rol"], ["Admin","JC"])) {
                    $accionHtml = "<button type='button' class='btn btn-sm btn-success accion-factura' data-id='".htmlspecialchars($row["ID"])."' data-accion='devolver_caja'>Devolver a Caja</button>";
                }
                break;
            case 2:
            default:
                $badge = "<span class='badge badge-verde'>Devuelta a Caja</span>";
                break;
        }

        // Chofer asignado -> resaltar si vacío
        $choferAsignado = $row["CHOFER_ASIGNADO"] ?? '';
        $colorChofer = ($choferAsignado === '' ? "#FFCCCC" : "#FFFFFF");

        echo "<tr>";
        echo "<td>" . htmlspecialchars($row["ID"]) . "</td>";
        echo "<td>{$badge}<div style='margin-top:6px'>{$accionHtml}</div></td>";
        echo "<td style='background-color: $colorEstado;'>" . htmlspecialchars($estado) . "</td>";
        echo "<td style='background-color: $colorTipo;'>" . htmlspecialchars($tipoEnvio) . "</td>";
        echo "<td>" . htmlspecialchars($row["SUCURSAL"] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($row["FECHA_RECEPCION_FACTURA"] ?? '') . "</td>";
        echo "<td style='background-color: $colorChofer;'>" . htmlspecialchars($choferAsignado) . "</td>";
        echo "<td>" . htmlspecialchars($row["VENDEDOR"] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($row["FACTURA"] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($row["DIRECCION"] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($row["NOMBRE_CLIENTE"] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($row["CONTACTO"] ?? '') . "</td>";
        echo "<td><a href='Inicio.php?id=" . urlencode($row["ID"]) . "'>Ver Detalles</a></td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No se encontraron resultados para la búsqueda: '" . htmlspecialchars($busqueda, ENT_QUOTES, 'UTF-8') . "'.";
}

$conn->close();
?>
<style>
/* --- La celda toma el color del estado --- */
.mi-tabla td:has(.badge-azul){
  background:#e6f0ff; color:#1247d6;
}
.mi-tabla td:has(.badge-amarillo){
  background:#fff6d6; color:#8a6d00;
}
.mi-tabla td:has(.badge-verde){
  background:#e7f9e7; color:#217a21;
}

/* Respira y alinea mejor contenido dentro de la celda coloreada */
.mi-tabla td:has(.badge-azul),
.mi-tabla td:has(.badge-amarillo),
.mi-tabla td:has(.badge-verde){
  padding:10px 12px;
}

/* El badge ya no pinta fondo: solo texto (para que se vea el color de la celda) */
.badge{ padding:0; border-radius:8px; font-size:12px; font-weight:700; }
.badge-azul, .badge-amarillo, .badge-verde{
  background:transparent; color:inherit;
}

/* Separación entre etiqueta y botón */
.mi-tabla td .badge + div{ margin-top:8px; }

/* Botones legibles sobre fondos claros */
.btn{ border:0; padding:6px 10px; border-radius:8px; cursor:pointer; font-weight:600; }
.btn-primary{ background:#2d6cdf; color:#fff; }
.btn-success{ background:#22a06b; color:#fff; }
.btn:disabled{ opacity:.6; cursor:not-allowed; }

/* (opcional) que los links dentro de la celda sigan siendo visibles */
.mi-tabla td:has(.badge-azul) a,
.mi-tabla td:has(.badge-amarillo) a,
.mi-tabla td:has(.badge-verde) a{ color:inherit; }
</style>
