<?php
/* ======================================================
   Pedidos_GA — vehiculos.php
   - Cards de vehículo clickeables
   - Avatar inicial si no hay foto
   - Resolución robusta de rutas de imagen
   Última revisión: 2025-10-29
   ====================================================== */

// —— Sesión segura —— //
ini_set('session.cookie_httponly', true);
ini_set('session.cookie_secure', true);
session_name("GA");
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
  header("location: /Pedidos_GA/Sesion/login.html");
  exit;
}

require_once __DIR__ . "/Conexiones/Conexion.php";
$conn->set_charset('utf8mb4');

$sucursal = $_SESSION["Sucursal"] ?? '';
$rol      = $_SESSION["Rol"] ?? '';

/* ======================================================
   Helpers (rutas, iniciales)
   ====================================================== */

/**
 * Normaliza una ruta web sirviendo desde /Pedidos_GA.
 * Acepta:
 *  - URL absolutas (http/https) -> se devuelven tal cual.
 *  - Rutas que inician con /Pedidos_GA/... -> se verifican en disco.
 *  - Rutas relativas tipo uploads/..., Img/..., etc. -> se antepone /Pedidos_GA/.
 *  - Permite backslashes de Windows en la BD.
 * Devuelve '' si no existe en disco.
 */
function web_path_if_exists(string $maybePath): string {
  $maybePath = trim($maybePath);
  if ($maybePath === '') return '';

  // Normaliza backslashes -> slashes
  $maybePath = str_replace('\\','/',$maybePath);

  // URL absoluta
  if (preg_match('~^https?://~i', $maybePath)) {
    return $maybePath; // asumimos válida públicamente
  }

  // Asegurar que haya prefijo /Pedidos_GA si es relativa
  $web = $maybePath;
  if ($web[0] !== '/') {
    // Relativa -> anteponer base del proyecto
    $web = '/Pedidos_GA/' . ltrim($web, '/');
  }

  // Algunas BD guardan doble prefijo (/Pedidos_GA/Pedidos_GA/...)
  $web = preg_replace('~^/Pedidos_GA/(?:Pedidos_GA/)+~', '/Pedidos_GA/', $web);

  // Resuelve a disco con DOCUMENT_ROOT
  $docRoot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '\\/');
  if ($docRoot !== '') {
    $disk = $docRoot . $web;
    if (is_file($disk)) return $web;
  }

  // Alternativa: cuando DOCUMENT_ROOT no coincide (p.ej. alias Apache).
  // Probamos relativo a este archivo asumiendo que vehiculos.php vive en /Pedidos_GA/
  $disk2 = __DIR__ . $web; // esto duplicaría la carpeta; ajustamos:
  // __DIR__ ya es .../Pedidos_GA, así que quitamos prefijo /Pedidos_GA para pegarlo.
  $withoutBase = preg_replace('~^/Pedidos_GA/~','',$web);
  $disk2 = rtrim(__DIR__, '/\\') . '/' . $withoutBase;
  if (is_file($disk2)) return $web;

  return ''; // no existe en disco (o no accesible)
}

/** Toma la primera letra legible de una cadena (en mayúscula). */
function str_initial(string $s, string $fallback = '?'): string {
  $s = trim($s);
  if ($s === '') return $fallback;
  if (preg_match('/\p{L}/u', $s, $m, 0, 0)) {
    return mb_strtoupper($m[0], 'UTF-8');
  }
  return mb_strtoupper(mb_substr($s, 0, 1, 'UTF-8'), 'UTF-8');
}

/* ======================================================
   1) CONSULTAS A BD
   ====================================================== */

// Vehículos
if ($rol === "Admin" || $rol === "MEC") {
  $vehiculosSql = "
    SELECT v.*,
           (SELECT fecha_registro
              FROM registro_kilometraje
             WHERE id_vehiculo = v.id_vehiculo
               AND Tipo_Registro = 'Servicio'
          ORDER BY id_registro DESC
             LIMIT 1) AS fecha_ultimo_servicio
      FROM vehiculos v
  ";
} else {
  $suc = $conn->real_escape_string($sucursal);
  $vehiculosSql = "
    SELECT v.*,
           (SELECT fecha_registro
              FROM registro_kilometraje
             WHERE id_vehiculo = v.id_vehiculo
               AND Tipo_Registro = 'Servicio'
          ORDER BY id_registro DESC
             LIMIT 1) AS fecha_ultimo_servicio
      FROM vehiculos v
     WHERE v.Sucursal = '$suc'
  ";
}
$vehiculos = $conn->query($vehiculosSql);
if (!$vehiculos) { http_response_code(500); die("Error en consulta vehículos: " . $conn->error); }

// Choferes + métricas (incluyendo foto_perfil)
$escSucursal   = $conn->real_escape_string($sucursal);
$esAdminOMec   = ($rol === 'Admin' || $rol === 'MEC');

$joinFiltroSucursalPedidos = $esAdminOMec ? "" : " AND p.SUCURSAL = '$escSucursal' ";
$whereFiltroSucursalChofer  = $esAdminOMec ? "" : " AND c.Sucursal = '$escSucursal' ";

$start = $_GET['start_date'] ?? '';
$end   = $_GET['end_date']   ?? '';
$start = preg_match('/^\d{4}-\d{2}-\d{2}$/', $start) ? $start : '';
$end   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)   ? $end   : '';

$joinFiltroFecha = "";
if ($start !== "") { $esc = $conn->real_escape_string($start . " 00:00:00");
  $joinFiltroFecha .= " AND COALESCE(p.FECHA_ENTREGA_CLIENTE, p.FECHA_RECEPCION_FACTURA) >= '$esc' "; }
if ($end !== "") { $esc = $conn->real_escape_string($end . " 23:59:59");
  $joinFiltroFecha .= " AND COALESCE(p.FECHA_ENTREGA_CLIENTE, p.FECHA_RECEPCION_FACTURA) <= '$esc' "; }

