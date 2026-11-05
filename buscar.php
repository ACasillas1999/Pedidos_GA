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
$sql = "SELECT p.*, pd.lat, pd.lng, (SELECT COUNT(*) FROM pedidos_incidencias WHERE pedido_id = p.ID) as total_incidencias 
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
    $checkboxHeader = $mostrarCheckbox ? "<th style='width: 34px;'><input type='checkbox' id='selectAll' title='Seleccionar todos'></th>" : "";

    echo "<tr>
            $checkboxHeader
            <th style='width: 56px;'>N°</th>
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
            <th style='width: 112px; text-align: center;'>Acción</th>
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

        $choferDisplay = ($choferAsignado === '' || trim($choferAsignado) === '[object Object]')
            ? "<span class='tbl-sin-chofer'>Sin asignar</span>"
            : "<span class='tbl-chofer-nombre'>" . htmlspecialchars($choferAsignado) . "</span>";

        echo "<tr>";
        echo $checkboxCell;
        echo "<td><span class='tbl-id-badge'>" . htmlspecialchars($row["ID"]) . "</span></td>";
        echo "<td>{$badge}<div style='margin-top:6px'>{$accionHtml}</div></td>";
        echo "<td style='background-color: $colorEstado;'><span class='tbl-status-pill'>" . htmlspecialchars($estado) . "</span></td>";
        echo "<td style='background-color: $colorTipo;'><span class='tbl-envio-pill'>" . strtoupper(htmlspecialchars($tipoEnvio)) . "</span></td>";
        echo "<td><span class='tbl-sucursal-tag'>" . htmlspecialchars($row["SUCURSAL"] ?? '') . "</span></td>";
        echo "<td class='tbl-fecha-cell'>" . htmlspecialchars($row["FECHA_RECEPCION_FACTURA"] ?? '') . "</td>";
        echo "<td style='background-color: $colorChofer;'>" . $choferDisplay . "</td>";
        echo "<td class='tbl-vendedor-cell'>" . htmlspecialchars($row["VENDEDOR"] ?? '') . "</td>";
        echo "<td class='tbl-factura-cell'>" . htmlspecialchars($row["FACTURA"] ?? '') . "</td>";
        echo "<td class='tbl-direccion-cell'>" . htmlspecialchars($row["DIRECCION"] ?? '') . "</td>";
        echo "<td class='tbl-cliente-cell'><div class='tbl-cliente-nombre'>" . htmlspecialchars($row["NOMBRE_CLIENTE"] ?? '') . "</div></td>";
        echo "<td class='tbl-contacto-cell'>" . htmlspecialchars($row["CONTACTO"] ?? '') . "</td>";
        $totalIncidencias = intval($row["total_incidencias"] ?? 0);
        $facturaEsc = htmlspecialchars($row["FACTURA"] ?? '', ENT_QUOTES);
        $svgSearch = "<svg width='13' height='13' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round' style='flex-shrink:0;'><circle cx='11' cy='11' r='8'/><line x1='21' y1='21' x2='16.65' y2='16.65'/></svg>";
        $svgAlert = "<svg width='13' height='13' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round' style='flex-shrink:0;'><path d='m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z'/><line x1='12' y1='9' x2='12' y2='13'/><line x1='12' y1='17' x2='12.01' y2='17'/></svg>";
        $svgPlus = "<svg width='13' height='13' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2.4' stroke-linecap='round' stroke-linejoin='round' style='flex-shrink:0;'><line x1='12' y1='5' x2='12' y2='19'/><line x1='5' y1='12' x2='19' y2='12'/></svg>";
        if ($totalIncidencias > 0) {
            $txtInc = ($totalIncidencias === 1) ? "1 Incidencia" : "$totalIncidencias Incidencias";
            $btnIncidencia = "<button type='button' class='btn-accion-modern btn-accion-inc-activa' onclick='abrirModalIncidenciasDesktop(" . $row["ID"] . ", \"" . $facturaEsc . "\")' title='Ver incidencias de este pedido'>$svgAlert<span>$txtInc</span></button>";
        } else {
            $btnIncidencia = "<button type='button' class='btn-accion-modern btn-accion-inc-nueva' onclick='abrirFormularioNuevaIncidenciaQuick(" . $row["ID"] . ", \"" . $facturaEsc . "\")' title='Registrar incidencia en este pedido'>$svgPlus<span>Incidencia</span></button>";
        }
        echo "<td><div class='acciones-pedido-wrap'><a href='Inicio.php?id=" . urlencode($row["ID"]) . "' class='btn-accion-modern btn-accion-detalles' title='Ver detalles del pedido'>$svgSearch<span>Ver Detalles</span></a>$btnIncidencia</div></td>";
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

/* Evitar desbordamiento de textos largos (correos, direcciones) y botones entre columnas */
.mi-tabla td,
.mi-tabla th {
  word-break: break-word;
  overflow-wrap: anywhere;
}

.mi-tabla {
  border-collapse: separate !important;
  border-spacing: 0 !important;
  border: 1px solid #cbd5e1 !important;
  border-radius: 14px !important;
  overflow: hidden !important;
  box-shadow: 0 6px 20px -4px rgba(15, 23, 42, 0.08) !important;
  background: #ffffff !important;
}

