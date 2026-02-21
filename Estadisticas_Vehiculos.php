<?php
require_once __DIR__ . "/Conexiones/Conexion.php";

function date_ymd(string $value, string $fallback): string
{
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : $fallback;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function nfmt(float $value, int $dec = 0): string
{
    return number_format($value, $dec, '.', ',');
}

function text_filter($value): string
{
    return trim((string)$value);
}

$activeTab = $_GET['tab'] ?? 'reporte';
if (!in_array($activeTab, ['reporte', 'estadisticas'], true)) {
    $activeTab = 'reporte';
}

$fecha_inicio = date_ymd((string)($_GET['fecha_inicio'] ?? ''), date('Y-m-01'));
$fecha_fin = date_ymd((string)($_GET['fecha_fin'] ?? ''), date('Y-m-d'));
if ($fecha_inicio > $fecha_fin) {
    [$fecha_inicio, $fecha_fin] = [$fecha_fin, $fecha_inicio];
}

$costoMinuto = 1.145833;
try {
    $stCfg = $conn->prepare("SELECT valor FROM servicios_config WHERE clave = 'costo_minuto_mo' LIMIT 1");
    if ($stCfg) {
        $stCfg->execute();
        $rsCfg = $stCfg->get_result();
        if ($rsCfg && ($rowCfg = $rsCfg->fetch_assoc())) {
            $costoMinuto = (float)($rowCfg['valor'] ?? $costoMinuto);
        }
        $stCfg->close();
    }
} catch (Throwable $e) {
    // Continuar con fallback.
}

$sql = "
SELECT
  v.id_vehiculo,
  v.placa,
  v.tipo,
  v.Sucursal,
  v.Km_Actual,
  v.Km_Total,
  v.Km_de_Servicio,
  v.es_particular,
  v.responsable,
  COALESCE(ch.username, 'Sin asignar') AS chofer,
  COALESCE(km.registros_km, 0) AS registros_km,
  COALESCE(km.km_recorridos, 0) AS km_recorridos,
  COALESCE(gas.cargas_gas, 0) AS cargas_gas,
  COALESCE(gas.litros_total, 0) AS litros_total,
  COALESCE(gas.costo_total, 0) AS costo_gasolina,
  COALESCE(srv.ordenes_total, 0) AS ordenes_servicio,
  COALESCE(srv.ordenes_abiertas, 0) AS ordenes_abiertas,
  COALESCE(srv.costo_mo, 0) AS costo_mo,
  COALESCE(srv.costo_material, 0) AS costo_material,
  COALESCE(chk.checks_total, 0) AS checks_total,
  COALESCE(chk.checks_mal, 0) AS checks_mal,
  COALESCE(chk.checks_pendientes, 0) AS checks_pendientes,
  COALESCE(ped.pedidos_total, 0) AS pedidos_total,
  COALESCE(ped.pedidos_entregados, 0) AS pedidos_entregados,
  COALESCE(ped.pedidos_cancelados, 0) AS pedidos_cancelados
FROM vehiculos v
LEFT JOIN choferes ch
  ON ch.ID = v.id_chofer_asignado
LEFT JOIN (
  SELECT
    rk.id_vehiculo,
    COUNT(*) AS registros_km,
    SUM(GREATEST(0, COALESCE(rk.kilometraje_final, 0) - COALESCE(rk.kilometraje_inicial, 0))) AS km_recorridos
  FROM registro_kilometraje rk
  WHERE rk.fecha_registro BETWEEN ? AND ?
  GROUP BY rk.id_vehiculo
) km
  ON km.id_vehiculo = v.id_vehiculo
LEFT JOIN (
  SELECT
    rg.id_vehiculo,
    COUNT(*) AS cargas_gas,
    SUM(COALESCE(rg.litros, 0)) AS litros_total,
    SUM(COALESCE(rg.costo, 0)) AS costo_total
  FROM registro_gasolina rg
  WHERE rg.fecha_registro BETWEEN ? AND ?
  GROUP BY rg.id_vehiculo
) gas
  ON gas.id_vehiculo = v.id_vehiculo
LEFT JOIN (
  SELECT
    os.id_vehiculo,
    COUNT(*) AS ordenes_total,
    SUM(CASE WHEN os.estatus IS NULL OR os.estatus IN ('Pendiente', 'Programado', 'EnTaller') THEN 1 ELSE 0 END) AS ordenes_abiertas,
    SUM(COALESCE(os.duracion_minutos, 0) * ?) AS costo_mo,
    SUM(COALESCE(mat.costo_material, 0)) AS costo_material
  FROM orden_servicio os
  LEFT JOIN (
    SELECT
      osm.id_orden,
      SUM(COALESCE(osm.cantidad, 0) * COALESCE(i.costo, 0)) AS costo_material
    FROM orden_servicio_material osm
    LEFT JOIN inventario i
      ON i.id = osm.id_inventario
    GROUP BY osm.id_orden
  ) mat
    ON mat.id_orden = os.id
  WHERE DATE(os.creado_en) BETWEEN ? AND ?
  GROUP BY os.id_vehiculo
) srv
  ON srv.id_vehiculo = v.id_vehiculo
LEFT JOIN (
  SELECT
    cv.id_vehiculo,
    COUNT(*) AS checks_total,
    SUM(CASE WHEN cv.calificacion = 'Mal' THEN 1 ELSE 0 END) AS checks_mal,
    SUM(CASE WHEN cv.calificacion = 'Mal' AND COALESCE(cv.resuelto, 0) = 0 THEN 1 ELSE 0 END) AS checks_pendientes
  FROM checklist_vehicular cv
  WHERE DATE(cv.fecha_inspeccion) BETWEEN ? AND ?
  GROUP BY cv.id_vehiculo
) chk
  ON chk.id_vehiculo = v.id_vehiculo
LEFT JOIN (
  SELECT
    v.id_vehiculo,
    COUNT(*) AS pedidos_total,
    SUM(CASE WHEN UPPER(TRIM(COALESCE(p.ESTADO, ''))) IN ('ENTREGADO', 'ENTREGADA') THEN 1 ELSE 0 END) AS pedidos_entregados,
    SUM(CASE WHEN UPPER(TRIM(COALESCE(p.ESTADO, ''))) IN ('CANCELADO', 'CANCELADA', 'CANCELADO CLIENTE') THEN 1 ELSE 0 END) AS pedidos_cancelados
  FROM pedidos p
  JOIN choferes c
    ON c.username = p.CHOFER_ASIGNADO
  JOIN vehiculos v
    ON v.id_chofer_asignado = c.ID
  WHERE 1
    AND DATE(COALESCE(p.FECHA_ENTREGA_CLIENTE, p.FECHA_RECEPCION_FACTURA)) BETWEEN ? AND ?
  GROUP BY v.id_vehiculo
) ped
  ON ped.id_vehiculo = v.id_vehiculo
ORDER BY v.Sucursal ASC, v.placa ASC, v.id_vehiculo ASC
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    die("Error al preparar consulta de estadisticas: " . e($conn->error));
}

