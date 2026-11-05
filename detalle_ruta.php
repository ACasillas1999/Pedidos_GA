<?php
session_name("GA");
session_start();

// Verificar si el usuario está logeado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: /Pedidos_GA/Sesion/login.html");
    exit;
}

// Establecer la conexión a la base de datos
require_once __DIR__ . "/Conexiones/Conexion.php";

$grupoId = intval($_GET['grupo_id'] ?? 0);

if ($grupoId <= 0) {
    die("ID de grupo inválido");
}

// Obtener información del grupo
$sqlGrupo = "SELECT gr.*, c.username as chofer_nombre, c.Numero as chofer_telefono
            FROM grupos_rutas gr
            LEFT JOIN choferes c ON gr.chofer_asignado = c.username
            WHERE gr.id = ?";

$stmtGrupo = $conn->prepare($sqlGrupo);
$stmtGrupo->bind_param("i", $grupoId);
$stmtGrupo->execute();
$resultGrupo = $stmtGrupo->get_result();
$grupo = $resultGrupo->fetch_assoc();

if (!$grupo) {
    die("Grupo no encontrado");
}

// Obtener pedidos del grupo (incluyendo SUCURSAL)
$sqlPedidos = "SELECT p.ID, p.FACTURA, p.NOMBRE_CLIENTE, p.DIRECCION,
                     p.TELEFONO, p.ESTADO, p.tipo_envio, p.Coord_Destino,
                     p.precio_factura_real, p.COMENTARIOS, p.SUCURSAL, pg.orden_entrega
              FROM pedidos p
              INNER JOIN pedidos_grupos pg ON p.ID = pg.pedido_id
              WHERE pg.grupo_id = ?
              ORDER BY pg.orden_entrega ASC";

$stmtPedidos = $conn->prepare($sqlPedidos);
$stmtPedidos->bind_param("i", $grupoId);
$stmtPedidos->execute();
$resultPedidos = $stmtPedidos->get_result();
$pedidos = [];
while ($pedido = $resultPedidos->fetch_assoc()) {
    $pedidos[] = $pedido;
}

// Obtener coordenadas de la sucursal (bodega/origen)
// Usar la sucursal del primer pedido (todos deben ser de la misma sucursal)
$sucursalOrigen = '';
$montoTotalRuta = 0;
if (!empty($pedidos)) {
    $sucursalOrigen = $pedidos[0]['SUCURSAL'];
    foreach ($pedidos as $p) {
        $montoTotalRuta += floatval($p['precio_factura_real'] ?? 0);
    }
}

// Si la sucursal es ILUMINACION o TAPATIA, usar TAPATIA como origen
if ($sucursalOrigen === 'ILUMINACION' || $sucursalOrigen === 'TAPATIA') {
    $sucursalOrigen = 'TAPATIA';
}

$sqlUbicacion = "SELECT coordenadas, NombreCompleto, Direccion FROM ubicaciones WHERE Ubicacion = ?";
$stmtUbicacion = $conn->prepare($sqlUbicacion);
$stmtUbicacion->bind_param("s", $sucursalOrigen);
$stmtUbicacion->execute();
$resultUbicacion = $stmtUbicacion->get_result();
$ubicacion = $resultUbicacion->fetch_assoc();
$stmtUbicacion->close();

// Generar color del grupo
$colores = [
    ['bg' => '#28a745', 'border' => '#20c997'],
    ['bg' => '#007bff', 'border' => '#0056b3'],
    ['bg' => '#ffc107', 'border' => '#ff9800'],
    ['bg' => '#dc3545', 'border' => '#c82333'],
    ['bg' => '#6f42c1', 'border' => '#5a32a3'],
    ['bg' => '#fd7e14', 'border' => '#e8590c'],
    ['bg' => '#20c997', 'border' => '#17a2b8'],
    ['bg' => '#e83e8c', 'border' => '#d63384']
];
$colorIndex = $grupoId % count($colores);
$color = $colores[$colorIndex];