$baseChoferesSQL = "
  SELECT
    c.ID, c.username, c.Sucursal, c.Numero, c.Estado, c.foto_perfil,
    (
      SELECT v.id_vehiculo
      FROM vehiculos v
      WHERE v.id_chofer_asignado = c.ID
      ORDER BY v.id_vehiculo DESC
      LIMIT 1
    ) AS id_vehiculo_asignado,
    COUNT(p.ID) AS total_pedidos,
    SUM(CASE WHEN UPPER(p.ESTADO) IN ('ENTREGADO','ENTREGADA') THEN 1 ELSE 0 END) AS entregados,
    SUM(CASE WHEN UPPER(p.ESTADO) IN ('EN RUTA','RUTA','EN TRANSITO') THEN 1 ELSE 0 END) AS en_ruta,
    SUM(CASE WHEN UPPER(p.ESTADO) LIKE 'REPROGRAM%' THEN 1 ELSE 0 END) AS reprogramado,
    SUM(CASE WHEN UPPER(p.ESTADO) IN ('EN TIENDA','TIENDA') THEN 1 ELSE 0 END) AS en_tienda,
    SUM(CASE WHEN UPPER(p.ESTADO) IN ('CANCELADO','CANCELADA','CANCELADO CLIENTE') THEN 1 ELSE 0 END) AS cancelados
  FROM choferes c
  LEFT JOIN pedidos p
    ON TRIM(UPPER(p.CHOFER_ASIGNADO)) = TRIM(UPPER(c.username))
    $joinFiltroSucursalPedidos
    $joinFiltroFecha
  WHERE c.Estado = 'ACTIVO'
  $whereFiltroSucursalChofer
  GROUP BY c.ID, c.username, c.Sucursal, c.Numero, c.Estado, c.foto_perfil
  ORDER BY c.username
";
$choferes = $conn->query($baseChoferesSQL);
if (!$choferes) { http_response_code(500); die("Error en consulta choferes: " . $conn->error); }

/* ======================================================
   2) MÉTRICAS AUXILIARES
   ====================================================== */
