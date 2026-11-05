<?php
// Iniciar la sesión de forma segura
session_name("GA");
session_start();

// Verificar si el usuario está logeado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: /Pedidos_GA/Sesion/login.html");
    exit;
}

require_once __DIR__ . "/Conexiones/Conexion.php";

$sucursalSesion = strtoupper($_SESSION["Sucursal"] ?? "");
$rolSesion      = $_SESSION["Rol"] ?? "";

// Solo roles autorizados pueden acceder a este reporte (Admin, JC)
if (!in_array($rolSesion, ["Admin", "JC"])) {
    header("location: /Pedidos_GA/Pedidos_GA.php");
    exit;
}

// Auto-crear la tabla si no existe
$sqlTable = "CREATE TABLE IF NOT EXISTS `pedidos_incidencias` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `pedido_id` INT NOT NULL,
  `motivo` VARCHAR(150) NOT NULL,
  `comentarios` TEXT NULL,
  `archivo_ruta` VARCHAR(255) NULL,
  `archivo_nombre` VARCHAR(255) NULL,
  `archivo_tipo` VARCHAR(50) NULL,
  `chofer` VARCHAR(100) NULL,
  `fecha_registro` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_pedido_id` (`pedido_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
$conn->query($sqlTable);

// --- Filtros ---
$fechaDesde     = $_GET['fecha_desde'] ?? date('Y-m-d', strtotime('-30 days'));
$fechaHasta     = $_GET['fecha_hasta'] ?? date('Y-m-d');
$sucursalFiltro = $_GET['sucursal']    ?? '';
$motivoFiltro   = $_GET['motivo']      ?? '';
$evidenciaFiltro= $_GET['evidencia']   ?? ''; // '', 'con', 'sin'
$buscarFiltro   = trim($_GET['buscar'] ?? '');

// Restringir sucursal según rol
$where = " WHERE 1=1 ";
$params = [];
$types  = "";

// Rango de fechas
if (!empty($fechaDesde) && !empty($fechaHasta)) {
    $where .= " AND DATE(pi.fecha_registro) BETWEEN ? AND ? ";
    $params[] = $fechaDesde;
    $params[] = $fechaHasta;
    $types   .= "ss";
} elseif (!empty($fechaDesde)) {
    $where .= " AND DATE(pi.fecha_registro) >= ? ";
    $params[] = $fechaDesde;
    $types   .= "s";
} elseif (!empty($fechaHasta)) {
    $where .= " AND DATE(pi.fecha_registro) <= ? ";
    $params[] = $fechaHasta;
    $types   .= "s";
}

// Restricción por sucursal del usuario
if ($sucursalSesion !== "TODAS") {
    if (strtoupper($rolSesion) === 'JC' && $sucursalSesion === 'TAPATIA') {
        $where .= " AND p.SUCURSAL IN ('TAPATIA','ILUMINACION') ";
    } else {
        $where .= " AND p.SUCURSAL = ? ";
        $params[] = $sucursalSesion;
        $types   .= "s";
    }
} elseif (!empty($sucursalFiltro)) {
    $where .= " AND p.SUCURSAL = ? ";
    $params[] = $sucursalFiltro;
    $types   .= "s";
}

// Filtro por motivo
if (!empty($motivoFiltro)) {
    $where .= " AND pi.motivo = ? ";
    $params[] = $motivoFiltro;
    $types   .= "s";
}

// Filtro por evidencia
if ($evidenciaFiltro === 'con') {
    $where .= " AND pi.archivo_ruta IS NOT NULL AND pi.archivo_ruta != '' ";
} elseif ($evidenciaFiltro === 'sin') {
    $where .= " AND (pi.archivo_ruta IS NULL OR pi.archivo_ruta = '') ";
}

// Búsqueda por texto (Factura, Pedido ID, Cliente, Chofer, Comentarios)
if (!empty($buscarFiltro)) {
    $where .= " AND (p.FACTURA LIKE ? OR p.NOMBRE_CLIENTE LIKE ? OR pi.chofer LIKE ? OR pi.comentarios LIKE ? OR pi.pedido_id = ?) ";
    $likeBuscar = "%" . $buscarFiltro . "%";
    $idNum = intval($buscarFiltro);
    $params[] = $likeBuscar;
    $params[] = $likeBuscar;
    $params[] = $likeBuscar;
    $params[] = $likeBuscar;
    $params[] = $idNum;
    $types   .= "ssssi";
}

// Consulta de incidencias
$sql = "SELECT pi.*, 
               p.FACTURA, 
               p.SUCURSAL, 
               p.NOMBRE_CLIENTE, 
               p.DIRECCION, 
               p.CHOFER_ASIGNADO, 
               p.VENDEDOR, 
               p.ESTADO, 
               p.tipo_envio,
               DATE_FORMAT(pi.fecha_registro, '%d/%m/%Y %H:%i') as fecha_formato
        FROM pedidos_incidencias pi
        LEFT JOIN pedidos p ON pi.pedido_id = p.ID
        $where
        ORDER BY pi.fecha_registro DESC, pi.id DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$incidencias = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();

// --- Calcular estadísticas y KPIs ---
$totalIncidencias = count($incidencias);
$pedidosUnicos = [];
$motivosCount = [];
$sucursalesCount = [];
$conEvidenciaCount = 0;
$reportantesCount = [];
$clientesCount = [];

