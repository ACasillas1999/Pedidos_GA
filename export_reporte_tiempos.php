<?php
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

$sumDur = array_fill_keys($transKeys, 0);
$cntDur = array_fill_keys($transKeys, 0);
$userStats = [];

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
    $ped['trans_users'] = [];
    $evs = $ped['events'];
    for ($i = 0; $i < count($evs)-1; $i++) {
        $from = $evs[$i]['step']; $to = $evs[$i+1]['step'];
        $key = $from.'→'.$to;
        $min = ($evs[$i+1]['ts'] - $evs[$i]['ts']) / 60;
        $ped['durations'][$key] = $min;
        $user = $evs[$i+1]['user'] ?: $ped['chofer'];
        $ped['trans_users'][$key] = $user;
        if (isset($sumDur[$key])) { $sumDur[$key] += $min; $cntDur[$key]++; }
        // Acumular estadísticas de usuario
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

// Generar userAvg estructurado para el desglose de usuarios
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
uasort($userAvg, fn($a,$b) => $b['count'] <=> $a['count']);

// Configurar descarga de archivo Excel
$filename = "Reporte_Tiempos_Estados_" . date('Y-m-d_His') . ".xls";
header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

echo "\xEF\xBB\xBF"; // UTF-8 BOM
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
            font-family: Calibri, sans-serif;
            font-size: 11pt;
            margin-bottom: 30px;
        }
        th, td {
            border: 1px solid #cbd5e1;
            padding: 8px 12px;
            text-align: left;
        }
        th {
            background-color: #0f172a;
            color: #ffffff;
            font-weight: bold;
        }
        .section-header {
            background-color: #1e293b;
            color: #ffffff;
            font-size: 14pt;
            font-weight: bold;
            padding: 10px;
            margin-top: 25px;
            margin-bottom: 15px;
        }
        .metric-header {
            background-color: #e2e8f0;
            font-weight: bold;
            font-size: 12pt;
        }
        .slow {
            background-color: #fca5a5;
            color: #7f1d1d;
        }
        .mid {
            background-color: #fde047;
            color: #713f12;
        }
        .ok {
            background-color: #86efac;
            color: #14532d;
        }
        .badge-suc {
            background-color: #bae6fd;
            color: #0369a1;
            font-weight: bold;
            text-align: center;
        }
        .badge-time {
            font-weight: bold;
        }
    </style>
</head>
<body>

<h2>Reporte de Tiempos de Transiciones por Estado</h2>
<p><strong>Rango de Fechas:</strong> <?php echo $fechaDesde; ?> al <?php echo $fechaHasta; ?></p>
<p><strong>Sucursal Filtro:</strong> <?php echo $sucursalFiltro === '' ? 'TODAS' : $sucursalFiltro; ?></p>
<p><strong>Generado el:</strong> <?php echo date('d/m/Y H:i:s'); ?></p>

<hr>

<!-- SECCIÓN 1: RESUMEN DE PROMEDIOS GENERALES -->
<div class="section-header">1. RESUMEN DE PROMEDIOS GENERALES</div>
<table>
    <thead>
        <tr>
            <th>Transición / Paso</th>
            <th style="text-align: right;">Tiempo Promedio</th>
            <th style="text-align: right;">Pedidos Evaluados</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($transKeys as $k): ?>
        <tr>
            <td><strong><?php echo $transLabels[$k]; ?></strong></td>
            <td style="text-align: right;"><?php echo fmtMin($avgDur[$k]); ?></td>
            <td style="text-align: right;"><?php echo number_format($cntDur[$k]); ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<!-- SECCIÓN 2: DETALLE DE TIEMPOS POR PEDIDO -->
