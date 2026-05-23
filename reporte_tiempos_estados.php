<?php
session_name("GA");
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: /Pedidos_GA/Sesion/login.html");
    exit;
}
require_once __DIR__ . "/Conexiones/Conexion.php";

$sucursalSesion = strtoupper($_SESSION["Sucursal"] ?? "");
$rolSesion      = $_SESSION["Rol"] ?? "";

// --- Filtros ---
$fechaDesde     = $_GET['fecha_desde'] ?? date('Y-m-d', strtotime('-30 days'));
$fechaHasta     = $_GET['fecha_hasta'] ?? date('Y-m-d');
$sucursalFiltro = $_GET['sucursal']    ?? '';

// Restringir sucursal según rol
$sucursalWhere = "";
$params = [$fechaDesde, $fechaHasta];
$types  = "ss";
if ($sucursalSesion !== "TODAS") {
    $sucursalWhere = " AND p.SUCURSAL = ? ";
    $params[] = $sucursalSesion;
    $types   .= "s";
} elseif ($sucursalFiltro !== '') {
    $sucursalWhere = " AND p.SUCURSAL = ? ";
    $params[] = $sucursalFiltro;
    $types   .= "s";
}

// --- Query historial_cambios (estados factura) ---
$sqlHC = "SELECT hc.Pedido_ID, hc.Cambio, hc.Fecha_Hora, hc.Usuario_ID,
                 p.SUCURSAL, p.FACTURA, p.NOMBRE_CLIENTE, p.CHOFER_ASIGNADO
          FROM historial_cambios hc
          JOIN pedidos p ON p.ID = hc.Pedido_ID
          WHERE hc.Cambio LIKE 'Factura:%→%'
            AND hc.Cambio NOT LIKE '%Devuelta a Caja%'
            AND DATE(hc.Fecha_Hora) BETWEEN ? AND ?
            $sucursalWhere
          ORDER BY hc.Pedido_ID, hc.Fecha_Hora ASC";
$stHC = $conn->prepare($sqlHC);
$stHC->bind_param($types, ...$params);
$stHC->execute();
$rowsHC = $stHC->get_result()->fetch_all(MYSQLI_ASSOC);

// --- Query EstadoPedido (EN RUTA / ENTREGADO) ---
$sqlEP = "SELECT ep.ID_Pedido AS Pedido_ID,
                 ep.Estado AS Cambio,
                 CONCAT(ep.Fecha,' ',ep.Hora) AS Fecha_Hora,
                 '' AS Usuario_ID,
                 p.SUCURSAL, p.FACTURA, p.NOMBRE_CLIENTE, p.CHOFER_ASIGNADO
          FROM EstadoPedido ep
          JOIN pedidos p ON p.ID = ep.ID_Pedido
          WHERE ep.Estado IN ('EN RUTA','ENTREGADO')
            AND ep.Fecha BETWEEN ? AND ?
            $sucursalWhere
          ORDER BY ep.ID_Pedido, ep.Fecha ASC, ep.Hora ASC";
$stEP = $conn->prepare($sqlEP);
$stEP->bind_param($types, ...$params);
$stEP->execute();
$rowsEP = $stEP->get_result()->fetch_all(MYSQLI_ASSOC);

// --- Obtener lista de choferes dados de alta ---
$driverUsernames = [];
$resDrivers = $conn->query("SELECT DISTINCT username FROM choferes");
if ($resDrivers) {
    while ($rDrv = $resDrivers->fetch_assoc()) {
        $username = trim($rDrv['username']);
        if ($username !== '') {
            $driverUsernames[strtolower($username)] = true;
        }
    }
}

// --- Obtener lista de usuarios de sistema (oficina) dados de alta ---
$systemUsernames = [];
$resUsers = $conn->query("SELECT DISTINCT username FROM usuarios");
if ($resUsers) {
    while ($rUsr = $resUsers->fetch_assoc()) {
        $username = trim($rUsr['username']);
        if ($username !== '') {
            $systemUsernames[strtolower($username)] = true;
        }
    }
}

