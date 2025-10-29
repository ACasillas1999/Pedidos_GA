<?php
ini_set('session.cookie_httponly', true);
ini_set('session.cookie_secure', true);
session_name("GA");
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
  echo json_encode(['ok'=>false,'error'=>'No autenticado']); exit;
}
require_once __DIR__ . "/Conexiones/Conexion.php";

// --- CSRF ---
$csrf = $_POST['csrf'] ?? '';
if (!hash_equals($_SESSION['csrf_veh'] ?? '', $csrf)) {
  echo json_encode(['ok'=>false,'error'=>'CSRF inválido']); exit;
}

// --- Params ---
$id_vehiculo = isset($_POST['id_vehiculo']) ? (int)$_POST['id_vehiculo'] : 0;
if ($id_vehiculo <= 0) { echo json_encode(['ok'=>false,'error'=>'ID inválido']); exit; }

if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
  echo json_encode(['ok'=>false,'error'=>'Archivo no recibido']); exit;
}

$tmp = $_FILES['foto']['tmp_name'];
$origName = $_FILES['foto']['name'] ?? '';

// === Detección robusta de formato ===
function detectarExtensionImagen(string $tmp, string $origName = ''): array {
  // 1) exif_imagetype (la más segura)
  if (function_exists('exif_imagetype')) {
    $t = @exif_imagetype($tmp);
    if ($t === IMAGETYPE_JPEG) return ['ext'=>'jpg','mime'=>'image/jpeg'];
    if ($t === IMAGETYPE_PNG ) return ['ext'=>'png','mime'=>'image/png'];
    if (defined('IMAGETYPE_WEBP') && $t === IMAGETYPE_WEBP) return ['ext'=>'webp','mime'=>'image/webp'];
  }

  // 2) finfo (si está disponible)
  $mime = null;
  if (function_exists('finfo_open')) {
    $fi = @finfo_open(FILEINFO_MIME_TYPE);
    if ($fi) { $mime = @finfo_file($fi, $tmp); @finfo_close($fi); }
    $map = [
      'image/jpeg'=>'jpg', 'image/pjpeg'=>'jpg',
      'image/png'=>'png',  'image/x-png'=>'png',
      'image/webp'=>'webp'
    ];
    if ($mime && isset($map[$mime])) return ['ext'=>$map[$mime],'mime'=>$mime];
  }

  // 3) Fallback: extensión del nombre original (menos seguro, pero útil)
  $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
  $extMap = ['jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp'];
  if (isset($extMap[$ext])) return ['ext'=>$ext==='jpeg'?'jpg':$ext,'mime'=>$extMap[$ext]];

  // Nada coincidió
  return ['ext'=>null,'mime'=>$mime];
}

$det = detectarExtensionImagen($tmp, $origName);
if (!$det['ext']) {
  $hint = $det['mime'] ? " (detectado: {$det['mime']})" : '';
  echo json_encode(['ok'=>false,'error'=>"Formato no permitido$hint. Sube JPG/PNG/WEBP."]); exit;
}

// Límite de tamaño (5MB)
if ($_FILES['foto']['size'] > 5*1024*1024) {
  echo json_encode(['ok'=>false,'error'=>'Archivo demasiado grande (máx 5MB)']); exit;
}

// === Guardado ===
$dir = __DIR__ . '/Img/vehiculos';
if (!is_dir($dir)) { @mkdir($dir, 0775, true); }

$fname   = 'veh_'.$id_vehiculo.'_'.date('Ymd_His').'.'.$det['ext'];
$pathAbs = $dir . '/' . $fname;
$pathRel = 'Img/vehiculos/' . $fname;

if (!@move_uploaded_file($tmp, $pathAbs)) {
  echo json_encode(['ok'=>false,'error'=>'No se pudo mover el archivo']); exit;
}

// Actualizar BD
$stmt = $conn->prepare("UPDATE vehiculos SET foto_path=? WHERE id_vehiculo=?");
$stmt->bind_param('si', $pathRel, $id_vehiculo);
$stmt->execute();
$stmt->close();

// URL pública (ajusta base si es distinto)
$urlPublica = '/Pedidos_GA/' . $pathRel;

echo json_encode(['ok'=>true,'url'=>$urlPublica]);
