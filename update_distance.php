<?php
// Iniciar la sesión
session_name("GA");
session_start();

// Verificar si el usuario no está logeado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: /Pedidos_GA/Sesion/login.html");
    exit;
}

// Verificar si la distancia ha sido enviada
if (isset($_POST['distancia']) && isset($_POST['pedido_id'])) {
    // Conectar a la base de datos
    $mysqli = new mysqli("localhost", "gpoascen_Pedidos", 'Pa$$w0rd3026', "gpoascen_pedidos_app");

    // Verificar la conexión
    if ($mysqli->connect_error) {
        die("Conexión fallida: " . $mysqli->connect_error);
    }

    // Limpiar los datos recibidos
    $distancia = $mysqli->real_escape_string($_POST['distancia']);
    $pedido_id = $mysqli->real_escape_string($_POST['pedido_id']);

    // Actualizar la base de datos
    $sql = "UPDATE pedidos SET Kilometros = '$distancia' WHERE id = '$pedido_id'";

    if ($mysqli->query($sql) === TRUE) {
        echo "Distancia actualizada correctamente";
    } else {
        echo "Error: " . $sql . "<br>" . $mysqli->error;
    }

    // Cerrar la conexión
    $mysqli->close();
} else {
    echo "Datos insuficientes";
}
?>