$stmt->bind_param(
    "ssssdssssss",
    $fecha_inicio,
    $fecha_fin,
    $fecha_inicio,
    $fecha_fin,
    $costoMinuto,
    $fecha_inicio,
    $fecha_fin,
    $fecha_inicio,
    $fecha_fin,
    $fecha_inicio,
    $fecha_fin
);
$stmt->execute();
$result = $stmt->get_result();

$vehiculos = [];
$porSucursal = [];
$kpi = [
    'vehiculos' => 0,
    'con_chofer' => 0,
    'con_movimiento' => 0,
    'km' => 0.0,
    'litros' => 0.0,
    'costo_gas' => 0.0,
    'costo_servicios' => 0.0,
    'costo_operativo' => 0.0,
    'pedidos_total' => 0,
    'pedidos_entregados' => 0,
    'ordenes_abiertas' => 0,
    'checks_pendientes' => 0,
];

while ($r = $result->fetch_assoc()) {
    $idVehiculo = (int)($r['id_vehiculo'] ?? 0);
    $placa = (string)($r['placa'] ?? '');
    $tipo = (string)($r['tipo'] ?? '');
    $sucursal = (string)($r['Sucursal'] ?? 'Sin sucursal');
    $chofer = (string)($r['chofer'] ?? 'Sin asignar');
    $esParticular = (int)($r['es_particular'] ?? 0) === 1;
    $responsable = trim((string)($r['responsable'] ?? ''));
    $kmActual = (float)($r['Km_Actual'] ?? 0);
    $kmTotal = (float)($r['Km_Total'] ?? 0);
    $kmServicio = (float)($r['Km_de_Servicio'] ?? 0);
    $registrosKm = (int)($r['registros_km'] ?? 0);
    $kmRecorridos = (float)($r['km_recorridos'] ?? 0);
    $cargasGas = (int)($r['cargas_gas'] ?? 0);
    $litrosTotal = (float)($r['litros_total'] ?? 0);
    $costoGasolina = (float)($r['costo_gasolina'] ?? 0);
    $ordenesServicio = (int)($r['ordenes_servicio'] ?? 0);
    $ordenesAbiertas = (int)($r['ordenes_abiertas'] ?? 0);
    $costoMO = (float)($r['costo_mo'] ?? 0);
    $costoMaterial = (float)($r['costo_material'] ?? 0);
    $checksTotal = (int)($r['checks_total'] ?? 0);
    $checksMal = (int)($r['checks_mal'] ?? 0);
    $checksPendientes = (int)($r['checks_pendientes'] ?? 0);
    $pedidosTotal = (int)($r['pedidos_total'] ?? 0);
    $pedidosEntregados = (int)($r['pedidos_entregados'] ?? 0);
    $pedidosCancelados = (int)($r['pedidos_cancelados'] ?? 0);

    $costoServicios = $costoMO + $costoMaterial;
    $costoOperativo = $costoGasolina + $costoServicios;
    $rendimiento = $litrosTotal > 0 ? ($kmRecorridos / $litrosTotal) : 0.0;
    $costoPorKm = $kmRecorridos > 0 ? ($costoOperativo / $kmRecorridos) : 0.0;
    $kmRestantes = $kmServicio > 0 ? ($kmServicio - $kmActual) : 0.0;

    if ($kmServicio <= 0) {
        $estatusServicio = 'Sin objetivo';
    } elseif ($kmActual >= $kmServicio) {
        $estatusServicio = 'Vencido';
    } elseif ($kmActual >= ($kmServicio * 0.8)) {
        $estatusServicio = 'Proximo';
    } else {
        $estatusServicio = 'OK';
    }

    $titular = $esParticular
        ? ($responsable !== '' ? ("Particular: " . $responsable) : 'Particular sin responsable')
        : $chofer;

    $vehiculos[] = [
        'id_vehiculo' => $idVehiculo,
        'placa' => $placa !== '' ? $placa : ('ID ' . $idVehiculo),
        'tipo' => $tipo,
        'sucursal' => $sucursal,
        'titular' => $titular,
        'km_actual' => $kmActual,
        'km_total' => $kmTotal,
        'km_servicio' => $kmServicio,
        'km_restantes' => $kmRestantes,
        'estatus_servicio' => $estatusServicio,
        'registros_km' => $registrosKm,
        'km_recorridos' => $kmRecorridos,
        'cargas_gas' => $cargasGas,
        'litros_total' => $litrosTotal,
        'costo_gasolina' => $costoGasolina,
        'ordenes_servicio' => $ordenesServicio,
        'ordenes_abiertas' => $ordenesAbiertas,
        'costo_servicios' => $costoServicios,
        'checks_total' => $checksTotal,
        'checks_mal' => $checksMal,
        'checks_pendientes' => $checksPendientes,
        'pedidos_total' => $pedidosTotal,
        'pedidos_entregados' => $pedidosEntregados,
        'pedidos_cancelados' => $pedidosCancelados,
        'costo_operativo' => $costoOperativo,
        'rendimiento' => $rendimiento,
        'costo_por_km' => $costoPorKm,
    ];

    if (!isset($porSucursal[$sucursal])) {
        $porSucursal[$sucursal] = [
            'vehiculos' => 0,
            'km' => 0.0,
            'litros' => 0.0,
            'costo_operativo' => 0.0,
            'pedidos_total' => 0,
            'ordenes_abiertas' => 0,
            'checks_pendientes' => 0,
        ];
    }

    $porSucursal[$sucursal]['vehiculos']++;
    $porSucursal[$sucursal]['km'] += $kmRecorridos;
    $porSucursal[$sucursal]['litros'] += $litrosTotal;
    $porSucursal[$sucursal]['costo_operativo'] += $costoOperativo;
    $porSucursal[$sucursal]['pedidos_total'] += $pedidosTotal;
    $porSucursal[$sucursal]['ordenes_abiertas'] += $ordenesAbiertas;
    $porSucursal[$sucursal]['checks_pendientes'] += $checksPendientes;

    $kpi['vehiculos']++;
    if (!$esParticular && $chofer !== 'Sin asignar') {
        $kpi['con_chofer']++;
    }
    if ($kmRecorridos > 0 || $cargasGas > 0 || $ordenesServicio > 0 || $checksTotal > 0 || $pedidosTotal > 0) {
        $kpi['con_movimiento']++;
    }
    $kpi['km'] += $kmRecorridos;
    $kpi['litros'] += $litrosTotal;
    $kpi['costo_gas'] += $costoGasolina;
    $kpi['costo_servicios'] += $costoServicios;
    $kpi['costo_operativo'] += $costoOperativo;
    $kpi['pedidos_total'] += $pedidosTotal;
    $kpi['pedidos_entregados'] += $pedidosEntregados;
    $kpi['ordenes_abiertas'] += $ordenesAbiertas;
    $kpi['checks_pendientes'] += $checksPendientes;
}

