<?php
// api_mantto.php
header('Content-Type: application/json; charset=utf-8');
@ini_set('session.cookie_httponly', true);
@ini_set('session.cookie_secure', true);
@session_name('GA');
@session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: /Pedidos_GA/Sesion/login.html");
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

/** CONFIG DB **/
$DB_HOST = '18.211.75.118';
$DB_NAME = 'gpoascen_pedidos_app';
$DB_USER = 'root';
$DB_PASS = '04nm2fdLefCxM';

try {
  $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
  ]);
  // Asegurar columnas de control en orden_servicio (estatus/fecha_programada)
  try {
    $pdo->exec("ALTER TABLE orden_servicio ADD COLUMN IF NOT EXISTS estatus VARCHAR(20) NULL, ADD COLUMN IF NOT EXISTS fecha_programada DATE NULL");
  } catch (Throwable $e) { /* ignora si no aplica */ }
  try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS orden_servicio_hist (
                  id INT AUTO_INCREMENT PRIMARY KEY,
                  id_orden INT NOT NULL,
                  de VARCHAR(20) NULL,
                  a VARCHAR(20) NOT NULL,
                  hecho_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  usuario VARCHAR(64) NULL,
                  comentario VARCHAR(255) NULL,
                  INDEX idx_hist_orden (id_orden)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
  } catch (Throwable $e) {}
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false, 'error'=>'DB_CONN', 'msg'=>$e->getMessage()]);
  exit;
}

function json_input() {
  $raw = file_get_contents('php://input');
  if ($raw) {
    $j = json_decode($raw, true);
    if (json_last_error() === JSON_ERROR_NONE) return $j;
  }
  return [];
}

/** Helpers de consulta **/
function select_tablero(PDO $pdo, ?int $singleId = null) {
  // Nota: usamos subconsultas; no alteramos tablas.
  $where = $singleId ? "WHERE os.id = :id" : "";
  $sql = "
    SELECT
      os.id,
      os.id_vehiculo,
      os.id_servicio,
      os.duracion_minutos,
      os.notas,
      DATE_FORMAT(os.creado_en, '%Y-%m-%d') AS fecha,

      (SELECT v.placa FROM vehiculos v WHERE v.id_vehiculo = os.id_vehiculo)   AS placa,
      (SELECT v.tipo  FROM vehiculos v WHERE v.id_vehiculo = os.id_vehiculo)   AS tipo,
      (SELECT v.Sucursal FROM vehiculos v WHERE v.id_vehiculo = os.id_vehiculo) AS suc,
      (SELECT v.Km_Actual FROM vehiculos v WHERE v.id_vehiculo = os.id_vehiculo) AS km,
      (SELECT s.nombre FROM servicios s WHERE s.id = os.id_servicio)           AS servicio,

      COALESCE((SELECT SUM(m.cantidad) FROM orden_servicio_material m WHERE m.id_orden = os.id),0) AS req,
      (
        SELECT COUNT(*) FROM orden_servicio_material m
        JOIN inventario i ON i.id = m.id_inventario
        WHERE m.id_orden = os.id AND i.cantidad < m.cantidad
      ) AS faltantes,

      os.estatus,
      os.notas,
      os.fecha_programada,
      EXISTS(SELECT 1 FROM inventario_movimiento im
        WHERE im.referencia = CONCAT('OS:', os.id) AND im.tipo='AJUSTE') AS has_reserva
    FROM orden_servicio os
    $where
    ORDER BY os.id DESC
  ";
  $stmt = $pdo->prepare($sql);
  if ($singleId) $stmt->bindValue(':id', $singleId, PDO::PARAM_INT);
  $stmt->execute();
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // calcular status en PHP para mayor legibilidad
  foreach ($rows as &$r) {
    $st = trim((string)($r['estatus'] ?? ''));
    if ($st === '') {
      $st = !empty($r['has_reserva']) ? 'Programado' : 'Pendiente';
    }
    $r['status'] = $st;
    $r['faltantes'] = (int)($r['faltantes'] ?? 0);
    $r['prio'] = ($r['duracion_minutos'] >= 120 ? 'Alta' : ($r['duracion_minutos'] >= 60 ? 'Media' : 'Baja'));
    $notes = (string)($r['notas'] ?? '');
    $r['auto_km'] = (stripos($notes, 'AUTO_KM') !== false);
  }
  return $rows;
}