// --- Clasificar paso ---
function parseStep($cambio) {
    $cambio = strtolower($cambio);
    if (str_contains($cambio, 'en ruta'))             return 5;
    if (str_contains($cambio, 'entregado'))           return 6;
    $dest = trim(substr($cambio, strpos($cambio, '→') + 2));
    if (str_contains($dest, 'con jefe') || str_contains($dest, 'entregada a jefe')) return 1;
    if (str_contains($dest, 'pendiente de surtido'))  return 2;
    if (str_contains($dest, 'cargado en camioneta'))  return 4;
    if (str_contains($dest, 'surtido'))               return 3;
    return -1;
}

function fmtMin($m) {
    if ($m === null || $m < 0) return '—';
    $m = round($m);
    if ($m < 60) return $m . 'm';
    $h = intdiv($m, 60); $rm = $m % 60;
    if ($h >= 24) return intdiv($h,24) . 'd ' . ($h%24) . 'h';
    return $h . 'h ' . $rm . 'm';
}

function durClass($min, $warn=120, $err=480) {
    if ($min === null) return '';
    if ($min > $err)  return 'slow';
    if ($min > $warn) return 'mid';
    return 'ok';
}

// --- Construir estructura por pedido ---
$pedidos = [];
foreach (array_merge($rowsHC, $rowsEP) as $r) {
    $step = parseStep($r['Cambio']);
    if ($step < 0) continue;
    $pid = $r['Pedido_ID'];
    if (!isset($pedidos[$pid])) {
        $pedidos[$pid] = [
            'sucursal' => $r['SUCURSAL'], 'factura' => $r['FACTURA'],
            'cliente' => $r['NOMBRE_CLIENTE'], 'chofer' => $r['CHOFER_ASIGNADO'],
            'events' => []
        ];
    }
    
    // Extraer rol del texto del cambio si está presente
    $loggedRole = null;
    if (str_contains($r['Cambio'], '[Chofer]')) {
        $loggedRole = 'Chofer';
    } elseif (str_contains($r['Cambio'], '[Oficina]')) {
        $loggedRole = 'Oficina';
    }
    
    $pedidos[$pid]['events'][] = [
        'ts' => strtotime($r['Fecha_Hora']), 
        'step' => $step, 
        'user' => $r['Usuario_ID'],
        'role' => $loggedRole
    ];
}

$transKeys = ['0→1','1→2','2→3','3→4','4→5','5→6','total'];
$transLabels = [
    '0→1'=>'En Caja → Jefe','1→2'=>'Jefe → Pend. Surtido',
    '2→3'=>'Pend. Surtido → Surtido','3→4'=>'Surtido → Camioneta',
    '4→5'=>'Camioneta → En Ruta','5→6'=>'En Ruta → Entregado','total'=>'Tiempo Total'
];
$transColors = [
    '0→1'=>'#2563eb','1→2'=>'#d97706','2→3'=>'#ea580c',
    '3→4'=>'#0891b2','4→5'=>'#7c3aed','5→6'=>'#16a34a','total'=>'#374151'
];

$sumDur = array_fill_keys($transKeys, 0);
$cntDur = array_fill_keys($transKeys, 0);
$userStats = []; // [username => ['pedidos'=>[], 'trans'=>[key=>[min,...]]]]