foreach ($incidencias as $inc) {
    if (!empty($inc['pedido_id'])) {
        $pedidosUnicos[$inc['pedido_id']] = true;
    }
    
    $m = $inc['motivo'] ?: 'Sin motivo';
    $motivosCount[$m] = ($motivosCount[$m] ?? 0) + 1;
    
    $s = $inc['SUCURSAL'] ?: 'Sin Sucursal';
    $sucursalesCount[$s] = ($sucursalesCount[$s] ?? 0) + 1;
    
    if (!empty($inc['archivo_ruta'])) {
        $conEvidenciaCount++;
    }
    
    // Usuario / Chofer que generó o registró la incidencia
    $rep = trim($inc['chofer'] ?: 'No especificado');
    $reportantesCount[$rep] = ($reportantesCount[$rep] ?? 0) + 1;

    // Cliente
    $cli = trim($inc['NOMBRE_CLIENTE'] ?: 'Sin cliente');
    $clientesCount[$cli] = ($clientesCount[$cli] ?? 0) + 1;
}

arsort($motivosCount);
arsort($sucursalesCount);
arsort($reportantesCount);
arsort($clientesCount);

$topMotivo = !empty($motivosCount) ? array_key_first($motivosCount) : 'Ninguno';
$topMotivoCantidad = !empty($motivosCount) ? reset($motivosCount) : 0;
$topMotivoPct = $totalIncidencias > 0 ? round(($topMotivoCantidad / $totalIncidencias) * 100, 1) : 0;

$topSucursal = !empty($sucursalesCount) ? array_key_first($sucursalesCount) : 'Ninguna';
$topSucursalCantidad = !empty($sucursalesCount) ? reset($sucursalesCount) : 0;

$topCliente = !empty($clientesCount) ? array_key_first($clientesCount) : 'Ninguno';
$topClienteCantidad = !empty($clientesCount) ? reset($clientesCount) : 0;
$topClientePct = $totalIncidencias > 0 ? round(($topClienteCantidad / $totalIncidencias) * 100, 1) : 0;

$topUsuario = !empty($reportantesCount) ? array_key_first($reportantesCount) : 'Ninguno';
$topUsuarioCantidad = !empty($reportantesCount) ? reset($reportantesCount) : 0;
$topUsuarioPct = $totalIncidencias > 0 ? round(($topUsuarioCantidad / $totalIncidencias) * 100, 1) : 0;

$pctEvidencia = $totalIncidencias > 0 ? round(($conEvidenciaCount / $totalIncidencias) * 100, 1) : 0;

// Sucursales para el selector de filtros
$allSucs = [];
if ($sucursalSesion === "TODAS") {
    $resSucs = $conn->query("SELECT DISTINCT SUCURSAL FROM pedidos WHERE SUCURSAL IS NOT NULL AND SUCURSAL != '' ORDER BY SUCURSAL");
    if ($resSucs) {
        $allSucs = $resSucs->fetch_all(MYSQLI_ASSOC);
    }
}

// Lista oficial de motivos
$catalogoMotivos = [
    'Faltante',
    'Sobrante',
    'Error de entrega',
    'Producto erróneo',
    'Cliente no recibe pedido',
    'Cliente reprograma pedido',
    'Cliente no cuenta con maniobra para descarga',
    'Cuestiones climáticas'
];

