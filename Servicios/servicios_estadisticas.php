<?php
ini_set('session.cookie_httponly', true);
ini_set('session.cookie_secure', true);
session_name('GA');
session_start();

require_once __DIR__ . '/../Conexiones/Conexion.php';
@$conn->set_charset('utf8mb4');

$fecha_inicio = isset($_GET['inicio']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['inicio']) ? $_GET['inicio'] : date('Y-m-01');
$fecha_fin    = isset($_GET['fin'])    && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['fin'])    ? $_GET['fin']    : date('Y-m-d');

// costo por minuto de MO
$costoMin = 1.145833;
try { $r=$conn->query("SELECT valor FROM servicios_config WHERE clave='costo_minuto_mo' LIMIT 1"); if($r && $r->num_rows){ $costoMin=(float)$r->fetch_assoc()['valor']; } } catch (Throwable $e) {}

$fi = $conn->real_escape_string($fecha_inicio.' 00:00:00');
$ff = $conn->real_escape_string($fecha_fin.' 23:59:59');
$stats = [ 'ordenes'=>0, 'mat'=>0.0, 'mo'=>0.0, 'por_servicio'=>[] ];

try {
  $sql = "SELECT os.id, os.id_servicio, os.duracion_minutos,
                 (SELECT SUM(m.cantidad*COALESCE(i.costo,0)) FROM orden_servicio_material m JOIN inventario i ON i.id=m.id_inventario WHERE m.id_orden=os.id) AS costo_mat,
                 COALESCE((SELECT h.hecho_en FROM orden_servicio_hist h WHERE h.id_orden=os.id AND h.a='Completado' ORDER BY h.hecho_en DESC LIMIT 1), os.creado_en) AS fecha_fin
          FROM orden_servicio os
          WHERE (
             EXISTS(SELECT 1 FROM orden_servicio_hist h2 WHERE h2.id_orden=os.id AND h2.a='Completado' AND h2.hecho_en BETWEEN '{$fi}' AND '{$ff}')
             OR (os.creado_en BETWEEN '{$fi}' AND '{$ff}' AND NOT EXISTS(SELECT 1 FROM orden_servicio_hist h3 WHERE h3.id_orden=os.id AND h3.a='Completado'))
          )";
  if ($res=$conn->query($sql)){
    while($r=$res->fetch_assoc()){
      $mat = (float)($r['costo_mat']??0);
      $mo  = ((int)($r['duracion_minutos']??0)) * $costoMin;
      $stats['ordenes']++;
      $stats['mat'] += $mat;
      $stats['mo']  += $mo;
      $sid = (int)($r['id_servicio']??0);
      if(!isset($stats['por_servicio'][$sid])) $stats['por_servicio'][$sid] = ['count'=>0,'mat'=>0.0,'mo'=>0.0];
      $stats['por_servicio'][$sid]['count']++;
      $stats['por_servicio'][$sid]['mat'] += $mat;
      $stats['por_servicio'][$sid]['mo']  += $mo;
    }
  }
} catch (Throwable $e) {}

// Nombres de servicios para mostrar en el Top por costo
$srvNames = [];
if (!empty($stats['por_servicio'])) {
  $ids = array_keys($stats['por_servicio']);
  $ids = array_values(array_filter(array_map('intval',$ids), fn($x)=>$x>0));
  if (!empty($ids)) {
    $in = implode(',', $ids);
    try {
      $res = $conn->query("SELECT id, nombre FROM servicios WHERE id IN ($in)");
      if ($res) { while($r=$res->fetch_assoc()){ $srvNames[(int)$r['id']] = (string)$r['nombre']; } }
    } catch (Throwable $e) {}
  }
}