foreach ($pedidos as &$ped) {
    usort($ped['events'], function($a,$b) {
        if ($a['ts'] === $b['ts']) {
            return empty($b['user']) <=> empty($a['user']);
        }
        return $a['ts'] - $b['ts'];
    });
    // Dedup: keep first event per step
    $seen = []; $ped['events'] = array_values(array_filter($ped['events'], function($e) use (&$seen) {
        if (in_array($e['step'], $seen)) return false; $seen[] = $e['step']; return true;
    }));
    $ped['durations'] = [];
    $ped['trans_users'] = []; // which user triggered each transition
    $evs = $ped['events'];
    for ($i = 0; $i < count($evs)-1; $i++) {
        $from = $evs[$i]['step']; $to = $evs[$i+1]['step'];
        $key = $from.'→'.$to;
        $min = ($evs[$i+1]['ts'] - $evs[$i]['ts']) / 60;
        $ped['durations'][$key] = $min;
        // User responsible = who triggered the destination step
        // For EN RUTA / ENTREGADO (no user in historial), use chofer
        $user = $evs[$i+1]['user'] ?: $ped['chofer'];
        $ped['trans_users'][$key] = $user;
        if (isset($sumDur[$key])) { $sumDur[$key] += $min; $cntDur[$key]++; }
        // Accumulate per-user stats
        if ($user) {
            $evRole = $evs[$i+1]['role'] ?? null;
            $usrLower = strtolower($user);
            if ($evRole === 'Chofer') {
                $roleKey = 'Chofer';
            } elseif ($evRole === 'Oficina') {
                $roleKey = 'Oficina';
            } else {
                if (isset($driverUsernames[$usrLower])) {
                    $roleKey = 'Chofer';
                } elseif (isset($systemUsernames[$usrLower])) {
                    $roleKey = 'Oficina';
                } else {
                    $isDriver = (str_contains($usrLower, 'chof') || !array_key_exists('0→1', $ped['durations']));
                    $roleKey = $isDriver ? 'Chofer' : 'Oficina';
                }
            }
            $statKey = $user . '|' . $roleKey;

            if (!isset($userStats[$statKey])) {
                $userStats[$statKey] = ['username' => $user, 'role' => $roleKey, 'pedidos' => [], 'trans' => []];
            }
            $userStats[$statKey]['pedidos'][$ped['factura'] ?: $i] = true;
            if (!isset($userStats[$statKey]['trans'][$key])) $userStats[$statKey]['trans'][$key] = [];
            $userStats[$statKey]['trans'][$key][] = $min;
        }
    }
    if (count($evs) >= 2) {
        $total = ($evs[count($evs)-1]['ts'] - $evs[0]['ts']) / 60;
        $ped['durations']['total'] = $total;
        $sumDur['total'] += $total; $cntDur['total']++;
    }
}
unset($ped);

$avgDur = [];
foreach ($transKeys as $k) {
    $avgDur[$k] = $cntDur[$k] > 0 ? $sumDur[$k] / $cntDur[$k] : null;
}