// Colores por motivo
function getMotivoColor($motivo) {
    switch ($motivo) {
        case 'Faltante':
            return ['bg' => '#fee2e2', 'text' => '#b91c1c', 'border' => '#fca5a5', 'bar' => '#ef4444'];
        case 'Sobrante':
            return ['bg' => '#f3e8ff', 'text' => '#6b21a8', 'border' => '#d8b4fe', 'bar' => '#a855f7'];
        case 'Error de entrega':
            return ['bg' => '#ffedd5', 'text' => '#c2410c', 'border' => '#fdba74', 'bar' => '#f97316'];
        case 'Producto erróneo':
            return ['bg' => '#fef3c7', 'text' => '#b45309', 'border' => '#fde68a', 'bar' => '#f59e0b'];
        case 'Cliente no recibe pedido':
            return ['bg' => '#fce7f3', 'text' => '#be185d', 'border' => '#fbcfe8', 'bar' => '#ec4899'];
        case 'Cliente reprograma pedido':
            return ['bg' => '#e0e7ff', 'text' => '#3730a3', 'border' => '#c7d2fe', 'bar' => '#6366f1'];
        case 'Cliente no cuenta con maniobra para descarga':
            return ['bg' => '#e0f2fe', 'text' => '#0369a1', 'border' => '#bae6fd', 'bar' => '#0284c7'];
        case 'Cuestiones climáticas':
            return ['bg' => '#ccfbf1', 'text' => '#0f766e', 'border' => '#99f6e4', 'bar' => '#14b8a6'];
        default:
            return ['bg' => '#f1f5f9', 'text' => '#334155', 'border' => '#cbd5e1', 'bar' => '#64748b'];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reporte General de Incidencias - Pedidos GA</title>
  <link rel="icon" type="image/png" href="/Pedidos_GA/Img/Botones%20entregas/ICONOSPAG/ICONOPEDIDOS.png">
  <style>
    :root {
      --primary: #005aa3;
      --primary-dark: #004075;
      --primary-light: #e0f2fe;
      --text-main: #0f172a;
      --text-muted: #64748b;
      --border-color: #e2e8f0;
      --bg-page: #f8fafc;
      --card-bg: #ffffff;
      --radius: 12px;
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
      background: var(--bg-page);
      color: var(--text-main);
      line-height: 1.5;
    }

    /* Topbar */
    .topbar {
      background: linear-gradient(135deg, #005aa3 0%, #005aa3 100%);
      color: #fff;
      padding: 16px 28px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
    .topbar-title {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .topbar-title svg {
      flex-shrink: 0;
    }
    .topbar h1 {
      font-size: 21px;
      font-weight: 700;
      letter-spacing: -0.02em;
    }
    .topbar a.btn-back {
      color: #bae6fd;
      text-decoration: none;
      font-size: 13px;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 14px;
      border-radius: 8px;
      background: rgba(255, 255, 255, 0.1);
      transition: all 0.2s;
    }
    .topbar a.btn-back:hover {
      background: rgba(255, 255, 255, 0.2);
      color: #fff;
    }

    /* Contenedor */
    .container {
      max-width: 1440px;
      margin: 0 auto;
      padding: 24px 20px 60px 20px;
    }

    /* Barra de Filtros */
    .filter-card {
      background: var(--card-bg);
      border-radius: var(--radius);
      padding: 20px;
      margin-bottom: 24px;
      box-shadow: 0 2px 8px -2px rgba(15, 23, 42, 0.08);
      border: 1px solid var(--border-color);
    }
    .filter-form {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 14px;
      align-items: end;
    }
    .filter-group {
      display: flex;
      flex-direction: column;
      gap: 5px;
    }
    .filter-group label {
      font-size: 12px;
      font-weight: 700;
      color: var(--text-muted);
      text-transform: uppercase;
      letter-spacing: 0.03em;
    }
    .filter-group input, 
    .filter-group select {
      padding: 8px 12px;
      border: 1px solid var(--border-color);
      border-radius: 8px;
      font-size: 13px;
      background: #fdfdfd;
      color: var(--text-main);
      outline: none;
      transition: border-color 0.2s, box-shadow 0.2s;
    }
    .filter-group input:focus, 
    .filter-group select:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(0, 90, 163, 0.12);
    }
    .filter-actions {
      display: flex;
      gap: 8px;
      grid-column: span 2;
    }
    @media (max-width: 900px) {
      .filter-actions {
        grid-column: 1 / -1;
      }
    }
    .btn-submit {
      flex: 1;
      background: var(--primary);
      color: #fff;
      border: none;
      padding: 9px 18px;
      border-radius: 8px;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      transition: background 0.2s;
    }
    .btn-submit:hover {
      background: var(--primary-dark);
    }
    .btn-reset {
      background: #f1f5f9;
      color: var(--text-muted);
      border: 1px solid var(--border-color);
      padding: 9px 14px;
      border-radius: 8px;
      font-size: 13px;
      font-weight: 600;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      transition: all 0.2s;
    }
    .btn-reset:hover {
      background: #e2e8f0;
      color: var(--text-main);
    }
    .btn-export {
      background: #16a34a;
      color: #fff;
      border: none;
      padding: 9px 16px;
      border-radius: 8px;
      font-size: 13px;
      font-weight: 600;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      transition: background 0.2s;
    }
    .btn-export:hover {
      background: #15803d;
    }

    /* KPI Cards */
    .kpi-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
      gap: 16px;
      margin-bottom: 24px;
    }
    .kpi-card {
      background: var(--card-bg);
      border-radius: var(--radius);
      padding: 18px 20px;
      border: 1px solid var(--border-color);
      box-shadow: 0 2px 6px -1px rgba(15, 23, 42, 0.05);
      position: relative;
      overflow: hidden;
    }
    .kpi-card::before {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: var(--kpi-color, #005aa3);
    }
    .kpi-label {
      font-size: 11px;
      font-weight: 700;
      color: var(--text-muted);
      text-transform: uppercase;
      letter-spacing: 0.04em;
      margin-bottom: 6px;
    }
    .kpi-val {
      font-size: 28px;
      font-weight: 800;
      color: var(--text-main);
      line-height: 1.1;
    }
    .kpi-sub {
      font-size: 12px;
      color: var(--text-muted);
      margin-top: 6px;
      display: flex;
      align-items: center;
      gap: 4px;
    }

    /* Sección de Gráficos y Distribución */
    .analytics-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
      margin-bottom: 24px;
    }
    @media (max-width: 1024px) {
      .analytics-grid {
        grid-template-columns: 1fr;
      }
    }
    .panel-card {
      background: var(--card-bg);
      border-radius: var(--radius);
      padding: 20px;
      border: 1px solid var(--border-color);
      box-shadow: 0 2px 8px -2px rgba(15, 23, 42, 0.06);
    }
    .panel-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 16px;
      padding-bottom: 10px;
      border-bottom: 1px solid var(--border-color);
    }
    .panel-title {
      font-size: 15px;
      font-weight: 700;
      color: var(--text-main);
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .bar-item {
      margin-bottom: 12px;
    }
    .bar-info {
      display: flex;
      justify-content: space-between;
      font-size: 12.5px;
      font-weight: 600;
      margin-bottom: 4px;
    }
    .bar-name {
      color: var(--text-main);
      display: flex;
      align-items: center;
      gap: 6px;
    }
    .bar-count {
      color: var(--text-muted);
      font-variant-numeric: tabular-nums;
    }
    .bar-track {
      background: #f1f5f9;
      border-radius: 999px;
      height: 12px;
      overflow: hidden;
      display: flex;
    }
    .bar-fill {
      height: 100%;
      border-radius: 999px;
      transition: width 0.6s ease;
    }

    /* Badges de Ranking y Acciones de Filtro */
    .rank-badge {
      width: 20px;
      height: 20px;
      border-radius: 50%;
      background: #f1f5f9;
      color: #64748b;
      font-size: 10.5px;
      font-weight: 700;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }
    .rank-badge.top-1 { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
    .rank-badge.top-2 { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
    .rank-badge.top-3 { background: #ffedd5; color: #c2410c; border: 1px solid #fed7aa; }

    .btn-quick-filter {
      background: transparent;
      border: 1px solid #e2e8f0;
      border-radius: 6px;
      padding: 1px 7px;
      font-size: 11px;
      font-weight: 600;
      color: #0284c7;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 3px;
      transition: all 0.15s;
    }
    .btn-quick-filter:hover {
      background: #e0f2fe;
      border-color: #bae6fd;
      color: #0369a1;
    }

    /* Tabla de Incidencias */
    .table-container {
      background: var(--card-bg);
      border-radius: var(--radius);
      border: 1px solid var(--border-color);
      box-shadow: 0 4px 12px -2px rgba(15, 23, 42, 0.08);
      overflow: hidden;
    }
    .table-header-bar {
      padding: 16px 20px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-bottom: 1px solid var(--border-color);
      background: #fafcff;
      flex-wrap: wrap;
      gap: 12px;
    }
    .table-title {
      font-size: 16px;
      font-weight: 700;
      color: var(--text-main);
    }
    .table-quicksearch {
      padding: 6px 12px;
      border: 1px solid var(--border-color);
      border-radius: 8px;
      font-size: 13px;
      width: 250px;
      outline: none;
    }
    .table-quicksearch:focus {
      border-color: var(--primary);
    }

    .table-scroll {
      overflow-x: auto;
      width: 100%;
    }
    table.inc-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 12.5px;
      text-align: left;
    }
    table.inc-table th {
      background: #f8fafc;
      color: #475569;
      font-weight: 700;
      text-transform: uppercase;
      font-size: 11px;
      letter-spacing: 0.03em;
      padding: 12px 14px;
      border-bottom: 2px solid var(--border-color);
      white-space: nowrap;
    }
    table.inc-table td {
      padding: 12px 14px;
      border-bottom: 1px solid #f1f5f9;
      vertical-align: middle;
      color: var(--text-main);
    }
    table.inc-table tr:hover td {
      background: #f8fafc;
    }

    /* Elementos de la tabla */
    .badge-motivo {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      padding: 3px 10px;
      border-radius: 999px;
      font-size: 11.5px;
      font-weight: 700;
      white-space: nowrap;
      border: 1px solid transparent;
    }
    .badge-sucursal {
      display: inline-block;
      padding: 2px 8px;
      border-radius: 6px;
      font-size: 11px;
      font-weight: 700;
      background: #e0f2fe;
      color: #0369a1;
      border: 1px solid #bae6fd;
    }
    .pedido-link {
      font-weight: 700;
      color: #005aa3;
      text-decoration: none;
    }
    .pedido-link:hover {
      text-decoration: underline;
    }
    .factura-code {
      font-weight: 800;
      color: #0f172a;
      font-variant-numeric: tabular-nums;
    }
    .comentario-text {
      max-width: 280px;
      color: #334155;
      font-size: 12px;
      line-height: 1.4;
      word-break: break-word;
    }
    .chofer-tag {
      font-weight: 600;
      color: #334155;
      display: flex;
      align-items: center;
      gap: 5px;
    }
    .btn-evidencia {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      background: #f0fdf4;
      color: #15803d;
      border: 1px solid #bbf7d0;
      padding: 4px 10px;
      border-radius: 6px;
      font-size: 11.5px;
      font-weight: 600;
      text-decoration: none;
      cursor: pointer;
      transition: all 0.15s;
    }
    .btn-evidencia:hover {
      background: #dcfce7;
      border-color: #86efac;
    }
    .btn-ver-pedido {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      padding: 5px 10px;
      background: #f1f5f9;
      color: #0f172a;
      border: 1px solid #cbd5e1;
      border-radius: 6px;
      font-size: 11px;
      font-weight: 600;
      text-decoration: none;
      transition: all 0.15s;
    }
    .btn-ver-pedido:hover {
      background: #e2e8f0;
      border-color: #94a3b8;
    }

    /* Modal Lightbox para evidencias */
    .lightbox-modal {
      display: none;
      position: fixed;
      z-index: 99999;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background: rgba(15, 23, 42, 0.85);
      backdrop-filter: blur(4px);
      align-items: center;
      justify-content: center;
      padding: 20px;
    }
    .lightbox-modal.active {
      display: flex;
    }
    .lightbox-content {
      background: #fff;
      border-radius: 12px;
      max-width: 900px;
      width: 100%;
      max-height: 90vh;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3);
      position: relative;
    }
    .lightbox-header {
      padding: 14px 20px;
      border-bottom: 1px solid var(--border-color);
      display: flex;
      align-items: center;
      justify-content: space-between;
      background: #f8fafc;
    }
    .lightbox-header h3 {
      font-size: 15px;
      font-weight: 700;
      color: var(--text-main);
    }
    .lightbox-close {
      background: transparent;
      border: none;
      font-size: 22px;
      color: #64748b;
      cursor: pointer;
      line-height: 1;
      padding: 4px;
    }
    .lightbox-close:hover {
      color: #0f172a;
    }
    .lightbox-body {
      padding: 20px;
      overflow-y: auto;
      text-align: center;
      background: #0f172a;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 300px;
    }
    .lightbox-body img {
      max-width: 100%;
      max-height: 75vh;
      border-radius: 8px;
      object-fit: contain;
    }
    .lightbox-footer {
      padding: 12px 20px;
      background: #f8fafc;
      border-top: 1px solid var(--border-color);
      display: flex;
      justify-content: flex-end;
      gap: 10px;
    }
    .empty-state {
      text-align: center;
      padding: 50px 20px;
      color: var(--text-muted);
    }
  </style>
</head>
<body>

  <!-- Topbar -->
  <div class="topbar">
    <div class="topbar-title">
      <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#fdba74" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
        <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
        <line x1="12" y1="9" x2="12" y2="13"/>
        <line x1="12" y1="17" x2="12.01" y2="17"/>
      </svg>
      <h1>Reporte General de Incidencias</h1>
    </div>
    <a href="Pedidos_GA.php" class="btn-back">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <line x1="19" y1="12" x2="5" y2="12"/>
        <polyline points="12 19 5 12 12 5"/>
      </svg>
      Volver a Pedidos
    </a>
  </div>

  <div class="container">

    <!-- Tarjeta de Filtros -->
    <div class="filter-card">
      <form method="GET" class="filter-form">
        <div class="filter-group">
          <label>Fecha Desde</label>
          <input type="date" name="fecha_desde" value="<?= htmlspecialchars($fechaDesde) ?>">
        </div>
        <div class="filter-group">
          <label>Fecha Hasta</label>
          <input type="date" name="fecha_hasta" value="<?= htmlspecialchars($fechaHasta) ?>">
        </div>

        <?php if ($sucursalSesion === "TODAS"): ?>
        <div class="filter-group">
          <label>Sucursal</label>
          <select name="sucursal">
            <option value="">Todas las sucursales</option>
            <?php foreach ($allSucs as $s): ?>
              <option value="<?= htmlspecialchars($s['SUCURSAL']) ?>" <?= ($sucursalFiltro === $s['SUCURSAL']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($s['SUCURSAL']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php endif; ?>

        <div class="filter-group">
          <label>Motivo</label>
          <select name="motivo">
            <option value="">Todos los motivos</option>
            <?php foreach ($catalogoMotivos as $mot): ?>
              <option value="<?= htmlspecialchars($mot) ?>" <?= ($motivoFiltro === $mot) ? 'selected' : '' ?>>
                <?= htmlspecialchars($mot) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="filter-group">
          <label>Evidencia / Adjunto</label>
          <select name="evidencia">
            <option value="">Todos</option>
            <option value="con" <?= ($evidenciaFiltro === 'con') ? 'selected' : '' ?>>Solo con evidencia</option>
            <option value="sin" <?= ($evidenciaFiltro === 'sin') ? 'selected' : '' ?>>Sin evidencia</option>
          </select>
        </div>

        <div class="filter-group">
          <label>Búsqueda Rápida</label>
          <input type="text" name="buscar" placeholder="Factura, cliente, chofer..." value="<?= htmlspecialchars($buscarFiltro) ?>">
        </div>

        <div class="filter-actions">
          <button type="submit" class="btn-submit">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="11" cy="11" r="8"/>
              <line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            Filtrar
          </button>
          <a href="reporte_incidencias.php" class="btn-reset" title="Limpiar filtros">Limpiar</a>
          <a href="export_reporte_incidencias.php?fecha_desde=<?= urlencode($fechaDesde) ?>&fecha_hasta=<?= urlencode($fechaHasta) ?>&sucursal=<?= urlencode($sucursalFiltro) ?>&motivo=<?= urlencode($motivoFiltro) ?>&evidencia=<?= urlencode($evidenciaFiltro) ?>&buscar=<?= urlencode($buscarFiltro) ?>" class="btn-export" title="Exportar reporte a Excel">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
              <polyline points="7 10 12 15 17 10"/>
              <line x1="12" y1="15" x2="12" y2="3"/>
            </svg>
            Excel
          </a>
        </div>
      </form>
    </div>

    <!-- Métricas / KPIs -->
    <div class="kpi-grid">
      <div class="kpi-card" style="--kpi-color: #dc2626;">
        <div class="kpi-label">Total Incidencias</div>
        <div class="kpi-val"><?= number_format($totalIncidencias) ?></div>
        <div class="kpi-sub">En el período consultado</div>
      </div>

      <div class="kpi-card" style="--kpi-color: #ea580c;">
        <div class="kpi-label">Pedidos Afectados</div>
        <div class="kpi-val"><?= number_format(count($pedidosUnicos)) ?></div>
        <div class="kpi-sub">Folios de pedidos únicos</div>
      </div>

      <div class="kpi-card" style="--kpi-color: #7c3aed;">
        <div class="kpi-label">Motivo Principal</div>
        <div class="kpi-val" style="font-size: 18px; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= htmlspecialchars($topMotivo) ?>"><?= htmlspecialchars($topMotivo) ?></div>
        <div class="kpi-sub"><?= $topMotivoCantidad ?> casos (<?= $topMotivoPct ?>%)</div>
      </div>

      <div class="kpi-card" style="--kpi-color: #0284c7;">
        <div class="kpi-label">Cliente con Más Incidencias</div>
        <div class="kpi-val" style="font-size: 18px; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= htmlspecialchars($topCliente) ?>"><?= htmlspecialchars($topCliente) ?></div>
        <div class="kpi-sub"><?= $topClienteCantidad ?> incidencias (<?= $topClientePct ?>%)</div>
      </div>

      <div class="kpi-card" style="--kpi-color: #0d9488;">
        <div class="kpi-label">Usuario con Más Incidencias</div>
        <div class="kpi-val" style="font-size: 18px; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= htmlspecialchars($topUsuario) ?>"><?= htmlspecialchars($topUsuario) ?></div>
        <div class="kpi-sub"><?= $topUsuarioCantidad ?> reportes (<?= $topUsuarioPct ?>%)</div>
      </div>

      <div class="kpi-card" style="--kpi-color: #16a34a;">
        <div class="kpi-label">Con Evidencia Adjunta</div>
        <div class="kpi-val"><?= number_format($conEvidenciaCount) ?></div>
        <div class="kpi-sub"><?= $pctEvidencia ?>% cuentan con adjunto</div>
      </div>
    </div>

    <!-- Fila 1 de Paneles: Motivos y Sucursales -->
    <div class="analytics-grid">
      <!-- Distribución por Motivo -->
      <div class="panel-card">
        <div class="panel-header">
          <div class="panel-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-width="2.3">
              <line x1="18" y1="20" x2="18" y2="10"/>
              <line x1="12" y1="20" x2="12" y2="4"/>
              <line x1="6" y1="20" x2="6" y2="14"/>
            </svg>
            Distribución por Causa o Motivo
          </div>
          <span style="font-size: 12px; color: var(--text-muted); font-weight: 600;"><?= count($motivosCount) ?> categorías</span>
        </div>

        <?php if (empty($motivosCount)): ?>
          <div class="empty-state">No se registraron incidencias en este período.</div>
        <?php else: ?>
          <?php foreach ($motivosCount as $motivo => $cantidad): 
            $pct = $totalIncidencias > 0 ? ($cantidad / $totalIncidencias) * 100 : 0;
            $colorInfo = getMotivoColor($motivo);
          ?>
            <div class="bar-item">
              <div class="bar-info">
                <span class="bar-name">
                  <span style="width: 10px; height: 10px; border-radius: 50%; background: <?= $colorInfo['bar'] ?>; display: inline-block;"></span>
                  <?= htmlspecialchars($motivo) ?>
                </span>
                <div style="display: flex; align-items: center; gap: 8px;">
                  <button type="button" class="btn-quick-filter" onclick="filtrarTablaPorTexto('<?= addslashes($motivo) ?>')" title="Filtrar tabla por este motivo">Filtrar</button>
                  <span class="bar-count"><strong><?= $cantidad ?></strong> (<?= round($pct, 1) ?>%)</span>
                </div>
              </div>
              <div class="bar-track">
                <div class="bar-fill" style="width: <?= max(2, round($pct)) ?>%; background: <?= $colorInfo['bar'] ?>;"></div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <!-- Distribución por Sucursal -->
      <div class="panel-card">
        <div class="panel-header">
          <div class="panel-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#005aa3" stroke-width="2.3">
              <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
              <polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
            Incidencias por Sucursal
          </div>
          <span style="font-size: 12px; color: var(--text-muted); font-weight: 600;"><?= count($sucursalesCount) ?> sucursales</span>
        </div>

        <?php if (empty($sucursalesCount)): ?>
          <div class="empty-state">Sin datos de sucursal.</div>
        <?php else: ?>
          <?php foreach ($sucursalesCount as $suc => $cantidad): 
            $pct = $totalIncidencias > 0 ? ($cantidad / $totalIncidencias) * 100 : 0;
          ?>
            <div class="bar-item">
              <div class="bar-info">
                <span class="bar-name"><?= htmlspecialchars($suc) ?></span>
                <div style="display: flex; align-items: center; gap: 8px;">
                  <button type="button" class="btn-quick-filter" onclick="filtrarTablaPorTexto('<?= addslashes($suc) ?>')" title="Filtrar tabla por esta sucursal">Filtrar</button>
                  <span class="bar-count"><strong><?= $cantidad ?></strong> (<?= round($pct, 1) ?>%)</span>
                </div>
              </div>
              <div class="bar-track">
                <div class="bar-fill" style="width: <?= max(2, round($pct)) ?>%; background: #005aa3;"></div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- Fila 2 de Paneles: Top Clientes y Top Usuarios -->
    <div class="analytics-grid">
      <!-- Top Clientes con Más Incidencias -->
      <div class="panel-card">
        <div class="panel-header">
          <div class="panel-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#0284c7" stroke-width="2.3">
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
              <circle cx="9" cy="7" r="4"/>
              <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
              <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
            Clientes con Más Incidencias (Top)
          </div>
          <span style="font-size: 12px; color: var(--text-muted); font-weight: 600;"><?= count($clientesCount) ?> clientes</span>
        </div>

        <?php if (empty($clientesCount)): ?>
          <div class="empty-state">No hay registros de clientes en este período.</div>
        <?php else: ?>
          <?php 
          $posCli = 1;
          foreach (array_slice($clientesCount, 0, 8, true) as $cliNombre => $cantidad): 
            $pct = $totalIncidencias > 0 ? ($cantidad / $totalIncidencias) * 100 : 0;
            $rankClass = ($posCli === 1) ? 'top-1' : (($posCli === 2) ? 'top-2' : (($posCli === 3) ? 'top-3' : ''));
          ?>
            <div class="bar-item">
              <div class="bar-info">
                <span class="bar-name">
                  <span class="rank-badge <?= $rankClass ?>">#<?= $posCli ?></span>
                  <strong><?= htmlspecialchars($cliNombre) ?></strong>
                </span>
                <div style="display: flex; align-items: center; gap: 8px;">
                  <button type="button" class="btn-quick-filter" onclick="filtrarTablaPorTexto('<?= addslashes($cliNombre) ?>')" title="Filtrar tabla por este cliente">Ver pedidos</button>
                  <span class="bar-count"><strong><?= $cantidad ?></strong> (<?= round($pct, 1) ?>%)</span>
                </div>
              </div>
              <div class="bar-track">
                <div class="bar-fill" style="width: <?= max(2, round($pct)) ?>%; background: linear-gradient(90deg, #0284c7 0%, #38bdf8 100%);"></div>
              </div>
            </div>
          <?php 
            $posCli++;
          endforeach; 
          ?>
        <?php endif; ?>
      </div>

      <!-- Top Usuarios que Generan Más Incidencias -->
      <div class="panel-card">
        <div class="panel-header">
          <div class="panel-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#0d9488" stroke-width="2.3">
              <circle cx="12" cy="7" r="4"/>
              <path d="M5.5 21a8.38 8.38 0 0 1 13 0"/>
              <path d="M12 11v3"/>
              <path d="M10 14h4"/>
            </svg>
            Usuarios que Generan / Reportan Más Incidencias
          </div>
          <span style="font-size: 12px; color: var(--text-muted); font-weight: 600;"><?= count($reportantesCount) ?> usuarios/choferes</span>
        </div>

        <?php if (empty($reportantesCount)): ?>
          <div class="empty-state">No hay registros de usuarios en este período.</div>
        <?php else: ?>
          <?php 
          $posUsr = 1;
          foreach (array_slice($reportantesCount, 0, 8, true) as $usrNombre => $cantidad): 
            $pct = $totalIncidencias > 0 ? ($cantidad / $totalIncidencias) * 100 : 0;
            $rankClass = ($posUsr === 1) ? 'top-1' : (($posUsr === 2) ? 'top-2' : (($posUsr === 3) ? 'top-3' : ''));
          ?>
            <div class="bar-item">
              <div class="bar-info">
                <span class="bar-name">
                  <span class="rank-badge <?= $rankClass ?>">#<?= $posUsr ?></span>
                  <strong><?= htmlspecialchars($usrNombre) ?></strong>
                </span>
                <div style="display: flex; align-items: center; gap: 8px;">
                  <button type="button" class="btn-quick-filter" onclick="filtrarTablaPorTexto('<?= addslashes($usrNombre) ?>')" title="Filtrar tabla por este usuario">Ver reportes</button>
                  <span class="bar-count"><strong><?= $cantidad ?></strong> (<?= round($pct, 1) ?>%)</span>
                </div>
              </div>
              <div class="bar-track">
                <div class="bar-fill" style="width: <?= max(2, round($pct)) ?>%; background: linear-gradient(90deg, #0d9488 0%, #2dd4bf 100%);"></div>
              </div>
            </div>
          <?php 
            $posUsr++;
          endforeach; 
          ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- Tabla Detallada -->
    <div class="table-container">
      <div class="table-header-bar">
        <div class="table-title">Detalle de Incidencias Registradas (<?= count($incidencias) ?>)</div>
        <input type="text" id="tablaBusquedaEnVivo" class="table-quicksearch" placeholder="Filtrar en esta tabla en vivo...">
      </div>

      <div class="table-scroll">
        <table class="inc-table" id="tablaIncidencias">
          <thead>
            <tr>
              <th style="width: 50px;">#</th>
              <th style="width: 130px;">Fecha / Hora</th>
              <th>Pedido / Factura</th>
              <th>Sucursal</th>
              <th>Cliente</th>
              <th>Motivo de la Incidencia</th>
              <th>Comentarios Adicionales</th>
              <th>Reportado Por</th>
              <th style="text-align: center;">Evidencia</th>
              <th style="text-align: center;">Acción</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($incidencias)): ?>
              <tr>
                <td colspan="10" style="text-align: center; padding: 40px; color: #94a3b8; font-size: 14px;">
                  No se encontraron incidencias que coincidan con los filtros seleccionados.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($incidencias as $idx => $inc): 
                $colores = getMotivoColor($inc['motivo']);
                $tieneArchivo = !empty($inc['archivo_ruta']);
                $esImagen = ($inc['archivo_tipo'] === 'imagen');
                $rutaArchivo = htmlspecialchars($inc['archivo_ruta'] ?? '');
                $nombreArchivo = htmlspecialchars($inc['archivo_nombre'] ?? 'Evidencia');
              ?>
                <tr class="fila-incidencia">
                  <td style="color: #64748b; font-weight: 600; font-variant-numeric: tabular-nums;">
                    <?= $inc['id'] ?>
                  </td>
                  <td style="white-space: nowrap; font-variant-numeric: tabular-nums; color: #475569; font-size: 12px;">
                    <strong><?= $inc['fecha_formato'] ?></strong>
                  </td>
                  <td>
                    <div>
                      <a href="Inicio.php?id=<?= $inc['pedido_id'] ?>" target="_blank" class="pedido-link" title="Ver pedido en pestaña nueva">
                        Pedido #<?= $inc['pedido_id'] ?>
                      </a>
                    </div>
                    <?php if (!empty($inc['FACTURA'])): ?>
                      <div class="factura-code" style="font-size: 11.5px; color: #64748b;">
                        Fact: <span style="color: #0f172a;"><?= htmlspecialchars($inc['FACTURA']) ?></span>
                      </div>
                    <?php endif; ?>
                  </td>
                  <td>
                    <span class="badge-sucursal"><?= htmlspecialchars($inc['SUCURSAL'] ?: 'N/A') ?></span>
                  </td>
                  <td>
                    <div style="font-weight: 700; color: #0f172a; font-size: 12.5px;">
                      <?= htmlspecialchars($inc['NOMBRE_CLIENTE'] ?: 'Sin cliente') ?>
                    </div>
                    <?php if (!empty($inc['DIRECCION'])): ?>
                      <div style="font-size: 11px; color: #64748b; max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= htmlspecialchars($inc['DIRECCION']) ?>">
                        <?= htmlspecialchars($inc['DIRECCION']) ?>
                      </div>
                    <?php endif; ?>
                  </td>
                  <td>
                    <span class="badge-motivo" style="background: <?= $colores['bg'] ?>; color: <?= $colores['text'] ?>; border-color: <?= $colores['border'] ?>;">
                      <?= htmlspecialchars($inc['motivo']) ?>
                    </span>
                  </td>
                  <td>
                    <div class="comentario-text">
                      <?= !empty($inc['comentarios']) ? nl2br(htmlspecialchars($inc['comentarios'])) : '<span style="color:#94a3b8; font-style:italic;">Sin comentarios adicionales</span>' ?>
                    </div>
                  </td>
                  <td>
                    <div class="chofer-tag">
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                      </svg>
                      <span><?= htmlspecialchars($inc['chofer'] ?: 'No registrado') ?></span>
                    </div>
                  </td>
                  <td style="text-align: center; white-space: nowrap;">
                    <?php if ($tieneArchivo): ?>
                      <?php if ($esImagen): ?>
                        <button type="button" class="btn-evidencia" onclick="abrirLightbox('<?= $rutaArchivo ?>', '<?= addslashes($nombreArchivo) ?>', 'Pedido #<?= $inc['pedido_id'] ?> - <?= addslashes($inc['motivo']) ?>')">
                          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                            <circle cx="8.5" cy="8.5" r="1.5"/>
                            <polyline points="21 15 16 10 5 21"/>
                          </svg>
                          Ver Foto
                        </button>
                      <?php else: ?>
                        <a href="<?= $rutaArchivo ?>" target="_blank" download class="btn-evidencia">
                          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <line x1="16" y1="13" x2="8" y2="13"/>
                            <line x1="16" y1="17" x2="8" y2="17"/>
                            <polyline points="10 9 9 9 8 9"/>
                          </svg>
                          Adjunto
                        </a>
                      <?php endif; ?>
                    <?php else: ?>
                      <span style="color: #cbd5e1; font-size: 13px;">—</span>
                    <?php endif; ?>
                  </td>
                  <td style="text-align: center;">
                    <a href="Inicio.php?id=<?= $inc['pedido_id'] ?>" target="_blank" class="btn-ver-pedido" title="Abrir detalle del pedido">
                      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3">
                        <circle cx="11" cy="11" r="8"/>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                      </svg>
                      Ver
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>

  <!-- Modal Lightbox para evidencias -->
  <div id="modalLightbox" class="lightbox-modal" onclick="cerrarLightbox(event)">
    <div class="lightbox-content" onclick="event.stopPropagation()">
      <div class="lightbox-header">
        <div>
          <h3 id="lightboxTitulo">Evidencia de Incidencia</h3>
          <div id="lightboxSubtitulo" style="font-size: 12px; color: #64748b; margin-top: 2px;"></div>
        </div>
        <button type="button" class="lightbox-close" onclick="cerrarLightbox(event)">&times;</button>
      </div>
      <div class="lightbox-body">
        <img id="lightboxImg" src="" alt="Evidencia de Incidencia">
      </div>
      <div class="lightbox-footer">
        <a id="lightboxDownloadBtn" href="" target="_blank" download class="btn-submit" style="text-decoration:none;">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
            <polyline points="7 10 12 15 17 10"/>
            <line x1="12" y1="15" x2="12" y2="3"/>
          </svg>
          Descargar Archivo Original
        </a>
      </div>
    </div>
  </div>

  <script>
    // Búsqueda en vivo en la tabla de incidencias
    const inputBusquedaEnVivo = document.getElementById('tablaBusquedaEnVivo');

    function aplicarFiltroEnVivo(term) {
      const termLower = (term || '').toLowerCase().trim();
      const filas = document.querySelectorAll('.fila-incidencia');
      filas.forEach(fila => {
        const texto = fila.textContent.toLowerCase();
        fila.style.display = texto.includes(termLower) ? '' : 'none';
      });
    }

    if (inputBusquedaEnVivo) {
      inputBusquedaEnVivo.addEventListener('input', function() {
        aplicarFiltroEnVivo(this.value);
      });
    }

    // Filtrar desde botones de los paneles (Top Clientes, Top Usuarios, Motivos, Sucursales)
    function filtrarTablaPorTexto(texto) {
      if (inputBusquedaEnVivo) {
        inputBusquedaEnVivo.value = texto;
        aplicarFiltroEnVivo(texto);
        // Scroll suave a la tabla
        const tablaContainer = document.querySelector('.table-container');
        if (tablaContainer) {
          tablaContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      }
    }

    // Control de Lightbox
    function abrirLightbox(ruta, nombre, contexto) {
      const modal = document.getElementById('modalLightbox');
      const img = document.getElementById('lightboxImg');
      const titulo = document.getElementById('lightboxTitulo');
      const subtitulo = document.getElementById('lightboxSubtitulo');
      const downloadBtn = document.getElementById('lightboxDownloadBtn');

      img.src = ruta;
      titulo.textContent = nombre || 'Evidencia de Incidencia';
      subtitulo.textContent = contexto || '';
      downloadBtn.href = ruta;

      modal.classList.add('active');
    }

    function cerrarLightbox(e) {
      const modal = document.getElementById('modalLightbox');
      modal.classList.remove('active');
      document.getElementById('lightboxImg').src = '';
    }

    // Cerrar con Escape
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        cerrarLightbox();
      }
    });
  </script>
</body>
</html>

