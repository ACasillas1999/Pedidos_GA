<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
session_name("GA");
session_start();
header('Content-Type: application/json');

require_once __DIR__ . "/Conexiones/Conexion.php";

try {
    $sucursal = isset($_GET['sucursal']) ? trim($_GET['sucursal']) : '';

    $sql = "SELECT id_vehiculo AS id,
                   tipo,
                   placa,
                   Sucursal
            FROM vehiculos
            WHERE (id_chofer_asignado IS NULL OR id_chofer_asignado = 0)
              AND (es_particular = 0 OR es_particular IS NULL)
              AND NOT EXISTS (
                SELECT 1 FROM orden_servicio os
                WHERE os.id_vehiculo = vehiculos.id_vehiculo
                  AND os.estatus = 'EnTaller'
              )";
    $params = [];
    $types  = '';

    if ($sucursal !== '' && strcasecmp($sucursal, 'TODAS') !== 0) {
        $sql .= " AND Sucursal = ?";
        $params[] = $sucursal;
        $types .= 's';
    }

    $sql .= " ORDER BY tipo ASC";

    $stmt = $conn->prepare($sql);
    if (!$stmt) { echo json_encode(['ok'=>false, 'error'=>$conn->error]); exit; }
    if ($types) { $stmt->bind_param($types, ...$params); }
    $stmt->execute();
    $res = $stmt->get_result();

    $data = [];
    while ($row = $res->fetch_assoc()) {
        $data[] = [
          'id'       => (int)$row['id'],
          'tipo'     => (string)$row['tipo'],
          'placa'    => (string)($row['placa'] ?? ''),
          'Sucursal' => (string)$row['Sucursal']
        ];
    }

    echo json_encode(['ok'=>true, 'data'=>$data], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    echo json_encode(['ok'=>false, 'error'=>$e->getMessage()]);
}