// Sucursales para filtro
$allSucs = $conn->query("SELECT DISTINCT SUCURSAL FROM pedidos ORDER BY SUCURSAL")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Reporte de Tiempos por Estado</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',system-ui,sans-serif;background:#f1f5f9;color:#1e293b}
.topbar{background:linear-gradient(135deg,#004d6f,#006996);color:#fff;padding:18px 28px;display:flex;align-items:center;justify-content:space-between}
.topbar h1{font-size:20px;font-weight:700}
.topbar a{color:#7dd3f8;font-size:13px;text-decoration:none}
.container{max-width:1400px;margin:0 auto;padding:24px 20px}
.filter-bar{background:#fff;border-radius:12px;padding:16px 20px;display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;margin-bottom:24px;box-shadow:0 1px 4px rgba(0,0,0,.08)}
.filter-bar label{font-size:12px;font-weight:600;color:#64748b;display:block;margin-bottom:4px}
.filter-bar input,.filter-bar select{padding:7px 10px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;background:#f8fafc}
.btn-filter{background:#006996;color:#fff;border:0;padding:8px 18px;border-radius:8px;font-weight:600;font-size:13px;cursor:pointer}
.btn-filter:hover{background:#005580}
.btn-export{background:#16a34a;color:#fff;text-decoration:none;padding:8px 18px;border-radius:8px;font-weight:600;font-size:13px;display:inline-flex;align-items:center;gap:6px;cursor:pointer;transition:background 0.2s}
.btn-export:hover{background:#15803d}
/* Cards */
.cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:14px;margin-bottom:28px}
.card{background:#fff;border-radius:12px;padding:16px;box-shadow:0 1px 4px rgba(0,0,0,.08);border-top:4px solid var(--c)}
.card .label{font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.4px;margin-bottom:6px}
.card .val{font-size:26px;font-weight:800;color:var(--c)}
.card .sub{font-size:11px;color:#94a3b8;margin-top:4px}
/* Table */
.tbl-wrap{background:#fff;border-radius:12px;padding:20px;box-shadow:0 1px 4px rgba(0,0,0,.08);overflow-x:auto}
.tbl-wrap h2{font-size:16px;font-weight:700;margin-bottom:14px;color:#1e293b}
table{width:100%;border-collapse:collapse;font-size:12px}
th{background:#f8fafc;padding:9px 10px;text-align:left;font-weight:700;color:#475569;border-bottom:2px solid #e2e8f0;white-space:nowrap}
td{padding:8px 10px;border-bottom:1px solid #f1f5f9;white-space:nowrap}
tr:hover td{background:#f8fafc}
.dur{text-align:center;font-weight:600}
.dur.slow{color:#dc2626}
.dur.ok{color:#16a34a}
.dur.mid{color:#d97706}
.badge-suc{display:inline-block;padding:2px 7px;border-radius:20px;font-size:10px;font-weight:700;background:#e0f2fe;color:#0369a1}
.no-data{text-align:center;padding:40px;color:#94a3b8;font-size:14px}
.chart-wrap{background:#fff;border-radius:12px;padding:20px;box-shadow:0 1px 4px rgba(0,0,0,.08);margin-bottom:24px}
.chart-wrap h2{font-size:15px;font-weight:700;margin-bottom:16px}
.bar-row{display:flex;align-items:center;margin-bottom:10px;gap:10px}
.bar-row .bar-label{width:180px;font-size:12px;color:#475569;text-align:right;flex-shrink:0}
.bar-row .bar-track{flex:1;background:#f1f5f9;border-radius:6px;height:22px;position:relative}
.bar-row .bar-fill{height:100%;border-radius:6px;display:flex;align-items:center;justify-content:flex-end;padding-right:8px;font-size:11px;font-weight:700;color:#fff;transition:width .5s}
/* Tabs */
.tabs-nav{display:flex;gap:4px;margin-bottom:20px;border-bottom:2px solid #e2e8f0}
.tab-btn{padding:10px 20px;border:0;background:transparent;font-weight:600;font-size:13px;color:#64748b;cursor:pointer;border-bottom:3px solid transparent;margin-bottom:-2px;border-radius:6px 6px 0 0;transition:all .2s}
.tab-btn.active{color:#006996;border-bottom-color:#006996;background:#f0f9ff}
.tab-btn:hover:not(.active){background:#f8fafc;color:#334155}
.tab-pane { display: none; }
.tab-pane.active { display: block; }
/* User filter buttons */
.user-filter-bar {
    margin-bottom: 20px;
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}
.filter-btn {
    padding: 8px 16px;
    border: 1px solid #e2e8f0;
    background: #fff;
    border-radius: 9999px;
    font-size: 13px;
    font-weight: 600;
    color: #64748b;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
}
.filter-btn:hover {
    background: #f8fafc;
    color: #334155;
    border-color: #cbd5e1;
}
.filter-btn.active {
    background: #0284c7;
    color: #fff;
    border-color: #0284c7;
    box-shadow: 0 2px 4px rgba(2, 132, 199, 0.2);
}

/* User cards modern styling */
.user-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(480px, 1fr));
    gap: 20px;
    margin-top: 10px;
}
.user-card {
    background: #fff;
    border-radius: 14px;
    padding: 16px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
    border: 1px solid #e2e8f0;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    flex-direction: column;
}
.user-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -2px rgba(0, 0, 0, 0.04);
    border-color: #cbd5e1;
}
.user-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #f1f5f9;
    padding-bottom: 14px;
    margin-bottom: 16px;
}
.user-card-title {
    display: flex;
    align-items: center;
    gap: 10px;
}
.user-card-avatar {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: #f0f9ff;
    color: #0369a1;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    font-weight: 700;
    border: 1px solid #e0f2fe;
}
.user-card-avatar.driver {
    background: #f0fdf4;
    color: #166534;
    border-color: #dcfce7;
}
.user-card-name {
    font-size: 16px;
    font-weight: 700;
    color: #0f172a;
}
.user-card-count {
    font-size: 11px;
    font-weight: 600;
    color: #0284c7;
    background: #e0f2fe;
    padding: 4px 10px;
    border-radius: 9999px;
    border: 1px solid #bae6fd;
}
.user-card-count.driver {
    color: #166534;
    background: #dcfce7;
    border-color: #bbf7d0;
}
.user-table-wrap {
    overflow-x: auto;
    width: 100%;
}
.user-table {
    width: 100%;
    border-collapse: collapse;
}
.user-table th {
    font-size: 10px;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 6px 4px;
    text-align: left;
    border-bottom: 1px solid #e2e8f0;
    white-space: nowrap;
}
.user-table td {
    padding: 6px 4px;
    font-size: 12px;
    color: #334155;
    border-bottom: 1px solid #f8fafc;
    white-space: nowrap;
}
.user-table tr:last-child td {
    border-bottom: none;
}
.trans-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 500;
}
.trans-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
}
.badge-time {
    display: inline-block;
    padding: 3px 6px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
    text-align: center;
    min-width: 40px;
}
.badge-time.avg {
    background: #f1f5f9;
    color: #475569;
}
.badge-time.best {
    background: #ecfdf5;
    color: #065f46;
}
.badge-time.worst {
    background: #fff5f5;
    color: #991b1b;
}
.badge-collision {
    background: #fffbeb;
    color: #b45309;
    border: 1px solid #fde68a;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 10px;
    font-weight: 700;
    margin-left: 6px;
    display: inline-block;
    vertical-align: middle;
}
</style>
</head>
<body>
<div class="topbar">
  <h1>📊 Reporte de Tiempos por Estado</h1>
  <a href="Pedidos_GA.php">← Volver a Pedidos</a>
</div>
<div class="container">

<!-- Filtros -->
<form method="GET" class="filter-bar">
  <div>
    <label>Desde</label>
    <input type="date" name="fecha_desde" value="<?= htmlspecialchars($fechaDesde) ?>">
  </div>
  <div>
    <label>Hasta</label>
    <input type="date" name="fecha_hasta" value="<?= htmlspecialchars($fechaHasta) ?>">
  </div>
  <?php if ($sucursalSesion === 'TODAS'): ?>
  <div>
    <label>Sucursal</label>
    <select name="sucursal">
      <option value="">Todas</option>
      <?php foreach ($allSucs as $s): ?>
        <option value="<?= $s['SUCURSAL'] ?>" <?= $sucursalFiltro === $s['SUCURSAL'] ? 'selected' : '' ?>><?= $s['SUCURSAL'] ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <?php endif; ?>
  <div><button type="submit" class="btn-filter">Aplicar filtros</button></div>
  <div style="margin-left: auto;">
    <a href="export_reporte_tiempos.php?fecha_desde=<?= urlencode($fechaDesde) ?>&fecha_hasta=<?= urlencode($fechaHasta) ?>&sucursal=<?= urlencode($sucursalFiltro) ?>" class="btn-export">
      📊 Exportar a Excel
    </a>
  </div>
</form>

<!-- Tabs nav -->
<div class="tabs-nav">
  <button class="tab-btn active" onclick="showTab('tab-pedidos',this)">📋 Por Pedido</button>
  <button class="tab-btn" onclick="showTab('tab-usuarios',this)">👤 Por Usuario</button>
</div>

<div id="tab-pedidos" class="tab-pane active">

<!-- Tarjetas de promedio -->
<div class="cards">
<?php foreach ($transKeys as $k):
    $avg = $avgDur[$k]; $cnt = $cntDur[$k]; $color = $transColors[$k]; ?>
<div class="card" style="--c:<?= $color ?>">
  <div class="label"><?= $transLabels[$k] ?></div>
  <div class="val"><?= fmtMin($avg) ?></div>
  <div class="sub">Promedio · <?= $cnt ?> pedidos</div>
</div>
<?php endforeach; ?>
</div>

<!-- Gráfico de barras -->
<?php
$maxAvg = max(array_filter($avgDur, fn($v) => $v !== null) ?: [1]);
?>
<div class="chart-wrap">
  <h2>Tiempo promedio por transición</h2>
  <?php foreach (array_slice($transKeys,0,6) as $k):
    $avg = $avgDur[$k]; $color = $transColors[$k];
    $pct = $avg !== null ? min(100, $avg / $maxAvg * 100) : 0;
  ?>
  <div class="bar-row">
    <div class="bar-label"><?= $transLabels[$k] ?></div>
    <div class="bar-track">
      <div class="bar-fill" style="width:<?= round($pct) ?>%;background:<?= $color ?>">
        <?= $avg !== null ? fmtMin($avg) : '' ?>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Tabla detallada -->
<div class="tbl-wrap">
  <h2>Detalle por Pedido (<?= count($pedidos) ?> pedidos)</h2>
  <?php if (empty($pedidos)): ?>
    <div class="no-data">No hay datos para el rango seleccionado.</div>
  <?php else: ?>
  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Sucursal</th>
        <th>Factura</th>
        <th>Cliente</th>
        <th>Chofer</th>
        <th title="En Caja → Jefe">Caja→Jefe</th>
        <th title="Jefe → Pendiente Surtido">Jefe→Pend</th>
        <th title="Pendiente Surtido → Surtido">Pend→Surtido</th>
        <th title="Surtido → Camioneta">Surtido→Cam</th>
        <th title="Camioneta → En Ruta">Cam→Ruta</th>
        <th title="En Ruta → Entregado">Ruta→Entregado</th>
        <th>Total</th>
      </tr>
    </thead>
    <tbody>
    <?php
    // Sort by total time desc
    uasort($pedidos, function($a,$b){
        return ($b['durations']['total'] ?? 0) <=> ($a['durations']['total'] ?? 0);
    });
    foreach ($pedidos as $pid => $ped):
        $dur = $ped['durations'];
    ?>
    <tr>
      <td><strong><?= $pid ?></strong></td>
      <td><span class="badge-suc"><?= htmlspecialchars($ped['sucursal']) ?></span></td>
      <td><?= htmlspecialchars($ped['factura']) ?></td>
      <td><?= htmlspecialchars(substr($ped['cliente'],0,22)) ?></td>
      <td><?= htmlspecialchars($ped['chofer']) ?></td>
      <td class="dur <?= durClass($dur['0→1']??null) ?>"><?= fmtMin($dur['0→1']??null) ?></td>
      <td class="dur <?= durClass($dur['1→2']??null) ?>"><?= fmtMin($dur['1→2']??null) ?></td>
      <td class="dur <?= durClass($dur['2→3']??null) ?>"><?= fmtMin($dur['2→3']??null) ?></td>
      <td class="dur <?= durClass($dur['3→4']??null) ?>"><?= fmtMin($dur['3→4']??null) ?></td>
      <td class="dur <?= durClass($dur['4→5']??null) ?>"><?= fmtMin($dur['4→5']??null) ?></td>
      <td class="dur <?= durClass($dur['5→6']??null,60,240) ?>"><?= fmtMin($dur['5→6']??null) ?></td>
      <td class="dur <?= durClass($dur['total']??null,480,1440) ?>"><strong><?= fmtMin($dur['total']??null) ?></strong></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

</div><!-- /tab-pedidos -->

<!-- TAB POR USUARIO -->
<div id="tab-usuarios" class="tab-pane">
<?php
// Build averaged user stats
$userAvg = [];
foreach ($userStats as $usr => $data) {
    $userAvg[$usr] = [
        'count' => count($data['pedidos']),
        'trans' => [],
    ];
    foreach ($data['trans'] as $key => $mins) {
        $avg = array_sum($mins) / count($mins);
        $userAvg[$usr]['trans'][$key] = [
            'avg' => $avg,
            'min' => min($mins),
            'max' => max($mins),
            'cnt' => count($mins),
        ];
    }
}
// Sort by total pedidos desc
uasort($userAvg, fn($a,$b) => $b['count'] <=> $a['count']);
?>
<?php if (empty($userAvg)): ?>
  <div class="no-data" style="background:#fff;border-radius:12px;padding:40px">No hay datos suficientes para el período seleccionado.</div>
<?php else: ?>
<div class="user-filter-bar">
  <button class="filter-btn active" onclick="filterUsers('all', this)">👥 Todos</button>
  <button class="filter-btn" onclick="filterUsers('drivers', this)">🚚 Choferes</button>
  <button class="filter-btn" onclick="filterUsers('admins', this)">👤 Oficina / Internos</button>
</div>

<div class="user-grid">
<?php foreach ($userAvg as $usr => $data):
    $parts = explode('|', $usr);
    $realName = $parts[0];
    $determinedRole = $parts[1] ?? 'Oficina';

    $usrLower = strtolower($realName);
    $inDriver = isset($driverUsernames[$usrLower]);
    $inSystem = isset($systemUsernames[$usrLower]);
    $isCollision = ($inDriver && $inSystem);

    $isDriver = ($determinedRole === 'Chofer');
?>
<div class="user-card" data-driver="<?= $isDriver ? '1' : '0' ?>">
  <div class="user-card-header">
    <div class="user-card-title">
      <div class="user-card-avatar <?= $isDriver ? 'driver' : '' ?>">
        <?= $isDriver ? '🚚' : '👤' ?>
      </div>
      <div class="user-card-name">
        <?= htmlspecialchars($realName) ?>
        <?php if ($isCollision): ?>
          <span class="badge-collision" title="Nombre registrado como Usuario y como Chofer en la base de datos">⚠️ Duplicado</span>
        <?php endif; ?>
      </div>
    </div>
    <div class="user-card-count <?= $isDriver ? 'driver' : '' ?>">
      <?= $data['count'] ?> pedidos
    </div>
  </div>
  <div class="user-table-wrap">
    <table class="user-table">
      <thead>
        <tr>
          <th>Transición</th>
          <th style="text-align:center">Promedio</th>
          <th style="text-align:center">Rápido</th>
          <th style="text-align:center">Lento</th>
          <th style="text-align:right">Cant</th>
        </tr>
      </thead>
      <tbody>
      <?php
      $sliceKeys = array_slice($transKeys, 0, 6);
      foreach ($sliceKeys as $k):
          if (!isset($data['trans'][$k])) continue;
          $t = $data['trans'][$k];
          $color = $transColors[$k];
      ?>
      <tr>
        <td>
          <div class="trans-label">
            <span class="trans-dot" style="background:<?= $color ?>"></span>
            <?= $transLabels[$k] ?>
          </div>
        </td>
        <td style="text-align:center"><span class="badge-time avg"><?= fmtMin($t['avg']) ?></span></td>
        <td style="text-align:center"><span class="badge-time best"><?= fmtMin($t['min']) ?></span></td>
        <td style="text-align:center"><span class="badge-time worst"><?= fmtMin($t['max']) ?></span></td>
        <td style="text-align:right; font-weight:600; color:#64748b"><?= $t['cnt'] ?></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div><!-- /tab-usuarios -->

</div><!-- /container -->

<script>
function showTab(id, btn) {
    document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById(id).classList.add('active');
    btn.classList.add('active');
}

function filterUsers(type, btn) {
    // Activar botón del filtro
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    
    // Mostrar/ocultar tarjetas en la cuadrícula
    document.querySelectorAll('.user-card').forEach(card => {
        const isDriver = card.getAttribute('data-driver') === '1';
        if (type === 'all') {
            card.style.display = 'flex';
        } else if (type === 'drivers') {
            card.style.display = isDriver ? 'flex' : 'none';
        } else if (type === 'admins') {
            card.style.display = !isDriver ? 'flex' : 'none';
        }
    });
}
</script>
</body>
</html>
