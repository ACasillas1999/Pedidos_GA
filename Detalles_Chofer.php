<?php
// Iniciar la sesión de forma segura
ini_set('session.cookie_httponly', true);
ini_set('session.cookie_secure', true);
session_name("GA");
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
  header("location: /Pedidos_GA/Sesion/login.html");
  exit;
}

require_once __DIR__ . "/Conexiones/Conexion.php";

$sucursal = $_SESSION["Sucursal"];
$rol      = $_SESSION["Rol"];

// 1) Utilidades
function e($s)
{
  return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
function soloDigitos($s)
{
  return preg_replace('/\D+/', '', (string)$s);
}

$idChofer = isset($_GET['id']) ? max(0, (int)$_GET['id']) : 0;
if ($idChofer <= 0) {
  echo '<p>Falta el parámetro ?id.</p>';
  return;
}

// 2) Traer chofer
$ch = null;
$stmt = $conn->prepare("SELECT ID, username, Sucursal, Numero, Estado, foto_perfil FROM choferes WHERE ID=?");
$stmt->bind_param("i", $idChofer);
$stmt->execute();
$res = $stmt->get_result();
$ch  = $res->fetch_assoc();
$stmt->close();

if (!$ch) {
  echo '<p>No se encontró el chofer solicitado.</p>';
  return;
}

function mins_human(int $m): string
{
  if ($m < 0) return '—';
  $d = intdiv($m, 1440);
  $m -= $d * 1440;
  $h = intdiv($m, 60);
  $m -= $h * 60;
  $out = [];
  if ($d) $out[] = "{$d}d";
  if ($h) $out[] = "{$h}h";
  if ($m || !$out) $out[] = "{$m}m";
  return implode(' ', $out);
}


$nombreChofer = (string)$ch['username'];
$telRaw       = (string)$ch['Numero'];
$telDigits    = soloDigitos($telRaw);
$telWa        = (strpos($telDigits, '52') === 0 ? $telDigits : '52' . $telDigits); // MX por defecto
$iniciales    = mb_strtoupper(mb_substr($nombreChofer, 0, 1));

// 3) KPIs rápidos
// Total de pedidos asignados
$stmt = $conn->prepare("SELECT COUNT(*) AS t FROM pedidos WHERE CHOFER_ASIGNADO = ?");
$stmt->bind_param("s", $nombreChofer);
$stmt->execute();
$totalAsignados = (int)($stmt->get_result()->fetch_assoc()['t'] ?? 0);
$stmt->close();

// Pedidos por ESTADO
$porEstado = [];
$stmt = $conn->prepare("SELECT ESTADO, COUNT(*) AS t FROM pedidos WHERE CHOFER_ASIGNADO=? GROUP BY ESTADO ORDER BY t DESC");
$stmt->bind_param("s", $nombreChofer);
$stmt->execute();
$r = $stmt->get_result();
while ($row = $r->fetch_assoc()) {
  $porEstado[] = $row;
}
$stmt->close();

// Últimos pedidos
$pedidosRecientes = [];
$stmt = $conn->prepare("
  SELECT ID, SUCURSAL, ESTADO, FACTURA, NOMBRE_CLIENTE, DIRECCION,
         FECHA_RECEPCION_FACTURA, FECHA_ENTREGA_CLIENTE
  FROM pedidos
  WHERE CHOFER_ASIGNADO=?
  ORDER BY COALESCE(FECHA_ENTREGA_CLIENTE, FECHA_RECEPCION_FACTURA) DESC
  LIMIT 10");
$stmt->bind_param("s", $nombreChofer);
$stmt->execute();
$r = $stmt->get_result();
while ($row = $r->fetch_assoc()) {
  $pedidosRecientes[] = $row;
}
$stmt->close();

// Vehículos (no hay relación directa chofer↔vehículo en la BD mostrada; mostramos los de su Sucursal)
$vehiculos = [];
$stmt = $conn->prepare("SELECT id_vehiculo, numero_serie, placa, tipo, Km_Actual FROM vehiculos WHERE id_chofer_asignado=? ");
$stmt->bind_param("i", $idChofer);
$stmt->execute();
$r = $stmt->get_result();
while ($row = $r->fetch_assoc()) {
  $vehiculos[] = $row;
}
$stmt->close();

// --- Pedidos completos del chofer para trabajar en memoria (desde 2024-01-01 para empatar con el slider) ---
$pedidosAll = [];
$stmt = $conn->prepare("
  SELECT
    ID, ESTADO, SUCURSAL, FECHA_RECEPCION_FACTURA, FECHA_ENTREGA_CLIENTE,
    CHOFER_ASIGNADO, VENDEDOR, FACTURA, DIRECCION, NOMBRE_CLIENTE, CONTACTO
  FROM pedidos
  WHERE CHOFER_ASIGNADO = ?
    AND DATE(COALESCE(FECHA_ENTREGA_CLIENTE, FECHA_RECEPCION_FACTURA)) >= '2024-01-01'
  ORDER BY COALESCE(FECHA_ENTREGA_CLIENTE, FECHA_RECEPCION_FACTURA) ASC
");
$stmt->bind_param("s", $nombreChofer);
$stmt->execute();
$r = $stmt->get_result();
while ($row = $r->fetch_assoc()) {
  $pedidosAll[] = $row;
}
$stmt->close();

// --- Historial de estados de los pedidos del chofer (desde 2024-01-01) ---
$eventosAll = [];
$stmt = $conn->prepare("
  SELECT 
    e.ID_Pedido,
    UPPER(TRIM(e.Estado)) AS Estado,
    e.Fecha,
    e.Hora
  FROM estadopedido e
  INNER JOIN pedidos p ON p.ID = e.ID_Pedido
  WHERE p.CHOFER_ASIGNADO = ?
    AND e.Fecha >= '2024-01-01'
");
$stmt->bind_param("s", $nombreChofer);
$stmt->execute();
$r = $stmt->get_result();
while ($row = $r->fetch_assoc()) {
  $eventosAll[] = $row;
}
$stmt->close();

// --- Historial de vehículos tomados por el chofer ---
$histVeh = [];
$stmt = $conn->prepare("
  SELECT
  hc.id,
  hc.id_vehiculo,
  hc.fecha_inicio,
  hc.fecha_fin,
  TIMESTAMPDIFF(MINUTE, hc.fecha_inicio, IFNULL(hc.fecha_fin, NOW())) AS minutos_total,
  ch.username   AS chofer_nombre,
  ch.Sucursal   AS sucursal_chofer,
  v.placa, v.numero_serie, v.tipo
FROM historial_conductores hc
JOIN choferes  ch ON ch.ID = hc.id_chofer
LEFT JOIN vehiculos v ON v.id_vehiculo = hc.id_vehiculo
WHERE ch.ID = ?
ORDER BY hc.id DESC
LIMIT 100;

");
$stmt->bind_param("i", $idChofer);
$stmt->execute();
$r = $stmt->get_result();
while ($row = $r->fetch_assoc()) {
  $histVeh[] = $row;
}
$stmt->close();

// Observaciones del checklist vehicular hechas por el chofer
$observacionesChofer = [];
$stmt = $conn->prepare("
  SELECT
    cv.id,
    cv.id_vehiculo,
    v.placa,
    v.numero_serie,
    cv.fecha_inspeccion,
    cv.kilometraje,
    cv.seccion,
    cv.item,
    cv.calificacion,
    cv.observaciones_rotulado,
    cv.resuelto,
    cv.fecha_resolucion
  FROM checklist_vehicular cv
  LEFT JOIN vehiculos v ON v.id_vehiculo = cv.id_vehiculo
  WHERE cv.id_chofer = ?
  ORDER BY cv.fecha_inspeccion DESC, cv.id DESC
  LIMIT 200
");
$stmt->bind_param("i", $idChofer);
$stmt->execute();
$r = $stmt->get_result();
while ($row = $r->fetch_assoc()) {
  $observacionesChofer[] = $row;
}
$stmt->close();

?>
<!DOCTYPE html>
<html>
<script>
  window.PEDIDOS_CHOFER = <?= json_encode($pedidosAll, JSON_UNESCAPED_UNICODE) ?>;
  window.EVENTOS_PEDIDOS = <?= json_encode($eventosAll, JSON_UNESCAPED_UNICODE) ?>;
</script>


<head>
  <title>Detalles Chofer</title>
  <meta charset="utf-8">
  <link rel="stylesheet" href="styles.css">
  <link rel="icon" type="image/png" href="/Img/Botones%20entregas/ICONOSPAG/ICONOPEDIDOS.png">
  <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
  <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/dom-to-image/2.6.0/dom-to-image.min.js"></script>

  <style>
    /* ===== Estilos del perfil ===== */
    .driver-profile {
      position: relative;
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, .08);
      overflow: hidden;
      font-family: system-ui, Segoe UI, Roboto, Arial, sans-serif
    }

    .driver-cover {
      height: 180px;
      background: linear-gradient(135deg, #0a66c290, #0f172a);
      position: relative
    }

    .driver-avatar {
      position: absolute;
      left: 24px;
      bottom: -48px;
      width: 96px;
      height: 96px;
      border-radius: 999px;
      background: #e2e8f0;
      display: grid;
      place-items: center;
      font-size: 40px;
      font-weight: 700;
      color: #0f172a;
      border: 5px solid #fff;
      user-select: none
    }

    .driver-head {
      padding: 64px 24px 16px 24px;
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
      align-items: center;
      justify-content: space-between
    }

    .driver-title {
      display: flex;
      gap: 14px;
      align-items: center
    }

    .driver-name {
      font-size: 22px;
      font-weight: 800;
      color: #0f172a;
      margin: 0
    }

    .driver-meta {
      font-size: 13px;
      color: #475569
    }

    .driver-actions a {
      display: inline-block;
      padding: 10px 14px;
      border-radius: 12px;
      border: 1px solid #e5e7eb;
      text-decoration: none;
      color: #0f172a;
      font-weight: 600
    }

    .driver-actions a+a {
      margin-left: 8px
    }

    .driver-actions a.primary {
      background: #0a66c2;
      color: #fff;
      border-color: #0a66c2
    }

    .driver-stats {
      display: grid;
      grid-template-columns: repeat(4, minmax(0, 1fr));
      gap: 10px;
      padding: 0 24px 14px
    }

    .stat {
      background: #f8fafc;
      border: 1px solid #eef2f7;
      border-radius: 12px;
      padding: 12px
    }

    .stat strong {
      display: block;
      font-size: 20px
    }

    .badges {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      margin-top: 8px
    }

    .badge {
      font-size: 12px;
      background: #eef2ff;
      color: #1e3a8a;
      border-radius: 999px;
      padding: 6px 10px;
      border: 1px solid #e5e7eb
    }

    .driver-tabs {
      display: flex;
      gap: 8px;
      padding: 8px 8px 0 8px;
      border-top: 1px solid #eef2f7;
      background: #fff;
      position: sticky;
      top: 0;
      z-index: 1
    }

    .driver-tabs button {
      flex: 1;
      padding: 12px;
      border: 0;
      background: #f1f5f9;
      border-radius: 10px;
      font-weight: 700;
      color: #334155;
      cursor: pointer
    }

    .driver-tabs button.active {
      background: #0a66c2;
      color: #fff
    }

    .tab {
      display: none;
      padding: 16px 20px 24px
    }

    .tab.active {
      display: block
    }

    .table {
      width: 100%;
      border-collapse: collapse
    }

    .table th,
    .table td {
      padding: 10px;
      border-bottom: 1px solid #eef2f7;
      text-align: left;
      font-size: 14px
    }

    .table th {
      font-size: 12px;
      text-transform: uppercase;
      letter-spacing: .04em;
      color: #64748b
    }

    .kpi {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 14px
    }

    .kpi .card {
      background: #f8fafc;
      border: 1px solid #eef2f7;
      border-radius: 12px;
      padding: 12px
    }

    .vehis {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 12px
    }

    .vehi {
      border: 1px solid #eef2f7;
      background: #f8fafc;
      border-radius: 12px;
      padding: 12px
    }

    @media (max-width:900px) {
      .driver-stats {
        grid-template-columns: repeat(2, 1fr)
      }

      .vehis {
        grid-template-columns: repeat(2, 1fr)
      }
    }

    @media (max-width:600px) {
      .driver-stats {
        grid-template-columns: 1fr
      }

      .vehis {
        grid-template-columns: 1fr
      }

      .driver-actions {
        width: 100%
      }
    }

    /* Estados (si tus ESTADO vienen en mayúsculas, agrega variantes) */
    .badge.estado-Entregado,
    .badge.estado-ENTREGADO {
      background: #e7f9e7;
      color: #217a21;
      border-color: #cfeacf
    }

    .badge.estado-Pendiente,
    .badge.estado-PENDIENTE {
      background: #fff6d6;
      color: #8a6d00;
      border-color: #fde9a8
    }

    .badge.estado-Cancelado,
    .badge.estado-CANCELADO {
      background: #ffe4e6;
      color: #9f1239;
      border-color: #fecdd3
    }

    /* Gráfica */
    #barchart {
      min-height: 380px;
    }

    .driver-avatar {
      position: absolute;
      left: 24px;
      bottom: -48px;
      width: 96px;
      height: 96px;
      border-radius: 999px;
      background: #e2e8f0;
      display: grid;
      place-items: center;
      font-size: 40px;
      font-weight: 700;
      color: #0f172a;
      border: 5px solid #fff;
      user-select: none;
      cursor: pointer;
      /* <- clic para subir/cambiar */
    }

    .driver-avatar img {
      width: 100%;
      height: 100%;
      display: block;
      border-radius: 999px;
      object-fit: cover;
    }

    .driver-avatar:hover {
      box-shadow: 0 0 0 3px rgba(10, 102, 194, .25) inset;
    }
  </style>
</head>

<body>

  <script>
    document.addEventListener("DOMContentLoaded", function() {
      // Listeners seguros (solo si existen)
      var iconoAddChofer = document.querySelector(".icono-AddChofer");
      if (iconoAddChofer) {
        var imgNormalAddChoferes = "/Img/Botones%20entregas/Choferes/ADDSERVMECNA.png";
        var imgHoverCAddhoferes = "/Img/Botones%20entregas/Choferes/ADDSERVMECBLANC.png";
        iconoAddChofer.addEventListener("mouseover", function() {
          this.src = imgHoverCAddhoferes;
        });
        iconoAddChofer.addEventListener("mouseout", function() {
          this.src = imgNormalAddChoferes;
        });
      }

      var iconoVolver = document.querySelector(".icono-Volver");
      if (iconoVolver) {
        var imgNormalVolver = "/Img/Botones%20entregas/Usuario/VOLVAZ.png";
        var imgHoverVolver = "/Img/Botones%20entregas/Usuario/VOLVNA.png";
        iconoVolver.addEventListener("mouseover", function() {
          this.src = imgHoverVolver;
        });
        iconoVolver.addEventListener("mouseout", function() {
          this.src = imgNormalVolver;
        });
      }

      var iconoEstadisticas = document.querySelector(".icono-estadisticas");
      if (iconoEstadisticas) {
        var imgNormalEstadisticas = "/Img/Botones%20entregas/Pedidos_GA/ESTNA2.png";
        var imgHoverEstadisticas = "/Img/Botones%20entregas/Pedidos_GA/ESTBL2.png";
        iconoEstadisticas.addEventListener("mouseover", function() {
          this.src = imgHoverEstadisticas;
        });
        iconoEstadisticas.addEventListener("mouseout", function() {
          this.src = imgNormalEstadisticas;
        });
      }
    });
  </script>

  <div class="sidebar">
    <ul>



      <li class="corner-left-bottom"><a href="vehiculos.php">
          <img src="/Img/Botones%20entregas/Usuario/VOLVAZ.png" alt="Volver" class="icono-Volver" style="max-width: 35%; height: auto;">
        </a></li>
    </ul>
  </div>

  <div class="container">
    <div class="driver-profile">
      <div class="driver-cover">
        <div class="driver-avatar" id="avatar" title="Clic para subir/cambiar foto de <?= e($nombreChofer) ?>">
          <?php if (!empty($ch['foto_perfil'])): ?>
            <img id="avatar-img" src="<?= e($ch['foto_perfil']) ?>" alt="Foto de <?= e($nombreChofer) ?>">
          <?php else: ?>
            <span id="avatar-initial"><?= e($iniciales) ?></span>
          <?php endif; ?>
        </div>

        <form id="upload-form" action="subir_foto_chofer.php" method="post" enctype="multipart/form-data" style="display:none">
          <input type="hidden" name="id_chofer" value="<?= (int)$ch['ID'] ?>">
          <input type="hidden" name="csrf" value="<?php $_SESSION['csrf'] = bin2hex(random_bytes(16));
                                                  echo e($_SESSION['csrf']); ?>">
          <input type="file" name="foto" id="file-input" accept="image/*">
        </form>
      </div>

      <div class="driver-head">
        <div class="driver-title">
          <div>
            <h2 class="driver-name"><?= e($nombreChofer) ?></h2>
            <div class="driver-meta">
              Sucursal: <strong><?= e($ch['Sucursal']) ?></strong> ·
              Estado: <strong><?= e($ch['Estado']) ?></strong> ·
              ID: <?= (int)$ch['ID'] ?>
            </div>
          </div>
        </div>
        <div class="driver-actions">
          <?php if ($telDigits): ?>
            <a class="primary" href="tel:<?= e($telDigits) ?>">Llamar</a>
            <a href="https://wa.me/<?= e($telWa) ?>?text=Hola%20<?= urlencode($nombreChofer) ?>,%20te%20escribo%20de%20GA.">WhatsApp</a>
          <?php else: ?>
            <a class="primary" href="#" onclick="return false;" title="Sin teléfono">Sin teléfono</a>
          <?php endif; ?>
          <a href="editar_chofer.php?id=<?= (int)$ch['ID'] ?>">Editar</a>
        </div>
      </div>

      <div class="driver-stats">
        <div class="stat">
          <strong><?= number_format($totalAsignados) ?></strong>
          <span>Total de pedidos asignados</span>
        </div>
        <div class="stat">
          <strong><?= e($ch['Sucursal']) ?></strong>
          <span>Sucursal</span>
        </div>
        <div class="stat">
          <strong><?= $telDigits ? e($telDigits) : '—' ?></strong>
          <span>Teléfono</span>
        </div>
        <div class="stat">
          <span>Estados de sus pedidos</span>
          <div class="badges">
            <?php if (!$porEstado): ?>
              <span class="badge">Sin movimientos</span>
              <?php else: foreach ($porEstado as $pe): ?>
                <span class="badge estado-<?= e($pe['ESTADO']) ?>"><?= e($pe['ESTADO']) ?> · <?= (int)$pe['t'] ?></span>
            <?php endforeach;
            endif; ?>
          </div>
        </div>
      </div>

      <div class="driver-tabs">
        <button class="active" data-tab="info">Información</button>
        <button data-tab="pedidos">Pedidos recientes</button>
        <button data-tab="vehiculos">Vehículos (sucursal)</button>
        <button data-tab="historial">Historial vehículos</button>
        <button data-tab="observaciones">Observaciones</button>
        <button data-tab="contacto">Contacto</button>
      </div>


      <section id="tab-info" class="tab active">
        <div class="kpi">
          <div class="card">
            <strong>Datos del chofer</strong>
            <div>Nombre de usuario: <b><?= e($nombreChofer) ?></b></div>
            <div>Sucursal: <b><?= e($ch['Sucursal']) ?></b></div>
            <div>Estado: <b><?= e($ch['Estado']) ?></b></div>
            <div>Teléfono: <b><?= $telDigits ? e($telDigits) : 'No registrado' ?></b></div>
          </div>
          <div class="card">
            <strong>Resumen</strong>
            <div>Pedidos asignados: <b><?= number_format($totalAsignados) ?></b></div>
            <hr>
            </hr>
            <div>
              <?php foreach ($porEstado as $pe): ?>
                <span class="badge estado-<?= e($pe['ESTADO']) ?>"><?= e($pe['ESTADO']) ?>: <?= (int)$pe['t'] ?></span>
              <?php endforeach;
              if (!$porEstado) echo 'Sin datos'; ?>
            </div>
          </div>
        </div>
      </section>

      <section id="tab-pedidos" class="tab">
        <?php if (!$pedidosRecientes): ?>
          <p>No hay pedidos recientes para este chofer.</p>
        <?php else: ?>
          <table class="table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Factura</th>
                <th>Cliente</th>
                <th>Estado</th>
                <th>Recibida</th>
                <th>Entrega</th>
                <th>Dirección</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($pedidosRecientes as $p): ?>
                <tr>
                  <td>#<?= (int)$p['ID'] ?></td>
                  <td><?= e($p['FACTURA'] ?: '—') ?></td>
                  <td><?= e($p['NOMBRE_CLIENTE'] ?: '—') ?></td>
                  <td><span class="badge estado-<?= e($p['ESTADO']) ?>"><?= e($p['ESTADO']) ?></span></td>
                  <td><?= e($p['FECHA_RECEPCION_FACTURA'] ?: '—') ?></td>
                  <td><?= e($p['FECHA_ENTREGA_CLIENTE'] ?: '—') ?></td>
                  <td><?= e($p['DIRECCION'] ?: '—') ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </section>

      <section id="tab-vehiculos" class="tab">
        <?php if (!$vehiculos): ?>
          <p>No hay vehículos registrados en la sucursal <b><?= e($ch['Sucursal']) ?></b>.</p>
        <?php else: ?>
          <?php
          // Toma ?veh= de la URL o usa el primero de la lista
          $vehInicial = $_GET['veh']
            ?? ($vehiculos[0]['placa'] ?? $vehiculos[0]['numero_serie'] ?? '');
          $iframeURL = 'https://spot.resser.com/admin/#' . rawurlencode($vehInicial);
          ?>
          <style>
            .vehiculos-wrap {
              display: grid;
              grid-template-columns: 320px 1fr;
              gap: 16px
            }

            .veh-list {
              max-height: 72vh;
              overflow: auto;
              padding: 8px;
              border-radius: 12px;
              background: #fff
            }

            .veh-card {
              padding: 10px 12px;
              border: 1px solid #e5e7eb;
              border-radius: 10px;
              margin-bottom: 8px;
              cursor: pointer
            }

            .veh-card.active {
              outline: 2px solid #2d6cdf;
              background: #eef4ff
            }

            .map-frame {
              width: 100%;
              height: 72vh;
              border: 0;
              border-radius: 12px
            }

            @media (max-width: 1024px) {
              .vehiculos-wrap {
                grid-template-columns: 1fr
              }
            }
          </style>

          <div class="vehiculos-wrap">
            <div id="vehiculos-list" class="veh-list">
              <?php foreach ($vehiculos as $v):
                $busqueda = $v['placa'] ?: ($v['numero_serie'] ?? '');
                $idv = (int)$v['id_vehiculo'];
              ?>
                <div
                  class="veh-card"
                  data-busqueda="<?= e($busqueda) ?>"
                  data-id="<?= $idv ?>"
                  role="button" tabindex="0"
                  onclick="location.href='/Pedidos_GA/detalles_vehiculo.php?id=<?= $idv ?>'">
                  <div><b><?= e($v['tipo'] ?: 'Vehículo') ?></b> · ID <?= $idv ?></div>
                  <div>Placa: <b><?= e($v['placa'] ?: '—') ?></b></div>
                  <div>N° Serie: <b><?= e($v['numero_serie'] ?: '—') ?></b></div>
                  <div>Km actual: <b><?= number_format((int)$v['Km_Actual']) ?></b></div>
                </div>
              <?php endforeach; ?>
            </div>


            <!-- OJO: admin/#<vehiculo>. El autoselector leerá ese hash dentro del iframe -->
            <iframe id="resserMap"
              src="https://spot.resser.com/admin/lastPosition#JM7542A"
              style="width:100%;height:72vh;border:0;border-radius:12px"
              loading="lazy"></iframe>

          </div>

          <script>
            (function() {
              const iframe = document.getElementById('resserMap');
              const cards = Array.from(document.querySelectorAll('#vehiculos-list .veh-card'));
              // marcar activo y cambiar hash del iframe al hacer click
              function setVeh(term) {
                cards.forEach(c => c.classList.remove('active'));
                const card = cards.find(c => c.dataset.busqueda === term) || cards[0];
                if (card) card.classList.add('active');
                iframe.src = 'https://spot.resser.com/admin/lastPosition#' + encodeURIComponent(term);
              }
              // inicial
              const ini = new URL(location.href).searchParams.get('veh') || (cards[0]?.dataset.busqueda || '');
              if (ini) setVeh(ini);
              // clicks
              cards.forEach(c => c.addEventListener('click', () => setVeh(c.dataset.busqueda)));
            })();
          </script>
        <?php endif; ?>
      </section>

      <script>
        // ==UserScript==
        // @name         Resser: autoselect por hash (soporta iframes)
        // @match        https://spot.resser.com/admin/lastPosition*
        // @grant        none
        // @run-at       document-idle
        // @inject-into  page
        // ==/UserScript==

        (function() {
          const wanted = decodeURIComponent(location.hash.replace(/^#/, '')).trim();
          if (!wanted) return;

          // Set value "a la React" (para que la SPA detecte el cambio)
          function setReactInputValue(el, val) {
            const desc = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value');
            desc && desc.set.call(el, val);
            el.dispatchEvent(new Event('input', {
              bubbles: true
            }));
            el.dispatchEvent(new Event('change', {
              bubbles: true
            }));
            el.dispatchEvent(new KeyboardEvent('keydown', {
              key: 'Enter',
              code: 'Enter',
              keyCode: 13,
              which: 13,
              bubbles: true
            }));
          }

          function clickCandidate(text) {
            const needle = text.toLowerCase();
            const nodes = Array.from(document.querySelectorAll('div[role="button"][title],div[role="button"][aria-label]'));
            const hit = nodes.find(el => ((el.getAttribute('title') || el.getAttribute('aria-label') || '').toLowerCase().includes(needle)));
            if (hit) {
              ['mouseenter', 'mousedown', 'mouseup', 'click'].forEach(t => hit.dispatchEvent(new MouseEvent(t, {
                bubbles: true
              })));
              // opcional: centrar mapa
              setTimeout(() => {
                const center = Array.from(document.querySelectorAll('button, a, div')).find(x => /centrar mapa/i.test(x.textContent || ''));
                center && center.click();
              }, 400);
              return true;
            }
            return false;
          }

          function tryInit() {
            const input = document.querySelector('input.searchBox, input[placeholder*="Buscar tu vehí"], input[type="search"]');
            if (!input) return false;
            setReactInputValue(input, wanted);

            // Si la app usa botón de búsqueda, intenta pulsarlo
            document.querySelector('.searchButton, button.searchButton')?.click();

            // Varios intentos para clicar el marker que coincide
            let tries = 0;
            const id = setInterval(() => {
              if (clickCandidate(wanted) || ++tries > 20) clearInterval(id);
            }, 250);

            return true;
          }

          if (!tryInit()) {
            const obs = new MutationObserver(() => {
              if (tryInit()) obs.disconnect();
            });
            obs.observe(document.body, {
              childList: true,
              subtree: true
            });
            // respaldo por tiempo
            setTimeout(tryInit, 800);
          }
        })();
      </script>

      <section id="tab-historial" class="tab">
        <?php if (!$histVeh): ?>
          <p>Este chofer no tiene historial de uso de vehículos.</p>
        <?php else: ?>
          <table class="table">
            <thead>
              <tr>
                <th>#</th>
                <th>Vehículo</th>
                <th>Placa</th>
                <th>N° Serie</th>
                <th>Inicio</th>
                <th>Fin</th>
                <th>Duración</th>
                <th>Estatus</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($histVeh as $h):
                $activo = empty($h['fecha_fin']);
                $busq   = $h['placa'] ?: ($h['numero_serie'] ?? '');
                $linkMapa = '?id=' . (int)$ch['ID'] . '&tab=vehiculos' . ($busq ? '&veh=' . rawurlencode($busq) : '');
              ?>
                <tr>
                  <td><?= (int)$h['id'] ?></td>
                  <td><?= e($h['tipo'] ?: 'Vehículo') . ' · ID ' . (int)$h['id_vehiculo'] ?></td>
                  <td><?= e($h['placa'] ?: '—') ?></td>
                  <td><?= e($h['numero_serie'] ?: '—') ?></td>
                  <td><?= e($h['fecha_inicio'] ?: '—') ?></td>
                  <td><?= e($h['fecha_fin'] ?: '—') ?></td>
                  <td><?= mins_human((int)($h['minutos_total'] ?? -1)) ?></td>
                  <td>
                    <span class="badge <?= $activo ? 'estado-ACTIVO' : 'estado-ENTREGADO' ?>">
                      <?= $activo ? 'Activo' : 'Finalizado' ?>
                    </span>
                  </td>

                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </section>



      <!-- Sección de Observaciones del Chofer -->
      <section id="tab-observaciones" class="tab">
        <h3 style="margin:0 0 15px 0">Observaciones realizadas por el chofer</h3>

        <?php
        $total_obs = count($observacionesChofer);
        $obs_mal = 0;
        $obs_resueltas = 0;
        foreach ($observacionesChofer as $obs) {
          if ($obs['calificacion'] === 'Mal') $obs_mal++;
          if ($obs['resuelto']) $obs_resueltas++;
        }
        ?>

        <!-- Resumen -->
        <div style="display:flex;gap:15px;margin-bottom:20px;flex-wrap:wrap">
          <div style="background:#f0f9ff;padding:15px 20px;border-radius:10px;border-left:4px solid #0ea5e9">
            <div style="font-size:0.85rem;color:#64748b">Total inspecciones</div>
            <div style="font-size:1.5rem;font-weight:600;color:#0369a1"><?= $total_obs ?></div>
          </div>
          <div style="background:#fef2f2;padding:15px 20px;border-radius:10px;border-left:4px solid #ef4444">
            <div style="font-size:0.85rem;color:#64748b">Reportes "Mal"</div>
            <div style="font-size:1.5rem;font-weight:600;color:#dc2626"><?= $obs_mal ?></div>
          </div>
          <div style="background:#f0fdf4;padding:15px 20px;border-radius:10px;border-left:4px solid #22c55e">
            <div style="font-size:0.85rem;color:#64748b">Resueltas</div>
            <div style="font-size:1.5rem;font-weight:600;color:#15803d"><?= $obs_resueltas ?></div>
          </div>
        </div>

        <?php if ($total_obs > 0): ?>
        <!-- Controles de paginación -->
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;flex-wrap:wrap;gap:10px">
          <div style="display:flex;align-items:center;gap:10px">
            <label style="font-size:0.9rem;color:#64748b">Mostrar:</label>
            <select id="obs-per-page" style="padding:6px 10px;border:1px solid #e2e8f0;border-radius:6px;font-size:0.9rem">
              <option value="10">10</option>
              <option value="20" selected>20</option>
              <option value="50">50</option>
              <option value="100">100</option>
            </select>
          </div>
          <div id="obs-pagination-info" style="font-size:0.9rem;color:#64748b"></div>
          <div id="obs-pagination-controls" style="display:flex;gap:5px"></div>
        </div>

        <div style="overflow-x:auto">
          <table class="table" style="width:100%" id="obs-table">
            <thead>
              <tr>
                <th>Fecha</th>
                <th>Vehículo</th>
                <th>Km</th>
                <th>Sección</th>
                <th>Item</th>
                <th>Calificación</th>
                <th>Observaciones</th>
                <th>Estado</th>
              </tr>
            </thead>
            <tbody id="obs-tbody">
              <?php foreach ($observacionesChofer as $idx => $obs): ?>
              <tr class="obs-row" data-index="<?= $idx ?>">
                <td><?= date('d/m/Y H:i', strtotime($obs['fecha_inspeccion'])) ?></td>
                <td>
                  <?php if ($obs['id_vehiculo']): ?>
                    <a href="detalles_vehiculo.php?id=<?= $obs['id_vehiculo'] ?>" style="color:#0369a1;text-decoration:none">
                      <?= e($obs['placa'] ?: $obs['numero_serie'] ?: 'ID: '.$obs['id_vehiculo']) ?>
                    </a>
                  <?php else: ?>
                    -
                  <?php endif; ?>
                </td>
                <td><?= $obs['kilometraje'] ? number_format($obs['kilometraje']) : '-' ?></td>
                <td><?= e($obs['seccion']) ?></td>
                <td><?= e($obs['item']) ?></td>
                <td>
                  <?php
                  $calif = $obs['calificacion'];
                  $color = match($calif) {
                    'Bien' => '#22c55e',
                    'Mal' => '#ef4444',
                    'N/A' => '#64748b',
                    default => '#64748b'
                  };
                  ?>
                  <span style="background:<?= $color ?>22;color:<?= $color ?>;padding:4px 8px;border-radius:6px;font-size:0.85rem;font-weight:500">
                    <?= e($calif) ?>
                  </span>
                </td>
                <td style="max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis" title="<?= e($obs['observaciones_rotulado'] ?? '') ?>">
                  <?= e($obs['observaciones_rotulado'] ?: '-') ?>
                </td>
                <td>
                  <?php if ($obs['resuelto']): ?>
                    <span style="background:#22c55e22;color:#22c55e;padding:4px 8px;border-radius:6px;font-size:0.85rem">
                      Resuelto
                      <?php if ($obs['fecha_resolucion']): ?>
                        <br><small><?= date('d/m/Y', strtotime($obs['fecha_resolucion'])) ?></small>
                      <?php endif; ?>
                    </span>
                  <?php else: ?>
                    <?php if ($calif === 'Mal'): ?>
                      <span style="background:#f59e0b22;color:#f59e0b;padding:4px 8px;border-radius:6px;font-size:0.85rem">Pendiente</span>
                    <?php else: ?>
                      -
                    <?php endif; ?>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <script>
        (function() {
          const rows = document.querySelectorAll('.obs-row');
          const totalRows = rows.length;
          const perPageSelect = document.getElementById('obs-per-page');
          const info = document.getElementById('obs-pagination-info');
          const controls = document.getElementById('obs-pagination-controls');
          let currentPage = 1;
          let perPage = parseInt(perPageSelect.value);

          function render() {
            const totalPages = Math.ceil(totalRows / perPage);
            const start = (currentPage - 1) * perPage;
            const end = start + perPage;

            // Mostrar/ocultar filas
            rows.forEach((row, i) => {
              row.style.display = (i >= start && i < end) ? '' : 'none';
            });

            // Info
            const showing = Math.min(end, totalRows);
            info.textContent = `Mostrando ${start + 1}-${showing} de ${totalRows}`;

            // Controles
            let html = '';

            // Anterior
            html += `<button onclick="obsGoPage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}
                     style="padding:6px 12px;border:1px solid #e2e8f0;border-radius:6px;background:${currentPage === 1 ? '#f1f5f9' : '#fff'};cursor:${currentPage === 1 ? 'not-allowed' : 'pointer'}">
                     &laquo;
                     </button>`;

            // Páginas
            for (let i = 1; i <= totalPages; i++) {
              if (i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
                html += `<button onclick="obsGoPage(${i})"
                         style="padding:6px 12px;border:1px solid ${i === currentPage ? '#0369a1' : '#e2e8f0'};border-radius:6px;background:${i === currentPage ? '#0369a1' : '#fff'};color:${i === currentPage ? '#fff' : '#333'};cursor:pointer">
                         ${i}
                         </button>`;
              } else if (i === currentPage - 3 || i === currentPage + 3) {
                html += `<span style="padding:6px">...</span>`;
              }
            }

            // Siguiente
            html += `<button onclick="obsGoPage(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''}
                     style="padding:6px 12px;border:1px solid #e2e8f0;border-radius:6px;background:${currentPage === totalPages ? '#f1f5f9' : '#fff'};cursor:${currentPage === totalPages ? 'not-allowed' : 'pointer'}">
                     &raquo;
                     </button>`;

            controls.innerHTML = html;
          }

          window.obsGoPage = function(page) {
            const totalPages = Math.ceil(totalRows / perPage);
            if (page < 1 || page > totalPages) return;
            currentPage = page;
            render();
          };

          perPageSelect.addEventListener('change', () => {
            perPage = parseInt(perPageSelect.value);
            currentPage = 1;
            render();
          });

          render();
        })();
        </script>
        <?php else: ?>
        <div style="text-align:center;padding:40px;color:#64748b;background:#f8fafc;border-radius:10px">
          <svg style="width:48px;height:48px;margin-bottom:10px;opacity:0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
          </svg>
          <p style="margin:0;font-size:1.1rem">Este chofer no ha realizado inspecciones de vehículos</p>
        </div>
        <?php endif; ?>
      </section>

      <section id="tab-contacto" class="tab">
        <?php if ($telDigits): ?>
          <p><b>Teléfono:</b> <a href="tel:<?= e($telDigits) ?>"><?= e($telDigits) ?></a></p>
          <p><b>WhatsApp:</b> <a target="_blank" href="https://wa.me/<?= e($telWa) ?>?text=Hola%20<?= urlencode($nombreChofer) ?>,%20te%20escribo%20de%20GA.">Abrir chat</a></p>
        <?php else: ?>
          <p>Este chofer no tiene teléfono registrado.</p>
        <?php endif; ?>
      </section>
    </div>
  </div>

  <script>
    // Tabs simples
    document.querySelectorAll('.driver-tabs button').forEach(btn => {
      btn.addEventListener('click', () => {
        document.querySelectorAll('.driver-tabs button').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        btn.classList.add('active');
        const id = btn.dataset.tab;
        document.getElementById('tab-' + id).classList.add('active');
      });
    });
  </script>

  <!-- ====== Sección de gráficas y estadísticas ====== -->

  <script>
    google.charts.load('current', {
      'packages': ['corechart', 'bar']
    });
    google.charts.setOnLoadCallback(() => {
      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCharts);
      } else {
        initCharts();
      }
    });

    function getParameterByName(name) {
      var url = window.location.href;
      name = name.replace(/[\[\]]/g, "\\$&");
      var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
        results = regex.exec(url);
      if (!results) return null;
      if (!results[2]) return '';
      return decodeURIComponent(results[2].replace(/\+/g, " "));
    }

    function toYMD(ms) {
      return new Date(ms).toISOString().slice(0, 10);
    }

    function initCharts() {
      const minTs = new Date('2024-01-01').getTime() / 1000;
      const maxTs = Math.floor(Date.now() / 1000);

      $("#date-range").slider({
        range: true,
        min: minTs,
        max: maxTs,
        step: 86400,
        values: [minTs, maxTs],
        slide: function(event, ui) {
          const startDate = toYMD(ui.values[0] * 1000);
          const endDate = toYMD(ui.values[1] * 1000);
          $("#start_date").val(startDate);
          $("#end_date").val(endDate);
          drawChartAndStats();
        }
      });

      $("#start_date").val(toYMD($("#date-range").slider("values", 0) * 1000));
      $("#end_date").val(toYMD($("#date-range").slider("values", 1) * 1000));

      drawChartAndStats();
      window.addEventListener('resize', drawChartAndStats);
    }

    function drawChartAndStats() {
      const choferId = getParameterByName('id');
      const startDate = $('#start_date').val();
      const endDate = $('#end_date').val();

      $('#time_range').text('Rango de tiempo seleccionado: ' + startDate + ' - ' + endDate);
      document.getElementById('chofer-username').innerText = "Chofer: <?= e($nombreChofer) ?> (ID <?= (int)$ch['ID'] ?>)";

      const src = Array.isArray(window.PEDIDOS_CHOFER) ? window.PEDIDOS_CHOFER : [];

      // Filtrar por rango usando la fecha coalescida (Entrega o Recepción)
      const filtered = src.filter(r => {
        const ymd = String(r.FECHA_ENTREGA_CLIENTE || r.FECHA_RECEPCION_FACTURA || '').slice(0, 10);
        return ymd && ymd >= startDate && ymd <= endDate;
      });

      // ======= 1) Gráfica: Conteo por estado =======
      const counts = {};
      filtered.forEach(r => {
        const est = String(r.ESTADO || '').trim().toUpperCase();
        counts[est] = (counts[est] || 0) + 1;
      });

      const colors = {
        'ACTIVO': '#678cff',
        'CANCELADO': '#ff4642',
        'EN RUTA': '#ffa33e',
        'ENTREGADO': '#8cff8a',
        'EN TIENDA': '#faff06',
        'REPROGRAMADO': '#8b81ff',
        'REPROGRAMADOS': '#8b81ff'
      };

      const data = new google.visualization.DataTable();
      data.addColumn('string', 'Estado');
      data.addColumn('number', 'Cantidad');
      data.addColumn({
        type: 'string',
        role: 'style'
      });

      Object.keys(counts).sort((a, b) => counts[b] - counts[a]).forEach(est => {
        data.addRow([est, counts[est], colors[est] || null]);
      });

      if (data.getNumberOfRows() === 0) {
        $('#barchart').html('<p>Sin datos para el rango seleccionado.</p>');
      } else {
        const options = {
          title: 'Cantidad de Pedidos por Estado',
          height: 380,
          legend: {
            position: 'none'
          },
          chartArea: {
            left: 120,
            width: '70%',
            height: '70%'
          },
          hAxis: {
            minValue: 0
          },
          vAxis: {
            title: 'Estado'
          }
        };
        new google.visualization.BarChart(document.getElementById('barchart')).draw(data, options);
      }

      // ======= 2) KPIs =======
      const total = filtered.length;
      const entregados = counts['ENTREGADO'] || 0;
      const cancelados = counts['CANCELADO'] || 0;
      const enRuta = counts['EN RUTA'] || 0;
      const enTienda = counts['EN TIENDA'] || 0;
      const repro = (counts['REPROGRAMADO'] || 0) + (counts['REPROGRAMADOS'] || 0);
      const tasaEntrega = total ? (entregados / total * 100) : 0;

      // ======= 3) Tiempo promedio ACTIVO → ENTREGADO (usando EVENTOS_PEDIDOS) =======
      const eventos = Array.isArray(window.EVENTOS_PEDIDOS) ? window.EVENTOS_PEDIDOS : [];

      // Agrupar historial por ID de pedido
      const eventosPorPedido = {};
      eventos.forEach(e => {
        const id = Number(e.ID_Pedido);
        (eventosPorPedido[id] ||= []).push({
          ESTADO: String(e.Estado || '').trim().toUpperCase(),
          Fecha: e.Fecha,
          Hora: e.Hora
        });
      });

      function ts(fecha, hora) {
        if (!fecha) return null;
        const d = new Date((fecha + 'T' + (hora || '00:00:00')));
        return isNaN(d) ? null : d.getTime();
      }

      let sumHrs = 0,
        nHrs = 0;

      // === Tiempos por pedido (ACTIVO → ENTREGADO) en horas ===
      const tPorPedido = {};
      filtered.forEach(p => {
        const lista = (eventosPorPedido[p.ID] || []).slice()
          .sort((a, b) => ts(a.Fecha, a.Hora) - ts(b.Fecha, b.Hora));

        let tActivo = null,
          tEntregado = null;

        for (const ev of lista) {
          const t = ts(ev.Fecha, ev.Hora);
          if (t == null) continue;

          if (ev.ESTADO === 'ACTIVO') {
            tActivo = t;
          } else if (ev.ESTADO === 'ENTREGADO' && tActivo != null) {
            tEntregado = t;
            break; // primer ENTREGADO tras ACTIVO
          }
        }

        if (tEntregado != null) {
          const ymdEnt = new Date(tEntregado).toISOString().slice(0, 10);
          if (ymdEnt >= startDate && ymdEnt <= endDate) {
            const hrs = (tEntregado - tActivo) / 36e5;
            tPorPedido[p.ID] = (isFinite(hrs) && hrs >= 0) ? hrs : null;
          } else {
            tPorPedido[p.ID] = null;
          }
        } else {
          tPorPedido[p.ID] = null;
        }
      });

      // Opcional: formato legible
      function formatHours(h) {
        if (h == null) return '—';
        const totalMin = Math.round(h * 60);
        const d = Math.floor(totalMin / (60 * 24));
        const hh = Math.floor((totalMin - d * 60 * 24) / 60);
        const mm = totalMin % 60;
        if (d > 0) return `${d}d ${hh}h ${mm}m`;
        if (hh > 0) return `${hh}h ${mm}m`;
        return `${mm}m`;
      }

      // Acumular tiempos para el KPI de promedio
      Object.values(tPorPedido).forEach(h => {
        if (h != null && isFinite(h) && h >= 0) {
          sumHrs += h;
          nHrs++;
        }
      });


      const avgHrs = nHrs ? (sumHrs / nHrs) : 0;

      // Top Vendedores (por ENTREGADO en el rango)
      const vend = {};
      filtered.forEach(r => {
        if (String(r.ESTADO).trim().toUpperCase() !== 'ENTREGADO') return;
        const v = (r.VENDEDOR || '—').trim();
        vend[v] = (vend[v] || 0) + 1;
      });
      const topVend = Object.entries(vend).sort((a, b) => b[1] - a[1]).slice(0, 5);

      // Últimos pedidos del rango
      const lastRows = [...filtered].sort((A, B) => {
        const fA = new Date(A.FECHA_ENTREGA_CLIENTE || A.FECHA_RECEPCION_FACTURA || 0).getTime();
        const fB = new Date(B.FECHA_ENTREGA_CLIENTE || B.FECHA_RECEPCION_FACTURA || 0).getTime();
        return fB - fA;
      }).slice(0, 15);

      // Render en #pedidos-chofer
      const cont = document.getElementById('pedidos-chofer');
      cont.innerHTML = `
      <div class="kpis" style="display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:12px;margin-top:16px">
        ${kpiCard('Total', total)}
        ${kpiCard('Entregados', entregados)}
        ${kpiCard('Cancelados', cancelados)}
        ${kpiCard('En ruta', enRuta)}
        ${kpiCard('En tienda', enTienda)}
        ${kpiCard('Reprogramados', repro)}
      </div>

      <div class="kpis2" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin-top:12px">
        ${kpiCard('% Entregados', (tasaEntrega).toFixed(1) + '%')}
        ${kpiCard('Tiempo prom. entrega', nHrs ? (avgHrs.toFixed(1) + ' h') : '—')}
      </div>

      <div style="display:grid;grid-template-columns:1fr 2fr;gap:16px;margin-top:16px">
        <div class="card" style="background:#f8fafc;border:1px solid #eef2f7;border-radius:12px;padding:12px">
          <div style="font-weight:700;margin-bottom:8px">Top vendedores (por entregas)</div>
          ${renderTopVendedores(topVend)}
        </div>

        <div class="card" style="background:#f8fafc;border:1px solid #eef2f7;border-radius:12px;padding:12px;overflow:auto">
          <div style="font-weight:700;margin-bottom:8px">Últimos pedidos (rango)</div>
          ${renderTabla(lastRows, tPorPedido)}

        </div>
      </div>
    `;

      function kpiCard(label, value) {
        return `
        <div class="card" style="background:#fff;border:1px solid #eef2f7;border-radius:12px;padding:12px;text-align:center">
          <div style="font-size:12px;color:#64748b">${label}</div>
          <div style="font-size:22px;font-weight:800">${value}</div>
        </div>`;
      }

      function renderTopVendedores(list) {
        if (!list.length) return '<div>Sin datos</div>';
        let html = '<table class="table" style="width:100%;border-collapse:collapse;font-size:14px">';
        html += '<thead><tr><th style="text-align:left;padding:6px;border-bottom:1px solid #e5e7eb">Vendedor</th><th style="text-align:right;padding:6px;border-bottom:1px solid #e5e7eb">Entregas</th></tr></thead><tbody>';
        list.forEach(([v, c]) => {
          html += `<tr><td style="padding:6px;border-bottom:1px solid #f1f5f9">${escapeHtml(v)}</td><td style="padding:6px;text-align:right;border-bottom:1px solid #f1f5f9">${c}</td></tr>`;
        });
        html += '</tbody></table>';
        return html;
      }

      function renderTabla(rows, tMap) {
        if (!rows.length) return '<div>Sin datos</div>';
        let html = '<table class="table" style="width:100%;border-collapse:collapse;font-size:13px">';
        html += '<thead><tr>' +
          th('ID') + th('Estado') + th('Recepción') + th('Entrega') + th('Cliente') + th('Factura') + th('Ciclo (h)') +
          '</tr></thead><tbody>';
        rows.forEach(r => {
          const h = (tMap[r.ID] ?? tMap[Number(r.ID)] ?? null);
          html += '<tr>' +
            td('#' + escapeHtml(String(r.ID))) +
            td(escapeHtml(String(r.ESTADO))) +
            td(escapeHtml((r.FECHA_RECEPCION_FACTURA || '').slice(0, 19))) +
            td(escapeHtml((r.FECHA_ENTREGA_CLIENTE || '').slice(0, 19))) +
            td(escapeHtml(String(r.NOMBRE_CLIENTE || '—'))) +
            td(escapeHtml(String(r.FACTURA || '—'))) +
            td(h == null ? '—' : h.toFixed(1)) // si prefieres legible: formatHours(h)
            +
            '</tr>';
        });
        html += '</tbody></table>';
        return html;

        function th(t) {
          return `<th style="text-align:left;padding:6px;border-bottom:1px solid #e5e7eb">${t}</th>`;
        }

        function td(t) {
          return `<td style="padding:6px;border-bottom:1px solid #f1f5f9">${t}</td>`;
        }
      }


      function escapeHtml(s) {
        const div = document.createElement('div');
        div.textContent = s == null ? '' : String(s);
        return div.innerHTML;
      }
    }
  </script>

  <script>
    const avatar = document.getElementById('avatar');
    const fileIn = document.getElementById('file-input');
    const formUp = document.getElementById('upload-form');

    if (avatar && fileIn && formUp) {
      avatar.addEventListener('click', () => fileIn.click());

      fileIn.addEventListener('change', async () => {
        if (!fileIn.files.length) return;
        const fd = new FormData(formUp);
        fd.set('foto', fileIn.files[0]);

        try {
          const r = await fetch(formUp.action, {
            method: 'POST',
            body: fd
          });
          const j = await r.json();
          if (j.ok) {
            // si no existía img, créala y elimina la inicial
            let img = document.getElementById('avatar-img');
            if (!img) {
              img = document.createElement('img');
              img.id = 'avatar-img';
              const initial = document.getElementById('avatar-initial');
              if (initial) initial.remove();
              avatar.innerHTML = '';
              avatar.appendChild(img);
            }
            img.src = j.url + '?t=' + Date.now(); // bust cache
          } else {
            alert(j.error || 'No se pudo subir la foto.');
          }
        } catch (e) {
          alert('Error subiendo la foto.');
        } finally {
          fileIn.value = '';
        }
      });
    }
  </script>



  <div class="container" style="margin-top:16px">
    <div id="chofer-username" style="font-weight:700;"></div>

    <!-- Slider de rango de fechas -->
    <div id="date-range" style="margin:12px 0"></div>
    <input type="hidden" id="start_date" name="start_date" value="">
    <input type="hidden" id="end_date" name="end_date" value="">
    <div id="time_range" style="margin-bottom:10px;font-size:18px;color:#0a376b;"></div>

    <!-- Gráfica y resumen -->
    <div id="barchart" class="chart-container"></div>
    <div id="resumen" style="margin-top:10px"></div>

    <!-- KPIs y tablas (se llenan en drawChartAndStats) -->
    <div id="pedidos-chofer"></div>
  </div>


</body>

</html>