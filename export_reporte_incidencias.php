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

// Solo roles autorizados (Admin, JC)
if (!in_array($rolSesion, ["Admin", "JC"])) {
    header("location: /Pedidos_GA/Pedidos_GA.php");
    exit;
}

// --- Filtros ---
$fechaDesde     = $_GET['fecha_desde'] ?? date('Y-m-d', strtotime('-30 days'));
$fechaHasta     = $_GET['fecha_hasta'] ?? date('Y-m-d');
$sucursalFiltro = $_GET['sucursal']    ?? '';
$motivoFiltro   = $_GET['motivo']      ?? '';
$evidenciaFiltro= $_GET['evidencia']   ?? '';
$buscarFiltro   = trim($_GET['buscar'] ?? '');

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

// Búsqueda por texto
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

$totalIncidencias = count($incidencias);

// Estadísticas de Clientes, Usuarios y Motivos
$clientesCount = [];
$reportantesCount = [];
$motivosCount = [];

foreach ($incidencias as $inc) {
    $cli = trim($inc['NOMBRE_CLIENTE'] ?: 'Sin cliente');
    $clientesCount[$cli] = ($clientesCount[$cli] ?? 0) + 1;

    $rep = trim($inc['chofer'] ?: 'No especificado');
    $reportantesCount[$rep] = ($reportantesCount[$rep] ?? 0) + 1;

    $m = $inc['motivo'] ?: 'Sin motivo';
    $motivosCount[$m] = ($motivosCount[$m] ?? 0) + 1;
}

arsort($clientesCount);
arsort($reportantesCount);
arsort($motivosCount);

// Nombre de archivo para la descarga
$fileName = "Reporte_Incidencias_" . date('Ymd_His') . ".xls";

header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=\"$fileName\"");
header("Pragma: no-cache");
header("Expires: 0");

