<?php
session_name("GA");
session_start();

// Verificar si el usuario está logeado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

require_once __DIR__ . "/Conexiones/Conexion.php";

header('Content-Type: application/json');

// Obtener IDs de pedidos
$idsParam = $_POST['ids'] ?? $_GET['ids'] ?? '';

if (empty($idsParam)) {
    echo json_encode(['success' => false, 'message' => 'No se proporcionaron IDs de pedidos']);
    exit;
}

// Convertir a array
$ids = explode(',', $idsParam);
$ids = array_map('intval', $ids);
$ids = array_filter($ids, function($id) { return $id > 0; });

if (empty($ids)) {
    echo json_encode(['success' => false, 'message' => 'IDs de pedidos inválidos']);
    exit;
}

try {
    // Construir placeholders para la consulta
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $sql = "SELECT ID, FACTURA, NOMBRE_CLIENTE,
                   precio_factura_vendedor, precio_factura_real, precio_validado_jc
            FROM pedidos
            WHERE ID IN ($placeholders)";

    $stmt = $conn->prepare($sql);

    // Bind parameters
    $types = str_repeat('i', count($ids));
    $stmt->bind_param($types, ...$ids);

    $stmt->execute();
    $result = $stmt->get_result();

    $pedidos = [];
    while ($row = $result->fetch_assoc()) {
        $pedidos[] = [
            'id' => intval($row['ID']),
            'factura' => $row['FACTURA'],
            'cliente' => $row['NOMBRE_CLIENTE'],
            'precioVendedor' => number_format(floatval($row['precio_factura_vendedor']), 2, '.', ''),
            'precioReal' => floatval($row['precio_factura_real']),
            'validado' => intval($row['precio_validado_jc'])
        ];
    }

    if (empty($pedidos)) {
        echo json_encode(['success' => false, 'message' => 'No se encontraron pedidos con esos IDs']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'pedidos' => $pedidos
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener pedidos: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