<div class="section-header">2. DETALLE DE TIEMPOS POR PEDIDO</div>
<table>
    <thead>
        <tr>
            <th>ID Pedido</th>
            <th>Sucursal</th>
            <th>Factura</th>
            <th>Cliente</th>
            <th>Chofer</th>
            <th>En Caja → Jefe</th>
            <th>Jefe → Pend. Surtido</th>
            <th>Pend. Surtido → Surtido</th>
            <th>Surtido → Camioneta</th>
            <th>Camioneta → En Ruta</th>
            <th>En Ruta → Entregado</th>
            <th>Tiempo Total</th>
        </tr>
    </thead>
    <tbody>
        <?php
        uasort($pedidos, function($a, $b) {
            return ($b['durations']['total'] ?? 0) <=> ($a['durations']['total'] ?? 0);
        });
        foreach ($pedidos as $pid => $ped):
            $dur = $ped['durations'];
        ?>
        <tr>
            <td><strong><?php echo $pid; ?></strong></td>
            <td class="badge-suc"><?php echo htmlspecialchars($ped['sucursal']); ?></td>
            <td><?php echo htmlspecialchars($ped['factura']); ?></td>
            <td><?php echo htmlspecialchars($ped['cliente']); ?></td>
            <td><?php echo htmlspecialchars($ped['chofer']); ?></td>
            <td class="<?php echo durClass($dur['0→1']??null); ?>" style="text-align: right;"><?php echo fmtMin($dur['0→1']??null); ?></td>
            <td class="<?php echo durClass($dur['1→2']??null); ?>" style="text-align: right;"><?php echo fmtMin($dur['1→2']??null); ?></td>
            <td class="<?php echo durClass($dur['2→3']??null); ?>" style="text-align: right;"><?php echo fmtMin($dur['2→3']??null); ?></td>
            <td class="<?php echo durClass($dur['3→4']??null); ?>" style="text-align: right;"><?php echo fmtMin($dur['3→4']??null); ?></td>
            <td class="<?php echo durClass($dur['4→5']??null); ?>" style="text-align: right;"><?php echo fmtMin($dur['4→5']??null); ?></td>
            <td class="<?php echo durClass($dur['5→6']??null,60,240); ?>" style="text-align: right;"><?php echo fmtMin($dur['5→6']??null); ?></td>
            <td class="<?php echo durClass($dur['total']??null,480,1440); ?>" style="text-align: right;"><strong><?php echo fmtMin($dur['total']??null); ?></strong></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<!-- SECCIÓN 3: DESGLOSE DE TIEMPOS POR USUARIO -->
<div class="section-header">3. DESGLOSE DE TIEMPOS POR USUARIO / CHOFER</div>
<table>
    <thead>
        <tr>
            <th>Usuario / Responsable</th>
            <th>Área / Rol</th>
            <th>Pedidos Atendidos</th>
            <th>Transición</th>
            <th style="text-align: right;">Tiempo Promedio</th>
            <th style="text-align: right;">Mejor Tiempo</th>
            <th style="text-align: right;">Peor Tiempo</th>
            <th style="text-align: right;">Cantidad Realizada</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        foreach ($userAvg as $usr => $data):
            $parts = explode('|', $usr);
            $realName = $parts[0];
            $determinedRole = $parts[1] ?? 'Oficina';
            
            $sliceKeys = array_slice($transKeys, 0, 6);
            $rowspan = 0;
            foreach ($sliceKeys as $k) {
                if (isset($data['trans'][$k])) $rowspan++;
            }
            
            $isFirstRow = true;
            foreach ($sliceKeys as $k):
                if (!isset($data['trans'][$k])) continue;
                $t = $data['trans'][$k];
        ?>
        <tr>
            <?php if ($isFirstRow): ?>
            <td rowspan="<?php echo $rowspan; ?>"><strong><?php echo htmlspecialchars($realName); ?></strong></td>
            <td rowspan="<?php echo $rowspan; ?>"><?php echo $determinedRole; ?></td>
            <td rowspan="<?php echo $rowspan; ?>" style="text-align: right;"><?php echo number_format($data['count']); ?></td>
            <?php $isFirstRow = false; endif; ?>
            <td><?php echo $transLabels[$k]; ?></td>
            <td style="text-align: right; font-weight: bold;"><?php echo fmtMin($t['avg']); ?></td>
            <td style="text-align: right; color: #14532d;"><?php echo fmtMin($t['min']); ?></td>
            <td style="text-align: right; color: #7f1d1d;"><?php echo fmtMin($t['max']); ?></td>
            <td style="text-align: right; font-weight: bold;"><?php echo number_format($t['cnt']); ?></td>
        </tr>
        <?php 
            endforeach;
        endforeach; 
        ?>
    </tbody>
</table>

</body>
</html>
<?php
$conn->close();
?>
