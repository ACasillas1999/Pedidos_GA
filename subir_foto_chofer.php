<?php
// subir_foto_chofer.php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
session_name("GA");
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
  echo json_encode(["ok"=>false, "error"=>"No autenticado"]);
  exit;
}

require_once __DIR__ . "/Conexiones/Conexion.php";

function jend($ok, $msg='', $extra=[]){
  echo json_encode($ok ? (["ok"=>true] + $extra) : ["ok"=>false,"error"=>$msg]);
  exit;
}

// CSRF
$csrf = $_POST['csrf'] ?? '';
if (!$csrf || !isset($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $csrf)) {
  jend(false, "CSRF inválido");
}

$id = isset($_POST['id_chofer']) ? (int)$_POST['id_chofer'] : 0;
if ($id <= 0) jend(false, "ID inválido");

// Validar archivo
if (empty($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
  jend(false, "Archivo no recibido");
}

$maxBytes = 4 * 1024 * 1024; // 4MB
if ($_FILES['foto']['size'] > $maxBytes) {
  jend(false, "Archivo muy grande (máx 4MB)");
}

// Validar MIME real
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime  = $finfo->file($_FILES['foto']['tmp_name']);
$allow = ['image/jpeg'=>'jpg', 'image/png'=>'png', 'image/webp'=>'webp'];
if (!isset($allow[$mime])) {
  jend(false, "Formato no permitido (usa JPG, PNG o WEBP)");
}
$ext = $allow[$mime];

// Rutas
$publicBase = "/uploads/choferes";           // URL pública
$diskBase   = __DIR__ . "/uploads/choferes";            // ruta en disco

// Asegurar carpeta
if (!is_dir($diskBase)) {
  @mkdir($diskBase, 0775, true);
}

// Borrar foto anterior (si existía y apunta a nuestra carpeta)
$stmt = $conn->prepare("SELECT foto_perfil FROM choferes WHERE ID=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$old = $stmt->get_result()->fetch_assoc();
$stmt->close();

$oldPath = $old['foto_perfil'] ?? '';
if ($oldPath && str_starts_with($oldPath, $publicBase)) {
  $oldFile = $diskBase . substr($oldPath, strlen($publicBase));
  if (is_file($oldFile)) @unlink($oldFile);
}

// Generar nombre
$fname = sprintf("/chofer_%d_%s.%s", $id, date('YmdHis'), $ext);
$dstDisk = $diskBase . $fname;
$dstUrl  = $publicBase . $fname;

// Mover archivo
if (!move_uploaded_file($_FILES['foto']['tmp_name'], $dstDisk)) {
  jend(false, "No se pudo guardar el archivo");
}

// (Opcional) endurecer permisos
@chmod($dstDisk, 0644);

// Guardar en BD
$stmt = $conn->prepare("UPDATE choferes SET foto_perfil=? WHERE ID=?");
$stmt->bind_param("si", $dstUrl, $id);
$stmt->execute();
$ok = $stmt->affected_rows >= 0;
$stmt->close();

if (!$ok) {
  @unlink($dstDisk);
  jend(false, "No se pudo actualizar la BD");
}

jend(true, "", ["url"=>$dstUrl]);
?>