<?php
session_name("GA");
session_start();

// Verificar si el usuario está logeado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    echo json_encode(["success" => false, "message" => "No autorizado"]);
    exit;
}

// Establecer la conexión a la base de datos
require_once __DIR__ . "/Conexiones/Conexion.php";

header('Content-Type: application/json');

// Verificar que sea una petición POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["success" => false, "message" => "Método no permitido"]);
    exit;
}

// Obtener datos del POST
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['pedidos_ids']) || !is_array($input['pedidos_ids'])) {
    echo json_encode(["success" => false, "message" => "Datos inválidos"]);
    exit;
}

$pedidosIds = array_map('intval', $input['pedidos_ids']);

if (empty($pedidosIds)) {
    echo json_encode(["success" => false, "message" => "No se proporcionaron IDs de pedidos"]);
    exit;
}

try {
    // Verificar si alguno de los pedidos ya está en un grupo activo
    $placeholders = implode(',', array_fill(0, count($pedidosIds), '?'));

    $sql = "SELECT p.ID as pedido_id, p.FACTURA, p.NOMBRE_CLIENTE,
                   pg.grupo_id, gr.nombre_grupo, gr.chofer_asignado
            FROM pedidos p
            INNER JOIN pedidos_grupos pg ON p.ID = pg.pedido_id
            INNER JOIN grupos_rutas gr ON pg.grupo_id = gr.id
            WHERE p.ID IN ($placeholders) AND gr.estado = 'ACTIVO'";

    $stmt = $conn->prepare($sql);

    // Bind dinámico de parámetros
    $types = str_repeat('i', count($pedidosIds));
    $stmt->bind_param($types, ...$pedidosIds);

    $stmt->execute();
    $result = $stmt->get_result();

    $pedidosEnGrupos = [];
    while ($row = $result->fetch_assoc()) {
        $pedidosEnGrupos[] = $row;
    }

    $stmt->close();

    if (empty($pedidosEnGrupos)) {
        // Ningún pedido está en un grupo
        echo json_encode([
            "success" => true,
            "tiene_conflictos" => false,
            "pedidos_en_grupos" => []
        ]);
    } else {
        // Hay pedidos que ya están en grupos
        echo json_encode([
            "success" => true,
            "tiene_conflictos" => true,
            "pedidos_en_grupos" => $pedidosEnGrupos
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error: " . $e->getMessage()
    ]);
}

$conn->close();
?>