$stmt->close();

$reportePedidosVehiculo = [];
$repSucursal = text_filter($_GET['rep_sucursal'] ?? '');
$repChofer = text_filter($_GET['rep_chofer'] ?? '');
$repPlaca = text_filter($_GET['rep_placa'] ?? '');
$repEstado = text_filter($_GET['rep_estado'] ?? '');
$repFactura = text_filter($_GET['rep_factura'] ?? '');
$repFechaInicio = date_ymd((string)($_GET['rep_fecha_inicio'] ?? ''), '');
$repFechaFin = date_ymd((string)($_GET['rep_fecha_fin'] ?? ''), '');
if ($repFechaInicio !== '' && $repFechaFin !== '' && $repFechaInicio > $repFechaFin) {
    [$repFechaInicio, $repFechaFin] = [$repFechaFin, $repFechaInicio];
}

$sqlReportePedidosVehiculo = "
SELECT p.SUCURSAL, p.CHOFER_ASIGNADO, v.placa, p.ESTADO, p.FACTURA, p.FECHA_RECEPCION_FACTURA
FROM pedidos p
JOIN choferes c
  ON c.username = p.CHOFER_ASIGNADO
JOIN vehiculos v
  ON v.id_chofer_asignado = c.ID
WHERE 1
";

$repTypes = '';
$repParams = [];