echo "\xEF\xBB\xBF"; // UTF-8 BOM para soporte correcto de acentos en Excel
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    body { font-family: Calibri, Arial, sans-serif; font-size: 11pt; }
    table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
    th { background-color: #004d6f; color: #ffffff; border: 1px solid #cbd5e1; padding: 8px 10px; font-weight: bold; text-align: left; }
    td { border: 1px solid #cbd5e1; padding: 6px 10px; vertical-align: top; }
    .header-reporte { font-size: 16pt; font-weight: bold; color: #004d6f; margin-bottom: 5px; }
    .sub-reporte { font-size: 10pt; color: #64748b; margin-bottom: 15px; }
    .num { text-align: right; }
</style>
</head>
<body>

<div class="header-reporte">REPORTE GENERAL DE INCIDENCIAS - PEDIDOS GA</div>
<div class="sub-reporte">
    <strong>Período:</strong> <?= htmlspecialchars($fechaDesde) ?> al <?= htmlspecialchars($fechaHasta) ?> | 
    <strong>Sucursal:</strong> <?= htmlspecialchars($sucursalFiltro ?: ($sucursalSesion !== 'TODAS' ? $sucursalSesion : 'Todas')) ?> | 
    <strong>Generado el:</strong> <?= date('d/m/Y H:i:s') ?> | 
    <strong>Total de Registros:</strong> <?= $totalIncidencias ?>
</div>

<!-- SECCIÓN 1: CLIENTES CON MÁS INCIDENCIAS -->
<h3 style="color: #004d6f; margin-top: 20px; margin-bottom: 8px;">1. CLIENTES CON MÁS INCIDENCIAS (TOP 10)</h3>
<table style="width: 70%;">
    <thead>
        <tr>
            <th style="width: 50px;">#</th>
            <th>Cliente</th>
            <th style="width: 120px; text-align: right;">Total Incidencias</th>
            <th style="width: 100px; text-align: right;">% del Total</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($clientesCount)): ?>
            <tr><td colspan="4" style="text-align: center; color: #64748b;">Sin datos</td></tr>
        <?php else: ?>
            <?php 
            $iCli = 1;
            foreach (array_slice($clientesCount, 0, 10, true) as $cliNom => $cant): 
                $p = $totalIncidencias > 0 ? round(($cant / $totalIncidencias) * 100, 1) : 0;
            ?>
            <tr>
                <td style="text-align: center;"><strong>#<?= $iCli ?></strong></td>
                <td><strong><?= htmlspecialchars($cliNom) ?></strong></td>
                <td style="text-align: right; font-weight: bold;"><?= number_format($cant) ?></td>
                <td style="text-align: right;"><?= $p ?>%</td>
            </tr>
            <?php $iCli++; endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<!-- SECCIÓN 2: USUARIOS QUE GENERAN MÁS INCIDENCIAS -->
<h3 style="color: #004d6f; margin-top: 25px; margin-bottom: 8px;">2. USUARIOS / CHOFERES QUE REPORTAN O GENERAN MÁS INCIDENCIAS</h3>
<table style="width: 70%;">
    <thead>
        <tr>
            <th style="width: 50px;">#</th>
            <th>Usuario / Chofer</th>
            <th style="width: 120px; text-align: right;">Total Incidencias</th>
            <th style="width: 100px; text-align: right;">% del Total</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($reportantesCount)): ?>
            <tr><td colspan="4" style="text-align: center; color: #64748b;">Sin datos</td></tr>
        <?php else: ?>
            <?php 
            $iUsr = 1;
            foreach (array_slice($reportantesCount, 0, 10, true) as $usrNom => $cant): 
                $p = $totalIncidencias > 0 ? round(($cant / $totalIncidencias) * 100, 1) : 0;
            ?>
            <tr>
                <td style="text-align: center;"><strong>#<?= $iUsr ?></strong></td>
                <td><strong><?= htmlspecialchars($usrNom) ?></strong></td>
                <td style="text-align: right; font-weight: bold;"><?= number_format($cant) ?></td>
                <td style="text-align: right;"><?= $p ?>%</td>
            </tr>
            <?php $iUsr++; endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<!-- SECCIÓN 3: DETALLE COMPLETO -->
<h3 style="color: #004d6f; margin-top: 25px; margin-bottom: 8px;">3. DETALLE DE INCIDENCIAS (<?= $totalIncidencias ?> REGISTROS)</h3>
<table>
    <thead>
        <tr>
            <th style="width: 60px;">ID</th>
            <th style="width: 130px;">Fecha y Hora</th>
            <th style="width: 90px;">Folio Pedido</th>
            <th style="width: 100px;">Factura</th>
            <th style="width: 110px;">Sucursal</th>
            <th style="width: 180px;">Cliente</th>
            <th style="width: 200px;">Dirección</th>
            <th style="width: 150px;">Motivo Incidencia</th>
            <th style="width: 250px;">Comentarios</th>
            <th style="width: 140px;">Reportado Por</th>
            <th style="width: 100px;">Tiene Evidencia</th>
            <th style="width: 200px;">Archivo Adjunto</th>
            <th style="width: 110px;">Estado Pedido</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($incidencias)): ?>
            <tr>
                <td colspan="13" style="text-align: center; color: #64748b; padding: 20px;">No se encontraron registros de incidencias para este período.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($incidencias as $inc): 
                $tieneEvidencia = !empty($inc['archivo_ruta']) ? 'SÍ' : 'NO';
                $nombreArchivo = $inc['archivo_nombre'] ?: (!empty($inc['archivo_ruta']) ? basename($inc['archivo_ruta']) : '-');
            ?>
            <tr>
                <td class="num"><?= $inc['id'] ?></td>
                <td><?= $inc['fecha_formato'] ?></td>
                <td class="num"><?= $inc['pedido_id'] ?></td>
                <td><?= htmlspecialchars($inc['FACTURA'] ?? '') ?></td>
                <td><?= htmlspecialchars($inc['SUCURSAL'] ?? '') ?></td>
                <td><?= htmlspecialchars($inc['NOMBRE_CLIENTE'] ?? '') ?></td>
                <td><?= htmlspecialchars($inc['DIRECCION'] ?? '') ?></td>
                <td><strong><?= htmlspecialchars($inc['motivo']) ?></strong></td>
                <td><?= htmlspecialchars($inc['comentarios'] ?? '') ?></td>
                <td><?= htmlspecialchars($inc['chofer'] ?? '') ?></td>
                <td style="text-align: center;"><?= $tieneEvidencia ?></td>
                <td><?= htmlspecialchars($nombreArchivo) ?></td>
                <td><?= htmlspecialchars($inc['ESTADO'] ?? '') ?></td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

</body>
</html>
<?php
$conn->close();
?>

