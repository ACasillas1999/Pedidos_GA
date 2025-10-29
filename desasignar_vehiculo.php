<?php
session_name("GA"); session_start();
header('Content-Type: application/json');
require_once __DIR__ . "/Conexiones/Conexion.php";

try {
  $input = json_decode(file_get_contents('php://input'), true) ?: [];
  $vehiculo_id = isset($input['vehiculo_id']) ? (int)$input['vehiculo_id'] : 0;

  if (!$vehiculo_id) { echo json_encode(['ok'=>false,'error'=>'Parámetro vehiculo_id faltante']); exit; }

  $stmt = $conn->prepare("UPDATE vehiculos SET id_chofer_asignado=NULL WHERE id_vehiculo=?");
  if (!$stmt) { echo json_encode(['ok'=>false,'error'=>$conn->error]); exit; }
  $stmt->bind_param('i', $vehiculo_id);
  $stmt->execute();

  echo json_encode(['ok'=>true]);
} catch (Throwable $e) {
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
