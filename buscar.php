<?php
// buscar.php
session_name("GA");
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: /Pedidos_GA/Sesion/login.html");
    exit;
}

require_once __DIR__ . "/Conexiones/Conexion.php";
require_once __DIR__ . "/helpers/filtro_pedidos_rol.php";
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
$sucursales_permitidas = sucursalesPermitidasPorRol($rolSesion, $sucSesion);

// ---------- WHERE de búsqueda ----------
$cols = [
  'p.ID','p.VENDEDOR','p.ESTADO','p.FECHA_RECEPCION_FACTURA','p.CHOFER_ASIGNADO',
  'p.FACTURA','p.DIRECCION','p.NOMBRE_CLIENTE','p.CONTACTO','p.SUCURSAL','p.tipo_envio'
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
    $where[] = "p.SUCURSAL IN ($place_suc)";
    $types  .= str_repeat('s', count($sucursales_permitidas));
    foreach ($sucursales_permitidas as $s) { $params[] = $s; }
}

// ---------- Filtro por vendedor (Rol VR ve solo lo suyo) ----------
$vendedorFiltro = nombreVendedorFiltro($rolSesion);
if ($vendedorFiltro !== null) {
    $where[] = "TRIM(p.VENDEDOR) = ?";
    $types  .= 's';
    $params[] = $vendedorFiltro;
}

$whereSql = implode(' AND ', $where);

// ---------- Consulta segura ----------
$sql = "SELECT p.*, pd.lat, pd.lng 
        FROM pedidos p
        LEFT JOIN pedidos_destinatario pd ON p.ID = pd.pedido_id
        WHERE $whereSql
        ORDER BY p.FECHA_RECEPCION_FACTURA DESC
        LIMIT 500";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// ---------- Render ----------
