<?php
ini_set('session.cookie_httponly', true);
ini_set('session.cookie_secure', true);
session_name('GA');
session_start();

require_once __DIR__ . '/../Conexiones/Conexion.php';
$conn->set_charset('utf8mb4');

// Rango de fechas (para movimientos)
$fecha_inicio = isset($_GET['inicio']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['inicio']) ? $_GET['inicio'] : date('Y-m-01');
$fecha_fin    = isset($_GET['fin'])    && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['fin'])    ? $_GET['fin']    : date('Y-m-d');

function q1($conn, $sql){ $r=$conn->query($sql); return $r? $r->fetch_assoc() : null; }
function qall($conn,$sql){ $r=$conn->query($sql); $out=[]; if($r){ while($x=$r->fetch_assoc()) $out[]=$x; } return $out; }

$sum = q1($conn, "SELECT
  COUNT(*) AS skus,
  COALESCE(SUM(COALESCE(cantidad,0)),0) AS total_unidades,
  COALESCE(SUM(COALESCE(cantidad,0)*COALESCE(costo,0)),0) AS total_valor,
  COALESCE(SUM(CASE WHEN COALESCE(cantidad,0) <= COALESCE(stock_minimo,0) THEN 1 ELSE 0 END),0) AS bajos
FROM inventario");

$topLow = qall($conn, "SELECT id, nombre, marca, modelo, cantidad, stock_minimo
                         FROM inventario
                        WHERE COALESCE(stock_minimo,0) > 0
                        ORDER BY (COALESCE(stock_minimo,0) - COALESCE(cantidad,0)) DESC
                        LIMIT 10");

$topValor = qall($conn, "SELECT id, nombre, marca, modelo,
                               COALESCE(cantidad,0) AS cantidad,
                               COALESCE(costo,0) AS costo,
                               (COALESCE(cantidad,0)*COALESCE(costo,0)) AS valor
                          FROM inventario
                         ORDER BY valor DESC
                         LIMIT 10");

$porUnidad = qall($conn, "SELECT COALESCE(presentacion_unidad,'(sin unidad)') AS unidad,
                                 COUNT(*) AS skus,
                                 COALESCE(SUM(COALESCE(cantidad,0)),0) AS unidades,
                                 COALESCE(SUM(COALESCE(cantidad,0)*COALESCE(costo,0)),0) AS valor
                            FROM inventario
                           GROUP BY unidad
                           ORDER BY valor DESC, skus DESC");

$compat = q1($conn, "SELECT
   (SELECT COUNT(DISTINCT i.id) FROM inventario i LEFT JOIN inventario_vehiculo iv ON iv.id_inventario=i.id WHERE iv.id_inventario IS NULL) AS sin_veh,
   (SELECT COUNT(DISTINCT iv.id_inventario) FROM inventario_vehiculo iv) AS con_veh");

// Movimientos en el rango si existe la tabla
$movs = $sumMov = [];
try {
  $fi = $conn->real_escape_string($fecha_inicio.' 00:00:00');
  $ff = $conn->real_escape_string($fecha_fin.' 23:59:59');
  $movs = qall($conn, "SELECT id_inventario, tipo, cantidad, referencia, comentario, fecha
                        FROM inventario_movimiento
                        WHERE fecha BETWEEN '{$fi}' AND '{$ff}'
                        ORDER BY fecha DESC, id DESC");
  $sumMov = qall($conn, "SELECT tipo, COUNT(*) AS n, SUM(cantidad) AS qty
                          FROM inventario_movimiento
                          WHERE fecha BETWEEN '{$fi}' AND '{$ff}'
                          GROUP BY tipo");
} catch (Throwable $e) {}

?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Estadísticas de Inventario</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" type="image/png" href="/Pedidos_GA/Img/Botones%20entregas/ICONOSPAG/ICONOPEDIDOS.png">
  <link rel="stylesheet" href="../styles.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body{background:#f6f7fb;color:#0f172a;font-family:system-ui,-apple-system,Segoe UI,Roboto,Inter,Arial,sans-serif}
    .wrap{display:flex;min-height:100vh}
    .content{flex:1;padding:18px clamp(12px,2vw,24px);margin-left:var(--sidebar-width,7%)}
    .ga-card{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:16px;box-shadow:0 6px 20px rgba(17,24,39,.08);margin-bottom:16px}
    .row{display:grid;gap:12px;grid-template-columns:repeat(4,minmax(180px,1fr))}
    .kpi{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:12px}
    .kpi h3{margin:0 0 6px;font-weight:700;font-size:1rem;color:#111827}
    .kpi .n{font-size:1.4rem;font-weight:800}
    table{width:100%;border-collapse:collapse}
    th,td{border-top:1px solid #e5e7eb;padding:8px;text-align:left}
    thead th{background:#f3f4f6}
  </style>
</head>
<body>
  <div class="wrap">
    <div class="sidebar">
      <ul>
        <li><a href="inventario.php">Inventario</a></li>
        <li class="corner-left-bottom"><a href="../Servicios/Servicios.php"><img src="/Pedidos_GA/Img/Botones%20entregas/Usuario/VOLVAZ.png" alt="Volver" style="max-width:35%;height:auto;"></a></li>
      </ul>
    </div>
    <main class="content">
      <div class="ga-card" style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
        <div>
          <h2 style="margin:0">Estadísticas de inventario</h2>
          <div style="color:#64748b">Filtra movimientos por fecha para análisis.</div>
        </div>
        <form method="get" style="display:flex; gap:8px; align-items:center;">
          <label>Inicio <input type="date" name="inicio" value="<?=htmlspecialchars($fecha_inicio)?>"></label>
          <label>Fin <input type="date" name="fin" value="<?=htmlspecialchars($fecha_fin)?>"></label>
          <button class="ga-btn secondary" type="submit">Aplicar</button>
        </form>
      </div>
      <div class="ga-card">
        <h2 style="margin:0 0 8px">Estadísticas de inventario</h2>
        <div class="row">
          <div class="kpi"><h3>SKUs</h3><div class="n"><?=(int)($sum['skus']??0)?></div></div>
          <div class="kpi"><h3>Unidades totales</h3><div class="n"><?=number_format((float)($sum['total_unidades']??0))?></div></div>
          <div class="kpi"><h3>Valor total</h3><div class="n">$<?=number_format((float)($sum['total_valor']??0),2)?></div></div>
          <div class="kpi"><h3>Con stock bajo</h3><div class="n"><?=(int)($sum['bajos']??0)?></div></div>
        </div>
      </div>

      <div class="ga-card">
        <h3 style="margin:0 0 8px">Distribución por unidad</h3>
        <div style="max-width:820px"><canvas id="unidades"></canvas></div>
        <script>
          const D_UNI = <?=json_encode($porUnidad, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)?>;
          const ctxU = document.getElementById('unidades').getContext('2d');
          new Chart(ctxU,{type:'bar',data:{labels:D_UNI.map(x=>x.unidad),datasets:[
            {label:'Valor',data:D_UNI.map(x=>Number(x.valor||0)),backgroundColor:'rgba(2,132,199,.6)'},
            {label:'Unidades',data:D_UNI.map(x=>Number(x.unidades||0)),backgroundColor:'rgba(99,102,241,.5)'}
          ]}});
        </script>
      </div>

      <div class="ga-card">
        <h3 style="margin:0 0 8px">Compatibilidad con vehículos</h3>
        <p style="margin:0;color:#475569">Con mapeo a vehículos: <b><?=(int)($compat['con_veh']??0)?></b> &nbsp; | &nbsp; Sin mapeo: <b><?=(int)($compat['sin_veh']??0)?></b></p>
      </div>

      <div class="ga-card">
        <h3 style="margin:0 0 8px">Top 10 por valor</h3>
        <div class="table-wrap">
          <table>
            <thead><tr><th>ID</th><th>Nombre</th><th>Marca</th><th>Modelo</th><th>Cantidad</th><th>Costo</th><th>Valor</th></tr></thead>
            <tbody>
              <?php foreach($topValor as $r): ?>
                <tr>
                  <td><?=$r['id']?></td>
                  <td><?=htmlspecialchars($r['nombre'])?></td>
                  <td><?=htmlspecialchars((string)$r['marca'])?></td>
                  <td><?=htmlspecialchars((string)$r['modelo'])?></td>
                  <td><?=number_format((float)$r['cantidad'])?></td>
                  <td>$<?=number_format((float)$r['costo'],2)?></td>
                  <td>$<?=number_format((float)$r['valor'],2)?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="ga-card">
        <h3 style="margin:0 0 8px">Top 10 con stock bajo</h3>
        <div class="table-wrap">
          <table>
            <thead><tr><th>ID</th><th>Nombre</th><th>Marca</th><th>Modelo</th><th>Cantidad</th><th>Stock mínimo</th></tr></thead>
            <tbody>
              <?php foreach($topLow as $r): ?>
                <tr>
                  <td><?=$r['id']?></td>
                  <td><?=htmlspecialchars($r['nombre'])?></td>
                  <td><?=htmlspecialchars((string)$r['marca'])?></td>
                  <td><?=htmlspecialchars((string)$r['modelo'])?></td>
                  <td><?=number_format((float)$r['cantidad'])?></td>
                  <td><?=number_format((float)$r['stock_minimo'])?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="ga-card">
        <h3 style="margin:0 0 8px">Resumen de movimientos (<?=htmlspecialchars($fecha_inicio)?> a <?=htmlspecialchars($fecha_fin)?>)</h3>
        <?php if(!empty($sumMov)): ?>
        <div class="table-wrap"><table><thead><tr><th>Tipo</th><th>Número</th><th>Cantidad total</th></tr></thead><tbody>
          <?php foreach($sumMov as $s): ?>
            <tr><td><?=htmlspecialchars($s['tipo'])?></td><td><?= (int)$s['n'] ?></td><td><?= number_format((float)$s['qty']) ?></td></tr>
          <?php endforeach; ?>
        </tbody></table></div>
        <?php else: ?>
          <p style="margin:0;color:#64748b">Sin movimientos en el rango.</p>
        <?php endif; ?>
      </div>

      <?php if(!empty($movs)): ?>
      <div class="ga-card">
        <h3 style="margin:0 0 8px">Movimientos en el rango</h3>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Inventario</th><th>Tipo</th><th>Cantidad</th><th>Referencia</th><th>Comentario</th><th>Fecha</th></tr></thead>
            <tbody>
              <?php foreach($movs as $m): ?>
              <tr>
                <td><?= (int)$m['id_inventario'] ?></td>
                <td><?= htmlspecialchars($m['tipo']) ?></td>
                <td><?= number_format((float)$m['cantidad']) ?></td>
                <td><?= htmlspecialchars((string)$m['referencia']) ?></td>
                <td><?= htmlspecialchars((string)$m['comentario']) ?></td>
                <td><?= htmlspecialchars((string)($m['fecha'] ?? '')) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
      <?php endif; ?>

    </main>
  </div>
</body>
</html>
