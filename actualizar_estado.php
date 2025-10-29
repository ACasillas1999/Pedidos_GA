<?php


session_name("GA");
session_start();

// Verificar si el usuario no está logeado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    // Si no está logeado, redirigir al formulario de inicio de sesión
    header("location: /Pedidos_GA/Sesion/login.html");
    exit;
}
// Verificar si se recibieron los parámetros necesarios
if(isset($_POST['id']) && isset($_POST['estado'])) {
    // Obtener los valores del POST
    $pedidoId = $_POST['id'];
    $nuevoEstado = $_POST['estado'];
    
    // Establecer la conexión a la base de datos
require_once __DIR__ . "/Conexiones/Conexion.php";

    // Preparar la consulta SQL para actualizar el estado
    $sql = "UPDATE pedidos SET ESTADO = ? WHERE ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $nuevoEstado, $pedidoId);

    // Ejecutar la consulta y verificar el resultado
    if ($stmt->execute()) {
        // La actualización fue exitosa
        echo "Estado actualizado correctamente a: " . $nuevoEstado;
    } else {
        // Error al actualizar el estado
        echo "Error al actualizar el estado en la base de datos.";
    }

    // Cerrar la conexión a la base de datos
    $conn->close();
} else {
    // No se recibieron los parámetros necesarios
    echo "Faltan parámetros para actualizar el estado en la base de datos.";
}
?>