if ($repSucursal !== '') {
    $sqlReportePedidosVehiculo .= " AND p.SUCURSAL LIKE ? ";
    $repTypes .= 's';
    $repParams[] = '%' . $repSucursal . '%';
}
if ($repChofer !== '') {
    $sqlReportePedidosVehiculo .= " AND p.CHOFER_ASIGNADO LIKE ? ";
    $repTypes .= 's';
    $repParams[] = '%' . $repChofer . '%';
}
if ($repPlaca !== '') {
    $sqlReportePedidosVehiculo .= " AND v.placa LIKE ? ";
    $repTypes .= 's';
    $repParams[] = '%' . $repPlaca . '%';
}
if ($repEstado !== '') {
    $sqlReportePedidosVehiculo .= " AND p.ESTADO LIKE ? ";
    $repTypes .= 's';
    $repParams[] = '%' . $repEstado . '%';
}
if ($repFactura !== '') {
    $sqlReportePedidosVehiculo .= " AND p.FACTURA LIKE ? ";
    $repTypes .= 's';
    $repParams[] = '%' . $repFactura . '%';
}
if ($repFechaInicio !== '') {
    $sqlReportePedidosVehiculo .= " AND p.FECHA_RECEPCION_FACTURA >= ? ";
    $repTypes .= 's';
    $repParams[] = $repFechaInicio;
}
if ($repFechaFin !== '') {
    $sqlReportePedidosVehiculo .= " AND p.FECHA_RECEPCION_FACTURA <= ? ";
    $repTypes .= 's';
    $repParams[] = $repFechaFin;
}

$sqlReportePedidosVehiculo .= " ORDER BY p.FECHA_RECEPCION_FACTURA DESC, p.ID DESC ";

$stReporte = $conn->prepare($sqlReportePedidosVehiculo);
if ($stReporte) {
    if ($repTypes !== '') {
        $stReporte->bind_param($repTypes, ...$repParams);
    }
    $stReporte->execute();
    $rsReportePedidosVehiculo = $stReporte->get_result();
    if ($rsReportePedidosVehiculo) {
        while ($row = $rsReportePedidosVehiculo->fetch_assoc()) {
            $reportePedidosVehiculo[] = $row;
        }
    }
    $stReporte->close();
}

$conn->close();

ksort($porSucursal);

$topKm = array_values(array_filter($vehiculos, static fn($v) => $v['km_recorridos'] > 0));
usort($topKm, static fn($a, $b) => $b['km_recorridos'] <=> $a['km_recorridos']);
$topKm = array_slice($topKm, 0, 10);

$topCosto = array_values(array_filter($vehiculos, static fn($v) => $v['costo_operativo'] > 0));
usort($topCosto, static fn($a, $b) => $b['costo_operativo'] <=> $a['costo_operativo']);
$topCosto = array_slice($topCosto, 0, 10);

$labelsSuc = array_keys($porSucursal);
$dataSucKm = array_map(static fn($s) => round((float)$s['km'], 2), array_values($porSucursal));
$dataSucCosto = array_map(static fn($s) => round((float)$s['costo_operativo'], 2), array_values($porSucursal));
$dataSucPendientes = array_map(static fn($s) => (int)$s['checks_pendientes'], array_values($porSucursal));

