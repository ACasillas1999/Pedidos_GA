<?php
header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . "/Conexiones/Conexion.php";

if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'No hay conexión activa con la base de datos', 'incidencias' => []]);
    exit;
}

$conn->set_charset('utf8mb4');

// Auto-crear la tabla pedidos_incidencias si no existe
$sqlTable = "CREATE TABLE IF NOT EXISTS `pedidos_incidencias` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `pedido_id` INT NOT NULL,
  `motivo` VARCHAR(150) NOT NULL,
  `comentarios` TEXT NULL,
  `archivo_ruta` VARCHAR(255) NULL,
  `archivo_nombre` VARCHAR(255) NULL,
  `archivo_tipo` VARCHAR(50) NULL,
  `chofer` VARCHAR(100) NULL,
  `fecha_registro` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_pedido_id` (`pedido_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
$conn->query($sqlTable);

$pedido_id = isset($_GET['pedido_id']) ? intval($_GET['pedido_id']) : (isset($_POST['pedido_id']) ? intval($_POST['pedido_id']) : 0);

if ($pedido_id <= 0) {
    echo json_encode(['ok' => false, 'error' => 'ID de pedido inválido', 'incidencias' => []]);
    exit;
}

$stmt = $conn->prepare("SELECT id, pedido_id, motivo, comentarios, archivo_ruta, archivo_nombre, archivo_tipo, chofer, DATE_FORMAT(fecha_registro, '%d/%m/%Y %H:%i') as fecha_formato, fecha_registro FROM `pedidos_incidencias` WHERE pedido_id = ? ORDER BY id DESC");
if (!$stmt) {
    echo json_encode(['ok' => false, 'error' => $conn->error, 'incidencias' => []]);
    exit;
}

$stmt->bind_param('i', $pedido_id);
$stmt->execute();
$res = $stmt->get_result();

$incidencias = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $incidencias[] = $row;
    }
}
$stmt->close();

echo json_encode([
    'ok' => true,
    'total' => count($incidencias),
    'incidencias' => $incidencias
]);
?>