// Función para iconos SVG unicolor
function drSvg($name, $size = 14) {
    $paths = [
        'arrow-left' => '<line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>',
        'truck' => '<rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>',
        'trash' => '<polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/>',
        'user' => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'phone' => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>',
        'building' => '<rect x="4" y="2" width="16" height="20" rx="2" ry="2"/><line x1="9" y1="6" x2="9.01" y2="6"/><line x1="15" y1="6" x2="15.01" y2="6"/><line x1="9" y1="10" x2="9.01" y2="10"/><line x1="15" y1="10" x2="15.01" y2="10"/><line x1="9" y1="14" x2="9.01" y2="14"/><line x1="15" y1="14" x2="15.01" y2="14"/><path d="M9 22v-4h6v4"/>',
        'package' => '<line x1="16.5" y1="9.4" x2="7.5" y2="4.21"/><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>',
        'pin' => '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>',
        'zap' => '<polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>',
        'traffic' => '<rect x="6" y="2" width="12" height="20" rx="6"/><circle cx="12" cy="7" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="12" cy="17" r="2"/>',
        'info' => '<circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>',
        'grip' => '<circle cx="9" cy="6" r="1.5"/><circle cx="15" cy="6" r="1.5"/><circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/><circle cx="9" cy="18" r="1.5"/><circle cx="15" cy="18" r="1.5"/>',
        'external' => '<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/>',
        'dollar' => '<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>',
        'check' => '<polyline points="20 6 9 17 4 12"/>',
        'layers' => '<polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/>',
        'clock' => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>'
    ];
    $inner = $paths[$name] ?? $paths['info'];
    return "<svg width='$size' height='$size' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' style='vertical-align:-0.15em;flex-shrink:0;display:inline-block;'>$inner</svg>";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Detalle de Ruta - <?php echo htmlspecialchars($grupo['nombre_grupo']); ?></title>
  <link rel="icon" href="/Img/Logo 2.png" type="image/png">
  <link rel="stylesheet" type="text/css" href="styles.css">

  <!-- Mapbox GL JS v2.15.0 (Requerido para soporte nativo de estilo streets-v12) -->
  <script src='https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js'></script>
  <link href='https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css' rel='stylesheet' />

  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <!-- SortableJS para drag & drop -->
  <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

  <style>
    :root {
      --bg-page: #f8fafc;
      --card-bg: #ffffff;
      --border-color: #e2e8f0;
      --text-main: #0f172a;
      --text-muted: #64748b;
      --primary-navy: #0f4c81;
      --radius: 14px;
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
      background-color: var(--bg-page);
      color: var(--text-main);
      margin: 0 !important;
      padding: 0 !important;
      line-height: 1.45;
      min-height: 100vh;
      height: auto !important;
    }

    /* Topbar moderna, pegada arriba sin margen ni bordes exteriores (Full-Bleed) */
    .ruta-topbar {
      background: linear-gradient(135deg, #0f4c81 0%, #005aa3 100%);
      color: #ffffff;
      padding: 12px 24px;
      width: 100%;
      box-shadow: 0 2px 10px rgba(15, 76, 129, 0.18);
      border-bottom: 1px solid rgba(0, 0, 0, 0.1);
      position: sticky;
      top: 0;
      z-index: 100;
      border-radius: 0 !important;
      margin: 0 !important;
    }
    .ruta-topbar-inner {
      width: 100%;
      max-width: 1800px;
      margin: 0 auto;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      flex-wrap: wrap;
    }

    /* Contenedor principal de la página debajo del topbar */
    .ruta-main-wrap {
      width: 100%;
      max-width: 1800px;
      margin: 0 auto;
      padding: 18px 24px 30px 24px;
    }
    .ruta-topbar-left {
      display: flex;
      align-items: center;
      gap: 16px;
      flex-wrap: wrap;
    }
    .btn-topbar-back {
      background: rgba(255, 255, 255, 0.14);
      border: 1px solid rgba(255, 255, 255, 0.25);
      color: #ffffff;
      padding: 8px 15px;
      border-radius: 8px;
      font-size: 13px;
      font-weight: 700;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      transition: all 0.2s ease;
      white-space: nowrap;
    }
    .btn-topbar-back:hover {
      background: rgba(255, 255, 255, 0.25);
      border-color: rgba(255, 255, 255, 0.45);
      color: #ffffff;
      transform: translateY(-1px);
    }
    .topbar-divider {
      width: 1px;
      height: 38px;
      background: rgba(255, 255, 255, 0.22);
      flex-shrink: 0;
    }
    .ruta-topbar-title-wrap {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .ruta-topbar-icon {
      width: 42px;
      height: 42px;
      border-radius: 10px;
      background: rgba(255, 255, 255, 0.15);
      border: 1px solid rgba(255, 255, 255, 0.3);
      display: flex;
      align-items: center;
      justify-content: center;
      color: #ffffff;
      flex-shrink: 0;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
    }
    .ruta-topbar-headings {
      display: flex;
      flex-direction: column;
      gap: 3px;
    }
    .ruta-topbar-title-row {
      display: flex;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
    }
    .ruta-topbar-title-row h1 {
      margin: 0;
      font-size: 21px;
      font-weight: 800;
      letter-spacing: -0.01em;
      color: #ffffff;
      line-height: 1.2;
    }
    .ruta-badge-id {
      font-size: 11.5px;
      font-weight: 800;
      color: #e0f2fe;
      background: rgba(2, 132, 199, 0.4);
      border: 1px solid rgba(186, 230, 253, 0.4);
      padding: 2px 8px;
      border-radius: 6px;
      letter-spacing: 0.02em;
    }
    .ruta-badge-status {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 3px 10px;
      border-radius: 999px;
      font-size: 11.5px;
      font-weight: 700;
      background: rgba(34, 197, 94, 0.2);
      border: 1px solid rgba(74, 222, 128, 0.45);
      color: #dcfce7;
    }
    .pulse-dot {
      width: 7px;
      height: 7px;
      border-radius: 50%;
      background: #4ade80;
      box-shadow: 0 0 0 0 rgba(74, 222, 128, 0.7);
      animation: pulseGreen 2s infinite;
      display: inline-block;
    }
    @keyframes pulseGreen {
      0% {
        box-shadow: 0 0 0 0 rgba(74, 222, 128, 0.7);
      }
      70% {
        box-shadow: 0 0 0 6px rgba(74, 222, 128, 0);
      }
      100% {
        box-shadow: 0 0 0 0 rgba(74, 222, 128, 0);
      }
    }
    .ruta-topbar-subtitle {
      font-size: 12px;
      color: rgba(255, 255, 255, 0.82);
      display: flex;
      align-items: center;
      gap: 8px;
      flex-wrap: wrap;
    }
    .ruta-topbar-subtitle strong {
      color: #ffffff;
      font-weight: 700;
    }
    .sub-sep {
      opacity: 0.45;
    }
    .ruta-topbar-actions {
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .btn-desactivar {
      background: #dc2626;
      border: 1px solid #b91c1c;
      color: #ffffff;
      padding: 8px 16px;
      border-radius: 8px;
      font-size: 13px;
      font-weight: 700;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 7px;
      box-shadow: 0 2px 6px rgba(220, 38, 38, 0.35);
      transition: all 0.2s ease;
      white-space: nowrap;
    }
    .btn-desactivar:hover {
      background: #b91c1c;
      border-color: #991b1b;
      box-shadow: 0 4px 10px rgba(220, 38, 38, 0.45);
      transform: translateY(-1px);
    }

    /* KPIs bar */
    .kpis-bar {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 14px;
      padding: 0 0 16px 0;
      background: transparent;
      border-bottom: none;
    }
    .kpi-chip {
      background: #ffffff;
      border: 1px solid var(--border-color);
      border-radius: 10px;
      padding: 12px 14px;
      display: flex;
      align-items: center;
      gap: 12px;
      box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
    }
    .kpi-chip-icon {
      width: 36px;
      height: 36px;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      background: #f1f5f9;
      color: #0f4c81;
    }
    .kpi-chip-content {
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }
    .kpi-chip-label {
      font-size: 11px;
      font-weight: 700;
      color: var(--text-muted);
      text-transform: uppercase;
      letter-spacing: 0.03em;
    }
    .kpi-chip-val {
      font-size: 14.5px;
      font-weight: 800;
      color: var(--text-main);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .kpi-chip-sub {
      font-size: 11.5px;
      color: var(--text-muted);
    }
    .kpi-chip-sub a {
      color: #0284c7;
      text-decoration: none;
      font-weight: 600;
    }
    .kpi-chip-sub a:hover {
      text-decoration: underline;
    }

    /* Banner de Notas si existe */
    .ruta-notas-banner {
      margin: 0 0 16px 0;
      background: #f0f9ff;
      border: 1px solid #bae6fd;
      border-left: 4px solid #0284c7;
      border-radius: 8px;
      padding: 10px 14px;
      font-size: 12.5px;
      color: #0369a1;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    /* Layout principal de 2 columnas */
    .content-grid {
      display: grid;
      grid-template-columns: 440px 1fr;
      gap: 20px;
      padding: 0;
      align-items: stretch;
    }
    @media (max-width: 860px) {
      .content-grid {
        grid-template-columns: 1fr;
      }
    }

    /* Panel izquierdo de pedidos */
    .pedidos-panel {
      background: #ffffff;
      border: 1px solid var(--border-color);
      border-radius: 12px;
      box-shadow: 0 2px 8px -2px rgba(15, 23, 42, 0.05);
      overflow: hidden;
      display: flex;
      flex-direction: column;
    }
    .pedidos-panel-header {
      padding: 14px 16px;
      background: #f8fafc;
      border-bottom: 1px solid var(--border-color);
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
    }
    .pedidos-panel-title {
      font-size: 14.5px;
      font-weight: 800;
      color: var(--text-main);
      display: flex;
      align-items: center;
      gap: 7px;
    }
    .btn-optimizar-ruta {
      background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
      color: #ffffff;
      border: none;
      padding: 8px 14px;
      border-radius: 8px;
      font-size: 12.5px;
      font-weight: 700;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      box-shadow: 0 2px 6px rgba(22, 163, 74, 0.25);
      transition: all 0.2s;
    }
    .btn-optimizar-ruta:hover {
      box-shadow: 0 4px 10px rgba(22, 163, 74, 0.35);
      transform: translateY(-1px);
    }

    /* Stats container */
    .stats-card {
      margin: 12px 16px;
      padding: 12px 14px;
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 10px;
    }
    .stats-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12px;
      text-align: center;
    }
    .stat-box-label {
      font-size: 11px;
      font-weight: 700;
      color: var(--text-muted);
      text-transform: uppercase;
      letter-spacing: 0.03em;
      margin-bottom: 3px;
    }
    .stat-box-val {
      font-size: 19px;
      font-weight: 800;
      color: #0f172a;
    }
    .stat-traffic-pill {
      margin-top: 8px;
      padding: 6px 10px;
      background: #ecfdf5;
      border: 1px solid #a7f3d0;
      border-radius: 6px;
      font-size: 11.5px;
      color: #065f46;
      text-align: center;
      font-weight: 600;
    }

    .pedidos-tip-help {
      padding: 0 16px;
      font-size: 12px;
      color: var(--text-muted);
      line-height: 1.4;
      margin-top: 4px;
      margin-bottom: 8px;
    }

    .pedidos-scroll-wrap {
      padding: 4px 16px 16px 16px;
      max-height: calc(100vh - 360px);
      min-height: 400px;
      overflow-y: auto;
      display: flex;
      flex-direction: column;
      gap: 10px;
    }

    /* Tarjetas de pedido */
    .pedido-item {
      background: #ffffff;
      border: 1.5px solid #e2e8f0;
      border-radius: 10px;
      padding: 12px 14px;
      cursor: grab;
      display: flex;
      flex-direction: column;
      gap: 8px;
      transition: all 0.2s ease;
      position: relative;
    }
    .pedido-item:hover {
      border-color: #cbd5e1;
      box-shadow: 0 4px 12px rgba(15, 23, 42, 0.06);
      transform: translateY(-1px);
    }
    .pedido-item.sortable-drag {
      opacity: 0.85;
      background: #f0f9ff;
      border-color: #0284c7;
      box-shadow: 0 10px 25px -5px rgba(2, 132, 199, 0.25);
    }
    .sortable-ghost {
      opacity: 0.35;
      background: #f1f5f9;
      border-style: dashed;
    }

    .pedido-top-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 8px;
    }
    .pedido-top-left {
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .drag-handle {
      color: #94a3b8;
      cursor: grab;
      display: flex;
      align-items: center;
    }
    .pedido-orden-circle {
      width: 26px;
      height: 26px;
      border-radius: 50%;
      background: <?php echo $color['bg']; ?>;
      color: #ffffff;
      font-size: 12px;
      font-weight: 800;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }
    .pedido-fact-tag {
      font-size: 13.5px;
      font-weight: 800;
      color: #0f172a;
    }
    .pedido-id-tag {
      font-size: 11px;
      font-weight: 700;
      color: #0f4c81;
      background: #e0f2fe;
      padding: 1px 6px;
      border-radius: 4px;
    }
    .pedido-monto-tag {
      font-size: 12.5px;
      font-weight: 800;
      color: #15803d;
      font-variant-numeric: tabular-nums;
    }
    .pedido-body-info {
      font-size: 12px;
      color: #334155;
      display: flex;
      flex-direction: column;
      gap: 4px;
    }
    .pedido-info-line {
      display: flex;
      align-items: flex-start;
      gap: 6px;
      line-height: 1.35;
    }
    .pedido-info-line svg {
      color: #64748b;
      margin-top: 2px;
    }
    .pedido-link-btn {
      font-size: 11px;
      color: #0284c7;
      text-decoration: none;
      font-weight: 700;
      display: inline-flex;
      align-items: center;
      gap: 3px;
      margin-top: 2px;
    }
    .pedido-link-btn:hover {
      text-decoration: underline;
    }

    /* Panel derecho del mapa */
    .map-panel {
      background: #f1f5f9;
      border: 1px solid var(--border-color);
      border-radius: 12px;
      box-shadow: 0 2px 8px -2px rgba(15, 23, 42, 0.05);
      position: relative;
      overflow: hidden;
      min-height: 580px;
      height: calc(100vh - 220px);
    }
    #map {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      width: 100%;
      height: 100%;
      border-radius: 12px;
    }

    /* Control flotante de tráfico */
    .map-float-control {
      position: absolute;
      top: 14px;
      right: 14px;
      z-index: 10;
    }
    .btn-trafico-toggle {
      background: #ffffff;
      border: 1px solid #cbd5e1;
      padding: 8px 14px;
      border-radius: 8px;
      font-size: 12.5px;
      font-weight: 700;
      color: #0f172a;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 6px;
      box-shadow: 0 4px 12px rgba(15, 23, 42, 0.12);
      transition: all 0.2s;
    }
    .btn-trafico-toggle:hover {
      background: #f8fafc;
      border-color: #94a3b8;
    }
    .btn-trafico-toggle.active {
      background: #f0fdf4;
      border-color: #86efac;
      color: #15803d;
    }

    /* Leyenda flotante */
    .map-legend-pro {
      position: absolute;
      bottom: 20px;
      left: 16px;
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(4px);
      border: 1px solid rgba(203, 213, 225, 0.8);
      border-radius: 10px;
      padding: 10px 14px;
      font-size: 11.5px;
      box-shadow: 0 4px 12px rgba(15, 23, 42, 0.12);
      z-index: 10;
      display: flex;
      flex-direction: column;
      gap: 6px;
    }
    .legend-row {
      display: flex;
      align-items: center;
      gap: 8px;
      font-weight: 600;
      color: #334155;
    }
  </style>
</head>
<body>
  <!-- Topbar full-width pegado arriba sin espacio -->
  <header class="ruta-topbar">
    <div class="ruta-topbar-inner">
      <div class="ruta-topbar-left">
        <a href="Pedidos_GA.php" class="btn-topbar-back">
          <?= drSvg('arrow-left', 15) ?>
          <span>Volver a Pedidos</span>
        </a>

        <div class="topbar-divider"></div>

        <div class="ruta-topbar-title-wrap">
          <div class="ruta-topbar-icon">
            <?= drSvg('truck', 22) ?>
          </div>
          <div class="ruta-topbar-headings">
            <div class="ruta-topbar-title-row">
              <h1><?= htmlspecialchars($grupo['nombre_grupo']); ?></h1>
              <span class="ruta-badge-id">Ruta #<?= $grupoId ?></span>
              <span class="ruta-badge-status">
                <span class="pulse-dot"></span>
                <span>Activa</span>
              </span>
            </div>
            <div class="ruta-topbar-subtitle">
              <span>Sucursal: <strong><?= htmlspecialchars($grupo['sucursal']); ?></strong></span>
              <span class="sub-sep">•</span>
              <span>Chofer: <strong><?= htmlspecialchars($grupo['chofer_asignado'] ?: 'Sin asignar'); ?></strong></span>
              <span class="sub-sep">•</span>
              <span><?= count($pedidos) ?> <?= count($pedidos) === 1 ? 'pedido' : 'pedidos' ?> programados</span>
            </div>
          </div>
        </div>
      </div>

      <div class="ruta-topbar-actions">
        <button type="button" onclick="desactivarGrupo()" class="btn-desactivar" title="Desactivar esta ruta">
          <?= drSvg('trash', 14) ?>
          <span>Desactivar Grupo</span>
        </button>
      </div>
    </div>
  </header>

  <!-- Contenedor de contenidos debajo de la barra superior -->
  <main class="ruta-main-wrap">
    <!-- KPIs Bar -->
    <div class="kpis-bar">
      <div class="kpi-chip">
        <div class="kpi-chip-icon">
          <?= drSvg('user', 18) ?>
        </div>
        <div class="kpi-chip-content">
          <div class="kpi-chip-label">Chofer Asignado</div>
          <div class="kpi-chip-val"><?= htmlspecialchars($grupo['chofer_asignado']); ?></div>
          <?php if (!empty($grupo['chofer_telefono'])): ?>
            <div class="kpi-chip-sub">
              <a href="tel:<?= htmlspecialchars($grupo['chofer_telefono']) ?>">
                <?= drSvg('phone', 11) ?> <?= htmlspecialchars($grupo['chofer_telefono']) ?>
              </a>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="kpi-chip">
        <div class="kpi-chip-icon">
          <?= drSvg('building', 18) ?>
        </div>
        <div class="kpi-chip-content">
          <div class="kpi-chip-label">Sucursal Operativa</div>
          <div class="kpi-chip-val"><?= htmlspecialchars($grupo['sucursal']); ?></div>
          <div class="kpi-chip-sub">Almacén de despacho</div>
        </div>
      </div>

      <div class="kpi-chip">
        <div class="kpi-chip-icon">
          <?= drSvg('package', 18) ?>
        </div>
        <div class="kpi-chip-content">
          <div class="kpi-chip-label">Pedidos y Carga</div>
          <div class="kpi-chip-val"><?= count($pedidos); ?> <?= (count($pedidos) === 1 ? 'pedido' : 'pedidos') ?></div>
          <div class="kpi-chip-sub">Monto: <strong>$<?= number_format($montoTotalRuta, 2) ?></strong></div>
        </div>
      </div>

      <div class="kpi-chip">
        <div class="kpi-chip-icon">
          <?= drSvg('check', 18) ?>
        </div>
        <div class="kpi-chip-content">
          <div class="kpi-chip-label">Operador de Creación</div>
          <div class="kpi-chip-val"><?= htmlspecialchars($grupo['usuario_creo']); ?></div>
          <div class="kpi-chip-sub">Fecha: <?= date('d/m/Y', strtotime($grupo['fecha_creacion'] ?? 'now')) ?></div>
        </div>
      </div>
    </div>

    <?php if (!empty($grupo['notas'])): ?>
      <div class="ruta-notas-banner">
        <span style="display:flex;"><?= drSvg('info', 16) ?></span>
        <div><strong>Instrucciones de Ruta:</strong> <?= nl2br(htmlspecialchars($grupo['notas'])); ?></div>
      </div>
    <?php endif; ?>

    <!-- Contenido Grid (Pedidos + Mapa) -->
    <div class="content-grid">
      <!-- Panel de Pedidos -->
      <div class="pedidos-panel">
        <div class="pedidos-panel-header">
          <div class="pedidos-panel-title">
            <?= drSvg('layers', 16) ?> Secuencia de Paradas (<?= count($pedidos) ?>)
          </div>
          <button type="button" onclick="optimizarRuta()" class="btn-optimizar-ruta">
            <?= drSvg('zap', 14) ?> Optimizar Ruta
          </button>
        </div>

        <div id="stats-container" class="stats-card" style="display: none;">
          <div class="stats-row">
            <div>
              <div class="stat-box-label">Distancia Total</div>
              <div id="distancia-total" class="stat-box-val">--</div>
            </div>
            <div>
              <div class="stat-box-label">Tiempo Estimado</div>
              <div id="tiempo-total" class="stat-box-val">--</div>
            </div>
          </div>
          <div id="tiempo-ideal-container" class="stat-traffic-pill" style="display: none;">
            <span>Sin tráfico: <strong id="tiempo-ideal">--</strong></span>
            <span id="retraso-trafico" style="color: #dc2626; margin-left: 6px;">--</span>
          </div>
        </div>

        <div class="pedidos-tip-help">
          Arrastra las paradas para ajustar la secuencia de entrega manualmente o usa <strong>Optimizar Ruta</strong> para trazar el trayecto óptimo.
        </div>

        <div id="pedidos-sortable" class="pedidos-scroll-wrap">
          <?php foreach ($pedidos as $pedido): ?>
          <div class="pedido-item" data-pedido-id="<?php echo $pedido['ID']; ?>" data-orden="<?php echo $pedido['orden_entrega']; ?>">
            <div class="pedido-top-row">
              <div class="pedido-top-left">
                <span class="drag-handle" title="Arrastrar para ordenar"><?= drSvg('grip', 15) ?></span>
                <span class="pedido-orden-circle"><?php echo $pedido['orden_entrega']; ?></span>
                <span class="pedido-fact-tag"><?php echo htmlspecialchars($pedido['FACTURA'] ?: 'S/F'); ?></span>
                <span class="pedido-id-tag">#<?php echo $pedido['ID']; ?></span>
              </div>
              <?php if (!empty($pedido['precio_factura_real'])): ?>
                <span class="pedido-monto-tag">$<?php echo number_format($pedido['precio_factura_real'], 2); ?></span>
              <?php endif; ?>
            </div>

            <div class="pedido-body-info">
              <div class="pedido-info-line">
                <?= drSvg('user', 13) ?>
                <strong><?php echo htmlspecialchars($pedido['NOMBRE_CLIENTE']); ?></strong>
              </div>
              <div class="pedido-info-line">
                <?= drSvg('pin', 13) ?>
                <span><?php echo htmlspecialchars($pedido['DIRECCION']); ?></span>
              </div>
              <?php if (!empty($pedido['TELEFONO'])): ?>
                <div class="pedido-info-line">
                  <?= drSvg('phone', 13) ?>
                  <a href="tel:<?php echo htmlspecialchars($pedido['TELEFONO']); ?>" style="color:#0284c7; text-decoration:none;">
                    <?php echo htmlspecialchars($pedido['TELEFONO']); ?>
                  </a>
                </div>
              <?php endif; ?>
              <div>
                <a href="Inicio.php?id=<?php echo $pedido['ID']; ?>" target="_blank" class="pedido-link-btn">
                  <?= drSvg('external', 12) ?> Ver detalle del pedido
                </a>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Panel del Mapa -->
      <div class="map-panel">
        <div id="map"></div>

        <!-- Botón de tráfico flotante -->
        <div class="map-float-control">
          <button type="button" id="toggle-trafico" onclick="toggleTrafico()" class="btn-trafico-toggle">
            <?= drSvg('traffic', 15) ?>
            <span id="trafico-text">Ver Tráfico</span>
          </button>
        </div>

        <!-- Leyenda profesional -->
        <div class="map-legend-pro">
          <div class="legend-row">
            <span style="width: 14px; height: 14px; border-radius: 50%; background: #0f4c81; border: 2px solid #fff; display: inline-block;"></span>
            <span>Almacén / Origen</span>
          </div>
          <div class="legend-row">
            <span style="width: 14px; height: 14px; border-radius: 50%; background: <?php echo $color['bg']; ?>; border: 2px solid #fff; display: inline-block;"></span>
            <span>Paradas de entrega</span>
          </div>
          <div class="legend-row">
            <span style="width: 22px; height: 4px; background: <?php echo $color['bg']; ?>; border-radius: 2px; display: inline-block;"></span>
            <span>Ruta de ida</span>
          </div>
          <div class="legend-row">
            <span style="width: 22px; height: 3px; background: repeating-linear-gradient(90deg, #94a3b8 0px, #94a3b8 4px, transparent 4px, transparent 8px); display: inline-block;"></span>
            <span>Retorno a bodega</span>
          </div>
          <div class="legend-row" id="legend-trafico" style="display: none; margin-top: 2px;">
            <div style="display: flex; gap: 3px;">
              <span style="width: 8px; height: 4px; background: #22c55e;"></span>
              <span style="width: 8px; height: 4px; background: #eab308;"></span>
              <span style="width: 8px; height: 4px; background: #ef4444;"></span>
            </div>
            <span>Nivel de congestión</span>
          </div>
        </div>
      </div>
    </div>
  </main>

  <script>
    // Datos de los pedidos para JavaScript
    const pedidos = <?php echo json_encode($pedidos); ?>;
    const grupoId = <?php echo $grupoId; ?>;
    const grupoColor = '<?php echo $color['bg']; ?>';
    const ubicacionOrigen = <?php echo json_encode($ubicacion); ?>;

    // Inicializar mapa
    mapboxgl.accessToken = 'pk.eyJ1IjoiYWNhc2lsbGFzNzY2IiwiYSI6ImNsdW12cTZyMjB4NnMya213MDdseXp6ZGgifQ.t7-l1lQfd8mgHILM5YrdNw';

    const map = new mapboxgl.Map({
      container: 'map',
      style: 'mapbox://styles/mapbox/streets-v12',
      center: [-103.3494, 20.6737], // Guadalajara por defecto
      zoom: 12
    });

    let markers = [];
    let routeLayer = null;
    let trafficVisible = false;
    let estadisticasRutaEntregas = { distancia: 0, duracion: 0 };
    let estadisticasRutaRegreso = { distancia: 0, duracion: 0 };

    // Toggle de capa de tráfico
    function toggleTrafico() {
      trafficVisible = !trafficVisible;
      const btnTrafico = document.getElementById('toggle-trafico');

      if (trafficVisible) {
        // Mostrar tráfico
        if (!map.getLayer('traffic')) {
          // Agregar la fuente primero si no existe
          if (!map.getSource('traffic-source')) {
            map.addSource('traffic-source', {
              'type': 'vector',
              'url': 'mapbox://mapbox.mapbox-traffic-v1'
            });
          }

          // Agregar la capa
          map.addLayer({
            'id': 'traffic',
            'type': 'line',
            'source': 'traffic-source',
            'source-layer': 'traffic',
            'paint': {
              'line-width': 2,
              'line-color': [
                'case',
                ['==', ['get', 'congestion'], 'low'], '#4CAF50',
                ['==', ['get', 'congestion'], 'moderate'], '#FFC107',
                ['==', ['get', 'congestion'], 'heavy'], '#F44336',
                ['==', ['get', 'congestion'], 'severe'], '#B71C1C',
                '#888888'
              ]
            }
          });
        } else {
          // Si la capa ya existe, solo hacerla visible
          map.setLayoutProperty('traffic', 'visibility', 'visible');
        }
        document.getElementById('trafico-text').textContent = 'Ocultar Tráfico';
        if (btnTrafico) btnTrafico.classList.add('active');
        document.getElementById('legend-trafico').style.display = 'flex';
      } else {
        // Ocultar tráfico (no remover, solo ocultar)
        if (map.getLayer('traffic')) {
          map.setLayoutProperty('traffic', 'visibility', 'none');
        }
        document.getElementById('trafico-text').textContent = 'Ver Tráfico';
        if (btnTrafico) btnTrafico.classList.remove('active');
        document.getElementById('legend-trafico').style.display = 'none';
      }
    }

    function escapeHtml(text) {
      if (!text) return '';
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }

    // Función para actualizar el mapa
    function actualizarMapa() {
      // Limpiar marcadores anteriores
      markers.forEach(marker => marker.remove());
      markers = [];

      // Limpiar ruta anterior
      if (routeLayer && map.getLayer('route')) {
        map.removeLayer('route');
        map.removeSource('route');
      }

      // Limpiar ruta de regreso anterior
      if (map.getLayer('route-regreso')) {
        map.removeLayer('route-regreso');
        map.removeSource('route-regreso');
      }

      const bounds = new mapboxgl.LngLatBounds();
      const coordenadas = [];

      // Agregar coordenadas de la bodega/origen como primer punto
      if (ubicacionOrigen && ubicacionOrigen.coordenadas) {
        try {
          let coordString = ubicacionOrigen.coordenadas.trim();
          let lat, lng;

          // Parsear coordenadas (formato "lat, lng")
          if (coordString.includes(',')) {
            const parts = coordString.split(',').map(p => p.trim());
            if (parts.length === 2) {
              lat = parseFloat(parts[0]);
              lng = parseFloat(parts[1]);
            }
          }

          if (!isNaN(lng) && !isNaN(lat) && lng !== 0 && lat !== 0) {
            // Crear marcador de bodega/origen
            const elOrigen = document.createElement('div');
            elOrigen.innerHTML = `
              <div style="
                background: #0f4c81;
                color: #ffffff;
                width: 36px;
                height: 36px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                border: 3px solid #ffffff;
                box-shadow: 0 4px 10px rgba(15,76,129,0.35);
                cursor: pointer;
              ">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/>
                  <path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/>
                  <path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/>
                  <path d="M10 6h4"/>
                  <path d="M10 10h4"/>
                </svg>
              </div>
            `;

            const markerOrigen = new mapboxgl.Marker(elOrigen)
              .setLngLat([lng, lat])
              .setPopup(new mapboxgl.Popup({ offset: 25 }).setHTML(`
                <div style="font-family: inherit; padding: 4px 2px;">
                  <div style="display:inline-flex; align-items:center; gap:5px; font-weight:700; color:#0f4c81; font-size:11.5px; margin-bottom:4px; text-transform:uppercase; letter-spacing:0.3px;">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/></svg>
                    Bodega / Origen
                  </div>
                  <div style="font-weight:600; color:#1e293b; font-size:13px; line-height:1.3;">${escapeHtml(ubicacionOrigen.NombreCompleto || '')}</div>
                  <div style="color:#64748b; font-size:11px; margin-top:3px; line-height:1.35;">${escapeHtml(ubicacionOrigen.Direccion || '')}</div>
                </div>
              `))
              .addTo(map);

            markers.push(markerOrigen);
            bounds.extend([lng, lat]);
            coordenadas.push([lng, lat]);

            console.log('Marcador de bodega agregado:', ubicacionOrigen.NombreCompleto);
          }
        } catch (e) {
          console.error('Error procesando coordenadas de bodega:', e);
        }
      }

      // Obtener el orden actual de los pedidos
      const pedidosOrdenados = Array.from(document.querySelectorAll('.pedido-item')).map((item, index) => {
        const pedidoId = item.dataset.pedidoId;
        return pedidos.find(p => p && (p.ID == pedidoId || parseInt(p.ID) === parseInt(pedidoId)));
      }).filter(Boolean);

      // Agregar marcadores
      pedidosOrdenados.forEach((pedido, index) => {
        if (!pedido) return;
        console.log(`Procesando pedido ${pedido.ID}:`, pedido.Coord_Destino);

        if (pedido.Coord_Destino && pedido.Coord_Destino.trim() !== '') {
          try {
            // Limpiar la cadena antes de parsear
            let coordString = pedido.Coord_Destino.trim();
            let lat, lng;

            // Intentar parsear como JSON primero
            try {
              const coords = JSON.parse(coordString);
              lng = parseFloat(coords.lng);
              lat = parseFloat(coords.lat);
              console.log('Formato JSON detectado');
            } catch (jsonError) {
              console.log('No es JSON, intentando formato "lat, lng"...');

              // Intentar formato simple: "20.71685200, -103.36460500"
              if (coordString.includes(',')) {
                const parts = coordString.split(',').map(p => p.trim());
                if (parts.length === 2) {
                  lat = parseFloat(parts[0]);
                  lng = parseFloat(parts[1]);
                  console.log('Formato "lat, lng" detectado');
                } else {
                  throw new Error('Formato de coordenadas no válido');
                }
              } else {
                // Intentar extraer lng y lat con regex como último recurso
                const lngMatch = coordString.match(/lng["\s:]+(-?\d+\.?\d*)/i);
                const latMatch = coordString.match(/lat["\s:]+(-?\d+\.?\d*)/i);

                if (lngMatch && latMatch) {
                  lng = parseFloat(lngMatch[1]);
                  lat = parseFloat(latMatch[1]);
                  console.log('Formato regex detectado');
                } else {
                  throw new Error('No se pudieron extraer coordenadas');
                }
              }
            }

            console.log(`Coordenadas parseadas - Lng: ${lng}, Lat: ${lat}`);

            if (!isNaN(lng) && !isNaN(lat) && lng !== 0 && lat !== 0) {
              // Crear marcador personalizado
              const el = document.createElement('div');
              el.className = 'custom-marker';
              el.innerHTML = `
                <div style="
                  background: ${grupoColor};
                  color: white;
                  width: 30px;
                  height: 30px;
                  border-radius: 50%;
                  display: flex;
                  align-items: center;
                  justify-content: center;
                  font-weight: bold;
                  font-size: 13px;
                  border: 3px solid white;
                  box-shadow: 0 2px 6px rgba(0,0,0,0.3);
                  cursor: pointer;
                ">
                  ${index + 1}
                </div>
              `;

              const marker = new mapboxgl.Marker(el)
                .setLngLat([lng, lat])
                .setPopup(new mapboxgl.Popup({ offset: 25 }).setHTML(`
                  <div style="font-family: inherit; padding: 4px 2px;">
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom:4px;">
                      <span style="font-weight:700; color:#0f4c81; font-size:13px;">Parada #${index + 1}</span>
                      <span style="font-size:11px; font-weight:600; color:#475569; background:#f1f5f9; padding:1px 6px; border-radius:4px;">Fac: ${escapeHtml(pedido.FACTURA || '')}</span>
                    </div>
                    <div style="font-weight:600; color:#1e293b; font-size:12px; line-height:1.3;">${escapeHtml(pedido.NOMBRE_CLIENTE || '')}</div>
                    <div style="color:#64748b; font-size:11px; margin-top:3px; line-height:1.35;">${escapeHtml(pedido.DIRECCION || '')}</div>
                  </div>
                `))
                .addTo(map);

              markers.push(marker);
              bounds.extend([lng, lat]);
              coordenadas.push([lng, lat]);

              console.log(`Marcador agregado para pedido ${pedido.ID}`);
            } else {
              console.warn(`Coordenadas inválidas para pedido ${pedido.ID}:`, lng, lat);
            }
          } catch (e) {
            console.error(`Error procesando coordenadas del pedido ${pedido.ID}:`, e, pedido.Coord_Destino);
          }
        } else {
          console.warn(`Pedido ${pedido.ID} no tiene coordenadas`);
        }
      });

      // Ajustar vista al contenido
      if (coordenadas.length > 0) {
        console.log(`Total de coordenadas válidas: ${coordenadas.length}`, coordenadas);
        map.fitBounds(bounds, { padding: 50 });

        // Dibujar ruta entre los puntos (necesitamos al menos 2 puntos: origen + 1 destino)
        if (coordenadas.length > 1) {
          console.log('Obteniendo ruta entre puntos...');
          // Dibujar la ruta de entregas (origen -> pedidos)
          obtenerRuta(coordenadas);

          // Dibujar la ruta de regreso (último pedido -> origen) con estilo diferente
          const ultimoPedido = coordenadas[coordenadas.length - 1];
          const rutaRegreso = [ultimoPedido, coordenadas[0]];
          obtenerRutaRegreso(rutaRegreso);
        } else {
          console.log('Solo hay 1 punto, no se puede trazar ruta');
        }
      } else {
        console.warn('No hay coordenadas válidas para mostrar');
      }
    }

    // Obtener ruta de Mapbox Directions API
    async function obtenerRuta(coordenadas) {
      // Mapbox tiene límite de 25 waypoints
      if (coordenadas.length > 25) {
        console.warn('Demasiados puntos, limitando a 25');
        coordenadas = coordenadas.slice(0, 25);
      }

      const coordsString = coordenadas.map(c => c.join(',')).join(';');
      const url = `https://api.mapbox.com/directions/v5/mapbox/driving/${coordsString}?geometries=geojson&overview=full&access_token=${mapboxgl.accessToken}`;

      console.log('URL de ruta:', url);

      try {
        const response = await fetch(url);
        const data = await response.json();

        console.log('Respuesta de Directions API:', data);

        if (data.routes && data.routes.length > 0) {
          const route = data.routes[0].geometry;
          const distancia = data.routes[0].distance; // en metros
          const duracion = data.routes[0].duration; // en segundos

          console.log('Ruta obtenida exitosamente');

          // Guardar estadísticas de la ruta de entregas
          estadisticasRutaEntregas = { distancia, duracion };

          // Agregar capa de ruta
          if (map.getSource('route')) {
            console.log('Actualizando ruta existente');
            map.getSource('route').setData({
              type: 'Feature',
              geometry: route
            });
          } else {
            console.log('Creando nueva capa de ruta');
            map.addSource('route', {
              type: 'geojson',
              data: {
                type: 'Feature',
                geometry: route
              }
            });

            map.addLayer({
              id: 'route',
              type: 'line',
              source: 'route',
              layout: {
                'line-join': 'round',
                'line-cap': 'round'
              },
              paint: {
                'line-color': grupoColor,
                'line-width': 4,
                'line-opacity': 0.8
              }
            });
          }
        } else {
          console.error('No se encontraron rutas en la respuesta:', data);
        }
      } catch (error) {
        console.error('Error obteniendo ruta:', error);
      }
    }

    // Obtener ruta de regreso con estilo diferente
    async function obtenerRutaRegreso(coordenadas) {
      const coordsString = coordenadas.map(c => c.join(',')).join(';');
      const url = `https://api.mapbox.com/directions/v5/mapbox/driving/${coordsString}?geometries=geojson&overview=full&access_token=${mapboxgl.accessToken}`;

      console.log('URL de ruta de regreso:', url);

      try {
        const response = await fetch(url);
        const data = await response.json();

        console.log('Respuesta de Directions API (regreso):', data);

        if (data.routes && data.routes.length > 0) {
          const route = data.routes[0].geometry;
          const distancia = data.routes[0].distance; // en metros
          const duracion = data.routes[0].duration; // en segundos

          console.log('Ruta de regreso obtenida exitosamente');

          // Guardar estadísticas de la ruta de regreso
          estadisticasRutaRegreso = { distancia, duracion };

          // Actualizar estadísticas totales (entregas + regreso)
          const distanciaTotal = estadisticasRutaEntregas.distancia + estadisticasRutaRegreso.distancia;
          const duracionTotal = estadisticasRutaEntregas.duracion + estadisticasRutaRegreso.duracion;
          actualizarEstadisticas(distanciaTotal, duracionTotal);

          // Agregar capa de ruta de regreso con estilo diferente
          if (map.getSource('route-regreso')) {
            console.log('Actualizando ruta de regreso existente');
            map.getSource('route-regreso').setData({
              type: 'Feature',
              geometry: route
            });
          } else {
            console.log('Creando nueva capa de ruta de regreso');
            map.addSource('route-regreso', {
              type: 'geojson',
              data: {
                type: 'Feature',
                geometry: route
              }
            });

            map.addLayer({
              id: 'route-regreso',
              type: 'line',
              source: 'route-regreso',
              layout: {
                'line-join': 'round',
                'line-cap': 'round'
              },
              paint: {
                'line-color': '#9e9e9e', // Color gris
                'line-width': 4,
                'line-opacity': 0.6,
                'line-dasharray': [2, 2] // Línea punteada
              }
            });
          }
        } else {
          console.error('No se encontraron rutas de regreso en la respuesta:', data);
        }
      } catch (error) {
        console.error('Error obteniendo ruta de regreso:', error);
      }
    }

    // Actualizar estadísticas de distancia y tiempo
    function actualizarEstadisticas(distanciaMetros, duracionSegundos, duracionSinTrafico = null) {
      const distanciaKm = (distanciaMetros / 1000).toFixed(1);
      const horas = Math.floor(duracionSegundos / 3600);
      const minutos = Math.floor((duracionSegundos % 3600) / 60);

      let tiempoTexto = '';
      if (horas > 0) {
        tiempoTexto = `${horas}h ${minutos}m`;
      } else {
        tiempoTexto = `${minutos} min`;
      }

      document.getElementById('distancia-total').textContent = `${distanciaKm} km`;
      document.getElementById('tiempo-total').textContent = tiempoTexto;
      document.getElementById('stats-container').style.display = 'block';

      // Mostrar comparación con/sin tráfico si está disponible
      if (duracionSinTrafico && duracionSinTrafico < duracionSegundos) {
        const horasIdeal = Math.floor(duracionSinTrafico / 3600);
        const minutosIdeal = Math.floor((duracionSinTrafico % 3600) / 60);
        let tiempoIdealTexto = horasIdeal > 0 ? `${horasIdeal}h ${minutosIdeal}m` : `${minutosIdeal} min`;

        const retrasoMinutos = Math.round((duracionSegundos - duracionSinTrafico) / 60);

        document.getElementById('tiempo-ideal').textContent = tiempoIdealTexto;
        document.getElementById('retraso-trafico').textContent = `(+${retrasoMinutos} min de retraso)`;
        document.getElementById('tiempo-ideal-container').style.display = 'block';
      } else {
        document.getElementById('tiempo-ideal-container').style.display = 'none';
      }
    }

    // Optimizar ruta usando Mapbox Optimization API
    async function optimizarRuta() {
      console.log('Iniciando optimización de ruta...');

      // Validar que tenemos coordenadas válidas
      const pedidosOrdenados = Array.from(document.querySelectorAll('.pedido-item')).map((item) => {
        const pedidoId = item.dataset.pedidoId;
        return pedidos.find(p => p && (p.ID == pedidoId || parseInt(p.ID) === parseInt(pedidoId)));
      }).filter(Boolean);

      // Filtrar pedidos con coordenadas válidas
      const pedidosConCoords = pedidosOrdenados.filter(p => {
        if (!p.Coord_Destino || p.Coord_Destino.trim() === '') return false;
        try {
          const coordString = p.Coord_Destino.trim();
          let lat, lng;

          try {
            const coords = JSON.parse(coordString);
            lng = parseFloat(coords.lng);
            lat = parseFloat(coords.lat);
          } catch {
            if (coordString.includes(',')) {
              const parts = coordString.split(',').map(p => p.trim());
              if (parts.length === 2) {
                lat = parseFloat(parts[0]);
                lng = parseFloat(parts[1]);
              }
            }
          }

          return !isNaN(lng) && !isNaN(lat) && lng !== 0 && lat !== 0;
        } catch {
          return false;
        }
      });

      if (pedidosConCoords.length === 0) {
        Swal.fire({
          icon: 'error',
          title: 'No hay pedidos con coordenadas',
          text: 'No se encontraron pedidos con coordenadas válidas para optimizar',
          confirmButtonColor: grupoColor
        });
        return;
      }

      // Mostrar loading
      Swal.fire({
        title: 'Optimizando ruta...',
        html: 'Calculando la mejor ruta posible',
        allowOutsideClick: false,
        didOpen: () => {
          Swal.showLoading();
        }
      });

      try {
        // Preparar coordenadas para la API
        const coordenadas = [];

        // Agregar bodega/origen como punto fijo de inicio
        if (ubicacionOrigen && ubicacionOrigen.coordenadas) {
          try {
            let coordString = ubicacionOrigen.coordenadas.trim();
            let lat, lng;

            if (coordString.includes(',')) {
              const parts = coordString.split(',').map(p => p.trim());
              if (parts.length === 2) {
                lat = parseFloat(parts[0]);
                lng = parseFloat(parts[1]);
              }
            }

            if (!isNaN(lng) && !isNaN(lat) && lng !== 0 && lat !== 0) {
              coordenadas.push({lng, lat, type: 'origen'});
            }
          } catch (e) {
            console.error('Error procesando coordenadas de bodega:', e);
          }
        }

        // Agregar coordenadas de pedidos
        pedidosConCoords.forEach(pedido => {
          try {
            let coordString = pedido.Coord_Destino.trim();
            let lat, lng;

            try {
              const coords = JSON.parse(coordString);
              lng = parseFloat(coords.lng);
              lat = parseFloat(coords.lat);
            } catch {
              if (coordString.includes(',')) {
                const parts = coordString.split(',').map(p => p.trim());
                if (parts.length === 2) {
                  lat = parseFloat(parts[0]);
                  lng = parseFloat(parts[1]);
                }
              }
            }

            if (!isNaN(lng) && !isNaN(lat) && lng !== 0 && lat !== 0) {
              coordenadas.push({lng, lat, pedidoId: pedido.ID, type: 'destino'});
            }
          } catch (e) {
            console.error(`Error procesando coordenadas del pedido ${pedido.ID}:`, e);
          }
        });

        if (coordenadas.length < 2) {
          Swal.fire({
            icon: 'error',
            title: 'Coordenadas insuficientes',
            text: 'Se necesitan al menos 2 puntos para optimizar la ruta',
            confirmButtonColor: grupoColor
          });
          return;
        }

        // Mapbox Optimization API tiene límite de 12 waypoints (incluyendo origen)
        if (coordenadas.length > 12) {
          Swal.fire({
            icon: 'warning',
            title: 'Demasiados puntos',
            text: 'La API de optimización soporta máximo 12 puntos. Se optimizarán los primeros 12 pedidos.',
            confirmButtonColor: grupoColor
          });
          coordenadas.splice(12); // Limitar a 12 puntos
        }

        // Construir URL para Optimization API
        // source=first indica que debe empezar en el primer punto (bodega)
        // destination=last hace que regrese al último punto agregado (que será la bodega de nuevo)
        // Para asegurar que regrese a la bodega, agregamos la bodega al final también
        coordenadas.push(coordenadas[0]); // Agregar la bodega al final para asegurar el regreso

        const coordsString = coordenadas.map(c => `${c.lng},${c.lat}`).join(';');
        const url = `https://api.mapbox.com/optimized-trips/v1/mapbox/driving/${coordsString}?source=first&destination=last&roundtrip=true&geometries=geojson&overview=full&annotations=duration,distance&access_token=${mapboxgl.accessToken}`;

        console.log('URL de optimización:', url);

        const response = await fetch(url);
        const data = await response.json();

        console.log('Respuesta de Optimization API:', data);

        if (data.code !== 'Ok' || !data.trips || data.trips.length === 0) {
          throw new Error(data.message || 'No se pudo optimizar la ruta');
        }

        const trip = data.trips[0];
        const waypoints = data.waypoints;

        // El primer waypoint es el origen (bodega), el último también es el origen (regreso)
        // Los del medio son los destinos en orden optimizado
        const ordenOptimizado = waypoints.slice(1, -1).map(wp => {
          // wp.waypoint_index corresponde al índice original en coordenadas
          const coord = coordenadas[wp.waypoint_index];
          return coord.pedidoId;
        }).filter(id => id !== undefined);

        console.log('Orden optimizado:', ordenOptimizado);

        // Reordenar los elementos en la UI
        const contenedor = document.getElementById('pedidos-sortable');
        const items = Array.from(document.querySelectorAll('.pedido-item'));

        // Crear un nuevo orden
        ordenOptimizado.forEach((pedidoId, index) => {
          const item = items.find(i => parseInt(i.dataset.pedidoId) === pedidoId);
          if (item) {
            item.querySelector('.pedido-orden').textContent = index + 1;
            item.dataset.orden = index + 1;
            contenedor.appendChild(item); // Mover al final
          }
        });

        // Los pedidos sin coordenadas van al final
        items.forEach(item => {
          const pedidoId = parseInt(item.dataset.pedidoId);
          if (!ordenOptimizado.includes(pedidoId)) {
            contenedor.appendChild(item);
            const currentIndex = Array.from(contenedor.children).indexOf(item);
            item.querySelector('.pedido-orden').textContent = currentIndex + 1;
            item.dataset.orden = currentIndex + 1;
          }
        });

        // Actualizar mapa
        actualizarMapa();

        // Guardar nuevo orden
        await guardarNuevoOrden();

        // Mostrar resultados
        const distanciaKm = (trip.distance / 1000).toFixed(1);
        const horas = Math.floor(trip.duration / 3600);
        const minutos = Math.floor((trip.duration % 3600) / 60);
        let tiempoTexto = horas > 0 ? `${horas}h ${minutos}m` : `${minutos} min`;

        Swal.fire({
          icon: 'success',
          title: '¡Ruta optimizada!',
          html: `
            <div style="text-align: left; padding: 10px;">
              <p style="margin-bottom: 15px;">La ruta ha sido optimizada exitosamente.</p>
              <div style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                <div style="display: flex; justify-content: space-around;">
                  <div style="text-align: center;">
                    <div style="font-size: 24px; font-weight: bold; color: #28a745;">${distanciaKm} km</div>
                    <div style="font-size: 12px; color: #666;">Distancia total</div>
                  </div>
                  <div style="text-align: center;">
                    <div style="font-size: 24px; font-weight: bold; color: #007bff;">${tiempoTexto}</div>
                    <div style="font-size: 12px; color: #666;">Tiempo estimado</div>
                  </div>
                </div>
              </div>
              <p style="margin-top: 15px; font-size: 13px; color: #666;">
                Los pedidos han sido reordenados para minimizar la distancia y el tiempo de entrega.
              </p>
            </div>
          `,
          confirmButtonColor: grupoColor,
          confirmButtonText: 'Entendido'
        });

      } catch (error) {
        console.error('Error optimizando ruta:', error);
        Swal.fire({
          icon: 'error',
          title: 'Error al optimizar',
          text: error.message || 'No se pudo optimizar la ruta. Intenta nuevamente.',
          confirmButtonColor: '#dc3545'
        });
      }
    }

    // Inicializar SortableJS cuando el DOM esté listo
    document.addEventListener('DOMContentLoaded', function() {
      console.log('Inicializando Sortable...');
      const elemento = document.getElementById('pedidos-sortable');

      if (!elemento) {
        console.error('Elemento pedidos-sortable no encontrado');
        return;
      }

      const sortable = new Sortable(elemento, {
        animation: 150,
        ghostClass: 'sortable-ghost',
        cursor: 'move',
        onStart: function(evt) {
          console.log('Comenzó el arrastre');
        },
        onEnd: function(evt) {
          console.log('Terminó el arrastre');
        // Actualizar números de orden
        const items = document.querySelectorAll('.pedido-item');
        items.forEach((item, index) => {
          item.querySelector('.pedido-orden').textContent = index + 1;
          item.dataset.orden = index + 1;
        });

        // Actualizar mapa
        actualizarMapa();

          // Guardar nuevo orden en base de datos
          guardarNuevoOrden();
        }
      });

      console.log('Sortable inicializado correctamente');
    });

    // Guardar nuevo orden en la base de datos
    async function guardarNuevoOrden() {
      const items = document.querySelectorAll('.pedido-item');
      const ordenes = Array.from(items).map((item, index) => ({
        pedido_id: parseInt(item.dataset.pedidoId),
        orden: index + 1
      }));

      try {
        const response = await fetch('actualizar_grupo.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            accion: 'actualizar_orden',
            grupo_id: grupoId,
            ordenes: ordenes
          })
        });

        const result = await response.json();

        if (!result.success) {
          console.error('Error al guardar orden:', result.message);
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudo guardar el nuevo orden',
            confirmButtonColor: grupoColor
          });
        }
      } catch (error) {
        console.error('Error:', error);
      }
    }

    // Desactivar grupo
    async function desactivarGrupo() {
      const nombreGrupoSafe = <?php echo json_encode($grupo['nombre_grupo'] ?? ''); ?>;
      const choferGrupoSafe = <?php echo json_encode($grupo['chofer_asignado'] ?? 'este chofer'); ?>;

      const result = await Swal.fire({
        icon: 'warning',
        title: '¿Desactivar este grupo?',
        html: `
          <div style="text-align: left; font-size: 13.5px; color: #475569; padding: 4px 6px;">
            <p style="margin: 0 0 12px; color: #1e293b;">Esta acción desactivará la agrupación <strong>"${escapeHtml(nombreGrupoSafe)}"</strong>.</p>
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 14px; margin-bottom: 12px;">
              <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.3px; margin-bottom: 8px;">Opciones de Asignación</div>
              <label style="display: flex; align-items: flex-start; gap: 8px; margin: 8px 0; cursor: pointer; font-size: 13px; color: #1e293b;">
                <input type="radio" name="desactivar-opcion" value="mantener" checked style="margin-top: 3px; accent-color: #0f4c81;">
                <span><strong>Mantener chofer:</strong> Los pedidos continúan asignados a ${escapeHtml(choferGrupoSafe)}.</span>
              </label>
              <label style="display: flex; align-items: flex-start; gap: 8px; margin: 8px 0; cursor: pointer; font-size: 13px; color: #1e293b;">
                <input type="radio" name="desactivar-opcion" value="quitar" style="margin-top: 3px; accent-color: #0f4c81;">
                <span><strong>Liberar pedidos:</strong> Quitar la asignación del chofer para reasignarlos más tarde.</span>
              </label>
            </div>
            <p style="font-size: 12px; color: #94a3b8; margin: 0;">Los pedidos volverán a mostrarse individualmente en la tabla general.</p>
          </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Sí, desactivar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        preConfirm: () => {
          const opcion = document.querySelector('input[name="desactivar-opcion"]:checked');
          return opcion ? opcion.value : 'mantener';
        }
      });

      if (result.isConfirmed) {
        const opcion = result.value;

        // Mostrar loading
        Swal.fire({
          title: 'Desactivando grupo...',
          html: 'Por favor espere',
          allowOutsideClick: false,
          didOpen: () => {
            Swal.showLoading();
          }
        });

        try {
          const response = await fetch('actualizar_grupo.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({
              accion: 'desactivar',
              grupo_id: grupoId,
              quitar_chofer: opcion === 'quitar'
            })
          });

          const data = await response.json();

          if (data.success) {
            await Swal.fire({
              icon: 'success',
              title: 'Grupo desactivado',
              text: 'El grupo ha sido desactivado exitosamente',
              confirmButtonColor: '#28a745'
            });

            // Redirigir a la página principal
            window.location.href = 'Pedidos_GA.php';
          } else {
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: data.message || 'No se pudo desactivar el grupo',
              confirmButtonColor: '#dc3545'
            });
          }
        } catch (error) {
          console.error('Error:', error);
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Ocurrió un error al desactivar el grupo',
            confirmButtonColor: '#dc3545'
          });
        }
      }
    }

    // Captura de errores en Mapbox
    map.on('error', function(e) {
      console.error('Error reportado por Mapbox:', e);
    });

    // Función unificada para asegurar carga de mapa y trazado
    function iniciarCargaMapa() {
      if (typeof map !== 'undefined' && map.resize) {
        map.resize();
        actualizarMapa();
      }
    }

    // Inicializar mapa cuando esté listo o si ya cargó
    if (map.loaded()) {
      iniciarCargaMapa();
    } else {
      map.on('load', iniciarCargaMapa);
    }

    // Disparos de seguridad para reajuste de layout
    setTimeout(iniciarCargaMapa, 400);
    setTimeout(iniciarCargaMapa, 1000);

    window.addEventListener('resize', () => {
      if (typeof map !== 'undefined' && map.resize) {
        map.resize();
      }
    });
  </script>
</body>
</html>