$labelsTopKm = array_map(static fn($v) => $v['placa'], $topKm);
$dataTopKm = array_map(static fn($v) => round((float)$v['km_recorridos'], 2), $topKm);

$labelsTopCosto = array_map(static fn($v) => $v['placa'], $topCosto);
$dataTopCosto = array_map(static fn($v) => round((float)$v['costo_operativo'], 2), $topCosto);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Estadisticas de Vehiculos</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link rel="icon" type="image/png" href="/Pedidos_GA/Img/Botones%20entregas/ICONOSPAG/ICONOPEDIDOS.png">
  <style>
    :root {
      --bg: #f7f8fa;
      --card: #ffffff;
      --text: #111827;
      --muted: #6b7280;
      --line: #e5e7eb;
      --brand: #0f4c81;
      --brand-2: #f97316;
      --ok: #15803d;
      --warn: #b45309;
      --bad: #b91c1c;
    }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      padding: 20px;
      font-family: Arial, sans-serif;
      background: var(--bg);
      color: var(--text);
    }
    .wrap {
      max-width: 1400px;
      margin: 0 auto;
    }
    .header {
      background: var(--brand);
      color: #fff;
      border-radius: 12px;
      padding: 16px 18px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      flex-wrap: wrap;
      margin-bottom: 14px;
    }
    .header a {
      color: #fff;
      text-decoration: none;
      border: 1px solid rgba(255,255,255,.35);
      padding: 8px 12px;
      border-radius: 8px;
      background: rgba(255,255,255,.08);
    }
    .header h1 {
      margin: 0;
      font-size: 1.35rem;
      letter-spacing: .2px;
    }
    .panel {
      background: var(--card);
      border: 1px solid var(--line);
      border-radius: 12px;
      padding: 14px;
      margin-bottom: 14px;
    }
    .tabs {
      display: flex;
      gap: 8px;
      margin-bottom: 14px;
      flex-wrap: wrap;
    }
    .tab-btn {
      border: 1px solid var(--line);
      background: #fff;
      color: var(--text);
      border-radius: 9px;
      padding: 9px 14px;
      font-weight: bold;
      cursor: pointer;
    }
    .tab-btn.active {
      background: var(--brand);
      color: #fff;
      border-color: var(--brand);
    }
    .tab-content {
      display: none;
    }
    .tab-content.active {
      display: block;
    }
    .filters form {
      display: flex;
      gap: 10px;
      align-items: end;
      flex-wrap: wrap;
    }
    .field {
      display: flex;
      flex-direction: column;
      gap: 4px;
      min-width: 180px;
    }
    .field label {
      font-size: .85rem;
      color: var(--muted);
    }
    .field input, .btn {
      border: 1px solid var(--line);
      border-radius: 8px;
      padding: 9px 10px;
      font-size: .95rem;
      background: #fff;
    }
    .btn {
      cursor: pointer;
      background: var(--brand-2);
      color: #fff;
      border: none;
      min-width: 130px;
      font-weight: bold;
    }
    .kpis {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 10px;
    }
    .kpi {
      border: 1px solid var(--line);
      border-radius: 10px;
      padding: 10px;
      background: #fff;
    }
    .kpi small {
      color: var(--muted);
      display: block;
      margin-bottom: 4px;
    }
    .kpi strong {
      font-size: 1.15rem;
    }
    .charts {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
      gap: 12px;
    }
    .chart-box {
      border: 1px solid var(--line);
      border-radius: 10px;
      padding: 10px;
      background: #fff;
      display: flex;
      flex-direction: column;
    }
    .chart-box h3 {
      margin: 4px 0 10px;
      font-size: 1rem;
    }
    .chart-area {
      position: relative;
      width: 100%;
      height: 320px;
      min-height: 260px;
    }
    .chart-area canvas {
      position: absolute;
      inset: 0;
      width: 100% !important;
      height: 100% !important;
    }
    .table-wrap {
      overflow: auto;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      min-width: 1380px;
      background: #fff;
    }
    th, td {
      border-bottom: 1px solid var(--line);
      padding: 9px;
      text-align: left;
      vertical-align: top;
      font-size: .9rem;
    }
    thead th {
      position: sticky;
      top: 0;
      background: #f3f4f6;
      z-index: 2;
    }
    .muted { color: var(--muted); }
    .badge {
      display: inline-block;
      border-radius: 999px;
      padding: 2px 8px;
      font-size: .75rem;
      font-weight: bold;
      border: 1px solid transparent;
    }
    .badge-ok { color: var(--ok); background: #dcfce7; border-color: #86efac; }
    .badge-warn { color: var(--warn); background: #ffedd5; border-color: #fdba74; }
    .badge-bad { color: var(--bad); background: #fee2e2; border-color: #fca5a5; }
    a.link {
      color: #075985;
      text-decoration: none;
      font-weight: bold;
    }
    a.link:hover { text-decoration: underline; }
    @media (max-width: 768px) {
      .chart-area { height: 260px; }
    }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="header">
      <h1>Estadisticas de Vehiculos</h1>
      <a href="vehiculos.php">Volver a vehiculos</a>
    </div>

    <div class="tabs">
      <button type="button" class="tab-btn <?= $activeTab === 'reporte' ? 'active' : '' ?>" data-tab="reporte">Reporte Pedidos</button>
      <button type="button" class="tab-btn <?= $activeTab === 'estadisticas' ? 'active' : '' ?>" data-tab="estadisticas">Estadisticas Vehiculos</button>
    </div>

    <section id="tab-reporte" class="tab-content <?= $activeTab === 'reporte' ? 'active' : '' ?>">
      <div class="panel filters">
        <form method="GET">
          <input type="hidden" name="tab" value="reporte">
          <div class="field">
            <label for="rep_sucursal">Sucursal</label>
            <input type="text" id="rep_sucursal" name="rep_sucursal" value="<?= e($repSucursal) ?>" placeholder="Ej. DEASA">
          </div>
          <div class="field">
            <label for="rep_chofer">Chofer asignado</label>
            <input type="text" id="rep_chofer" name="rep_chofer" value="<?= e($repChofer) ?>" placeholder="Nombre de chofer">
          </div>
          <div class="field">
            <label for="rep_placa">Placa</label>
            <input type="text" id="rep_placa" name="rep_placa" value="<?= e($repPlaca) ?>" placeholder="Ej. JP43907">
          </div>
          <div class="field">
            <label for="rep_estado">Estado pedido</label>
            <input type="text" id="rep_estado" name="rep_estado" value="<?= e($repEstado) ?>" placeholder="Ej. ENTREGADO">
          </div>
          <div class="field">
            <label for="rep_factura">Factura</label>
            <input type="text" id="rep_factura" name="rep_factura" value="<?= e($repFactura) ?>" placeholder="Numero de factura">
          </div>
          <div class="field">
            <label for="rep_fecha_inicio">Fecha recepcion desde</label>
            <input type="date" id="rep_fecha_inicio" name="rep_fecha_inicio" value="<?= e($repFechaInicio) ?>">
          </div>
          <div class="field">
            <label for="rep_fecha_fin">Fecha recepcion hasta</label>
            <input type="date" id="rep_fecha_fin" name="rep_fecha_fin" value="<?= e($repFechaFin) ?>">
          </div>
          <button class="btn" type="submit">Filtrar reporte</button>
          <a class="btn" style="text-align:center;line-height:20px;text-decoration:none;background:#475569;" href="Estadisticas_Vehiculos.php?tab=reporte">Limpiar</a>
        </form>
      </div>

      <div class="panel">
        <h3 style="margin:0 0 10px;">Reporte pedidos por chofer y vehiculo asignado</h3>
        <?php if (!$reportePedidosVehiculo): ?>
          <p class="muted" style="margin:0;">No hay registros para el reporte.</p>
        <?php else: ?>
          <p class="muted" style="margin:0 0 10px;">Total registros: <strong><?= nfmt((float)count($reportePedidosVehiculo)) ?></strong></p>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>SUCURSAL</th>
                  <th>CHOFER_ASIGNADO</th>
                  <th>placa</th>
                  <th>ESTADO</th>
                  <th>FACTURA</th>
                  <th>FECHA_RECEPCION_FACTURA</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($reportePedidosVehiculo as $row): ?>
                  <tr>
                    <td><?= e((string)($row['SUCURSAL'] ?? '')) ?></td>
                    <td><?= e((string)($row['CHOFER_ASIGNADO'] ?? '')) ?></td>
                    <td><?= e((string)($row['placa'] ?? '')) ?></td>
                    <td><?= e((string)($row['ESTADO'] ?? '')) ?></td>
                    <td><?= e((string)($row['FACTURA'] ?? '')) ?></td>
                    <td><?= e((string)($row['FECHA_RECEPCION_FACTURA'] ?? '')) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </section>

    <section id="tab-estadisticas" class="tab-content <?= $activeTab === 'estadisticas' ? 'active' : '' ?>">
      <div class="panel filters">
        <form method="GET">
          <input type="hidden" name="tab" value="estadisticas">
          <div class="field">
            <label for="fecha_inicio">Fecha inicio</label>
            <input type="date" id="fecha_inicio" name="fecha_inicio" value="<?= e($fecha_inicio) ?>" required>
          </div>
          <div class="field">
            <label for="fecha_fin">Fecha fin</label>
            <input type="date" id="fecha_fin" name="fecha_fin" value="<?= e($fecha_fin) ?>" required>
          </div>
          <button class="btn" type="submit">Aplicar filtro</button>
        </form>
        <p class="muted" style="margin:10px 0 0;">
          Rango analizado: <strong><?= e($fecha_inicio) ?></strong> a <strong><?= e($fecha_fin) ?></strong>
        </p>
      </div>

      <div class="panel">
        <div class="kpis">
          <div class="kpi"><small>Vehiculos</small><strong><?= nfmt((float)$kpi['vehiculos']) ?></strong></div>
          <div class="kpi"><small>Con chofer actual</small><strong><?= nfmt((float)$kpi['con_chofer']) ?></strong></div>
          <div class="kpi"><small>Con movimiento en rango</small><strong><?= nfmt((float)$kpi['con_movimiento']) ?></strong></div>
          <div class="kpi"><small>Pedidos (query chofer-vehiculo)</small><strong><?= nfmt((float)$kpi['pedidos_total']) ?></strong></div>
          <div class="kpi"><small>Pedidos entregados</small><strong><?= nfmt((float)$kpi['pedidos_entregados']) ?></strong></div>
          <div class="kpi"><small>Km recorridos (rango)</small><strong><?= nfmt((float)$kpi['km'], 2) ?></strong></div>
          <div class="kpi"><small>Litros gasolina (rango)</small><strong><?= nfmt((float)$kpi['litros'], 2) ?></strong></div>
          <div class="kpi"><small>Costo gasolina</small><strong>$<?= nfmt((float)$kpi['costo_gas'], 2) ?></strong></div>
          <div class="kpi"><small>Costo servicios</small><strong>$<?= nfmt((float)$kpi['costo_servicios'], 2) ?></strong></div>
          <div class="kpi"><small>Costo operativo total</small><strong>$<?= nfmt((float)$kpi['costo_operativo'], 2) ?></strong></div>
          <div class="kpi"><small>OS abiertas</small><strong><?= nfmt((float)$kpi['ordenes_abiertas']) ?></strong></div>
          <div class="kpi"><small>Checklist "Mal" pendientes</small><strong><?= nfmt((float)$kpi['checks_pendientes']) ?></strong></div>
        </div>
      </div>

      <div class="panel">
        <div class="charts">
          <div class="chart-box">
            <h3>Km y costo operativo por sucursal</h3>
            <div class="chart-area"><canvas id="chartSucursal"></canvas></div>
          </div>
          <div class="chart-box">
            <h3>Top 10 vehiculos por km recorridos</h3>
            <div class="chart-area"><canvas id="chartTopKm"></canvas></div>
          </div>
          <div class="chart-box">
            <h3>Top 10 vehiculos por costo operativo</h3>
            <div class="chart-area"><canvas id="chartTopCosto"></canvas></div>
          </div>
          <div class="chart-box">
            <h3>Checklist pendientes por sucursal</h3>
            <div class="chart-area"><canvas id="chartPendientes"></canvas></div>
          </div>
        </div>
      </div>

      <div class="panel">
        <h3 style="margin:0 0 10px;">Detalle por vehiculo</h3>
        <?php if (!$vehiculos): ?>
          <p class="muted" style="margin:0;">No hay vehiculos para mostrar.</p>
        <?php else: ?>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Vehiculo</th>
                  <th>Sucursal</th>
                  <th>Titular actual</th>
                  <th>Km rango</th>
                  <th>Gas (L / cargas)</th>
                  <th>Costo gas</th>
                  <th>Costo servicios</th>
                  <th>Costo total</th>
                  <th>Rend km/L</th>
                  <th>Costo/km</th>
                  <th>Pedidos (tot/ent/can)</th>
                  <th>OS (tot/abiertas)</th>
                  <th>Checklist (tot/mal/pend)</th>
                  <th>Meta servicio</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($vehiculos as $v): ?>
                  <?php
                  $badge = 'badge-ok';
                  if ($v['estatus_servicio'] === 'Proximo') $badge = 'badge-warn';
                  if ($v['estatus_servicio'] === 'Vencido') $badge = 'badge-bad';
                  ?>
                  <tr>
                    <td>
                      <a class="link" href="detalles_vehiculo.php?id=<?= (int)$v['id_vehiculo'] ?>">
                        <?= e($v['placa']) ?>
                      </a>
                      <div class="muted"><?= e($v['tipo']) ?></div>
                    </td>
                    <td><?= e($v['sucursal']) ?></td>
                    <td><?= e($v['titular']) ?></td>
                    <td>
                      <strong><?= nfmt((float)$v['km_recorridos'], 2) ?></strong>
                      <div class="muted">registros: <?= nfmt((float)$v['registros_km']) ?></div>
                    </td>
                    <td>
                      <strong><?= nfmt((float)$v['litros_total'], 2) ?> L</strong>
                      <div class="muted">cargas: <?= nfmt((float)$v['cargas_gas']) ?></div>
                    </td>
                    <td>$<?= nfmt((float)$v['costo_gasolina'], 2) ?></td>
                    <td>$<?= nfmt((float)$v['costo_servicios'], 2) ?></td>
                    <td><strong>$<?= nfmt((float)$v['costo_operativo'], 2) ?></strong></td>
                    <td><?= $v['rendimiento'] > 0 ? nfmt((float)$v['rendimiento'], 2) : '-' ?></td>
                    <td><?= $v['costo_por_km'] > 0 ? ('$' . nfmt((float)$v['costo_por_km'], 2)) : '-' ?></td>
                    <td>
                      <?= nfmt((float)$v['pedidos_total']) ?>/<?= nfmt((float)$v['pedidos_entregados']) ?>/<?= nfmt((float)$v['pedidos_cancelados']) ?>
                    </td>
                    <td>
                      <?= nfmt((float)$v['ordenes_servicio']) ?>/<?= nfmt((float)$v['ordenes_abiertas']) ?>
                    </td>
                    <td>
                      <?= nfmt((float)$v['checks_total']) ?>/<?= nfmt((float)$v['checks_mal']) ?>/<?= nfmt((float)$v['checks_pendientes']) ?>
                    </td>
                    <td>
                      <span class="badge <?= $badge ?>"><?= e($v['estatus_servicio']) ?></span>
                      <div class="muted">
                        km act: <?= nfmt((float)$v['km_actual']) ?> |
                        meta: <?= nfmt((float)$v['km_servicio']) ?> |
                        rest: <?= nfmt((float)$v['km_restantes']) ?>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </section>
  </div>

  <script>
    const ACTIVE_TAB = <?= json_encode($activeTab, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    const labelsSuc = <?= json_encode($labelsSuc, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const dataSucKm = <?= json_encode($dataSucKm, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const dataSucCosto = <?= json_encode($dataSucCosto, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const dataSucPendientes = <?= json_encode($dataSucPendientes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    const labelsTopKm = <?= json_encode($labelsTopKm, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const dataTopKm = <?= json_encode($dataTopKm, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    const labelsTopCosto = <?= json_encode($labelsTopCosto, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const dataTopCosto = <?= json_encode($dataTopCosto, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    let chartsInited = false;

    function makeBarChart(id, labels, datasets) {
      const ctx = document.getElementById(id);
      if (!ctx) return;
      new Chart(ctx, {
        type: 'bar',
        data: { labels, datasets },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { position: 'bottom' } },
          scales: { y: { beginAtZero: true } }
        }
      });
    }

    function initCharts() {
      if (chartsInited) return;
      chartsInited = true;

      makeBarChart('chartSucursal', labelsSuc, [
        { label: 'Km recorridos', data: dataSucKm, backgroundColor: 'rgba(37, 99, 235, 0.72)' },
        { label: 'Costo operativo', data: dataSucCosto, backgroundColor: 'rgba(249, 115, 22, 0.72)' }
      ]);

      makeBarChart('chartTopKm', labelsTopKm, [
        { label: 'Km recorridos', data: dataTopKm, backgroundColor: 'rgba(22, 163, 74, 0.75)' }
      ]);

      makeBarChart('chartTopCosto', labelsTopCosto, [
        { label: 'Costo operativo', data: dataTopCosto, backgroundColor: 'rgba(220, 38, 38, 0.75)' }
      ]);

      makeBarChart('chartPendientes', labelsSuc, [
        { label: 'Checklist pendientes', data: dataSucPendientes, backgroundColor: 'rgba(234, 179, 8, 0.8)' }
      ]);
    }

    function showTab(tabName) {
      document.querySelectorAll('.tab-content').forEach((tab) => {
        tab.classList.toggle('active', tab.id === `tab-${tabName}`);
      });
      document.querySelectorAll('.tab-btn').forEach((btn) => {
        btn.classList.toggle('active', btn.dataset.tab === tabName);
      });
      if (tabName === 'estadisticas') {
        initCharts();
      }
    }

    document.querySelectorAll('.tab-btn').forEach((btn) => {
      btn.addEventListener('click', () => showTab(btn.dataset.tab));
    });

    showTab(ACTIVE_TAB || 'reporte');
  </script>
</body>
</html>
