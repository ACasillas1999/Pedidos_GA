<?php
// Modulo de gasolina semanal
ini_set('session.cookie_httponly', true);
ini_set('session.cookie_secure', true);
session_name("GA");
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
  header("location: /Pedidos_GA/Sesion/login.html");
  exit;
}

require_once __DIR__ . '/../Conexiones/Conexion.php';
$conn->set_charset('utf8mb4');

$rol = $_SESSION["Rol"] ?? '';
$sucursal = $_SESSION["Sucursal"] ?? '';
$anioActual = (int)date('o');
$allowCsv = ($rol === "Admin" || $rol === "MEC");

$hoy = new DateTime();
$inicioMesDT = new DateTime('first day of this month 00:00:00');
$finMesDT = new DateTime('last day of this month 23:59:59');

$mesParam = trim($_GET['mes'] ?? '');
$desdeParam = trim($_GET['desde'] ?? '');
$hastaParam = trim($_GET['hasta'] ?? '');
$semanasParam = $_GET['semanas'] ?? [];
if (!is_array($semanasParam)) {
  $semanasParam = [$semanasParam];
}
$weeksSelectedKeys = [];
foreach ($semanasParam as $s) {
  $s = trim((string)$s);
  if ($s === '') continue;
  if (preg_match('/^(\\d{4})-(\\d{1,2})$/', $s, $m)) {
    $weeksSelectedKeys[] = ((int)$m[1]) . '-' . ((int)$m[2]);
  }
}
$weeksSelectedKeys = array_values(array_unique($weeksSelectedKeys));
$weeksQueryStr = '';
foreach ($weeksSelectedKeys as $wk) {
  $weeksQueryStr .= '&semanas[]=' . urlencode($wk);
}

$filtroInicioDT = clone $inicioMesDT;
$filtroFinDT = clone $finMesDT;

if ($mesParam !== '' && preg_match('/^\\d{4}-\\d{2}$/', $mesParam)) {
  try {
    $filtroInicioDT = new DateTime($mesParam . '-01 00:00:00');
    $filtroFinDT = (clone $filtroInicioDT)->modify('last day of this month 23:59:59');
  } catch (Exception $e) {}
} else {
  if ($desdeParam !== '') {
    try { $filtroInicioDT = new DateTime($desdeParam . ' 00:00:00'); } catch (Exception $e) {}
  }
  if ($hastaParam !== '') {
    try { $filtroFinDT = new DateTime($hastaParam . ' 23:59:59'); } catch (Exception $e) {}
  }
  if ($filtroInicioDT > $filtroFinDT) {
    $tmp = $filtroInicioDT;
    $filtroInicioDT = $filtroFinDT;
    $filtroFinDT = $tmp;
  }
}

$filtroInicio = $filtroInicioDT->format('Y-m-d H:i:s');
$filtroFin = $filtroFinDT->format('Y-m-d H:i:s');
$desdeVal = $filtroInicioDT->format('Y-m-d');
$hastaVal = $filtroFinDT->format('Y-m-d');
$mesVal = ($mesParam !== '' && preg_match('/^\\d{4}-\\d{2}$/', $mesParam)) ? $mesParam : '';
$filtroInicioDateOnly = $filtroInicioDT->format('Y-m-d');
$filtroFinDateOnly = $filtroFinDT->format('Y-m-d');
$mensaje = '';
$mensajeTipo = '';
$previewRows = [];
$previewSummary = [];
$importLogs = [];
$showImportModal = false;
if ($allowCsv) {
  ensureImportLogTable($conn);
  $resLog = $conn->query("SELECT id, usuario, rol, resumen, errores, creado_en FROM gasolina_import_log ORDER BY id DESC LIMIT 30");
  if ($resLog) {
    while ($r = $resLog->fetch_assoc()) {
      $importLogs[] = $r;
    }
  }
}

function h($v): string
{
  if ($v === null) $v = '';
  return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function buildWeekLabel(int $anio, int $semana, array $meses): string
{
  $d = new DateTime();
  $d->setISODate($anio, $semana, 1);
  $ini = $d->format('d') . ' ' . $meses[(int)$d->format('n')];
  $dFin = (clone $d)->modify('+6 day');
  $fin = $dFin->format('d') . ' ' . $meses[(int)$dFin->format('n')];
  return "Semana {$semana} ({$anio}): {$ini} al {$fin}";
}

function getWeekRange(int $anio, int $semana): array
{
  $d = new DateTime();
  $d->setISODate($anio, $semana, 1);
  $ini = clone $d;
  $fin = (clone $d)->modify('+6 day');
  return [$ini, $fin];
}

function ensureImportLogTable($conn)
{
  $sql = "
    CREATE TABLE IF NOT EXISTS gasolina_import_log (
      id INT AUTO_INCREMENT PRIMARY KEY,
      usuario VARCHAR(120) NULL,
      rol VARCHAR(50) NULL,
      resumen TEXT NULL,
      errores LONGTEXT NULL,
      creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
  ";
  $conn->query($sql);
}

// Normaliza fechas dd/mm/yyyy a yyyy-mm-dd para que DateTime pueda parsear.
function normalizeCsvDate(string $fecha): string
{
  $fecha = trim($fecha);
  if ($fecha === '') return '';
  if (preg_match('/^(\\d{1,2})\\/(\\d{1,2})\\/(\\d{2,4})$/', $fecha, $m)) {
    $dia = (int)$m[1];
    $mes = (int)$m[2];
    $anio = (int)$m[3];
    if ($anio < 100) {
      // Asume años de 2 dígitos en 2000s
      $anio = 2000 + $anio;
    }
    return sprintf('%04d-%02d-%02d', $anio, $mes, $dia);
  }
  return $fecha;
}

// Importa CSV masivo con columnas: EMPRESA, PLACA, FECHA, IMPORTE, OBSERVACIONES.
// Paso 1: previsualiza; Paso 2: confirma e inserta.
if ($allowCsv) {
  if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    unset($_SESSION['csv_preview_data']);
    unset($_SESSION['csv_import_log']);
  }

  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_import']) && isset($_SESSION['csv_preview_data'])) {
    ensureImportLogTable($conn);
    $csvData = base64_decode($_SESSION['csv_preview_data'], true);
    if ($csvData === false) {
      $mensajeTipo = 'danger';
      $mensaje = 'No hay datos de previsualizacion.';
    } else {
      $fh = fopen('php://memory', 'r+');
      fwrite($fh, $csvData);
      rewind($fh);
      $importResult = processCsvStream($fh, $rol, $sucursal, $conn, true);
      fclose($fh);
      $mensajeTipo = $importResult['tipo'];
      $mensaje = $importResult['mensaje'];
      $_SESSION['csv_import_log'] = [
        'mensaje' => $mensaje,
        'summary' => $importResult['summary'] ?? [],
        'errors' => $importResult['errors_full'] ?? []
      ];
      $usuarioLog = $_SESSION['Nombre'] ?? ($_SESSION['Usuario'] ?? '');
      $erroresLog = implode("\n", $importResult['errors_full'] ?? []);
      $summaryLog = json_encode($importResult['summary'] ?? []);
      $summaryLogMsg = $summaryLog . ' | ' . $mensaje;
      $stmtLog = $conn->prepare("INSERT INTO gasolina_import_log (usuario, rol, resumen, errores) VALUES (?, ?, ?, ?)");
      $stmtLog->bind_param('ssss', $usuarioLog, $rol, $summaryLogMsg, $erroresLog);
      $stmtLog->execute();
      $stmtLog->close();
      unset($_SESSION['csv_preview_data']);
    }
  }

  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_csv']) && $_POST['import_csv'] === 'preview') {
    $showImportModal = true; // tras previsualizar volvemos a mostrar el modal con los resultados
    ensureImportLogTable($conn);
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
      $mensajeTipo = 'danger';
      $mensaje = 'No se pudo subir el archivo CSV.';
    } else {
      $tmp = $_FILES['csv_file']['tmp_name'];
      if (!is_uploaded_file($tmp)) {
        $mensajeTipo = 'danger';
        $mensaje = 'Archivo CSV invalido.';
      } else {
        $data = file_get_contents($tmp);
        if ($data === false) {
          $mensajeTipo = 'danger';
          $mensaje = 'No se pudo leer el CSV.';
        } else {
          $fh = fopen('php://memory', 'r+');
          fwrite($fh, $data);
          rewind($fh);
          $preview = processCsvStream($fh, $rol, $sucursal, $conn, false, 200);
          fclose($fh);
          $previewRows = $preview['rows'];
          $previewSummary = $preview['summary'];
          $mensajeTipo = $preview['tipo'];
          $mensaje = $preview['mensaje'];
          $_SESSION['csv_import_log'] = [
            'mensaje' => $mensaje,
            'summary' => $preview['summary'] ?? [],
            'errors' => $preview['errors_full'] ?? []
          ];
          if ($preview['canImport']) {
            $_SESSION['csv_preview_data'] = base64_encode($data);
          } else {
            unset($_SESSION['csv_preview_data']);
          }
        }
      }
    }
  }
}