try {
  if ($method === 'GET' && $action === 'list') {
    echo json_encode(['ok'=>true, 'items'=>select_tablero($pdo)]); exit;
  }

  if ($method === 'GET' && $action === 'options') {
    // Catálogos para modal con compatibilidades
    $veh = $pdo->query("SELECT id_vehiculo AS id, placa, tipo, Km_Actual AS km FROM vehiculos ORDER BY placa")->fetchAll(PDO::FETCH_ASSOC);
    $srv = $pdo->query("SELECT s.id, s.nombre, s.duracion_minutos,
                               GROUP_CONCAT(sv.id_vehiculo) AS vehs
                        FROM servicios s
                        LEFT JOIN servicio_vehiculo sv ON sv.id_servicio = s.id
                        GROUP BY s.id
                        ORDER BY s.nombre")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($srv as &$s) {
      $list = [];
      if (!empty($s['vehs'])) foreach (explode(',', $s['vehs']) as $v) { if ($v!=='') $list[]=(int)$v; }
      $s['vehiculos'] = $list; unset($s['vehs']);
    }
    $inv = $pdo->query("SELECT i.id, i.nombre, i.cantidad, i.stock_minimo,
                               i.presentacion_unidad, i.presentacion_cantidad,
                               GROUP_CONCAT(iv.id_vehiculo) AS vehs
                        FROM inventario i
                        LEFT JOIN inventario_vehiculo iv ON iv.id_inventario=i.id
                        GROUP BY i.id
                        ORDER BY i.nombre")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($inv as &$i) {
      $list = [];
      if (!empty($i['vehs'])) foreach (explode(',', $i['vehs']) as $v) { if ($v!=='') $list[]=(int)$v; }
      $i['vehiculos'] = $list; unset($i['vehs']);
      $i['unidad'] = $i['presentacion_unidad']; unset($i['presentacion_unidad']);
      $i['contenido'] = isset($i['presentacion_cantidad']) ? (float)$i['presentacion_cantidad'] : null; unset($i['presentacion_cantidad']);
    }
    echo json_encode(['ok'=>true, 'vehiculos'=>$veh, 'servicios'=>$srv, 'inventario'=>$inv]); exit;
  }

  if ($method === 'GET' && $action === 'order') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'VALIDATION']); exit; }

    $row = select_tablero($pdo, $id)[0] ?? null;
    if (!$row) { http_response_code(404); echo json_encode(['ok'=>false,'error'=>'NOT_FOUND']); exit; }

    // materiales requeridos
    $stmtM = $pdo->prepare("SELECT m.id_inventario, m.cantidad,
                                   i.nombre, i.marca, i.modelo, i.cantidad AS stock
                            FROM orden_servicio_material m
                            JOIN inventario i ON i.id = m.id_inventario
                            WHERE m.id_orden = ?");
    $stmtM->execute([$id]);
    $mats = $stmtM->fetchAll(PDO::FETCH_ASSOC);

    // Si la orden aún no tiene materiales cargados, sugerirlos desde el catálogo del servicio
    if (empty($mats)) {
      try {
        $srvId = (int)($row['id_servicio'] ?? 0);
        if ($srvId > 0) {
          $q = $pdo->prepare("SELECT si.id_inventario, si.cantidad,
                                      i.nombre, i.marca, i.modelo, i.cantidad AS stock
                               FROM servicio_insumo si
                               JOIN inventario i ON i.id = si.id_inventario
                               WHERE si.id_servicio = ?");
          $q->execute([$srvId]);
          $mats = $q->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }
      } catch (Throwable $e) { /* ignorar sugerencias si falla */ }
    }

    // movimientos relacionados (si existen)
    $stmtV = $pdo->prepare("SELECT id, id_inventario, tipo, cantidad, referencia, comentario
                            FROM inventario_movimiento
                            WHERE referencia = CONCAT('OS:', ?)
                            ORDER BY id DESC");
    $stmtV->execute([$id]);
    $movs = $stmtV->fetchAll(PDO::FETCH_ASSOC);

    $stmtH = $pdo->prepare("SELECT de,a,hecho_en,COALESCE(usuario,'') AS usuario, COALESCE(comentario,'') AS comentario FROM orden_servicio_hist WHERE id_orden=? ORDER BY hecho_en DESC, id DESC");
    $stmtH->execute([$id]);
    $hist = $stmtH->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['ok'=>true, 'order'=>$row, 'materiales'=>$mats, 'movimientos'=>$movs, 'historial'=>$hist]); exit;
  }

  if ($method === 'POST' && $action === 'reschedule') {
    $in = json_input();
    $id = (int)($in['id'] ?? 0);
    $fecha = trim($in['fecha_programada'] ?? '');
    if ($id<=0 || $fecha==='') { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'VALIDATION']); exit; }
    $cur = select_tablero($pdo, $id)[0] ?? null;
    if (!$cur) { http_response_code(404); echo json_encode(['ok'=>false,'error'=>'NOT_FOUND']); exit; }
    if ((string)$cur['status'] !== 'Programado') { echo json_encode(['ok'=>false,'error'=>'ONLY_PROGRAMADO','msg'=>'Solo puedes reprogramar cuando la orden está Programada']); exit; }
    try { $pdo->exec("ALTER TABLE orden_servicio ADD COLUMN IF NOT EXISTS fecha_programada DATE NULL"); } catch (Throwable $e) {}
    try {
      $pdo->prepare("UPDATE orden_servicio SET fecha_programada = ? WHERE id = ?")->execute([$fecha,$id]);
      $user = isset($_SESSION['usuario']) ? (string)$_SESSION['usuario'] : null;
      $pdo->prepare("INSERT INTO orden_servicio_hist (id_orden,de,a,usuario,comentario) VALUES (?,?,?,?,?)")
          ->execute([$id,'Programado','Programado',$user,'Reprogramado: '.$fecha]);
      echo json_encode(['ok'=>true,'msg'=>'Fecha programada actualizada']); exit;
    } catch (Throwable $e) { http_response_code(500); echo json_encode(['ok'=>false,'error'=>'SERVER','msg'=>$e->getMessage()]); exit; }
  }

  if ($method === 'POST' && $action === 'create') {
    $in = json_input();
    $id_vehiculo = (int)($in['id_vehiculo'] ?? 0);
    $id_servicio = (int)($in['id_servicio'] ?? 0);
    $duracion    = (int)($in['duracion_minutos'] ?? 0);
    $notas       = trim($in['notas'] ?? '');
    $materiales  = $in['materiales'] ?? []; // [{id_inventario, cantidad}, ...]

    if ($id_vehiculo<=0) {
      http_response_code(400);
      echo json_encode(['ok'=>false,'error'=>'VALIDATION','msg'=>'Vehículo es requerido']); exit;
    }

    // Compatibilidad servicio-vehículo (si el servicio tiene restricciones y está asignado)
    if ($id_servicio > 0) {
      try {
        $st = $pdo->prepare("SELECT COUNT(*) FROM servicio_vehiculo WHERE id_servicio=?");
        $st->execute([$id_servicio]);
        $srvHasRestr = (int)$st->fetchColumn();
        if ($srvHasRestr > 0) {
          $chk = $pdo->prepare("SELECT COUNT(*) FROM servicio_vehiculo WHERE id_servicio=? AND id_vehiculo=?");
          $chk->execute([$id_servicio, $id_vehiculo]);
          if ((int)$chk->fetchColumn() === 0) {
            echo json_encode(['ok'=>false,'error'=>'INCOMPATIBLE','msg'=>'Servicio no compatible con el vehículo seleccionado']); exit;
          }
        }
      } catch (Throwable $e) {}
    }

    // Determinar materiales a validar (enviados o catálogo)
    $matsToUse = [];
    if (is_array($materiales) && count($materiales)>0) {
      foreach ($materiales as $m) {
        $iid = (int)($m['id_inventario'] ?? 0);
        $cant = (float)($m['cantidad'] ?? 0);
        if ($iid>0 && $cant>0) $matsToUse[] = ['id_inventario'=>$iid,'cantidad'=>$cant];
      }
    } else {
      try {
        $q = $pdo->prepare("SELECT id_inventario, cantidad FROM servicio_insumo WHERE id_servicio=?");
        $q->execute([$id_servicio]);
        foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $m) {
          $iid=(int)$m['id_inventario']; $cant=(float)$m['cantidad'];
          if ($iid>0 && $cant>0) $matsToUse[] = ['id_inventario'=>$iid,'cantidad'=>$cant];
        }
      } catch (Throwable $e) {}
    }

    // Compatibilidad inventario-vehículo (si el insumo tiene restricciones)
    if (!empty($matsToUse)) {
      $bad = [];
      foreach ($matsToUse as $m) {
        $iid = (int)$m['id_inventario'];
        try {
          $stAll = $pdo->prepare("SELECT COUNT(*) FROM inventario_vehiculo WHERE id_inventario=?");
          $stAll->execute([$iid]);
          $invHasRestr = (int)$stAll->fetchColumn();
          if ($invHasRestr > 0) {
            $chk = $pdo->prepare("SELECT COUNT(*) FROM inventario_vehiculo WHERE id_inventario=? AND id_vehiculo=?");
            $chk->execute([$iid, $id_vehiculo]);
            if ((int)$chk->fetchColumn() === 0) {
              $nm = $pdo->prepare("SELECT nombre FROM inventario WHERE id=?");
              $nm->execute([$iid]);
              $label = $nm->fetchColumn();
              $bad[] = $label ? (string)$label : ('ID '.$iid);
            }
          }
        } catch (Throwable $e) {}
      }
      if (!empty($bad)) { echo json_encode(['ok'=>false,'error'=>'INCOMPATIBLE','msg'=>'Hay materiales no compatibles con el vehículo: '.implode(', ',$bad)]); exit; }
    }

    $pdo->beginTransaction();

    // crear orden (id_servicio puede ser NULL si no está asignado)
    $stmt = $pdo->prepare("INSERT INTO orden_servicio (id_vehiculo, id_servicio, duracion_minutos, notas) VALUES (?,?,?,?)");
    $stmt->execute([$id_vehiculo, ($id_servicio > 0 ? $id_servicio : null), $duracion, $notas ?: null]);
    $newId = (int)$pdo->lastInsertId();

    // materiales: usar los enviados o, si vienen vacíos, precargar desde servicio_insumo
    $stmtM = $pdo->prepare("INSERT INTO orden_servicio_material (id_orden, id_inventario, cantidad) VALUES (?,?,?)");
    $inserted = 0;
    if (is_array($materiales) && count($materiales)>0) {
      foreach ($materiales as $m) {
        $iid = (int)($m['id_inventario'] ?? 0);
        $cant = (float)($m['cantidad'] ?? 0);
        if ($iid>0 && $cant>0) { $stmtM->execute([$newId, $iid, $cant]); $inserted++; }
      }
    }
    if ($inserted === 0) {
      try {
        $q = $pdo->prepare("SELECT id_inventario, cantidad FROM servicio_insumo WHERE id_servicio=?");
        $q->execute([$id_servicio]);
        foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $m) {
          $iid=(int)$m['id_inventario']; $cant=(float)$m['cantidad'];
          if ($iid>0 && $cant>0) { $stmtM->execute([$newId, $iid, $cant]); $inserted++; }
        }
      } catch (Throwable $e) { /* sin bloqueo si no hay catálogo */ }
    }

    // marcador programado si usuario así lo pidió
    if (!empty($in['programar']) && $in['programar'] === true) {
      try { $pdo->exec("ALTER TABLE orden_servicio ADD COLUMN IF NOT EXISTS estatus VARCHAR(20) NULL"); } catch (Throwable $e) {}
      try { $pdo->prepare("UPDATE orden_servicio SET estatus='Programado' WHERE id=?")->execute([$newId]); } catch (Throwable $e) {}
      try { $user = isset($_SESSION['usuario']) ? (string)$_SESSION['usuario'] : null; $pdo->prepare("INSERT INTO orden_servicio_hist (id_orden,de,a,usuario,comentario) VALUES (?,?,?,?,?)")->execute([$newId,'Pendiente','Programado',$user,'Creación programada']); } catch (Throwable $e) {}
    }

    // Si se creó ya como Programado, reservamos materiales ahora dentro de la misma transacción
    if (!empty($in['programar']) && $in['programar'] === true) {
      // obtener requeridos
      $mats = $pdo->prepare("SELECT id_inventario, cantidad FROM orden_servicio_material WHERE id_orden = ?");
      $mats->execute([$newId]);
      $reqs = $mats->fetchAll(PDO::FETCH_ASSOC);
      // validar stock
      foreach ($reqs as $m) {
        $iid = (int)$m['id_inventario']; $q = (int)$m['cantidad'];
        $row = $pdo->prepare("SELECT cantidad FROM inventario WHERE id = ? FOR UPDATE");
        $row->execute([$iid]);
        $disp = (int)($row->fetchColumn() ?: 0);
        if ($disp < $q) {
          $pdo->rollBack();
          http_response_code(400);
          echo json_encode(['ok'=>false,'error'=>'NO_STOCK','msg'=>'Inventario insuficiente al programar']); exit;
        }
      }
      // descontar + movimiento
      $insMov = $pdo->prepare("INSERT INTO inventario_movimiento (id_inventario, tipo, cantidad, referencia, comentario) VALUES (?,?,?,?,?)");
      $updInv = $pdo->prepare("UPDATE inventario SET cantidad = cantidad - ? WHERE id = ?");
      foreach ($reqs as $m) {
        $iid = (int)$m['id_inventario']; $q = (int)$m['cantidad'];
        $updInv->execute([$q, $iid]);
        $insMov->execute([$iid, 'AJUSTE', $q, 'OS:'.$newId, 'Reserva Programado (creación)']);
      }
    }

    $pdo->commit();

    $row = select_tablero($pdo, $newId)[0] ?? null;
    echo json_encode(['ok'=>true, 'item'=>$row]); exit;
  }

  if ($method === 'POST' && $action === 'update_status') {
    $in = json_input();
    $id = (int)($in['id'] ?? 0);
    $to = $in['estatus'] ?? '';
    if ($id<=0 || !in_array($to, ['Pendiente','Programado','EnTaller','Completado'], true)) {
      http_response_code(400);
      echo json_encode(['ok'=>false,'error'=>'VALIDATION']); exit;
    }

    $cur = select_tablero($pdo, $id)[0] ?? null;
    if (!$cur) { http_response_code(404); echo json_encode(['ok'=>false,'error'=>'NOT_FOUND']); exit; }

    if ($to === 'Programado') {
      // Exigir servicio asignado antes de programar
      $cur = select_tablero($pdo, $id)[0] ?? null;
      if (!$cur) { http_response_code(404); echo json_encode(['ok'=>false,'error'=>'NOT_FOUND']); exit; }
      if ((int)($cur['id_servicio'] ?? 0) <= 0) {
        echo json_encode(['ok'=>false,'error'=>'NEED_SERVICE','msg'=>'Asigna un servicio antes de programar']); exit;
      }
      $fecha = trim($in['fecha_programada'] ?? '');
      // si ya está programado o más adelante, solo actualiza fecha
      if (in_array((string)$cur['status'], ['Programado','EnTaller','Completado'], true)) {
        if ($fecha !== '') {
          try { $pdo->exec("ALTER TABLE orden_servicio ADD COLUMN IF NOT EXISTS fecha_programada DATE NULL"); } catch (Throwable $e) {}
          try { $pdo->prepare("UPDATE orden_servicio SET fecha_programada = ?, estatus='Programado' WHERE id = ?")->execute([$fecha, $id]); }
          catch (Throwable $e) { /* sin fallback para no violar FK */ }
        }
        try { $user = isset($_SESSION['usuario']) ? (string)$_SESSION['usuario'] : null; $pdo->prepare("INSERT INTO orden_servicio_hist (id_orden,de,a,usuario,comentario) VALUES (?,?,?,?,?)")->execute([$id,(string)$cur['status'],'Programado',$user,$fecha?('Prog: '.$fecha):null]); } catch (Throwable $e) {}
        echo json_encode(['ok'=>true,'msg'=>'Ya estaba programado; fecha actualizada']); exit;
      }
      // obtener requeridos
      $mats = $pdo->prepare("SELECT id_inventario, cantidad FROM orden_servicio_material WHERE id_orden = ?");
      $mats->execute([$id]);
      $reqs = $mats->fetchAll(PDO::FETCH_ASSOC);
      // validar stock
      $faltantes = [];
      foreach ($reqs as $m) {
        $iid = (int)$m['id_inventario']; $q = (int)$m['cantidad'];
        $row = $pdo->prepare("SELECT cantidad FROM inventario WHERE id = ?");
        $row->execute([$iid]);
        $disp = (int)($row->fetchColumn() ?: 0);
        if ($disp < $q) $faltantes[] = ['id_inventario'=>$iid, 'req'=>$q, 'disp'=>$disp];
      }
      if (!empty($faltantes)) { echo json_encode(['ok'=>false,'error'=>'NO_STOCK','msg'=>'Inventario insuficiente','faltantes'=>$faltantes]); exit; }

      $pdo->beginTransaction();
      try {
        // reservar: descontar inventario + movimiento tipo AJUSTE (reserva controlada)
        $insMov = $pdo->prepare("INSERT INTO inventario_movimiento (id_inventario, tipo, cantidad, referencia, comentario) VALUES (?,?,?,?,?)");
        $updInv = $pdo->prepare("UPDATE inventario SET cantidad = cantidad - ? WHERE id = ?");
        foreach ($reqs as $m) {
          $iid = (int)$m['id_inventario']; $q = (int)$m['cantidad'];
          $updInv->execute([$q, $iid]);
          $insMov->execute([$iid, 'AJUSTE', $q, 'OS:'.$id, 'Reserva Programado']);
        }

        // fecha programada (si existe columna)
        if ($fecha !== '') {
          try {
            $pdo->exec("ALTER TABLE orden_servicio ADD COLUMN IF NOT EXISTS fecha_programada DATE NULL");
          } catch (Throwable $e) { /* ignora si no soporta IF NOT EXISTS */ }
          try {
            $stmtF = $pdo->prepare("UPDATE orden_servicio SET fecha_programada = ? WHERE id = ?");
            $stmtF->execute([$fecha, $id]);
          } catch (Throwable $e) { /* sin fallback para no violar FK */ }
        }
        // asegurar estatus aunque no haya fecha
        try { $pdo->prepare("UPDATE orden_servicio SET estatus='Programado' WHERE id = ?")->execute([$id]); } catch (Throwable $e) {}
        try { $user = isset($_SESSION['usuario']) ? (string)$_SESSION['usuario'] : null; $pdo->prepare("INSERT INTO orden_servicio_hist (id_orden,de,a,usuario,comentario) VALUES (?,?,?,?,?)")->execute([$id,(string)$cur['status'],'Programado',$user,$fecha?('Prog: '.$fecha):null]); } catch (Throwable $e) {}
        $pdo->commit();
        echo json_encode(['ok'=>true,'msg'=>'Programado y materiales reservados']); exit;
      } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['ok'=>false,'error'=>'SERVER','msg'=>$e->getMessage()]); exit;
      }
    }

    if ($to === 'EnTaller') {
      try { $pdo->prepare("UPDATE orden_servicio SET estatus='EnTaller' WHERE id=?")->execute([$id]); } catch (Throwable $e) {}
      try { $user = isset($_SESSION['usuario']) ? (string)$_SESSION['usuario'] : null; $pdo->prepare("INSERT INTO orden_servicio_hist (id_orden,de,a,usuario) VALUES (?,?,?,?)")->execute([$id,(string)$cur['status'],'EnTaller',$user]); } catch (Throwable $e) {}

      // Desasignar chofer del vehículo al entrar a taller (si tiene)
      try {
        $veh = (int)($cur['id_vehiculo'] ?? 0);
        if ($veh > 0) {
          // Cierra tramo abierto en historial_conductores
          try { $pdo->prepare("UPDATE historial_conductores SET fecha_fin = NOW() WHERE id_vehiculo = ? AND fecha_fin IS NULL")->execute([$veh]); } catch (Throwable $e) {}
          // Quita asignación actual en vehiculos
          try { $pdo->prepare("UPDATE vehiculos SET id_chofer_asignado = NULL WHERE id_vehiculo = ?")->execute([$veh]); } catch (Throwable $e) {}
        }
      } catch (Throwable $e) {}

      echo json_encode(['ok'=>true,'msg'=>'Movido a En Taller']); exit;
    }

    if ($to === 'Completado') {
      try { $pdo->prepare("UPDATE orden_servicio SET estatus='Completado' WHERE id=?")->execute([$id]); } catch (Throwable $e) {}
      // Reset opcional de Km_Actual a 0
      $reset = !empty($in['reset_km']);
      if ($reset) {
        try {
          $veh = (int)($cur['id_vehiculo'] ?? 0);
          if ($veh > 0) { $pdo->prepare("UPDATE vehiculos SET Km_Actual=0 WHERE id_vehiculo=?")->execute([$veh]); }
        } catch (Throwable $e) {}
      }
      try { $user = isset($_SESSION['usuario']) ? (string)$_SESSION['usuario'] : null; $pdo->prepare("INSERT INTO orden_servicio_hist (id_orden,de,a,usuario) VALUES (?,?,?,?)")->execute([$id,(string)$cur['status'],'Completado',$user]); } catch (Throwable $e) {}

      // Marcar observaciones de checklist como resueltas
      try {
        $notas = (string)($cur['notas'] ?? '');
        // Verificar si es una orden de checklist (contiene [CHECKLIST)
        if (stripos($notas, '[CHECKLIST') !== false) {
          // Extraer sección (entre [CHECKLIST - y ]) - usando trim para limpiar espacios
          if (preg_match('/\[CHECKLIST\s*-\s*(.+?)\]/i', $notas, $matchSeccion)) {
            $seccion = trim($matchSeccion[1]);
            // Extraer ítem (después de "Ítem: " hasta el salto de línea o final)
            // Usando preg_match con DOTALL para capturar todo hasta la siguiente línea que empieza con texto
            if (preg_match('/Ítem:\s*(.+?)(?:\n[A-Z]|\n\n|$)/s', $notas, $matchItem)) {
              $item = trim($matchItem[1]);
              $idVehiculo = (int)($cur['id_vehiculo'] ?? 0);

              // Validar que tengamos todos los datos necesarios
              if ($idVehiculo > 0 && !empty($seccion) && !empty($item)) {
                // Marcar TODOS los checklists de este vehículo+sección+ítem como resueltos
                // Usando TRIM y UPPER para hacer la comparación más flexible
                $sqlUpdate = "
                  UPDATE checklist_vehicular
                  SET resuelto = 1,
                      fecha_resolucion = NOW(),
                      orden_resolucion = ?
                  WHERE id_vehiculo = ?
                    AND UPPER(TRIM(seccion)) = UPPER(TRIM(?))
                    AND UPPER(TRIM(item)) = UPPER(TRIM(?))
                    AND calificacion = 'Mal'
                    AND COALESCE(resuelto, 0) = 0
                ";
                $stmtUpdate = $pdo->prepare($sqlUpdate);
                $stmtUpdate->execute([$id, $idVehiculo, $seccion, $item]);
                $affectedRows = $stmtUpdate->rowCount();

                // Log detallado de observaciones resueltas
                if ($affectedRows > 0) {
                  error_log("✅ Orden #{$id} completada: se marcaron {$affectedRows} observaciones como resueltas (Vehículo: {$idVehiculo}, Sección: '{$seccion}', Ítem: '{$item}')");
                } else {
                  error_log("⚠️ Orden #{$id} completada: no se encontraron observaciones pendientes que marcar como resueltas (Vehículo: {$idVehiculo}, Sección: '{$seccion}', Ítem: '{$item}')");
                }
              } else {
                error_log("⚠️ Orden #{$id}: datos incompletos para marcar como resuelta - Vehículo: {$idVehiculo}, Sección: '{$seccion}', Ítem: '{$item}'");
              }
            } else {
              error_log("⚠️ Orden #{$id}: no se pudo extraer el ítem de las notas");
            }
          } else {
            error_log("⚠️ Orden #{$id}: no se pudo extraer la sección de las notas");
          }
        }
      } catch (Throwable $e) {
        // No bloquear la finalización de la orden si falla el marcado de resolución
        error_log("❌ Error al marcar observaciones como resueltas para orden #{$id}: " . $e->getMessage());
      }

      echo json_encode(['ok'=>true,'msg'=>'Servicio completado']); exit;
    }

    if ($to === 'Pendiente') {
      try { $pdo->prepare("UPDATE orden_servicio SET estatus='Pendiente' WHERE id=?")->execute([$id]); } catch (Throwable $e) {}
      try { $user = isset($_SESSION['usuario']) ? (string)$_SESSION['usuario'] : null; $pdo->prepare("INSERT INTO orden_servicio_hist (id_orden,de,a,usuario) VALUES (?,?,?,?)")->execute([$id,(string)$cur['status'],'Pendiente',$user]); } catch (Throwable $e) {}
      echo json_encode(['ok'=>true,'msg'=>'Movido a Pendiente']); exit;
    }
  }

  // Asignar o cambiar servicio de una orden (usado para AUTO_KM)
  if ($method === 'POST' && $action === 'set_service') {
    $in = json_input();
    $id_orden   = (int)($in['id'] ?? 0);
    $id_serv    = (int)($in['id_servicio'] ?? 0);
    $dur        = isset($in['duracion_minutos']) ? (int)$in['duracion_minutos'] : null;
    $materiales = is_array($in['materiales'] ?? null) ? $in['materiales'] : null; // [{id_inventario,cantidad}]
    if ($id_orden<=0 || $id_serv<=0) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'VALIDATION','msg'=>'Datos incompletos']); exit; }

    // Cargar orden actual
    $cur = select_tablero($pdo, $id_orden)[0] ?? null;
    if (!$cur) { http_response_code(404); echo json_encode(['ok'=>false,'error'=>'NOT_FOUND']); exit; }
    if (in_array((string)$cur['status'], ['Programado','EnTaller','Completado'], true)) {
      http_response_code(409); echo json_encode(['ok'=>false,'error'=>'STATUS','msg'=>'Solo se puede cambiar servicio en Pendiente']); exit;
    }

    // Validar compatibilidad servicio-vehículo si hay restricciones
    try {
      $st = $pdo->prepare("SELECT COUNT(*) FROM servicio_vehiculo WHERE id_servicio=?");
      $st->execute([$id_serv]);
      $restrict = (int)$st->fetchColumn();
      if ($restrict>0) {
        $chk = $pdo->prepare("SELECT COUNT(*) FROM servicio_vehiculo WHERE id_servicio=? AND id_vehiculo=?");
        $chk->execute([$id_serv, (int)$cur['id_vehiculo']]);
        if ((int)$chk->fetchColumn()===0) {
          echo json_encode(['ok'=>false,'error'=>'INCOMPATIBLE','msg'=>'Servicio no compatible con el vehículo']); exit;
        }
      }
    } catch (Throwable $e) {}

    // Determinar materiales a usar: si llegan, reemplazan el catálogo; validar compatibilidad inventario-vehículo
    $matsToUse = [];
    if (is_array($materiales) && count($materiales)>0) {
      foreach ($materiales as $m) {
        $iid = (int)($m['id_inventario'] ?? 0);
        $cant = (float)($m['cantidad'] ?? 0);
        if ($iid>0 && $cant>0) $matsToUse[] = ['id_inventario'=>$iid,'cantidad'=>$cant];
      }
    } else {
      try {
        $q = $pdo->prepare("SELECT id_inventario, cantidad FROM servicio_insumo WHERE id_servicio=?");
        $q->execute([$id_serv]);
        foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $m) {
          $iid=(int)$m['id_inventario']; $cant=(float)$m['cantidad'];
          if ($iid>0 && $cant>0) $matsToUse[] = ['id_inventario'=>$iid,'cantidad'=>$cant];
        }
      } catch (Throwable $e) {}
    }
    if (!empty($matsToUse)) {
      $bad = [];
      foreach ($matsToUse as $m) {
        $iid = (int)$m['id_inventario'];
        try {
          $stAll = $pdo->prepare("SELECT COUNT(*) FROM inventario_vehiculo WHERE id_inventario=?");
          $stAll->execute([$iid]);
          $invHasRestr = (int)$stAll->fetchColumn();
          if ($invHasRestr > 0) {
            $chk = $pdo->prepare("SELECT COUNT(*) FROM inventario_vehiculo WHERE id_inventario=? AND id_vehiculo=?");
            $chk->execute([$iid, (int)$cur['id_vehiculo']]);
            if ((int)$chk->fetchColumn() === 0) {
              $nm = $pdo->prepare("SELECT nombre FROM inventario WHERE id=?");
              $nm->execute([$iid]);
              $label = $nm->fetchColumn();
              $bad[] = $label ? (string)$label : ('ID '.$iid);
            }
          }
        } catch (Throwable $e) {}
      }
      if (!empty($bad)) { echo json_encode(['ok'=>false,'error'=>'INCOMPATIBLE','msg'=>'Hay materiales no compatibles con el vehículo: '.implode(', ',$bad)]); exit; }
    }

    $pdo->beginTransaction();
    try {
      // Actualizar servicio y duración; limpiar marca AUTO_KM de notas
      $sql = "UPDATE orden_servicio SET id_servicio=?, notas=TRIM(REPLACE(COALESCE(notas,''),'[AUTO_KM]',''))";
      $params = [$id_serv];
      if ($dur !== null) { $sql .= ", duracion_minutos=?"; $params[] = $dur; }
      $sql .= " WHERE id=?"; $params[] = $id_orden;
      $stmt = $pdo->prepare($sql); $stmt->execute($params);

      // Reemplazar materiales por los del catálogo de ese servicio
      $pdo->prepare("DELETE FROM orden_servicio_material WHERE id_orden=?")->execute([$id_orden]);
      $ins = $pdo->prepare("INSERT INTO orden_servicio_material (id_orden,id_inventario,cantidad) VALUES (?,?,?)");
      foreach ($matsToUse as $m) {
        $iid=(int)$m['id_inventario']; $cant=(float)$m['cantidad'];
        if ($iid>0 && $cant>0) $ins->execute([$id_orden,$iid,$cant]);
      }

      // Historial
      try {
        $user = isset($_SESSION['usuario']) ? (string)$_SESSION['usuario'] : null;
        $pdo->prepare("INSERT INTO orden_servicio_hist (id_orden,de,a,usuario,comentario) VALUES (?,?,?,?,?)")
            ->execute([$id_orden,'Pendiente','Pendiente',$user,'Asignación de servicio #'.$id_serv]);
      } catch (Throwable $e) {}

      $pdo->commit();
      echo json_encode(['ok'=>true,'msg'=>'Servicio asignado']); exit;
    } catch (Throwable $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      http_response_code(500); echo json_encode(['ok'=>false,'error'=>'SERVER','msg'=>$e->getMessage()]); exit;
    }
  }

  if ($method === 'GET' && $action === 'observaciones') {
    try {
      // Obtener vehículos con ítems calificados como "Mal" en checklist
      // AGRUPADO por vehículo + sección + ítem, con contador de reportes
      // SOLO items NO RESUELTOS
      $sql = "
        SELECT
          cv.id_vehiculo,
          v.placa,
          v.tipo,
          v.Sucursal,
          v.Km_Actual,
          cv.seccion,
          cv.item,
          COUNT(*) as total_reportes,
          MAX(cv.fecha_inspeccion) as ultima_inspeccion,
          MAX(cv.kilometraje) as ultimo_km,
          GROUP_CONCAT(
            CONCAT(DATE_FORMAT(cv.fecha_inspeccion, '%Y-%m-%d'), '|', COALESCE(cv.observaciones_rotulado, 'Sin observaciones'))
            ORDER BY cv.fecha_inspeccion DESC
            SEPARATOR '###'
          ) as historial_reportes
        FROM checklist_vehicular cv
        INNER JOIN vehiculos v ON cv.id_vehiculo = v.id_vehiculo
        WHERE cv.calificacion = 'Mal'
          AND COALESCE(cv.resuelto, 0) = 0
        GROUP BY cv.id_vehiculo, cv.seccion, cv.item, v.placa, v.tipo, v.Sucursal, v.Km_Actual
        ORDER BY cv.seccion, v.placa, MAX(cv.fecha_inspeccion) DESC
      ";

      $stmt = $pdo->prepare($sql);
      $stmt->execute();
      $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

      // Procesar historial y buscar órdenes de servicio
      $result = [];
      foreach ($items as $item) {
        $idVeh = (int)$item['id_vehiculo'];
        $seccion = $item['seccion'];
        $itemNombre = $item['item'];

        // Parsear historial de reportes
        $historial = [];
        if (!empty($item['historial_reportes'])) {
          $reportes = explode('###', $item['historial_reportes']);
          foreach ($reportes as $reporte) {
            $partes = explode('|', $reporte, 2);
            if (count($partes) === 2) {
              $historial[] = [
                'fecha' => $partes[0],
                'observacion' => $partes[1]
              ];
            }
          }
        }
        $item['historial'] = $historial;
        unset($item['historial_reportes']);

        // Buscar orden de servicio relacionada ESPECÍFICA para este ítem
        $sqlOrden = "
          SELECT id, estatus
          FROM orden_servicio
          WHERE id_vehiculo = ?
            AND notas LIKE ?
            AND COALESCE(estatus, 'Pendiente') IN ('Pendiente', 'Programado', 'EnTaller')
          LIMIT 1
        ";
        $stmtOrden = $pdo->prepare($sqlOrden);
        // Buscar por ítem específico en las notas
        $searchPattern = "%Ítem: " . $itemNombre . "%";
        $stmtOrden->execute([$idVeh, $searchPattern]);
        $orden = $stmtOrden->fetch(PDO::FETCH_ASSOC);

        $item['orden_id'] = $orden ? $orden['id'] : null;
        $item['orden_estatus'] = $orden ? ($orden['estatus'] ?? 'Pendiente') : null;

        $result[] = $item;
      }

      echo json_encode(['ok'=>true, 'items'=>$result]); exit;
    } catch (Throwable $e) {
      http_response_code(500);
      echo json_encode(['ok'=>false, 'error'=>'OBSERVACIONES_ERROR', 'msg'=>$e->getMessage()]); exit;
    }
  }

  if ($method === 'POST' && $action === 'marcar_resuelto') {
    try {
      // Marcar TODOS los checklists de un ítem como resueltos cuando la orden se completa
      $data = json_decode(file_get_contents('php://input'), true);

      $idVehiculo = (int)($data['id_vehiculo'] ?? 0);
      $seccion = $data['seccion'] ?? '';
      $item = $data['item'] ?? '';
      $ordenId = (int)($data['orden_id'] ?? 0);

      if (!$idVehiculo || !$seccion || !$item || !$ordenId) {
        http_response_code(400);
        echo json_encode(['ok'=>false, 'msg'=>'Faltan parámetros requeridos']);
        exit;
      }

      // Marcar TODOS los checklists de este vehículo+sección+ítem como resueltos
      $sqlUpdate = "
        UPDATE checklist_vehicular
        SET resuelto = 1,
            fecha_resolucion = NOW(),
            orden_resolucion = ?
        WHERE id_vehiculo = ?
          AND seccion = ?
          AND item = ?
          AND calificacion = 'Mal'
          AND COALESCE(resuelto, 0) = 0
      ";

      $stmtUpdate = $pdo->prepare($sqlUpdate);
      $stmtUpdate->execute([$ordenId, $idVehiculo, $seccion, $item]);
      $affectedRows = $stmtUpdate->rowCount();

      echo json_encode([
        'ok' => true,
        'msg' => "Se marcaron {$affectedRows} observaciones como resueltas",
        'affected_rows' => $affectedRows
      ]);
      exit;
    } catch (Throwable $e) {
      http_response_code(500);
      echo json_encode(['ok'=>false, 'error'=>'MARCAR_RESUELTO_ERROR', 'msg'=>$e->getMessage()]);
      exit;
    }
  }

  if ($method === 'GET' && $action === 'observaciones_resueltas') {
    try {
      // Obtener órdenes de servicio completadas relacionadas con checklist
      $sql = "
        SELECT
          os.id as orden_id,
          os.id_vehiculo,
          os.notas,
          os.creado_en as fecha_creacion,
          os.estatus,
          v.placa,
          v.tipo,
          v.Sucursal,
          DATEDIFF(
            (SELECT MAX(hecho_en) FROM orden_servicio_hist WHERE id_orden = os.id AND a = 'Completado'),
            os.creado_en
          ) as dias_resolucion
        FROM orden_servicio os
        INNER JOIN vehiculos v ON os.id_vehiculo = v.id_vehiculo
        WHERE os.estatus = 'Completado'
          AND (os.notas LIKE '%[CHECKLIST%' OR os.notas LIKE '%Ítem:%')
        ORDER BY os.id DESC
        LIMIT 100
      ";

      $stmt = $pdo->prepare($sql);
      $stmt->execute();
      $ordenes = $stmt->fetchAll(PDO::FETCH_ASSOC);

      // Procesar cada orden para extraer sección e ítem de las notas
      $result = [];
      foreach ($ordenes as $orden) {
        $notas = $orden['notas'];

        // Extraer sección (entre [CHECKLIST - y ])
        preg_match('/\[CHECKLIST - (.+?)\]/', $notas, $matchSeccion);
        $seccion = $matchSeccion[1] ?? 'Sin sección';

        // Extraer ítem (después de "Ítem: " hasta el salto de línea)
        preg_match('/Ítem: (.+?)(\n|$)/s', $notas, $matchItem);
        $item = $matchItem[1] ?? 'Sin descripción';

        // Extraer observaciones (después de "Observaciones: " hasta el salto de línea)
        preg_match('/Observaciones: (.+?)(\n|$)/s', $notas, $matchObs);
        $observaciones = $matchObs[1] ?? '';

        $orden['seccion'] = $seccion;
        $orden['item'] = trim($item);
        $orden['observaciones'] = trim($observaciones);

        $result[] = $orden;
      }

      // Calcular métricas
      $totalResueltas = count($result);
      $diasPromedio = 0;
      if ($totalResueltas > 0) {
        $sumaDias = array_sum(array_column($result, 'dias_resolucion'));
        $diasPromedio = round($sumaDias / $totalResueltas, 1);
      }

      // Items más problemáticos (más veces resueltos)
      $itemsCount = [];
      foreach ($result as $r) {
        $key = $r['item'];
        if (!isset($itemsCount[$key])) {
          $itemsCount[$key] = ['item' => $key, 'count' => 0, 'seccion' => $r['seccion']];
        }
        $itemsCount[$key]['count']++;
      }
      arsort($itemsCount);
      $topProblematicos = array_slice(array_values($itemsCount), 0, 5);

      echo json_encode([
        'ok' => true,
        'items' => $result,
        'metricas' => [
          'total_resueltas' => $totalResueltas,
          'dias_promedio_resolucion' => $diasPromedio,
          'top_problematicos' => $topProblematicos
        ]
      ]);
      exit;
    } catch (Throwable $e) {
      http_response_code(500);
      echo json_encode(['ok'=>false, 'error'=>'HISTORIAL_ERROR', 'msg'=>$e->getMessage()]); exit;
    }
  }

  http_response_code(404);
  echo json_encode(['ok'=>false,'error'=>'NOT_FOUND']);
} catch (Throwable $e) {
  if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
  http_response_code(500);
  echo json_encode(['ok'=>false, 'error'=>'SERVER', 'msg'=>$e->getMessage()]);
}
