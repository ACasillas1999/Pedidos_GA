<?php
session_name("GA"); session_start();
header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . "/Conexiones/Conexion.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // Leer JSON
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $chofer_id   = isset($input['chofer_id'])   ? (int)$input['chofer_id']   : 0;
    $vehiculo_id = isset($input['vehiculo_id']) ? (int)$input['vehiculo_id'] : 0;

    if ($chofer_id <= 0 || $vehiculo_id <= 0) {
        echo json_encode(['ok' => false, 'error' => 'Parámetros inválidos']); exit;
    }

    // Verificar que el vehículo no sea particular
    $stmt = $conn->prepare("SELECT es_particular FROM vehiculos WHERE id_vehiculo=?");
    $stmt->bind_param('i', $vehiculo_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if (!$result || $result->num_rows === 0) {
        $stmt->close();
        echo json_encode(['ok'=>false,'error'=>'Vehículo no existe']); exit;
    }
    $row = $result->fetch_assoc();
    if ((int)$row['es_particular'] === 1) {
        $stmt->close();
        echo json_encode(['ok'=>false,'error'=>'No se puede asignar chofer a un vehículo particular']); exit;
    }
    $stmt->close();

    // ---- TRANSACCIÓN ----
    $conn->begin_transaction();

    // 1) Liberar cualquier vehículo previo del chofer
    $stmt = $conn->prepare("UPDATE vehiculos SET id_chofer_asignado = NULL WHERE id_chofer_asignado = ?");
    $stmt->bind_param('i', $chofer_id);
    $stmt->execute();
    $stmt->close();

    // 2) Asignar el nuevo vehículo a este chofer
    $stmt = $conn->prepare("UPDATE vehiculos SET id_chofer_asignado = ? WHERE id_vehiculo = ?");
    $stmt->bind_param('ii', $chofer_id, $vehiculo_id);
    $stmt->execute();

    // Verificar que realmente se actualizó ese vehículo
    if ($stmt->affected_rows === 0) {
        $stmt->close();
        $conn->rollback();
        echo json_encode(['ok' => false, 'error' => 'No se pudo asignar: id_vehiculo no encontrado o sin cambios']); exit;
    }
    $stmt->close();

    $conn->commit();

    echo json_encode([
        'ok' => true,
        'message' => 'Vehículo asignado correctamente y anteriores liberados',
        'chofer_id' => $chofer_id,
        'vehiculo_id' => $vehiculo_id
    ]);
} catch (Throwable $e) {
    if ($conn && $conn->errno === 0) {
        // si hay transacción abierta, intenta revertir
        try { $conn->rollback(); } catch (Throwable $e2) {}
    }
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
