<?php
header('Content-Type: application/json');
date_default_timezone_set('America/Mexico_City');

// Verifica que la solicitud sea POST
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(["error" => "Método no permitido"]);
    exit;
}

// Verifica que los datos estén presentes en la solicitud POST
if (!isset($_POST['id'], $_POST['estado'], $_POST['coordenada'])) {
    echo json_encode(["error" => "ID, estado y coordenada son requeridos"]);
    exit;
}

// Obtén los datos de la solicitud POST y verifica que no estén vacíos
$id = trim($_POST['id']);
$estado = trim($_POST['estado']);
$coordenada = trim($_POST['coordenada']);

if (empty($id) || empty($estado) || empty($coordenada)) {
    echo json_encode(["error" => "ID, estado y coordenada no pueden estar vacíos"]);
    exit;
}

// Conexión a la base de datos
/*$servername = "localhost";
$username = "root";
$password = "";
$dbname = "pedidos_app";

$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica la conexión
if ($conn->connect_error) {
    echo json_encode(["error" => "Error de conexión: " . $conn->connect_error]);
    exit;
}*/
require_once __DIR__ . "/Conexiones/Conexion.php";

// Inicia una transacción para asegurar que ambas consultas se ejecuten exitosamente
$conn->begin_transaction();

try {
    // Actualiza el estado del pedido en la base de datos
    $sql_update = "UPDATE pedidos SET estado = ? WHERE id = ?";
    $stmt_update = $conn->prepare($sql_update);
    if (!$stmt_update) {
        throw new Exception("Error en la preparación de la consulta de actualización: " . $conn->error);
    }

    $stmt_update->bind_param("si", $estado, $id);
    if (!$stmt_update->execute()) {
        throw new Exception("Error al actualizar el estado");
    }

    // Obtiene la fecha y hora actual
    $fecha = date("Y-m-d");
    $hora = date("H:i:s");

    // Inserta un nuevo registro en la tabla estadopedido
    $sql_insert = "INSERT INTO estadopedido (ID_Pedido, Estado, Fecha, Hora, Coordenada) VALUES (?, ?, ?, ?, ?)";
    $stmt_insert = $conn->prepare($sql_insert);
    if (!$stmt_insert) {
        throw new Exception("Error en la preparación de la consulta de inserción: " . $conn->error);
    }

    $stmt_insert->bind_param("issss", $id, $estado, $fecha, $hora, $coordenada);
    if (!$stmt_insert->execute()) {
        throw new Exception("Error al insertar el registro en estadopedido");
    }

    // Si ambas consultas se ejecutan correctamente, se confirma la transacción
    $conn->commit();
    echo json_encode("Estado actualizado y registro añadido correctamente");
} catch (Exception $e) {
    // Si alguna consulta falla, se revierte la transacción
    $conn->rollback();
    echo json_encode(["error" => $e->getMessage()]);
} finally {
    // Cierre de declaraciones y conexión
    if (isset($stmt_update)) {
        $stmt_update->close();
    }
    if (isset($stmt_insert)) {
        $stmt_insert->close();
    }
    $conn->close();
}
?>