.mi-tabla th {
  background: linear-gradient(180deg, #0f4c81 0%, #0b3c66 100%) !important;
  color: #ffffff !important;
  font-size: 11px !important;
  font-weight: 700 !important;
  text-transform: uppercase !important;
  letter-spacing: 0.04em !important;
  padding: 12px 8px !important;
  border: none !important;
  border-bottom: 2px solid #082f52 !important;
  border-right: 1px solid rgba(255, 255, 255, 0.1) !important;
  vertical-align: middle !important;
}

.mi-tabla th:last-child { border-right: none !important; }

.mi-tabla td {
  padding: 10px 8px !important;
  font-size: 12.5px !important;
  color: #1e293b !important;
  border: none !important;
  border-bottom: 1px solid #e2e8f0 !important;
  border-right: 1px solid #f1f5f9 !important;
  vertical-align: middle !important;
  transition: filter 0.15s ease;
}

.mi-tabla td:last-child { border-right: none !important; }
.mi-tabla tr:last-child td { border-bottom: none !important; }
.mi-tabla tr:hover td { filter: brightness(0.97); }

.tbl-id-badge {
  font-weight: 700;
  color: #0f4c81;
  font-size: 12.5px;
  white-space: nowrap;
}

.tbl-status-pill {
  font-weight: 700;
  font-size: 11.5px;
  letter-spacing: 0.02em;
  text-transform: uppercase;
  color: #0f172a;
}

.tbl-envio-pill {
  font-weight: 700;
  font-size: 11.5px;
  letter-spacing: 0.02em;
  color: #14532d;
}

.tbl-sucursal-tag {
  font-weight: 700;
  font-size: 12px;
  color: #334155;
}

.tbl-fecha-cell {
  font-variant-numeric: tabular-nums;
  font-weight: 600;
  color: #475569 !important;
  white-space: nowrap;
}

.tbl-sin-chofer {
  font-size: 11.5px;
  font-weight: 600;
  color: #991b1b;
}

.tbl-chofer-nombre { font-weight: 700; color: #0f172a; }
.tbl-vendedor-cell { color: #475569 !important; font-weight: 500; }
.tbl-factura-cell { font-weight: 800; color: #0f4c81 !important; font-variant-numeric: tabular-nums; }
.tbl-direccion-cell { font-size: 11.5px !important; line-height: 1.35; color: #334155 !important; text-align: left; }
.tbl-cliente-cell { text-align: center; }
.tbl-cliente-nombre { font-weight: 700; color: #0f172a; font-size: 12px; line-height: 1.3; }
.tbl-contacto-cell { font-size: 11.5px !important; color: #475569 !important; }
.pedido-checkbox, #selectAll { width: 16px; height: 16px; accent-color: #0f4c81; cursor: pointer; }

/* Línea gráfica moderna para columna Acción (Ver Detalles + Incidencias) */
.acciones-pedido-wrap {
  display: flex;
  flex-direction: column;
  gap: 4px;
  align-items: stretch;
  width: 100%;
  max-width: 100%;
  box-sizing: border-box;
  overflow: hidden;
  padding: 2px 0;
}

.btn-accion-modern {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 3px;
  width: 100%;
  max-width: 100%;
  box-sizing: border-box;
  padding: 4px 5px;
  border-radius: 6px;
  font-size: 10.5px;
  font-weight: 600;
  line-height: 1.15;
  text-align: center;
  text-decoration: none !important;
  cursor: pointer;
  transition: all 0.18s ease;
  white-space: normal;
  word-break: normal;
  overflow-wrap: normal;
  font-family: inherit;
}

.btn-accion-detalles {
  background: linear-gradient(135deg, #0f4c81 0%, #005aa3 100%);
  color: #ffffff !important;
  border: 1px solid #0b3c66;
  box-shadow: 0 1px 3px rgba(15, 76, 129, 0.22);
}

.btn-accion-detalles:hover {
  background: linear-gradient(135deg, #0b3c66 0%, #00447c 100%);
  color: #ffffff !important;
  transform: translateY(-1px);
  box-shadow: 0 3px 6px rgba(15, 76, 129, 0.3);
}

.btn-accion-inc-activa {
  background: linear-gradient(135deg, #ea580c 0%, #d97706 100%);
  color: #ffffff !important;
  border: 1px solid #c2410c;
  box-shadow: 0 1px 3px rgba(234, 88, 12, 0.25);
}

.btn-accion-inc-activa:hover {
  background: linear-gradient(135deg, #c2410c 0%, #b45309 100%);
  transform: translateY(-1px);
  box-shadow: 0 3px 6px rgba(234, 88, 12, 0.35);
}

.btn-accion-inc-nueva {
  background: #fff7ed;
  color: #c2410c !important;
  border: 1px solid #fdba74;
  box-shadow: 0 1px 2px rgba(234, 88, 12, 0.08);
}

.btn-accion-inc-nueva:hover {
  background: #ffedd5;
  color: #9a3412 !important;
  border-color: #fb923c;
  transform: translateY(-1px);
  box-shadow: 0 2px 5px rgba(234, 88, 12, 0.16);
}
</style>