$gasCountMap = [];
$qGas = $conn->query("SELECT id_vehiculo, COUNT(*) AS n
                        FROM registro_gasolina
                       WHERE fecha_registro >= DATE_SUB(NOW(), INTERVAL 90 DAY)
                    GROUP BY id_vehiculo");
if ($qGas) while ($r = $qGas->fetch_assoc()) $gasCountMap[(int)$r['id_vehiculo']] = (int)$r['n'];

$km30Map = [];
$qKm = $conn->query("SELECT id_vehiculo, SUM(GREATEST(0, kilometraje_final - kilometraje_inicial)) AS km
                       FROM registro_kilometraje
                      WHERE fecha_registro >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                   GROUP BY id_vehiculo");
if ($qKm) while ($r = $qKm->fetch_assoc()) $km30Map[(int)$r['id_vehiculo']] = (int)$r['km'];

function capacityScoreByTipo($t) {
  $t = strtolower(trim((string)$t));
  if ($t === '') return 60;
  if (strpos($t, 'moto') !== false) return 25;
  if (strpos($t, 'panel') !== false) return 75;
  if (strpos($t, 'camioneta') !== false) return 85;
  if (strpos($t, 'camion') !== false) return 90;
  if (strpos($t, 'pickup') !== false) return 80;
  if (strpos($t, 'auto') !== false || strpos($t, 'carro') !== false) return 55;
  return 60;
}

/* ======================================================
   3) NORMALIZAR RESULTADOS PARA EL FRONT
   ====================================================== */

// Imagen pública para vehículo (o '' para mostrar inicial)
function publicImgForVeh(array $v): string {
  // 1) ruta almacenada (foto_path) como uploads/... o /Pedidos_GA/uploads/...
  $path = isset($v['foto_path']) ? (string)$v['foto_path'] : '';
  $url = $path ? web_path_if_exists($path) : '';

  // 2) si no hubo, intenta ubicaciones comunes por placa o id (opcionales)
  if ($url === '') {
    $id  = (int)($v['id_vehiculo'] ?? 0);
    $guess1 = "uploads/vehiculos/veh_{$id}.jpg";
    $guess2 = "uploads/vehiculos/veh_{$id}.png";
    $guess3 = "Img/vehiculos/veh_{$id}.jpg";
    $guess4 = "Img/vehiculos/veh_{$id}.png";
    foreach ([$guess1,$guess2,$guess3,$guess4] as $g) {
      $try = web_path_if_exists($g);
      if ($try !== '') { $url = $try; break; }
    }
  }
  return $url; // '' si no existe -> se mostrará inicial
}

// Imagen pública para chofer (o '')
function publicImgForChofer(array $c): string {
  $path = isset($c['foto_perfil']) ? (string)$c['foto_perfil'] : '';
  $url = $path ? web_path_if_exists($path) : '';
  if ($url === '') {
    // fallback por convención: uploads/choferes/chofer_ID.*
    $id = (int)($c['ID'] ?? 0);
    $guess1 = "uploads/choferes/chofer_{$id}.jpg";
    $guess2 = "uploads/choferes/chofer_{$id}.png";
    foreach ([$guess1,$guess2] as $g) {
      $try = web_path_if_exists($g);
      if ($try !== '') { $url = $try; break; }
    }
  }
  return $url;
}

function pct($n, $t){ return $t > 0 ? (int)round(($n * 100) / $t) : 0; }

$vehiclesArr = [];
while ($v = $vehiculos->fetch_assoc()) {
  $assignedDriverId = null;
  if (!empty($v['id_chofer_asignado'])) $assignedDriverId = (int)$v['id_chofer_asignado'];
  elseif (!empty($v['id_chofer']))       $assignedDriverId = (int)$v['id_chofer'];

  $imgUrl = publicImgForVeh($v); // '' si no hay
  $idVeh   = (int)($v['id_vehiculo'] ?? 0);
  $kmSrv   = max(1, (int)($v['Km_de_Servicio'] ?? 5000));
  $kmAct   = max(0, (int)($v['Km_Actual'] ?? 0));
  $cap     = capacityScoreByTipo($v['tipo'] ?? '');
  $km30    = (int)($km30Map[$idVeh] ?? 0);
  $gas90   = (int)($gasCountMap[$idVeh] ?? 0);
  $pctMant = (int)max(0, min(100, round(100 - ($kmAct / $kmSrv) * 100)));
  $pctCons = (int)max(0, min(100, 100 - ($gas90 * 10)));
  $pctLlant= $pctMant;
  $uso30Pct= (int)max(0, min(100, round(($km30 / $kmSrv) * 100)));
  $pctSalud= (int)max(0, min(100, round(($pctMant * 0.7) + ((100 - $uso30Pct) * 0.3))));

  $titleAlias = (string)($v['placa'] ?? '');
  $vehName    = (string)($v['tipo']  ?? 'Vehículo');

  $vehiclesArr[] = [
    'id'        => $idVeh,
    'nombre'    => $vehName,
    'alias'     => $titleAlias,
    'placa'     => (string)($v['placa'] ?? ''),
    'tipo'      => (string)($v['tipo'] ?? ''),
    'sucursal'  => (string)($v['Sucursal'] ?? ''),
    'ultimo'    => (string)($v['fecha_ultimo_servicio'] ?? ''),
    'img'       => $imgUrl,  // puede ser ''
    'initial'   => str_initial($titleAlias !== '' ? $titleAlias : $vehName, 'V'),
    'assignedDriverId' => $assignedDriverId,
    'stats'     => [
      'capacidad'       => $cap,
      'consumo'         => $pctCons,
      'mantenimiento'   => $pctMant,
      'estado_llantas'  => $pctLlant,
      'salud'           => $pctSalud,
    ],
    'tags'      => array_values(array_filter([
      'GPS',
      (isset($v['tipo']) && stripos((string)$v['tipo'], 'panel') !== false) ? 'Panel' : null
    ])),
  ];
}

$driversArr = [];
while ($c = $choferes->fetch_assoc()) {
  $assignedVehicleId = !empty($c['id_vehiculo_asignado']) ? (int)$c['id_vehiculo_asignado'] : null;
  $total        = (int)($c['total_pedidos'] ?? 0);
  $entregados   = (int)($c['entregados'] ?? 0);
  $en_ruta      = (int)($c['en_ruta'] ?? 0);
  $reprog       = (int)($c['reprogramado'] ?? 0);
  $en_tienda    = (int)($c['en_tienda'] ?? 0);
  $cancelados   = (int)($c['cancelados'] ?? 0);

  $driversArr[] = [
    'id'        => (int)($c['ID'] ?? 0),
    'username'  => (string)($c['username'] ?? ''),
    'nombre'    => (string)($c['username'] ?? 'Chofer'),
    'sucursal'  => (string)($c['Sucursal'] ?? ''),
    'estado'    => (string)($c['Estado'] ?? ''),
    'numero'    => (string)($c['Numero'] ?? ''),
    'img'       => publicImgForChofer($c), // '' si no hay
    'initial'   => str_initial($c['username'] ?? 'C','C'),
    'stats'     => [
      'entregados'   => pct($entregados, $total),
      'en_ruta'      => pct($en_ruta, $total),
      'reprogramado' => pct($reprog, $total),
      'en_tienda'    => pct($en_tienda, $total),
      'cancelados'   => pct($cancelados, $total),
    ],
    'statsRaw'  => [
      'total'        => $total,
      'entregados'   => $entregados,
      'en_ruta'      => $en_ruta,
      'reprogramado' => $reprog,
      'en_tienda'    => $en_tienda,
      'cancelados'   => $cancelados,
    ],
    'assignedVehicleId' => $assignedVehicleId,
  ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <title>Gestión de Vehículos</title>
<<<<<<< HEAD
  <link rel="icon" type="image/png" href="/Img/Botones%20entregas/ICONOSPAG/ICONOPEDIDOS.png">
=======
  <link rel="icon" type="image/png" href="/Pedidos_GA/Img/Botones%20entregas/ICONOSPAG/ICONOPEDIDOS.png">
>>>>>>> parent of 5e8b02c (parra amazon Update image paths and SQL table names)
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <style>
  :root{
    --bg:#f0f2f5; --surface:#fff; --surface-mute:#fafafa; --stroke:#e6e8ee;
    --text:#1e293b; --muted:#64748b; --brand:#005aa3; --brand-2:#ed6b1f;
    --success:#22c55e; --danger:#e11d48; --radius:12px; --shadow:0 6px 18px rgba(15,23,42,.08);
  }
  *{box-sizing:border-box} html,body{height:100%}
  body{margin:0;background:var(--bg);color:var(--text);font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;padding-left:100px}

  /* Sidebar */
  .sidebar{position:fixed;inset:0 auto 0 0;width:100px;z-index:20;background:var(--brand);border-right:1px solid #004b88;display:flex;flex-direction:column}
  .sidebar ul{list-style:none;margin:0;padding:12px 6px;display:flex;flex-direction:column;height:100%}
  .sidebar li{display:flex;justify-content:center;margin:14px 0}
  .sidebar a{display:flex;flex-direction:column;align-items:center;gap:6px;text-decoration:none}
  .sidebar-icon{width:62px;height:auto;display:block;transition:transform .15s ease}
  .sidebar-icon.small{width:48px}
  .sidebar a:hover .sidebar-icon{transform:translateY(-2px)}
  .sidebar-bottom{margin-top:auto;margin-bottom:10px}

  .container{max-width:1200px;margin:20px auto 60px;padding:0 16px}
  h1{margin:10px 0 8px;font-size:28px;font-weight:800;color:var(--brand)}

  /* Tabs */
  .tabs{display:flex;width:100%;position:sticky;top:0;z-index:10;background:var(--bg);border-bottom:1px solid var(--stroke)}
  .tab{flex:1;border:none;cursor:pointer;padding:16px 0;font-weight:800;font-size:1.05rem;letter-spacing:.3px;color:var(--muted);background:transparent;border-radius:10px 10px 0 0;transition:.2s}
  .tab:hover{background:#f8fafc;color:var(--brand)}
  .tab.active{color:var(--brand);background:var(--surface);box-shadow:inset 0 -3px 0 var(--brand)}

  .wrap{max-width:1200px;margin:16px auto 40px;padding:0 2px}
  .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:16px}
  .hide{display:none!important}

  .card{position:relative;overflow:hidden;border-radius:var(--radius);background:var(--surface);border:1px solid var(--stroke);box-shadow:var(--shadow);padding:14px;transition:transform .2s, box-shadow .2s, border-color .2s}
  .card:hover{transform:translateY(-2px);box-shadow:0 10px 24px rgba(15,23,42,.12);border-color:#dbe0ea}

  /* Cards clickeables (vehículos) */
  .card.vehicle-card{ cursor:pointer; }
  .card.vehicle-card:focus-visible{ outline:2px dashed var(--brand); outline-offset:4px; }

  .branch-card{display:flex;align-items:center;gap:12px;padding:16px;cursor:pointer}
  .branch-card:hover{background:var(--surface-mute)}
  .branch-icon{width:54px;height:54px;border-radius:12px;display:grid;place-items:center;background:linear-gradient(135deg,#e3f2ff,#fdf2e9);color:var(--brand);font-weight:900;font-size:20px;border:1px solid var(--stroke)}
  .branch-info .name{font-weight:900;letter-spacing:.2px}
  .branch-info .meta{font-size:.85rem;color:var(--muted)}

  .head{display:flex;gap:12px;align-items:center;margin-bottom:8px;position:relative}

  /* ====== Avatar ====== */
  .avatar{width:58px;height:58px;border-radius:12px;overflow:hidden;flex:0 0 auto;border:1px solid var(--stroke);display:grid;place-items:center;background:#eef2f7}
  .avatar.veh{background:#fff7ed;border-color:#ffe4cc}
  .avatar img{width:100%;height:100%;object-fit:cover;display:block}
  .avatar-initial{width:100%;height:100%;display:grid;place-items:center;font-weight:900;font-size:22px;color:#0f172a}
  .avatar[data-color="0"]{background:#e9f5ff}
  .avatar[data-color="1"]{background:#eafdf3}
  .avatar[data-color="2"]{background:#fff7e5}
  .avatar[data-color="3"]{background:#f3e8ff}
  .avatar[data-color="4"]{background:#ffe8ee}
  .avatar[data-color="5"]{background:#e8f0ff}
  .avatar[data-color="6"]{background:#e7fff9}
  .avatar[data-color="7"]{background:#f7f7ff}

  .name{font-weight:800;font-size:1rem;letter-spacing:.2px}
  .meta{font-size:.86rem;color:var(--muted)}
  .chips{display:flex;gap:6px;flex-wrap:wrap;margin:6px 0 8px}
  .chip{font-size:.72rem;padding:6px 8px;border-radius:999px;border:1px solid var(--stroke);background:#f8fafc;color:#0f172a}
  .stats{display:grid;gap:8px}
  .bar{display:grid;gap:4px}
  .bar label{font-size:.78rem;color:var(--muted);display:flex;justify-content:space-between}
  .track{height:10px;border-radius:999px;background:#eef2f7;overflow:hidden;border:1px solid var(--stroke)}
  .fill{height:100%;width:0;border-radius:inherit;background:linear-gradient(90deg,var(--brand-2),var(--brand))}
  .footer{margin-top:10px;display:flex;align-items:center;justify-content:space-between;font-size:.84rem;color:var(--muted);border-top:1px dashed var(--stroke);padding-top:10px}

  .btn-mini{border:1px solid var(--stroke);background:var(--brand-2);color:#fff;padding:8px 12px;border-radius:8px;cursor:pointer;font-weight:700;font-size:.9rem;transition:background .2s,border-color .2s,transform .1s}
  .btn-mini:hover{background:#ff7b2c}
  .btn-mini:active{transform:translateY(1px)}
  .btn-mini.back{background:#e2e8f0;color:#0f172a;border-color:#d0d7e2}
  .btn-mini.back:hover{background:#dbe4f0}
  .btn-mini.danger{background:var(--danger);border-color:#fda4af}
  .btn-mini.danger:hover{background:#c5163f}

  .card.paired{--pair:var(--brand);--badgeW:210px;border:3px solid #eeff01;box-shadow:var(--shadow),0 0 0 2px rgba(0,90,163,.08) inset}
  .pair-badge{position:absolute;right:12px;top:12px;display:flex;align-items:center;gap:8px;background:#ffffffcc;border:1px solid #e6eef8;padding:6px 8px;border-radius:999px;font-size:.78rem;backdrop-filter: blur(4px);color:#0f172a}
  .mini-veh,.mini-avatar{width:26px;height:26px;border-radius:8px;overflow:hidden;border:1px solid #e6eef8}
  .mini-veh img,.mini-avatar img{width:100%;height:100%;object-fit:cover}

  .card.driver-card{cursor:pointer}
  .card.driver-card:focus-visible{outline:2px dashed var(--brand);outline-offset:4px}

  @media (max-width:760px){.sidebar{width:84px} body{padding-left:84px}}
  @media (max-width:520px){.pair-badge{position:static;margin-top:6px;align-self:flex-start}}
  </style>
</head>
<body>
  <!-- Sidebar -->
  <div class="sidebar">
    <ul>
      <?php if (($_SESSION["Rol"] ?? '') === "Admin"): ?>
        <li>
          <a href="NuevoVehiculo.php" title="Agregar Vehículo">
<<<<<<< HEAD
            <img src="/Img/Botones%20entregas/Choferes/ADDSERVMECNA.png" class="icono-AddChofer sidebar-icon" alt="Agregar">
=======
            <img src="/Pedidos_GA/Img/Botones%20entregas/Choferes/ADDSERVMECNA.png" class="icono-AddChofer sidebar-icon" alt="Agregar">
>>>>>>> parent of 5e8b02c (parra amazon Update image paths and SQL table names)
          </a>
        </li>
      <?php endif; ?>
      <li>
        <a href="Estadisticas_Vehiculos.php" title="Estadísticas">
<<<<<<< HEAD
          <img src="/Img/Botones%20entregas/Pedidos_GA/ESTNA2.png" class="icono-estadisticas sidebar-icon" alt="Estadísticas">
=======
          <img src="/Pedidos_GA/Img/Botones%20entregas/Pedidos_GA/ESTNA2.png" class="icono-estadisticas sidebar-icon" alt="Estadísticas">
>>>>>>> parent of 5e8b02c (parra amazon Update image paths and SQL table names)
        </a>
      </li>
      <li class="sidebar-bottom">
        <a href="Pedidos_GA.php" title="Volver">
<<<<<<< HEAD
          <img src="/Img/Botones%20entregas/Usuario/VOLVAZ.png" class="icono-Volver sidebar-icon small" alt="Volver">
=======
          <img src="/Pedidos_GA/Img/Botones%20entregas/Usuario/VOLVAZ.png" class="icono-Volver sidebar-icon small" alt="Volver">
>>>>>>> parent of 5e8b02c (parra amazon Update image paths and SQL table names)
        </a>
      </li>
    </ul>
  </div>

  <div class="container">
    <h1>Gestión de Vehículos</h1>

    <!-- Tabs -->
    <header class="topbar">
      <nav class="tabs">
        <button class="tab active" data-tab="vehicles">Vehículos</button>
        <button class="tab" data-tab="drivers">Choferes</button>
      </nav>
    </header>

    <main class="wrap">
      <!-- Vehículos -->
      <section id="vehicles-view">
        <div id="vehicles-header" class="sectionHeader"></div>
        <div id="vehicles-grid" class="grid"></div>
      </section>

      <!-- Choferes -->
      <section id="drivers-view" class="hide">
        <div id="drivers-header" class="sectionHeader"></div>
        <div id="drivers-grid" class="grid" data-selected="false"></div>
      </section>
    </main>

    <!-- Modal Asignar Vehículo -->
    <div id="assign-modal" style="position:fixed; inset:0; display:none; align-items:center; justify-content:center; background:rgba(0,0,0,.45); z-index:200;">
      <div style="background:#0d1626; color:#e8f0ff; border:1px solid rgba(255,255,255,.18); border-radius:16px; padding:16px; width:min(420px, 92vw);">
        <h3 style="margin:0 0 10px">Asignar vehículo</h3>
        <div id="assign-meta" style="font-size:.9rem; color:#9bb0d0; margin-bottom:10px;"></div>
        <label style="display:block; margin-bottom:10px;">
          Sucursal
          <select id="assign-branch" style="width:100%; margin-top:6px; background:#0f1a28; color:#e8f0ff; border:1px solid rgba(255,255,255,.18); border-radius:10px; padding:8px;"></select>
        </label>
        <label style="display:block; margin-bottom:10px;">
          Selecciona vehículo
          <select id="assign-select" style="width:100%; margin-top:6px; background:#0f1a28; color:#e8f0ff; border:1px solid rgba(255,255,255,.18); border-radius:10px; padding:8px;"></select>
        </label>
        <div style="display:flex; gap:8px; justify-content:flex-end;">
          <button id="assign-cancel" class="btn-mini">Cancelar</button>
          <button id="assign-confirm" class="btn-mini">Asignar</button>
        </div>
      </div>
    </div>
  </div>

  <script>
  // ===== Datos del servidor =====
  const VEHICLES = <?php echo json_encode($vehiclesArr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
  const DRIVERS  = <?php echo json_encode($driversArr,  JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

  const driverById  = Object.fromEntries(DRIVERS.map(d => [d.id, d]));
  const vehicleById = Object.fromEntries(VEHICLES.map(v => [v.id, v]));

  const PALETTE = ['#38e1ff','#7cff7c','#ffd166','#ff6b6b','#b58cff','#6ae3ff','#74ff9e','#ff9c66'];
  const PAIR_COLORS = {};
  let _pi = 0;
  DRIVERS.forEach(d => { if (d.assignedVehicleId) { const k = `${d.id}-${d.assignedVehicleId}`; PAIR_COLORS[k] = PALETTE[_pi++ % PALETTE.length]; } });

  const $ = (s, c=document) => c.querySelector(s);
  const $$ = (s, c=document) => Array.from(c.querySelectorAll(s));
  const unique = arr => [...new Set(arr)];
  function bar(label, value, max=100) {
    const pct = Math.max(0, Math.min(100, Math.round((value||0)/max*100)));
    return `<div class="bar"><label><span>${label}</span><span>${pct}%</span></label>
            <div class="track"><div class="fill" style="width:${pct}%"></div></div></div>`;
  }

// ===== Tabs: Vehículos / Choferes =====
$$('.tab').forEach(btn => {
  btn.addEventListener('click', () => {
    // estado visual
    $$('.tab').forEach(x => x.classList.remove('active'));
    btn.classList.add('active');

    // qué vista mostrar
    const tab = btn.dataset.tab;
    if (tab === 'vehicles') {
      $('#vehicles-view').classList.remove('hide');
      $('#drivers-view').classList.add('hide');
      renderVehicles(); 
    } else {
      $('#drivers-view').classList.remove('hide');
      $('#vehicles-view').classList.add('hide');
      renderDrivers();
    }
    animateBars();
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });
});



  // ====== AVATAR helper (img o inicial) ======
  function colorIndexFromText(t='X'){
    let h=0; for (let i=0;i<t.length;i++) h = (h*31 + t.charCodeAt(i))>>>0;
    return h % 8; // 0..7
  }
  function avatarHTML({img='', initial='?', veh=false, name=''}) {
    const colorIdx = colorIndexFromText(name||initial);
    const cls = 'avatar' + (veh ? ' veh' : '');
    if (img) return `<div class="${cls}" data-color="${colorIdx}"><img src="${img}" alt=""></div>`;
    return `<div class="${cls}" data-color="${colorIdx}"><div class="avatar-initial">${(initial||'?').slice(0,2)}</div></div>`;
  }

  // Vistas
  const vHeader = $('#vehicles-header'), vGrid = $('#vehicles-grid');
  const dHeader = $('#drivers-header'),  dGrid = $('#drivers-grid');
  let vState = { level: 'branches', branch: null };
  let dState = { level: 'branches', branch: null };

  // Render vehículos
  function renderVehicles() {
    if (vState.level === 'branches') {
      vHeader.innerHTML = `<div class="crumb"><span class="muted">Vehículos</span> · Sucursales</div>`;
      const branches = unique(VEHICLES.map(v => v.sucursal || '—')).sort();
      vGrid.innerHTML = branches.map(s => {
        const count = VEHICLES.filter(v => (v.sucursal || '—') === s).length;
        return `<article class="card branch-card" data-branch="${s}">
          <div class="branch-icon">🚏</div>
          <div class="branch-info"><div class="name">${s}</div><div class="meta">${count} vehículo${count!==1?'s':''}</div></div>
        </article>`;
      }).join('');
    } else {
      const s = vState.branch;
      vHeader.innerHTML = `<div class="crumb"><span class="muted">Vehículos</span> · <strong>${s}</strong></div>
                           <button class="btn-mini back" id="veh-back">Regresar</button>`;
      const list = VEHICLES.filter(v => (v.sucursal || '—') === s);
      vGrid.innerHTML = list.map(vehicleCard).join('');
      $('#veh-back')?.addEventListener('click', () => { vState = { level: 'branches', branch: null }; renderVehicles(); animateBars(); });
    }
  }
  vGrid.addEventListener('click', e => {
    const c = e.target.closest('.branch-card'); if (!c) return;
    vState = { level: 'list', branch: c.dataset.branch };
    renderVehicles(); animateBars();
  });

  // Card de vehículo (clickeable en toda el área)
  function vehicleCard(v) {
    const d = v.assignedDriverId ? driverById[v.assignedDriverId] : null;
    const key = d ? `${d.id}-${v.id}` : null;
    const pairColor = key ? (PAIR_COLORS[key] || '#6ae3ff') : null;
    const extraBtn = d ? `<button class="btn-mini danger" data-action="quitar-vehiculo" data-vehiculo-id="${v.id}">Quitar</button>` : '';
    const title = (v.alias && v.alias.trim()) ? v.alias : (v.nombre && v.nombre.trim() ? v.nombre : 'Vehículo');

    return `<article class="card vehicle-card ${d?'paired':''}"
                   style="${pairColor?`--pair:${pairColor}`:''}"
                   data-vehiculo-id="${v.id}"
                   tabindex="0"
                   role="link"
                   aria-label="Ver detalles de ${title}">
      ${d?`<div class="pair-badge"><span class="pair-dot"></span>
            <div class="mini-avatar">${d.img?`<img src="${d.img}" alt="">`:`<div class="avatar-initial" style="font-size:14px">${(d.initial||'?').slice(0,2)}</div>`}</div><strong>${d.nombre}</strong></div>`:''}
      <div class="head">
        ${avatarHTML({img:v.img, initial:v.initial, veh:true, name:title})}
        <div class="text-block">
          <div class="name">${title}</div>
          <div class="meta">${v.tipo||''} · Placa ${v.placa||''} · ${v.sucursal||''}</div>
        </div>
      </div>
      <div class="chips">${(v.tags||[]).map(t=>`<span class="chip">${t}</span>`).join('')}</div>
      <div class="stats">
        ${bar('Capacidad de carga', v.stats?.capacidad, 100)}
        ${bar('Rendimiento combustible', v.stats?.consumo, 100)}
        ${bar('Mantenimiento', v.stats?.mantenimiento, 100)}
        ${bar('Llantas', v.stats?.estado_llantas, 100)}
        ${bar('Salud general', v.stats?.salud, 100)}
      </div>
      <div class="footer">
        <span>${d?`Chofer: ${d.nombre}`:'Sin chofer asignado'}</span>
        <div style="display:flex; gap:8px;">${extraBtn}</div>
      </div>
    </article>`;
  }

  // Toda la card del vehículo -> ir a detalles (click)
  vGrid.addEventListener('click', (e) => {
    if (e.target.closest('button, a, input, select, option, label, textarea')) return;
    const card = e.target.closest('.card.vehicle-card[data-vehiculo-id]');
    if (!card) return;
    const id = card.getAttribute('data-vehiculo-id');
    if (!id) return;
    window.location.href = `/Pedidos_GA/detalles_vehiculo.php?id=${encodeURIComponent(id)}`;
  });
  // Accesibilidad teclado
  vGrid.addEventListener('keydown', (e) => {
    const card = e.target.closest('.card.vehicle-card[data-vehiculo-id]');
    if (!card) return;
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      const id = card.getAttribute('data-vehiculo-id');
      if (!id) return;
      window.location.href = `/Pedidos_GA/detalles_vehiculo.php?id=${encodeURIComponent(id)}`;
    }
  });

  // Render choferes
  function renderDrivers() {
    if (dState.level === 'branches') {
      dHeader.innerHTML = `<div class="crumb"><span class="muted">Choferes</span> · Sucursales</div>`;
      const branches = unique(DRIVERS.map(d => d.sucursal || '—')).sort();
      dGrid.innerHTML = branches.map(s => {
        const count = DRIVERS.filter(d => (d.sucursal || '—') === s).length;
        return `<article class="card branch-card" data-branch="${s}">
          <div class="branch-icon">🏢</div>
          <div class="branch-info"><div class="name">${s}</div><div class="meta">${count} chofer${count!==1?'es':''}</div></div>
        </article>`;
      }).join('');
      dGrid.dataset.selected = "false";
    } else {
      const s = dState.branch;
      dHeader.innerHTML = `<div class="crumb"><span class="muted">Choferes</span> · <strong>${s}</strong></div>
                           <button class="btn-mini back" id="drv-back">Regresar</button>`;
      const list = DRIVERS.filter(d => (d.sucursal || '—') === s);
      dGrid.innerHTML = list.map(driverCard).join('');
      $('#drv-back')?.addEventListener('click', () => { dState = { level: 'branches', branch: null }; renderDrivers(); animateBars(); });
      dGrid.dataset.selected = "false";
    }
  }
  dGrid.addEventListener('click', e => {
    const c = e.target.closest('.branch-card'); if (!c) return;
    dState = { level: 'list', branch: c.dataset.branch };
    renderDrivers(); animateBars();
  });

  function driverCard(d) {
    const v = d.assignedVehicleId ? vehicleById[d.assignedVehicleId] : null;
    const key = v ? `${d.id}-${v.id}` : null;
    const pairColor = key ? (PAIR_COLORS[key] || '#6ae3ff') : null;
    const hasVeh = !!v;
    const mainBtn = hasVeh ? 'Cambiar' : 'Asignar';
    const extraBtn = hasVeh ? `<button class="btn-mini danger" data-action="quitar-chofer">Quitar</button>` : '';
    return `<article class="card driver-card ${hasVeh?'paired':''}"
                     style="${pairColor?`--pair:${pairColor}`:''}"
                     data-driver-id="${d.id}" data-username="${d.username||''}"
                     tabindex="0" role="button" aria-label="Ver detalles de ${d.nombre}">
      ${hasVeh?`<div class="pair-badge"><span class="pair-dot"></span>
            <div class="mini-veh">${v.img?`<img src="${v.img}" alt="">`:`<div class="avatar-initial" style="font-size:14px">${(v.initial||'?').slice(0,2)}</div>`}</div><strong>${v.nombre}</strong></div>`:''}
      <div class="head">
        ${avatarHTML({img:d.img, initial:d.initial, veh:false, name:d.nombre})}
        <div class="text-block">
          <div class="name">${d.nombre}</div>
          <div class="meta">Sucursal: ${d.sucursal||'—'} · Estado: ${d.estado||''}</div>
        </div>
      </div>
      <div class="chips">${d.numero?`<span class="chip">📞 ${d.numero}</span>`:''}</div>
      <div class="stats">
        ${bar('Entregados',   d.stats?.entregados,   100)}
        ${bar('En ruta',      d.stats?.en_ruta,      100)}
        ${bar('Reprogramado', d.stats?.reprogramado, 100)}
        ${bar('En tienda',    d.stats?.en_tienda,    100)}
        ${bar('Cancelados',   d.stats?.cancelados,   100)}
      </div>
      <div class="footer">
        <span>${hasVeh?`Vehículo: ${v.nombre}`:'Sin vehículo asignado'}</span>
        <div style="display:flex; gap:8px;">
          <button class="btn-mini" data-action="asignar-chofer">${mainBtn}</button>
          ${extraBtn}
        </div>
      </div>
    </article>`;
  }

  // Animación de barras
  function animateBars() {
    const obs = new IntersectionObserver(entries => {
      entries.forEach(e => {
        if (e.isIntersecting) {
          e.target.querySelectorAll('.fill').forEach((f,i) => {
            const w = f.style.width;
            f.style.width = '0';
            setTimeout(() => f.style.transition = 'width .7s ease', 20);
            setTimeout(() => f.style.width = w, 60 + i*60);
          });
          obs.unobserve(e.target);
        }
      });
    }, { threshold: .2 });
    setTimeout(() => $$('.card').forEach(c => obs.observe(c)), 30);
  }

  // Hover iconos del sidebar
  document.addEventListener("DOMContentLoaded", function() {
    const iconoAddChofer   = document.querySelector(".icono-AddChofer");
    const iconoVolver      = document.querySelector(".icono-Volver");
    const iconoEstadisticas= document.querySelector(".icono-estadisticas");
<<<<<<< HEAD
    if (iconoAddChofer) { const n="/Img/Botones%20entregas/Choferes/ADDSERVMECNA.png", h="/Img/Botones%20entregas/Choferes/ADDSERVMECBLANC.png"; iconoAddChofer.addEventListener("mouseover", () => iconoAddChofer.src=h); iconoAddChofer.addEventListener("mouseout",  () => iconoAddChofer.src=n); }
    if (iconoVolver)      { const n="/Img/Botones%20entregas/Usuario/VOLVAZ.png", h="/Img/Botones%20entregas/Usuario/VOLVNA.png"; iconoVolver.addEventListener("mouseover", () => iconoVolver.src=h);      iconoVolver.addEventListener("mouseout",  () => iconoVolver.src=n); }
    if (iconoEstadisticas){ const n="/Img/Botones%20entregas/Pedidos_GA/ESTNA2.png", h="/Img/Botones%20entregas/Pedidos_GA/ESTBL2.png"; iconoEstadisticas.addEventListener("mouseover", () => iconoEstadisticas.src=h); iconoEstadisticas.addEventListener("mouseout",  () => iconoEstadisticas.src=n); }
=======
    if (iconoAddChofer) { const n="/Pedidos_GA/Img/Botones%20entregas/Choferes/ADDSERVMECNA.png", h="/Pedidos_GA/Img/Botones%20entregas/Choferes/ADDSERVMECBLANC.png"; iconoAddChofer.addEventListener("mouseover", () => iconoAddChofer.src=h); iconoAddChofer.addEventListener("mouseout",  () => iconoAddChofer.src=n); }
    if (iconoVolver)      { const n="/Pedidos_GA/Img/Botones%20entregas/Usuario/VOLVAZ.png", h="/Pedidos_GA/Img/Botones%20entregas/Usuario/VOLVNA.png"; iconoVolver.addEventListener("mouseover", () => iconoVolver.src=h);      iconoVolver.addEventListener("mouseout",  () => iconoVolver.src=n); }
    if (iconoEstadisticas){ const n="/Pedidos_GA/Img/Botones%20entregas/Pedidos_GA/ESTNA2.png", h="/Pedidos_GA/Img/Botones%20entregas/Pedidos_GA/ESTBL2.png"; iconoEstadisticas.addEventListener("mouseover", () => iconoEstadisticas.src=h); iconoEstadisticas.addEventListener("mouseout",  () => iconoEstadisticas.src=n); }
>>>>>>> parent of 5e8b02c (parra amazon Update image paths and SQL table names)
  });

  // ==== APIs de asignación (igual que antes) ====
  const API_ASIGNAR     = '/Pedidos_GA/asignar_vehiculo.php';
  const API_LIBRES      = '/Pedidos_GA/vehiculos_disponibles.php';
  const API_DESASIGNAR  = '/Pedidos_GA/desasignar_vehiculo.php';

  const modal   = $('#assign-modal');
  const sel     = $('#assign-select');
  const meta    = $('#assign-meta');
  const btnOk   = $('#assign-confirm');
  const btnNo   = $('#assign-cancel');
  const branchSel = $('#assign-branch');

  let _assign_ctx = { choferId:null, choferNombre:'', sucursal:'' };

  async function openAssignModal(chofer){
    _assign_ctx = { choferId: chofer.id, choferNombre: chofer.nombre, sucursal: chofer.sucursal };
    meta.textContent = `Chofer: ${chofer.nombre} · Sucursal: ${chofer.sucursal || '—'}`;
    modal.style.display = 'flex';

    branchSel.innerHTML = '<option>Cargando…</option>';
    sel.innerHTML        = '<option>—</option>';
    btnOk.disabled = true; branchSel.disabled = true; sel.disabled = true;

    try{
      const res = await fetch(API_LIBRES, { credentials:'same-origin' });
      const j = await res.json();
      if (!j.ok) throw new Error(j.error || 'No se pudo cargar');

      const byBranch = j.data.reduce((acc, v) => {
        const b = v.Sucursal || '—';
        (acc[b] = acc[b] || []).push(v);
        return acc;
      }, {});
      const branches = Object.keys(byBranch).sort();
      if (!branches.length){
        branchSel.innerHTML = '<option value="">(No hay sucursales con vehículos libres)</option>';
        sel.innerHTML        = '<option value="">(No hay vehículos)</option>';
        btnOk.disabled = true;
        return;
      }

      branchSel.innerHTML = branches.map(b => `<option value="${b}">${b} (${byBranch[b].length})</option>`).join('');
      branchSel.disabled = true;

      const pre = (chofer.sucursal && byBranch[chofer.sucursal]) ? chofer.sucursal : branches[0];
      branchSel.value = pre;

      function fillVehiclesFor(branch){
        const list = byBranch[branch] || [];
        if (!list.length){
          sel.innerHTML = '<option value="">(No hay vehículos)</option>';
          sel.disabled = true; btnOk.disabled = true; return;
        }
        sel.disabled = false; btnOk.disabled = false;
        sel.innerHTML = list.map(v => `<option value="${v.id}">${(v.tipo||'Vehículo')} · ${v.Sucursal||''} · ${v.placa||''}</option>`).join('');
        if (chofer.assignedVehicleId) {
          const exists = list.some(v => String(v.id) === String(chofer.assignedVehicleId));
          if (exists) sel.value = String(chofer.assignedVehicleId);
        }
      }
      fillVehiclesFor(pre);
      branchSel.disabled = false;
      branchSel.onchange = () => fillVehiclesFor(branchSel.value);

    }catch(e){
      branchSel.innerHTML = '<option value="">(Error al cargar)</option>';
      sel.innerHTML        = '<option value="">(Error al cargar)</option>';
      btnOk.disabled = true;
    }
  }

  btnNo.addEventListener('click', () => { modal.style.display = 'none'; });

  btnOk.addEventListener('click', async () => {
    const vehiculoId = parseInt(sel.value || '0', 10);
    if (!vehiculoId) return;
    btnOk.disabled = true;
    try {
      const res = await fetch(API_ASIGNAR, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify({ chofer_id: _assign_ctx.choferId, vehiculo_id: vehiculoId })
      });
      const j = await res.json();
      if (!j.ok) throw new Error(j.error || 'Error');

      // Sync local
      const d = driverById[_assign_ctx.choferId]; if (d) d.assignedVehicleId = vehiculoId;
      const v = vehicleById[vehiculoId];          if (v) v.assignedDriverId  = _assign_ctx.choferId;

      renderDrivers(); animateBars();
      renderVehicles(); animateBars();

      modal.style.display = 'none';
    } catch (e) {
      alert('No se pudo asignar: ' + (e.message || e));
      btnOk.disabled = false;
    }
  });

  // Navegación a detalles del chofer desde card
  dGrid.addEventListener('click', (e) => {
    if (e.target.closest('button, a, input, select, option, label, textarea')) return;
    const card = e.target.closest('.card[data-driver-id]'); if (!card) return;
    const id = card.getAttribute('data-driver-id'); if (!id) return;
    window.location.href = `/Pedidos_GA/detalles_chofer.php?id=${encodeURIComponent(id)}`;
  });
  dGrid.addEventListener('keydown', (e) => {
    const card = e.target.closest('.card.driver-card[data-driver-id]'); if (!card) return;
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      const id = card.getAttribute('data-driver-id'); if (!id) return;
      window.location.href = `/Pedidos_GA/detalles_chofer.php?id=${encodeURIComponent(id)}`;
    }
  });

  // Asignar/Quitar chofer
  dGrid.addEventListener('click', (e) => {
    const btn = e.target.closest('button[data-action="asignar-chofer"]'); if (!btn) return;
    const card = e.target.closest('.card'); const id = parseInt(card.getAttribute('data-driver-id') || '0', 10);
    const chofer = driverById[id]; if (!chofer) return;
    openAssignModal(chofer);
  });
  dGrid.addEventListener('click', async (e) => {
    const btn = e.target.closest('button[data-action="quitar-chofer"]'); if (!btn) return;
    const card = e.target.closest('.card'); const id = parseInt(card.getAttribute('data-driver-id') || '0', 10);
    const chofer = driverById[id]; if (!chofer || !chofer.assignedVehicleId) return;
    if (!confirm('¿Quitar el vehículo asignado a este chofer?')) return;
    try {
      const res = await fetch('/Pedidos_GA/desasignar_vehiculo.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify({ vehiculo_id: chofer.assignedVehicleId })
      });
      const j = await res.json(); if (!j.ok) throw new Error(j.error || 'Error');

      const v = vehicleById[chofer.assignedVehicleId]; if (v) v.assignedDriverId = null;
      chofer.assignedVehicleId = null;

      renderDrivers(); animateBars();
      renderVehicles(); animateBars();
    } catch (e) {
      alert('No se pudo desasignar: ' + (e.message || e));
    }
  });

  // Quitar asignación desde card de vehículo
  vGrid.addEventListener('click', async (e) => {
    const btn = e.target.closest('button[data-action="quitar-vehiculo"]'); if (!btn) return;
    const card = e.target.closest('.card'); const vehiculoId = parseInt(card.getAttribute('data-vehiculo-id') || '0', 10);
    const veh = vehicleById[vehiculoId]; if (!veh || !veh.assignedDriverId) return;
    if (!confirm('¿Quitar chofer de este vehículo?')) return;

    try {
      const res = await fetch('/Pedidos_GA/desasignar_vehiculo.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify({ vehiculo_id: vehiculoId })
      });
      const j = await res.json(); if (!j.ok) throw new Error(j.error || 'Error');

      const d = driverById[veh.assignedDriverId]; if (d) d.assignedVehicleId = null;
      veh.assignedDriverId = null;

      renderDrivers(); animateBars();
      renderVehicles(); animateBars();
    } catch (e) {
      alert('No se pudo desasignar: ' + (e.message || e));
    }
  });

  // Render inicial
  renderVehicles();
  renderDrivers();
  animateBars();
  </script>
</body>
</html>
