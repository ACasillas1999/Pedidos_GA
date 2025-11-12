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

$sucursal = $_GET['sucursal'] ?? '';

if (empty($sucursal)) {
    echo json_encode(["success" => false, "message" => "Sucursal no especificada"]);
    exit;
}

try {
    $sql = "SELECT Ubicacion, NombreCompleto, Direccion, coordenadas, Telefono FROM ubicaciones WHERE Ubicacion = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $sucursal);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($ubicacion = $result->fetch_assoc()) {
        echo json_encode([
            "success" => true,
            "ubicacion" => $ubicacion
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Ubicación no encontrada"
        ]);
    }

    $stmt->close();
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error: " . $e->getMessage()
    ]);
}

$conn->close();
?>
