<?php
// Conexión a la base de datos
$servername = "localhost";
$username = "root";
$password = '';
$database = "gpoascen_pedidos_app";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $database);

// Verificar la conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

//require_once __DIR__ . "/Conexiones/Conexion.php";
// Verificar si se recibió un ID de pedido válido
if (isset($_GET['id_pedido'])) {
    $idPedido = $_GET['id_pedido'];

    // Consulta SQL para obtener la información del pedido con el ID recibido
    $sql = "SELECT * FROM estadopedido WHERE ID_Pedido = ?";

    // Preparar la consulta
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        // Vincular parámetros
        $stmt->bind_param("s", $idPedido);

        // Ejecutar la consulta
        $stmt->execute();

        // Obtener el resultado de la consulta
        $result = $stmt->get_result();

        // Crear un array para almacenar los resultados
        $response = array();

        // Iterar sobre los resultados y agregarlos al array de respuesta
        while ($row = $result->fetch_assoc()) {
            $response[] = $row;
        }

        // Devolver los resultados como JSON
        echo json_encode($response);

        // Cerrar la declaración y la conexión
        $stmt->close();
        $conn->close();
    } else {
        // Si la preparación de la consulta falla, devolver un mensaje de error
        echo "Error: " . $conn->error;
    }
} else {
    // Si no se proporcionó un ID de pedido válido, devolver un mensaje de error
    echo "Error: ID de pedido no válido";
}
?>
