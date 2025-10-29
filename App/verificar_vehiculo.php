<?php
header('Content-Type: text/plain; charset=UTF-8');

require_once __DIR__ . '/Conexiones/Conexion.php';

if (isset($conn) && $conn instanceof mysqli) {
    $conn->set_charset('utf8mb4');
}

$username = isset($_POST['username']) ? trim($_POST['username']) : (isset($_GET['username']) ? trim($_GET['username']) : '');
if ($username === '') {
    http_response_code(400);
    echo 'FALTA_USERNAME';
    exit;
}

$sql = 'SELECT v.id_vehiculo
        FROM vehiculos AS v
        INNER JOIN choferes AS c ON c.ID = v.id_chofer_asignado
        WHERE c.username = ?
        LIMIT 1';

try {
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    echo ($row && isset($row['id_vehiculo'])) ? 'ASIGNADO' : 'NO_ASIGNADO';
} catch (Throwable $e) {
    http_response_code(500);
    echo 'ERROR';
}