// Acumulado por sucursal: ordenes, vehiculos distintos, materiales y MO
$porSucursal = [];
try {
  $sqlSuc = "SELECT v.Sucursal AS suc,
                    COUNT(*) AS ordenes,
                    COUNT(DISTINCT v.id_vehiculo) AS vehiculos,
                    SUM(COALESCE(os.duracion_minutos,0)) AS minutos,
                    SUM( (
                      SELECT COALESCE(SUM(m.cantidad*COALESCE(i.costo,0)),0)
                      FROM orden_servicio_material m
                      JOIN inventario i ON i.id=m.id_inventario
                      WHERE m.id_orden = os.id
                    )) AS mat
             FROM orden_servicio os
             JOIN vehiculos v ON v.id_vehiculo = os.id_vehiculo
             WHERE (
               EXISTS(SELECT 1 FROM orden_servicio_hist h2 WHERE h2.id_orden=os.id AND h2.a='Completado' AND h2.hecho_en BETWEEN '{$fi}' AND '{$ff}')
               OR (os.creado_en BETWEEN '{$fi}' AND '{$ff}' AND NOT EXISTS(SELECT 1 FROM orden_servicio_hist h3 WHERE h3.id_orden=os.id AND h3.a='Completado'))
             )
             GROUP BY v.Sucursal
             ORDER BY v.Sucursal";
  if ($rs = $conn->query($sqlSuc)) {
    while($r = $rs->fetch_assoc()){
      $suc = (string)($r['suc'] ?? '');
      $ord = (int)($r['ordenes'] ?? 0);
      $veh = (int)($r['vehiculos'] ?? 0);
      $min = (float)($r['minutos'] ?? 0);
      $mat = (float)($r['mat'] ?? 0);
      $porSucursal[] = [
        'suc'=>$suc,
        'ordenes'=>$ord,
        'vehiculos'=>$veh,
        'mat'=>$mat,
        'mo'=>$min * $costoMin,
        'total'=>$mat + ($min * $costoMin)
      ];
    }
  }
} catch (Throwable $e) {}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Estadísticas de Servicios</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" type="image/png" href="/Pedidos_GA/Img/Botones%20entregas/ICONOSPAG/ICONOPEDIDOS.png">
  <link rel="stylesheet" href="../styles.css">
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
  <script>
    document.addEventListener("DOMContentLoaded", function () {
    

      var iconoAgregar = document.querySelector(".icono-agregar_servicio");
      if (iconoAgregar) {
        var imgNormalAgregar = "/Pedidos_GA/Img/SVG/CrearSerN.svg";
        var imgHoverAgregar  = "/Pedidos_GA/Img/SVG/CrearSerB.svg";
        iconoAgregar.addEventListener("mouseover", function(){ this.src = imgHoverAgregar; });
        iconoAgregar.addEventListener("mouseout",  function(){ this.src = imgNormalAgregar; });
      }
    });
  </script>
  <div class="wrap">
    <div class="sidebar">
      <ul>
        <li><a href="agregar_servicio.php">
                    <img src="/Pedidos_GA/Img/SVG/CrearSerN.svg" class="icono-agregar_servicio sidebar-icon" alt="Agregar">

        </a>
      </li>
        <li class="corner-left-bottom"><a href="../Servicios/Servicios.php"><img src="/Pedidos_GA/Img/Botones%20entregas/Usuario/VOLVAZ.png" alt="Volver" style="max-width:35%;height:auto;"></a></li>
      </ul>
    </div>
    <main class="content">
      <div class="ga-card" style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
        <div>
          <h2 style="margin:0">Estadísticas de servicios</h2>
          <div style="color:#64748b">Rango: <b><?=htmlspecialchars($fecha_inicio)?></b> a <b><?=htmlspecialchars($fecha_fin)?></b>. Mano de obra y materiales se muestran por separado.</div>
        </div>
        <form method="get" style="display:flex; gap:8px; align-items:center;">
          <label>Inicio <input type="date" name="inicio" value="<?=htmlspecialchars($fecha_inicio)?>"></label>
          <label>Fin <input type="date" name="fin" value="<?=htmlspecialchars($fecha_fin)?>"></label>
          <button class="ga-btn secondary" type="submit">Aplicar</button>
        </form>
      </div>

      <div class="ga-card row">
        <div class="kpi"><h3>Órdenes</h3><div class="n"><?=$stats['ordenes']?></div></div>
        <div class="kpi"><h3>Costo materiales</h3><div class="n">$<?=number_format($stats['mat'],2)?></div></div>
        <div class="kpi"><h3>Mano de obra</h3><div class="n">$<?=number_format($stats['mo'],2)?></div></div>
        <div class="kpi"><h3>Total</h3><div class="n">$<?=number_format($stats['mat']+$stats['mo'],2)?></div></div>
      </div>

      <?php if(!empty($porSucursal)): ?>
      <div class="ga-card">
        <h3 style="margin:0 0 8px">Costos por sucursal</h3>
        <div class="table-wrap"><table>
          <thead><tr><th>Sucursal</th><th>Órdenes</th><th>Vehículos</th><th>Materiales</th><th>Mano de obra</th><th>Total</th></tr></thead>
          <tbody>
            <?php foreach($porSucursal as $r): ?>
              <tr>
                <td><?= htmlspecialchars($r['suc']) ?></td>
                <td><?= (int)$r['ordenes'] ?></td>
                <td><?= (int)$r['vehiculos'] ?></td>
                <td>$<?= number_format((float)$r['mat'],2) ?></td>
                <td>$<?= number_format((float)$r['mo'],2) ?></td>
                <td>$<?= number_format((float)$r['total'],2) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table></div>
      </div>
      <?php endif; ?>

      <?php if(!empty($stats['por_servicio'])): $bySrv=$stats['por_servicio']; arsort($bySrv); ?>
      <div class="ga-card">
        <h3 style="margin:0 0 8px">Top servicios por costo</h3>
        <div class="table-wrap"><table><thead><tr><th>Servicio</th><th>ID</th><th>Órdenes</th><th>Materiales</th><th>Mano de obra</th><th>Total</th></tr></thead><tbody>
          <?php foreach($bySrv as $sid=>$d): $name = $srvNames[$sid] ?? ('ID '.$sid); ?>
            <tr><td><?=htmlspecialchars($name)?></td><td><?=$sid?></td><td><?=$d['count']?></td><td>$<?=number_format($d['mat'],2)?></td><td>$<?=number_format($d['mo'],2)?></td><td>$<?=number_format($d['mat']+$d['mo'],2)?></td></tr>
          <?php endforeach; ?>
        </tbody></table></div>
      </div>
      <?php endif; ?>

    </main>
  </div>
</body>
</html>