if ($result && $result->num_rows > 0) {
    echo "<table class='mi-tabla' border='1'>";
    
    // Mostrar columna de checkbox solo para Admin y JC (igual que en filtrar.php)
    $mostrarCheckbox = in_array($rolSesion, ["Admin", "JC"]);
    $checkboxHeader = $mostrarCheckbox ? "<th><input type='checkbox' id='selectAll' title='Seleccionar todos'></th>" : "";

    echo "<tr>
            $checkboxHeader
            <th>N°</th>
            <th style='min-width: 145px; text-align: center;'>Factura (caja)</th>
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
                    $accionHtml = "<button type='button' class='btn btn-sm btn-warning accion-factura' data-id='".htmlspecialchars($row["ID"])."' data-accion='pendiente_surtido'>Pendiente de Surtido</button>";
                }
                break;
            case 2:
                $badge = "<span class='badge badge-naranja'>Pendiente de Surtido</span>";
                if (in_array($_SESSION["Rol"], ["Admin","JC"])) {
                    $accionHtml = "<button type='button' class='btn btn-sm btn-info accion-factura' data-id='".htmlspecialchars($row["ID"])."' data-accion='surtido'>&#10004; Surtido</button>";
                }
                break;
            case 3:
                $badge = "<span class='badge badge-cyan'>Surtido</span>";
                if (in_array($_SESSION["Rol"], ["Admin","JC"])) {
                    $accionHtml = "<button type='button' class='btn btn-sm btn-success accion-factura' data-id='".htmlspecialchars($row["ID"])."' data-accion='cargado_camioneta'>&#128666; Cargado en Camioneta</button>";
                }
                break;
            case 4:
                $badge = "<span class='badge badge-morado'>Cargado en Camioneta</span>";
                break;
            case 5:
                $badge = "<span class='badge badge-ruta'>En Ruta</span>";
                break;
            case 6:
            default:
                $badge = "<span class='badge badge-verde'>Entregado</span>";
                break;
        }

        // Chofer asignado -> resaltar si vacío
        $choferAsignado = $row["CHOFER_ASIGNADO"] ?? '';
        $colorChofer = ($choferAsignado === '' ? "#FFCCCC" : "#FFFFFF");

        // Coordenadas (Igual que en filtrar.php para compatibilidad con mapa)
        $finalLat = !empty($row["lat"]) ? $row["lat"] : null;
        $finalLng = !empty($row["lng"]) ? $row["lng"] : null;
        $coordenadasStr = '';
        if ($finalLat && $finalLng) {
            $coordenadasStr = $finalLat . "," . $finalLng;
        } else {
            $coordenadasStr = isset($row["Coord_Destino"]) ? $row["Coord_Destino"] : '';
        }
        $coordenadas = htmlspecialchars($coordenadasStr);
        
        $checkboxCell = $mostrarCheckbox ? "<td style='text-align:center;'><input type='checkbox' class='pedido-checkbox' 
            data-id='".htmlspecialchars($row["ID"])."' 
            data-estado='".htmlspecialchars($estado)."' 
            data-tipo-envio='".htmlspecialchars($tipoEnvio)."' 
            data-sucursal='".htmlspecialchars($row["SUCURSAL"] ?? '')."' 
            data-factura='".htmlspecialchars($row["FACTURA"] ?? '')."' 
            data-cliente='".htmlspecialchars($row["NOMBRE_CLIENTE"] ?? '')."' 
            data-direccion='".htmlspecialchars($row["DIRECCION"] ?? '')."' 
            data-coordenadas='$coordenadas'
            data-chofer='".htmlspecialchars($row["CHOFER_ASIGNADO"] ?? '')."'
            data-grupo-id='".htmlspecialchars($row["grupo_id"] ?? '')."'></td>" : "";

        echo "<tr>";
        echo $checkboxCell;
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
.mi-tabla td:has(.badge-azul)    { background:#e6f0ff; color:#1247d6; }
.mi-tabla td:has(.badge-amarillo){ background:#fff6d6; color:#8a6d00; }
.mi-tabla td:has(.badge-naranja) { background:#fff0e0; color:#b85c00; }
.mi-tabla td:has(.badge-cyan)    { background:#e0f7fa; color:#006064; }
.mi-tabla td:has(.badge-morado)  { background:#f3e5f5; color:#6a1b9a; }
.mi-tabla td:has(.badge-ruta)    { background:#fff8e1; color:#e65100; }
.mi-tabla td:has(.badge-verde)   { background:#e7f9e7; color:#217a21; }

.mi-tabla td:has(.badge-azul),
.mi-tabla td:has(.badge-amarillo),
.mi-tabla td:has(.badge-naranja),
.mi-tabla td:has(.badge-cyan),
.mi-tabla td:has(.badge-morado),
.mi-tabla td:has(.badge-ruta),
.mi-tabla td:has(.badge-verde){ padding:10px 12px; }

.badge{ padding:0; border-radius:8px; font-size:12px; font-weight:700; }
.badge-azul,.badge-amarillo,.badge-naranja,.badge-cyan,.badge-morado,.badge-ruta,.badge-verde{
  background:transparent; color:inherit;
}

.mi-tabla td .badge + div{ margin-top:8px; }

.btn{ border:0; padding:4px 8px; border-radius:8px; cursor:pointer; font-weight:600; font-size:11px; white-space:normal; line-height:1.2; text-align:center; display:inline-block; }
.btn-primary { background:#2d6cdf; color:#fff; }
.btn-warning { background:#f59e0b; color:#fff; }
.btn-info    { background:#0891b2; color:#fff; }
.btn-success { background:#22a06b; color:#fff; }
.btn:disabled{ opacity:.6; cursor:not-allowed; }

.mi-tabla td:has(.badge-azul) a,
.mi-tabla td:has(.badge-amarillo) a,
.mi-tabla td:has(.badge-naranja) a,
.mi-tabla td:has(.badge-cyan) a,
.mi-tabla td:has(.badge-morado) a,
.mi-tabla td:has(.badge-ruta) a,
.mi-tabla td:has(.badge-verde) a{ color:inherit; }
</style>