/**
 * Parsea y opcionalmente inserta un CSV. Si $doInsert=false retorna preview.
 */
function processCsvStream($fh, $rol, $sucursal, $conn, bool $doInsert, int $limitPreview = 0): array
{
  $vehMap = [];
  $vehSql = ($rol === "Admin" || $rol === "MEC")
    ? "SELECT id_vehiculo, placa, numero_serie FROM vehiculos"
    : "SELECT id_vehiculo, placa, numero_serie FROM vehiculos WHERE Sucursal = ?";
  if ($rol === "Admin" || $rol === "MEC") {
    $resVeh = $conn->query($vehSql);
  } else {
    $stmtVeh = $conn->prepare($vehSql);
    $stmtVeh->bind_param('s', $sucursal);
    $stmtVeh->execute();
    $resVeh = $stmtVeh->get_result();
  }
  if ($resVeh) {
    while ($v = $resVeh->fetch_assoc()) {
      $k1 = strtoupper(preg_replace('/\\s+/', '', (string)$v['placa']));
      $k2 = strtoupper(preg_replace('/\\s+/', '', (string)$v['numero_serie']));
      if ($k1 !== '') $vehMap[$k1] = (int)$v['id_vehiculo'];
      if ($k2 !== '') $vehMap[$k2] = (int)$v['id_vehiculo'];
    }
  }
  if (empty($vehMap)) {
    return ['tipo' => 'danger', 'mensaje' => 'No hay vehiculos para mapear la importacion.', 'rows' => [], 'summary' => [], 'canImport' => false];
  }

  // Leer completo y detectar delimitador (, o ;)
  $data = stream_get_contents($fh);
  if ($data === false || trim($data) === '') {
    return ['tipo' => 'danger', 'mensaje' => 'CSV vacio.', 'rows' => [], 'summary' => [], 'canImport' => false];
  }
  $lines = preg_split('/\r\n|\r|\n/', $data);
  $delimiters = [',', ';'];
  $parsed = [];
  // Elegir delimitador segun conteo en la primera linea no vacia
  $firstLine = '';
  foreach ($lines as $ln) {
    $lnTrim = trim($ln);
    if ($lnTrim !== '') {
      $firstLine = ltrim($ln, "\xEF\xBB\xBF");
      break;
    }
  }
  $firstLineRaw = $firstLine;
  $countComa = substr_count($firstLine, ',');
  $countPto = substr_count($firstLine, ';');
  $delimUsed = $countComa >= $countPto ? ',' : ';';

  $parsed = [];
  foreach ($lines as $ln) {
    $ln = ltrim($ln, "\xEF\xBB\xBF");
    if (trim($ln) === '') continue;
    $parsed[] = str_getcsv($ln, $delimUsed);
  }

  if (empty($parsed)) {
    return ['tipo' => 'danger', 'mensaje' => 'CSV vacio.', 'rows' => [], 'summary' => [], 'canImport' => false];
  }

  $header = $parsed[0];
  $idx = ['EMPRESA' => null, 'PLACA' => null, 'FECHA' => null, 'IMPORTE' => null, 'OBSERVACIONES' => null];
  $colDebug = [];
  foreach ($header as $i => $col) {
    $colUp = strtoupper(trim(ltrim($col, "\xEF\xBB\xBF")));
    $colDebug[] = $colUp . '(hex:' . bin2hex($col) . ')';
    if (array_key_exists($colUp, $idx)) $idx[$colUp] = $i;
  }
  if ($idx['PLACA'] === null || $idx['FECHA'] === null || $idx['IMPORTE'] === null) {
    $msgDbg = "El CSV debe incluir columnas PLACA, FECHA e IMPORTE. "
      . "Delimitador: {$delimUsed}. Cabecera leida: " . implode('|', $header)
      . ". Indexes: " . json_encode($idx)
      . ". Cabecera depurada: " . implode(' || ', $colDebug)
      . ". Primera linea hex: " . bin2hex($firstLineRaw);
    return ['tipo' => 'danger', 'mensaje' => $msgDbg, 'rows' => [], 'summary' => [], 'canImport' => false];
  }

  $rows = [];
  $ok = 0;
  $skip = 0;
  $missing = 0;
  $warnImporte = 0;
  $errorsFull = [];
  $errors = [];

  $stmtIns = null;
  if ($doInsert) {
    $stmtIns = $conn->prepare("
      INSERT INTO gasolina_semanal (id_vehiculo, anio, semana, importe, fecha_registro, observaciones)
      VALUES (?, ?, ?, ?, NOW(), ?)
      ON DUPLICATE KEY UPDATE
        importe = VALUES(importe),
        observaciones = VALUES(observaciones),
        fecha_registro = VALUES(fecha_registro)
    ");
  }

  // Procesar filas (ya parseadas)
  for ($i = 1; $i < count($parsed); $i++) {
    $row = $parsed[$i];
    $empresaCsv = $idx['EMPRESA'] !== null ? trim($row[$idx['EMPRESA']] ?? '') : '';
    $placaCsv = trim($row[$idx['PLACA']] ?? '');
    $fechaCsvRaw = trim($row[$idx['FECHA']] ?? '');
    $fechaCsv = normalizeCsvDate($fechaCsvRaw);
    $importeCsv = (float)($row[$idx['IMPORTE']] ?? 0);
    $obsCsv = isset($idx['OBSERVACIONES']) && $idx['OBSERVACIONES'] !== null ? trim($row[$idx['OBSERVACIONES']] ?? '') : '';

    $status = 'ok';
    $msg = '';

    if ($placaCsv === '' || $fechaCsv === '') {
      $status = 'skip';
      $msg = 'Faltan datos';
    }
    $key = strtoupper(preg_replace('/\\s+/', '', $placaCsv));
    $idVeh = $vehMap[$key] ?? null;
    $idVeh = $vehMap[$key] ?? null;
    if (!$idVeh) {
      $status = 'missing';
      $msg = 'Placa no registrada (no se insertara por FK)';
      $missing++;
    }
    if ($importeCsv <= 0) {
      $warnImporte++;
      if ($msg === '') $msg = 'Importe 0 (se insertara igualmente)';
    }
    try {
      $dt = new DateTime($fechaCsv);
    } catch (Exception $e) {
      $status = ($status === 'ok' || $status === 'missing') ? 'error' : $status;
      $msg = 'Fecha invalida';
    }
    if ($status === 'ok' || $status === 'missing') {
      $anio = (int)$dt->format('o');
      $semana = (int)$dt->format('W');
      if ($doInsert) {
        if ($idVeh) {
          $stmtIns->bind_param('iiids', $idVeh, $anio, $semana, $importeCsv, $obsCsv);
          if ($stmtIns->execute()) {
            $ok++;
          } else {
            $status = 'error';
            $msg = $stmtIns->error;
            $skip++;
          }
        } else {
          // No inserta por FK, pero contabiliza como omitida
          $status = 'error';
          $msg = 'FK requiere vehiculo existente';
          $skip++;
        }
      } else {
        $ok++;
      }
    } else {
      $skip++;
    }

  if (!$doInsert && ($limitPreview === 0 || count($rows) < $limitPreview)) {
    $rows[] = [
      'empresa' => $empresaCsv,
      'placa' => $placaCsv,
      'fecha' => $fechaCsvRaw,
      'importe' => $importeCsv,
      'obs' => $obsCsv,
      'anio' => $status === 'ok' ? $anio : null,
      'semana' => $status === 'ok' ? $semana : null,
      'status' => $status,
      'msg' => $msg
    ];
  }
    if ($status !== 'ok') {
      $errors[] = "Placa {$placaCsv}: {$msg}";
      $errorsFull[] = "Placa {$placaCsv}: {$msg}";
    }
  }

  if ($stmtIns) $stmtIns->close();

  $summary = ['ok' => $ok, 'skip' => $skip, 'missing' => $missing, 'warn_importe' => $warnImporte, 'errors' => array_slice($errors, 0, 5)];
  $tipo = $doInsert ? (empty($errors) ? 'success' : 'warn') : (empty($errors) ? 'success' : 'warn');
  $msgFinal = ($doInsert ? "Importacion: {$ok} filas cargadas, {$skip} omitidas (FK/fallas), {$missing} sin vehiculo, {$warnImporte} con importe 0." : "Previsualizacion lista: {$ok} OK, {$skip} omitidas, {$missing} sin vehiculo, {$warnImporte} importe 0.")
    . (empty($errors) ? '' : ' Detalles: ' . implode(' | ', $summary['errors']));
  return ['tipo' => $tipo, 'mensaje' => $msgFinal, 'rows' => $rows, 'summary' => $summary, 'errors_full' => $errorsFull, 'canImport' => $ok > 0 || $missing > 0];
}

// Registrar o actualizar la semana (ON DUPLICATE KEY usa la unique id_vehiculo+anio+semana)
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && !isset($_POST['import_csv'])
    && !isset($_POST['confirm_import'])) {
  $idVehiculo = (int)($_POST['id_vehiculo'] ?? 0);
  $importe = (float)($_POST['importe'] ?? 0);
  $obs = trim($_POST['observaciones'] ?? '');
  $fechaSemana = $_POST['fecha_semana'] ?? date('Y-m-d');

  try {
    $dt = new DateTime($fechaSemana);
  } catch (Exception $e) {
    $dt = new DateTime();
  }

  $anio = (int)$dt->format('o');
  $semana = (int)$dt->format('W');

  if ($idVehiculo > 0 && $importe > 0) {
    $stmt = $conn->prepare("
      INSERT INTO gasolina_semanal (id_vehiculo, anio, semana, importe, fecha_registro, observaciones)
      VALUES (?, ?, ?, ?, NOW(), ?)
      ON DUPLICATE KEY UPDATE
        importe = VALUES(importe),
        observaciones = VALUES(observaciones),
        fecha_registro = VALUES(fecha_registro)
    ");
    $stmt->bind_param('iiids', $idVehiculo, $anio, $semana, $importe, $obs);
    if ($stmt->execute()) {
      $mensajeTipo = 'success';
      $mensaje = "Registro guardado para la semana {$semana}/{$anio}.";
    } else {
      $mensajeTipo = 'danger';
      $mensaje = "No se pudo guardar: " . $stmt->error;
    }
    $stmt->close();
  } else {
    $mensajeTipo = 'danger';
    $mensaje = "Faltan datos obligatorios (vehiculo e importe).";
  }
}

// Lista de vehiculos disponibles para el usuario
$vehiculos = [];
if ($rol === "Admin" || $rol === "MEC") {
  $vehSql = "
    SELECT id_vehiculo, placa, numero_serie, Sucursal, razon_social, Km_Actual, Km_Total
      FROM vehiculos
  ORDER BY Sucursal, placa
  ";
  $result = $conn->query($vehSql);
} else {
  $vehSql = "
    SELECT id_vehiculo, placa, numero_serie, Sucursal, razon_social, Km_Actual, Km_Total
      FROM vehiculos
     WHERE Sucursal = ?
  ORDER BY placa
  ";
  $stmt = $conn->prepare($vehSql);
  $stmt->bind_param('s', $sucursal);
  $stmt->execute();
  $result = $stmt->get_result();
}
if ($result) {
  while ($row = $result->fetch_assoc()) {
    $vehiculos[] = $row;
  }
}

// Ultimos registros de gasolina semanal
$registros = [];
$weeks = [];
$matriz = [];
$empresaTotales = [];
$empresaWeek = [];
$weekLabels = [];
$weekLabelsAll = [];
$grandTotal = 0;

$weekDateExpr = "STR_TO_DATE(CONCAT(anio, ' ', LPAD(semana, 2, '0'), ' 1'), '%x %v %w')";
$weekDateExprGs = "STR_TO_DATE(CONCAT(gs.anio, ' ', LPAD(gs.semana, 2, '0'), ' 1'), '%x %v %w')";

// Semanas presentes en el mes (segun fecha_registro)
if ($rol === "Admin" || $rol === "MEC") {
  $sqlWeeks = "
    SELECT DISTINCT anio, semana
      FROM gasolina_semanal
     WHERE DATE($weekDateExpr) BETWEEN ? AND ?
  ORDER BY anio, semana
  ";
  $stmtWeeks = $conn->prepare($sqlWeeks);
  $stmtWeeks->bind_param('ss', $filtroInicioDateOnly, $filtroFinDateOnly);
} else {
  $sqlWeeks = "
    SELECT DISTINCT gs.anio, gs.semana
      FROM gasolina_semanal gs
      JOIN vehiculos v ON v.id_vehiculo = gs.id_vehiculo
     WHERE DATE($weekDateExprGs) BETWEEN ? AND ?
       AND v.Sucursal = ?
  ORDER BY gs.anio, gs.semana
  ";
  $stmtWeeks = $conn->prepare($sqlWeeks);
  $stmtWeeks->bind_param('sss', $filtroInicioDateOnly, $filtroFinDateOnly, $sucursal);
}
if ($stmtWeeks->execute()) {
  $resW = $stmtWeeks->get_result();
  while ($w = $resW->fetch_assoc()) {
    $weeks[] = ['anio' => (int)$w['anio'], 'semana' => (int)$w['semana']];
  }
}
$stmtWeeks->close();
$weeksFiltered = [];
foreach ($weeks as $w) {
  [$wIni, $wFin] = getWeekRange((int)$w['anio'], (int)$w['semana']);
  if ($wFin < $filtroInicioDT || $wIni > $filtroFinDT) {
    continue; // Semana fuera del rango de fechas seleccionado
  }
  $weeksFiltered[] = $w;
}
$weeks = $weeksFiltered;
$weeksAll = $weeksFiltered;
if (!empty($weeksSelectedKeys)) {
  $weeks = array_values(array_filter($weeks, function ($w) use ($weeksSelectedKeys) {
    $key = $w['anio'] . '-' . $w['semana'];
    return in_array($key, $weeksSelectedKeys, true);
  }));
}
$weekKeySet = [];
foreach ($weeks as $w) {
  $weekKeySet[$w['anio'] . '-' . $w['semana']] = true;
}

// Datos del mes por empresa/placa/semana
if ($rol === "Admin" || $rol === "MEC") {
  $gasSql = "
    SELECT gs.*, v.placa, v.numero_serie, v.Sucursal, v.razon_social
      FROM gasolina_semanal gs
      JOIN vehiculos v ON v.id_vehiculo = gs.id_vehiculo
     WHERE DATE($weekDateExprGs) BETWEEN ? AND ?
  ORDER BY v.razon_social, v.placa
  ";
  $stmtGas = $conn->prepare($gasSql);
  $stmtGas->bind_param('ss', $filtroInicioDateOnly, $filtroFinDateOnly);
} else {
  $gasSql = "
    SELECT gs.*, v.placa, v.numero_serie, v.Sucursal, v.razon_social
      FROM gasolina_semanal gs
      JOIN vehiculos v ON v.id_vehiculo = gs.id_vehiculo
     WHERE DATE($weekDateExprGs) BETWEEN ? AND ?
       AND v.Sucursal = ?
  ORDER BY v.razon_social, v.placa
  ";
  $stmtGas = $conn->prepare($gasSql);
  $stmtGas->bind_param('sss', $filtroInicioDateOnly, $filtroFinDateOnly, $sucursal);
}
if ($stmtGas->execute()) {
  $resGas = $stmtGas->get_result();
  while ($row = $resGas->fetch_assoc()) {
    $key = $row['anio'] . '-' . $row['semana'];
    if (!isset($weekKeySet[$key])) continue;
    $registros[] = $row;
    $empresa = trim((string)$row['razon_social']);
    if ($empresa === '') {
      $empresa = trim((string)$row['Sucursal']);
    }
    if ($empresa === '') {
      $empresa = 'Sin empresa';
    }
    $placa = $row['placa'] ?: $row['numero_serie'];
    if (!isset($matriz[$empresa])) $matriz[$empresa] = [];
    if (!isset($matriz[$empresa][$placa])) {
      $matriz[$empresa][$placa] = [
        'sucursal' => $row['Sucursal'],
        'tot' => 0,
        'semanas' => []
      ];
    }
    $matriz[$empresa][$placa]['semanas'][$key] = (float)$row['importe'];
    $matriz[$empresa][$placa]['tot'] += (float)$row['importe'];
    if (!isset($empresaTotales[$empresa])) $empresaTotales[$empresa] = 0;
    if (!isset($empresaWeek[$empresa])) $empresaWeek[$empresa] = [];
    if (!isset($empresaWeek[$empresa][$key])) $empresaWeek[$empresa][$key] = 0;
    $empresaWeek[$empresa][$key] += (float)$row['importe'];
    $empresaTotales[$empresa] += (float)$row['importe'];
    $grandTotal += (float)$row['importe'];
  }
}
$stmtGas->close();

// Etiquetas de semana
$meses = [1=>"ENE",2=>"FEB",3=>"MAR",4=>"ABR",5=>"MAY",6=>"JUN",7=>"JUL",8=>"AGO",9=>"SEP",10=>"OCT",11=>"NOV",12=>"DIC"];
foreach ($weeksAll as $w) {
  $weekLabelsAll[$w['anio'] . '-' . $w['semana']] = buildWeekLabel((int)$w['anio'], (int)$w['semana'], $meses);
}
foreach ($weeks as $w) {
  $weekLabels[$w['anio'] . '-' . $w['semana']] = $weekLabelsAll[$w['anio'] . '-' . $w['semana']] ?? buildWeekLabel((int)$w['anio'], (int)$w['semana'], $meses);
}

// Resumen anual por vehiculo
$totales = [];
if ($rol === "Admin" || $rol === "MEC") {
  $totSql = "
    SELECT gs.id_vehiculo, v.placa, v.Sucursal, v.razon_social,
           SUM(gs.importe) AS total_anual,
           MAX(CONCAT(gs.anio, '-', LPAD(gs.semana, 2, '0'))) AS ultima_semana
      FROM gasolina_semanal gs
      JOIN vehiculos v ON v.id_vehiculo = gs.id_vehiculo
     WHERE gs.anio = ?
  GROUP BY gs.id_vehiculo, v.placa, v.Sucursal, v.razon_social
  ORDER BY total_anual DESC
  ";
  $stmt = $conn->prepare($totSql);
  $stmt->bind_param('i', $anioActual);
  $stmt->execute();
  $resTot = $stmt->get_result();
} else {
  $totSql = "
    SELECT gs.id_vehiculo, v.placa, v.Sucursal, v.razon_social,
           SUM(gs.importe) AS total_anual,
           MAX(CONCAT(gs.anio, '-', LPAD(gs.semana, 2, '0'))) AS ultima_semana
      FROM gasolina_semanal gs
      JOIN vehiculos v ON v.id_vehiculo = gs.id_vehiculo
     WHERE gs.anio = ? AND v.Sucursal = ?
  GROUP BY gs.id_vehiculo, v.placa, v.Sucursal, v.razon_social
  ORDER BY total_anual DESC
  ";
  $stmt = $conn->prepare($totSql);
  $stmt->bind_param('is', $anioActual, $sucursal);
  $stmt->execute();
  $resTot = $stmt->get_result();
}
if ($resTot) {
  while ($row = $resTot->fetch_assoc()) {
    $totales[] = $row;
  }
}

$semanaDefault = (int)date('W');
$fechaDefault = date('Y-m-d');

// Export CSV (matriz rango)
if ($allowCsv && ($_GET['export'] ?? '') === 'csv') {
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="gasolina_rango.csv"');
  $out = fopen('php://output', 'w');

  $header = ['EMPRESA', 'PLACA'];
  foreach ($weeks as $w) {
    $header[] = $weekLabels[$w['anio'].'-'.$w['semana']] ?? ('Semana '.$w['semana']);
  }
  $header[] = 'TOTAL_RANGO';
  fputcsv($out, $header);

  foreach ($matriz as $empresa => $vehList) {
    foreach ($vehList as $placa => $info) {
      $row = [$empresa, $placa];
      foreach ($weeks as $w) {
        $keyW = $w['anio'].'-'.$w['semana'];
        $row[] = isset($info['semanas'][$keyW]) ? $info['semanas'][$keyW] : '';
      }
      $row[] = $info['tot'];
      fputcsv($out, $row);
    }
    $row = ["TOTAL {$empresa}", ''];
    foreach ($weeks as $w) {
      $keyW = $w['anio'].'-'.$w['semana'];
      $row[] = isset($empresaWeek[$empresa][$keyW]) ? $empresaWeek[$empresa][$keyW] : '';
    }
    $row[] = $empresaTotales[$empresa] ?? 0;
    fputcsv($out, $row);
  }
  exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gasolina semanal</title>
  <link rel="icon" type="image/png" href="/Pedidos_GA/Img/Botones%20entregas/ICONOSPAG/ICONOPEDIDOS.png">
  <style>
    :root {
      --bg: #f5f6fa;
      --card: #fff;
      --text: #111827;
      --muted: #6b7280;
      --brand: #0f59a6;
      --accent: #f97316;
      --stroke: #e5e7eb;
      --radius: 12px;
      --shadow: 0 8px 22px rgba(15, 23, 42, .08);
    }
    *{box-sizing:border-box}
    body{
      margin:0;
      font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background:var(--bg);
      color:var(--text);
      padding:18px;
      padding-left:110px;
    }
    h1{margin:0 0 6px;font-size:26px}
    .muted{color:var(--muted);font-size:.95rem}
    .layout{display:grid;grid-template-columns:1fr;gap:18px;align-items:start}
    .card{
      background:var(--card);
      border:1px solid var(--stroke);
      border-radius:var(--radius);
      box-shadow:var(--shadow);
      padding:16px;
    }
    .toolbar{display:none;}
    .btn{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      gap:6px;
      border:none;
      border-radius:12px;
      padding:10px 14px;
      font-weight:800;
      font-size:.9rem;
      cursor:pointer;
      text-decoration:none;
      transition:transform .08s ease, box-shadow .14s ease, filter .14s ease;
      box-shadow:0 8px 18px rgba(15, 23, 42, .12);
      color:#fff;
      letter-spacing:.2px;
    }
    .btn:hover{filter:brightness(1.07);}
    .btn:active{transform:translateY(1px);}
    .btn.primary{background:linear-gradient(135deg,#0f7ae8,#0b63bd);}
    .btn.secondary{background:linear-gradient(135deg,#44546a,#2f3b4e);}
    .btn.ghost{
      background:#fff;
      color:#0b68c4;
      border:1px solid #cbd5e1;
      box-shadow:0 6px 16px rgba(15,23,42,.1);
    }
    .btn.small{padding:10px 14px;font-size:.9rem;}
    .actions-row{display:flex;gap:6px;flex-wrap:wrap;align-items:center;}
    .toolbar-compact{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;background:transparent;padding:0;}
    .filters-grid{
      display:grid;
      grid-template-columns:2fr 1fr;
      gap:10px;
      width:100%;
      align-items:start;
    }
    .filter-card,
    .action-card{
      background:#fff;
      border:1px solid #d5d7dd;
      border-radius:14px;
      box-shadow:0 6px 16px rgba(15,23,42,.12);
      padding:10px 12px;
    }
    
    .filter-row{
      display:flex;
      gap:8px;
      width:100%;
    }
    .filter-row.two .field{flex:1;}
    .filter-row.full .field{flex:1;}
    .filter-row.actions{justify-content:flex-end;}
    .filter-row.actions .btn{min-width:120px;}
    .action-card{
      display:flex;
      flex-direction:column;
      gap:8px;
      justify-content:flex-start;
    }
    .action-card .btn{
      width:100%;
      justify-content:center;
      padding:10px 14px;
    }
    @media (max-width: 1024px){
      .filters-grid{
        grid-template-columns:1fr;
      }
    }
    .filter-range{
      display:flex;
      flex-direction:column;
      gap:8px;
      align-items:stretch;
      padding:0;
      background:transparent;
      border:none;
      border-radius:0;
      box-shadow:none;
      flex:1 1 560px;
    }
    .filter-range .field{
      display:flex;
      flex-direction:column;
      gap:6px;
      min-width:160px;
      flex:1 1 180px;
    }
    .filter-range .field label{margin:0;font-weight:800;font-size:.9rem;color:#0f172a;}
    .filter-range input[type="date"],
    .filter-range input[type="month"]{
      width:100%;
      background:#f8fafc;
      padding:8px 10px;
      border-radius:8px;
      border:1px solid #cbd5e1;
      font-weight:700;
      letter-spacing:.2px;
    }
    .filter-range .action-field{flex:0 0 auto;min-width:120px;}
    .topband{
      background:#d4d4d8;
      padding:12px 14px;
      border-radius:14px;
      display:flex;
      flex-direction:column;
      gap:8px;
      margin-bottom:12px;
      box-shadow:0 6px 14px rgba(15,23,42,.1);
      border:1px solid #c5c7cd;
    }
    .topband h1{color:#0f172a;margin:0;font-size:22px;}
    .topband .muted{color:#2f323a;margin:0;font-size:.93rem;}
    .table-header{
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:10px;
      margin:4px 0 8px;
    }
    .filter-range label{margin:0;font-weight:700;font-size:.9rem;}
    .filter-range input[type="date"]{
      width:auto;
      min-width:150px;
      background:#fff;
    }
    label{display:block;margin:10px 0 4px;font-weight:700;font-size:.92rem}
    select,input[type="date"],input[type="number"],textarea{
      width:100%;
      border:1px solid var(--stroke);
      border-radius:10px;
      padding:10px 12px;
      font-size:1rem;
      background:#f9fafb;
    }
    textarea{min-height:90px;resize:vertical}
    button{
      border:none;
      border-radius:10px;
      padding:12px 14px;
      background:linear-gradient(90deg,var(--brand),#0b66c3);
      color:#fff;
      font-weight:800;
      cursor:pointer;
      width:auto;
      margin-top:0;
      letter-spacing:.2px;
    }
    button:hover{filter:brightness(1.05)}
    table{width:100%;border-collapse:collapse;margin-top:10px;font-size:.95rem}
    th,td{padding:10px;border-bottom:1px solid var(--stroke);text-align:left}
    th{background:#f1f5f9;font-weight:800;color:#0f172a}
    .pill{
      display:inline-flex;
      align-items:center;
      gap:6px;
      padding:6px 10px;
      border-radius:999px;
      background:#e0f2fe;
      color:#0f3a7a;
      font-weight:700;
      font-size:.82rem;
    }
    .msg-card{
      display:flex;
      gap:12px;
      align-items:flex-start;
      padding:14px 16px;
      border-radius:14px;
      margin-bottom:14px;
      border:1px solid #e5e7eb;
      background:linear-gradient(120deg,#ffffff 0%, #f8fafc 100%);
      box-shadow:var(--shadow);
    }
    .msg-card .dot{
      width:12px;
      height:12px;
      border-radius:50%;
      margin-top:4px;
    }
    .msg-card .msg-body{flex:1;}
    .msg-card .msg-title{
      font-weight:800;
      font-size:1rem;
      margin:0 0 4px 0;
      color:#0f172a;
      letter-spacing:.2px;
    }
    .msg-card .msg-text{
      margin:0;
      font-weight:650;
      color:#1f2937;
      line-height:1.45;
    }
    .msg-card.success{border-color:#bbf7d0;background:linear-gradient(120deg,#f0fdf4 0%, #ffffff 100%);}
    .msg-card.success .dot{background:#16a34a;}
    .msg-card.danger{border-color:#fecdd3;background:linear-gradient(120deg,#fef2f2 0%, #ffffff 100%);}
    .msg-card.danger .dot{background:#dc2626;}
    .msg-card.warn{border-color:#fcd34d;background:linear-gradient(120deg,#fffbeb 0%, #ffffff 100%);}
    .msg-card.warn .dot{background:#d97706;}
    .msg-card.info{border-color:#c7d2fe;background:linear-gradient(120deg,#eef2ff 0%, #ffffff 100%);}
    .msg-card.info .dot{background:#4f46e5;}
    .grid{
      display:grid;
      grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
      gap:12px;
      margin-top:8px;
    }
    .summary{
      border:1px solid var(--stroke);
      border-radius:12px;
      padding:12px;
      background:#f8fafc;
    }
    .matrix{
      width:100%;
      border-collapse:collapse;
      margin-top:12px;
      font-size:.93rem;
    }
    .matrix th,.matrix td{border:1px solid #cfd4dc;padding:8px;text-align:right}
    .matrix th{
      background:#e3e7f1;
      font-size:.9rem;
      text-transform:uppercase;
      letter-spacing:.3px;
    }
    .matrix td.label-left{text-align:left;font-weight:800;color:#0f172a}
    .matrix td.empresa{
      background:#fff8d3;
      font-weight:900;
      color:#2f2f2f;
      text-align:left;
    }
    .matrix td.total-empresa{
      background:#f7d7c2;
      font-weight:900;
      color:#4b1200;
      text-align:right;
    }
    .matrix tr:nth-child(even) td:not(.empresa):not(.total-empresa){background:#f8fafc}
    .table-wrapper{
      overflow-x:auto;
      border:1px solid var(--stroke);
      border-radius:12px;
      background:#fff;
      box-shadow:var(--shadow);
      padding:10px;
    }
    /* Modal */
    .modal-backdrop{
      position:fixed;
      inset:0;
      background:rgba(0,0,0,.35);
      display:none;
      align-items:center;
      justify-content:center;
      z-index:30;
    }
    .modal-backdrop.show{display:flex;}
    .modal{
      background:#fff;
      border-radius:14px;
      padding:18px;
      width:820px;
      max-width:95%;
      max-height:90vh;
      overflow:auto;
      box-shadow:0 14px 40px rgba(0,0,0,.2);
      border:1px solid #e5e7eb;
      position:relative;
    }
    .modal .close{
      position:absolute;
      right:12px;
      top:12px;
      border:none;
      background:#f1f5f9;
      border-radius:50%;
      width:34px;
      height:34px;
      cursor:pointer;
      font-weight:900;
      color:#0f172a;
    }
    @media (max-width:920px){
      .layout{grid-template-columns:1fr}
      .toolbar{flex-direction:column;align-items:flex-start;}
    }

    /* Sidebar */
    @media (max-width:760px){
      body{padding-left:90px;}
      .sidebar{width:84px;}
    }
    .sidebar {
      position: fixed;
      inset: 0 auto 0 0;
      width: 100px;
      z-index: 20;
      background: var(--brand);
      border-right: 1px solid #004b88;
      display: flex;
      flex-direction: column;
    }
    .sidebar ul {
      list-style: none;
      margin: 0;
      padding: 12px 6px;
      display: flex;
      flex-direction: column;
      height: 100%;
    }
    .sidebar li {
      display: flex;
      justify-content: center;
      margin: 14px 0;
    }
    .sidebar a {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 6px;
      text-decoration: none;
    }
    .sidebar-icon {
      width: 62px;
      height: auto;
      display: block;
      transition: transform .15s ease;
    }
    .sidebar-icon.small {
      width: 48px;
    }
    .sidebar a:hover .sidebar-icon {
      transform: translateY(-2px);
    }
    .sidebar-bottom {
      margin-top: auto;
      margin-bottom: 10px;
    }
  </style>
</head>
<body>
  <div class="sidebar">
    <ul>
      <?php if ($rol === "Admin"): ?>
        <li>
          <a href="/Pedidos_GA/NuevoVehiculo.php" title="Agregar Vehiculo">
            <img src="/Pedidos_GA/Img/Botones%20entregas/Choferes/ADDSERVMECNA.png" class="icono-AddChofer sidebar-icon" alt="Agregar">
          </a>
        </li>
      <?php endif; ?>
      <li>
        <a href="/Pedidos_GA/Estadisticas_Vehiculos.php" title="Estadisticas">
          <img src="/Pedidos_GA/Img/Botones%20entregas/Pedidos_GA/ESTNA2.png" class="icono-estadisticas sidebar-icon" alt="Estadisticas">
        </a>
      </li>
      <li>
        <a href="/Pedidos_GA/Servicios/Servicios.php" title="Servicios" aria-label="Servicios">
          <img src="/Pedidos_GA/Img/SVG/ServiciosN.svg"  class="icono-servicios sidebar-icon" alt="Servicios">
        </a>
      </li>
      <li class="sidebar-bottom">
        <a href="/Pedidos_GA/Vehiculos.php" title="Volver">
          <img src="/Pedidos_GA/Img/Botones%20entregas/Usuario/VOLVAZ.png" class="icono-Volver sidebar-icon small" alt="Volver">
        </a>
      </li>
    </ul>
  </div>

  <div class="topband">
    <h1>Gasolina semanal</h1>
    <div class="muted">Captura por vehiculo usando la llave (id_vehiculo, anio, semana).</div>
    <div class="filters-grid">
      <div class="filter-card">
        <form class="filter-range" method="get">
          <div class="filter-row two">
            <div class="field">
              <label for="desde">Desde</label>
              <input type="date" id="desde" name="desde" value="<?php echo h($desdeVal); ?>">
            </div>
            <div class="field">
              <label for="hasta">Hasta</label>
              <input type="date" id="hasta" name="hasta" value="<?php echo h($hastaVal); ?>">
            </div>
          </div>
          <div class="filter-row full">
            <div class="field">
              <label for="mes">Mes</label>
              <input type="month" id="mes" name="mes" value="<?php echo h($mesVal); ?>">
            </div>
          </div>
          <div class="filter-row actions">
            <button type="submit" class="btn btn primary small">Aplicar</button>
          </div>
        </form>
      </div>
      <?php if ($allowCsv): ?>
      <div class="action-card">
        <a class="btn ghost small" href="?desde=<?php echo h($desdeVal); ?>&hasta=<?php echo h($hastaVal); ?><?php echo $weeksQueryStr ? h($weeksQueryStr) : ''; ?>&export=csv">Exportar CSV</a>
        <a class="btn secondary small" href="/Pedidos_GA/Machotes/gasolina_import_template.csv" download>Machote CSV</a>
        <button type="button" class="btn primary small" id="openImport">Importar CSV</button>
        <button type="button" class="btn secondary small" id="openImportLog">Ver historial importacion</button>
        <?php if (isset($_SESSION['csv_preview_data'])): ?>
          <a href="?desde=<?php echo h($desdeVal); ?>&hasta=<?php echo h($hastaVal); ?><?php echo $weeksQueryStr ? h($weeksQueryStr) : ''; ?>" class="btn ghost small">Limpiar preview</a>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
  <div class="pill" style="margin-bottom:10px;">Rango aplicado: <?php echo h($desdeVal); ?> al <?php echo h($hastaVal); ?></div>
  <?php if ($mesVal): ?>
    <div class="pill" style="margin-bottom:10px;">Mes seleccionado: <?php echo h($mesVal); ?></div>
  <?php endif; ?>
  <?php if (!empty($weeksSelectedKeys)): ?>
    <div class="pill" style="margin-bottom:10px;">Semanas mostradas: <?php echo h(implode(', ', array_map(function($wk) use ($weekLabelsAll) { return $weekLabelsAll[$wk] ?? $wk; }, $weeksSelectedKeys))); ?></div>
  <?php endif; ?>

  <?php if ($mensaje): ?>
    <?php
      $msgTitle = 'Aviso';
      if ($mensajeTipo === 'success') $msgTitle = 'Listo';
      elseif ($mensajeTipo === 'danger') $msgTitle = 'Revisa por favor';
      elseif ($mensajeTipo === 'warn') $msgTitle = 'Atencion';
    ?>
    <div class="msg-card <?php echo h($mensajeTipo ?: 'info'); ?>">
      <div class="dot"></div>
      <div class="msg-body">
        <div class="msg-title"><?php echo h($msgTitle); ?></div>
        <div class="msg-text"><?php echo h($mensaje); ?></div>
      </div>
    </div>
  <?php endif; ?>

  <div class="summary" style="margin:10px 0 16px;display:flex;align-items:center;justify-content:space-between;gap:10px;max-width:540px;">
    <div style="font-weight:800;font-size:1rem;color:#0f172a;">Total general del rango</div>
    <div style="font-size:1.2rem;font-weight:900;color:#0b68c4;">$<?php echo number_format($grandTotal, 2); ?></div>
  </div>

  <!-- Totales anuales removidos a solicitud -->

  <div class="table-wrapper" style="margin-top:14px;">
    <div class="table-header">
      <h3 style="margin:0;">Gasolina por semana (rango)</h3>
      <button type="button" class="btn primary small" id="openModal">Registrar semana</button>
    </div>
    <?php if (empty($weeks)): ?>
      <div class="muted">Sin semanas capturadas en este rango.</div>
    <?php else: ?>
      <table class="matrix">
        <thead>
          <tr>
            <th style="text-align:left;">Empresa</th>
            <th style="text-align:left;">Placa</th>
            <?php foreach ($weeks as $w): ?>
              <th><?php echo h($weekLabels[$w['anio'].'-'.$w['semana']] ?? ('Semana '.$w['semana'])); ?></th>
            <?php endforeach; ?>
            <th>Total rango</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($matriz)): ?>
            <tr><td colspan="<?php echo 3 + count($weeks); ?>" class="muted" style="text-align:center;">Sin registros.</td></tr>
          <?php else: ?>
      <?php foreach ($matriz as $empresa => $vehList): ?>
        <?php
          $first = true;
          $totalEmp = $empresaTotales[$empresa] ?? 0;
        ?>
        <?php foreach ($vehList as $placa => $info): ?>
          <tr>
            <td class="label-left"><?php echo $first ? h($empresa) : ''; ?></td>
            <td class="label-left"><?php echo h($placa); ?></td>
            <?php foreach ($weeks as $w): ?>
              <?php $keyW = $w['anio'].'-'.$w['semana']; ?>
              <td><?php echo isset($info['semanas'][$keyW]) ? '$'.number_format($info['semanas'][$keyW], 2) : '-'; ?></td>
            <?php endforeach; ?>
            <td>$<?php echo number_format($info['tot'], 2); ?></td>
          </tr>
          <?php $first = false; ?>
        <?php endforeach; ?>
        <tr>
          <td class="empresa" colspan="2">Total <?php echo h($empresa); ?></td>
          <?php foreach ($weeks as $w): ?>
            <?php $keyW = $w['anio'].'-'.$w['semana']; ?>
            <td class="empresa"><?php echo isset($empresaWeek[$empresa][$keyW]) ? '$'.number_format($empresaWeek[$empresa][$keyW], 2) : '-'; ?></td>
          <?php endforeach; ?>
          <td class="total-empresa" style="text-align:right;">$<?php echo number_format($totalEmp, 2); ?></td>
        </tr>
      <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <div class="modal-backdrop" id="modalGas">
    <div class="modal">
      <button type="button" class="close" id="closeModal">&times;</button>
      <h3>Registrar semana</h3>
      <form method="POST" autocomplete="off">
        <label for="id_vehiculo">Vehiculo</label>
        <select id="id_vehiculo" name="id_vehiculo" required>
          <option value="">Seleccionar</option>
          <?php foreach ($vehiculos as $v): ?>
            <option value="<?php echo (int)$v['id_vehiculo']; ?>">
              <?php echo h($v['placa'] ?: $v['numero_serie']); ?>
              - <?php echo h($v['Sucursal']); ?>
            </option>
          <?php endforeach; ?>
        </select>

        <label for="fecha_semana">Fecha dentro de la semana</label>
        <input type="date" id="fecha_semana" name="fecha_semana" value="<?php echo h($fechaDefault); ?>" data-default="<?php echo h($fechaDefault); ?>" required>

        <label for="importe">Importe semanal (MXN)</label>
        <input type="number" step="0.01" min="0" id="importe" name="importe" placeholder="0.00" required>

        <label for="observaciones">Observaciones</label>
        <textarea id="observaciones" name="observaciones" placeholder="Opcional"></textarea>

        <button type="submit">Guardar / Actualizar semana</button>
      </form>
    </div>
  </div>

  <?php if ($allowCsv): ?>
  <div class="modal-backdrop" id="modalImport">
    <div class="modal">
      <button type="button" class="close" id="closeImport">&times;</button>
      <h3>Importar CSV masivo</h3>
      <div class="muted" style="margin-bottom:8px;">Formato: PLACA, FECHA (YYYY-MM-DD), IMPORTE. Opcionales: EMPRESA, OBSERVACIONES.</div>
      <form method="post" enctype="multipart/form-data" id="form-import" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
        <input type="file" name="csv_file" accept=".csv" id="csv_file_input">
        <button type="submit" name="import_csv" value="preview" class="btn primary small" id="btn-preview" style="width:auto;">Previsualizar</button>
        <?php if (isset($_SESSION['csv_preview_data'])): ?>
          <button type="submit" name="confirm_import" value="1" class="btn secondary small" id="btn-confirm" style="width:auto;background:#16a34a;">Confirmar importacion</button>
        <?php endif; ?>
      </form>
      <?php if (!empty($previewRows)): ?>
        <div style="margin-top:10px;max-height:420px;overflow:auto;border:1px solid #e5e7eb;border-radius:10px;padding:8px;background:#fff;">
          <table style="width:100%;border-collapse:collapse;font-size:.9rem;">
            <thead>
              <tr style="background:#f1f5f9;">
                <th style="padding:6px;border:1px solid #e5e7eb;text-align:left;">Empresa</th>
                <th style="padding:6px;border:1px solid #e5e7eb;text-align:left;">Placa</th>
                <th style="padding:6px;border:1px solid #e5e7eb;text-align:left;">Fecha</th>
              <th style="padding:6px;border:1px solid #e5e7eb;text-align:right;">Importe</th>
              <th style="padding:6px;border:1px solid #e5e7eb;text-align:left;">Semana/Año</th>
              <th style="padding:6px;border:1px solid #e5e7eb;text-align:left;">Obs</th>
              <th style="padding:6px;border:1px solid #e5e7eb;text-align:left;">Estado</th>
            </tr>
          </thead>
          <tbody>
              <?php foreach ($previewRows as $row): ?>
                <tr>
                  <td style="padding:6px;border:1px solid #e5e7eb;"><?php echo h($row['empresa']); ?></td>
                  <td style="padding:6px;border:1px solid #e5e7eb;"><?php echo h($row['placa']); ?></td>
                  <td style="padding:6px;border:1px solid #e5e7eb;"><?php echo h($row['fecha']); ?></td>
                  <td style="padding:6px;border:1px solid #e5e7eb;text-align:right;"><?php echo number_format((float)$row['importe'], 2); ?></td>
                  <td style="padding:6px;border:1px solid #e5e7eb;"><?php echo ($row['anio'] && $row['semana']) ? h($row['semana'].'/'.$row['anio']) : '-'; ?></td>
                  <td style="padding:6px;border:1px solid #e5e7eb;"><?php echo h($row['obs']); ?></td>
                  <td style="padding:6px;border:1px solid #e5e7eb;color:<?php echo $row['status']==='ok' ? '#15803d' : ($row['status']==='skip' ? '#92400e' : '#b91c1c'); ?>;">
                    <?php echo h($row['status']); ?><?php echo $row['msg'] ? ' - '.h($row['msg']) : ''; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <?php if (!empty($previewSummary)): ?>
            <div style="margin-top:8px;font-weight:700;">Resumen: OK <?php echo (int)($previewSummary['ok'] ?? 0); ?>, omitidas <?php echo (int)($previewSummary['skip'] ?? 0); ?>, sin vehiculo <?php echo (int)($previewSummary['missing'] ?? 0); ?>, importe 0 <?php echo (int)($previewSummary['warn_importe'] ?? 0); ?>.</div>
            <?php if (!empty($previewSummary['errors'])): ?>
              <div class="muted">Errores: <?php echo h(implode(' | ', $previewSummary['errors'])); ?></div>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="modal-backdrop" id="modalImportLog">
    <div class="modal">
      <button type="button" class="close" id="closeImportLog">&times;</button>
      <h3>Ultimos detalles de importacion</h3>
      <?php if (empty($importLogs)): ?>
        <div class="muted">Aun no hay importaciones registradas.</div>
      <?php else: ?>
        <div style="max-height:400px;overflow:auto;border:1px solid #e5e7eb;border-radius:10px;padding:8px;background:#fff;">
          <?php foreach ($importLogs as $log): ?>
            <div style="padding:8px;border-bottom:1px solid #e5e7eb;">
              <div style="font-weight:700;"><?php echo h($log['creado_en']); ?> — <?php echo h($log['usuario']); ?> (<?php echo h($log['rol']); ?>)</div>
              <div class="muted" style="margin:4px 0;"><?php echo h($log['resumen']); ?></div>
              <?php
                $errs = array_filter(preg_split('/\\r\\n|\\r|\\n/', (string)$log['errores']));
              ?>
              <?php if (!empty($errs)): ?>
                <div style="margin-top:4px;">
                  <?php foreach ($errs as $err): ?>
                    <div style="padding:2px 0;"><?php echo h($err); ?></div>
                  <?php endforeach; ?>
                </div>
              <?php else: ?>
                <div class="muted">Sin errores registrados.</div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

  <script>
    document.addEventListener("DOMContentLoaded", function() {
      const shouldOpenImport = <?php echo $showImportModal ? 'true' : 'false'; ?>;
      const iconoAddChofer = document.querySelector(".icono-AddChofer");
      const iconoVolver = document.querySelector(".icono-Volver");
      const iconoEstadisticas = document.querySelector(".icono-estadisticas");
      const iconoServicios = document.querySelector(".icono-servicios");

      if (iconoAddChofer) {
        const n = "/Pedidos_GA/Img/Botones%20entregas/Choferes/ADDSERVMECNA.png";
        const h = "/Pedidos_GA/Img/Botones%20entregas/Choferes/ADDSERVMECBLANC.png";
        iconoAddChofer.addEventListener("mouseover", () => iconoAddChofer.src = h);
        iconoAddChofer.addEventListener("mouseout", () => iconoAddChofer.src = n);
      }
      if (iconoVolver) {
        const n = "/Pedidos_GA/Img/Botones%20entregas/Usuario/VOLVAZ.png";
        const h = "/Pedidos_GA/Img/Botones%20entregas/Usuario/VOLVNA.png";
        iconoVolver.addEventListener("mouseover", () => iconoVolver.src = h);
        iconoVolver.addEventListener("mouseout", () => iconoVolver.src = n);
      }
      if (iconoEstadisticas) {
        const n = "/Pedidos_GA/Img/Botones%20entregas/Pedidos_GA/ESTNA2.png";
        const h = "/Pedidos_GA/Img/Botones%20entregas/Pedidos_GA/ESTBL2.png";
        iconoEstadisticas.addEventListener("mouseover", () => iconoEstadisticas.src = h);
        iconoEstadisticas.addEventListener("mouseout", () => iconoEstadisticas.src = n);
      }
      if (iconoServicios) {
        const n = "/Pedidos_GA/Img/SVG/ServiciosN.svg";
        const h = "/Pedidos_GA/Img/SVG/ServiciosB.svg";
        iconoServicios.addEventListener("mouseover", () => iconoServicios.src = h);
        iconoServicios.addEventListener("mouseout", () => iconoServicios.src = n);
      }

      const modal = document.getElementById("modalGas");
      const openBtn = document.getElementById("openModal");
      const closeBtn = document.getElementById("closeModal");
      const form = modal ? modal.querySelector("form") : null;
      const fechaInput = modal ? modal.querySelector("#fecha_semana") : null;
      const fileInput = document.getElementById("csv_file_input");
      const btnPreview = document.getElementById("btn-preview");
      const btnConfirm = document.getElementById("btn-confirm");
      const modalImport = document.getElementById("modalImport");
      const openImport = document.getElementById("openImport");
      const closeImport = document.getElementById("closeImport");
      const modalImportLog = document.getElementById("modalImportLog");
      const openImportLog = document.getElementById("openImportLog");
      const closeImportLog = document.getElementById("closeImportLog");

      function openModal() {
        if (!modal) return;
        modal.classList.add("show");
        if (fechaInput && fechaInput.dataset.default) {
          fechaInput.value = fechaInput.dataset.default;
        }
        const importe = modal.querySelector("#importe");
        if (importe) importe.focus();
      }
      function closeModal() {
        if (!modal) return;
        modal.classList.remove("show");
        if (form) form.reset();
        if (fechaInput && fechaInput.dataset.default) {
          fechaInput.value = fechaInput.dataset.default;
        }
      }
      if (openBtn) openBtn.addEventListener("click", openModal);
      if (closeBtn) closeBtn.addEventListener("click", closeModal);
      if (modal) {
        modal.addEventListener("click", (e) => {
          if (e.target === modal) closeModal();
        });
      }
      if (btnPreview && fileInput) {
        btnPreview.addEventListener("click", () => {
          fileInput.required = true;
        });
      }
      if (btnConfirm && fileInput) {
        btnConfirm.addEventListener("click", () => {
          fileInput.required = false;
        });
      }
      function openImportModal() {
        if (!modalImport) return;
        modalImport.classList.add("show");
      }
      function closeImportModal() {
        if (!modalImport) return;
        modalImport.classList.remove("show");
        if (form) form.reset();
      }
      if (openImport) openImport.addEventListener("click", openImportModal);
      if (closeImport) closeImport.addEventListener("click", closeImportModal);
      if (modalImport) {
        modalImport.addEventListener("click", (e) => {
          if (e.target === modalImport) closeImportModal();
        });
      }
      function openLogModal() {
        if (!modalImportLog) return;
        modalImportLog.classList.add("show");
      }
      function closeLogModal() {
        if (!modalImportLog) return;
        modalImportLog.classList.remove("show");
      }
      if (openImportLog) openImportLog.addEventListener("click", openLogModal);
      if (closeImportLog) closeImportLog.addEventListener("click", closeLogModal);
      if (modalImportLog) {
        modalImportLog.addEventListener("click", (e) => {
          if (e.target === modalImportLog) closeLogModal();
        });
      }
      // Si se selecciona un mes, limpiar fechas manuales para evitar confusiones
      const mesInput = document.getElementById("mes");
      const desdeInput = document.getElementById("desde");
      const hastaInput = document.getElementById("hasta");
      if (mesInput) {
        mesInput.addEventListener("change", () => {
          if (mesInput.value && desdeInput && hastaInput) {
            desdeInput.value = "";
            hastaInput.value = "";
          }
        });
      }
      if (shouldOpenImport) {
        openImportModal();
      }
      document.addEventListener("keydown", (e) => {
        if (e.key === "Escape") closeModal();
        if (e.key === "Escape") closeImportModal();
        if (e.key === "Escape") closeLogModal();
      });
    });
    </script>
</body>
</html>
