<?php
header('Content-Type: application/json; charset=utf-8');
session_name("GA");
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
  http_response_code(401);
  echo json_encode(['ok'=>false,'msg'=>'No autenticado']); exit;
}

require_once __DIR__ . "/Conexiones/Conexion.php"; // $conn mysqli

$id = isset($_POST['id_usuario']) ? (int)$_POST['id_usuario'] : 0;
if ($id <= 0) { echo json_encode(['ok'=>false,'msg'=>'ID inválido']); exit; }

// === Generar contraseña: 4 números + 1 letra ===
$nums = '';
for ($i = 0; $i < 4; $i++) {
    $nums .= random_int(0, 9);
}
$letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
$letra = $letters[random_int(0, strlen($letters) - 1)];
$plain = $letra . $nums;

// === Hashear con bcrypt ===
$hash  = password_hash($plain, PASSWORD_DEFAULT);

// === Actualizar base de datos ===
$stmt = $conn->prepare("UPDATE usuarios SET password=? WHERE ID=?");
if (!$stmt) { echo json_encode(['ok'=>false,'msg'=>'Prepare: '.$conn->error]); exit; }
$stmt->bind_param('si', $hash, $id);
if ($stmt->execute()) {
  echo json_encode(['ok'=>true,'nueva'=>$plain]);
} else {
  echo json_encode(['ok'=>false,'msg'=>'Execute: '.$stmt->error]);
}
$stmt->close();
$conn->close();
