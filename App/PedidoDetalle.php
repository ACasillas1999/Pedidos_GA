<?php
/*// Definir los datos de conexión como constantes
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'pedidos_app');

// Conectar a la base de datos
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Verificar la conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}*/
require_once __DIR__ . "/Conexiones/Conexion.php";

// Obtener el ID del pedido desde la solicitud GET
if(isset($_GET['id'])) {
    $pedidoId = $_GET['id'];
    
    // Obtener los detalles del pedido con el ID proporcionado
    $sql = "SELECT * FROM pedidos WHERE ID = $pedidoId";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        // Obtener los detalles del pedido
        $row = $result->fetch_assoc();
        $pedido = array(
            'ID' => $row['ID'],
            'SUCURSAL' => $row['SUCURSAL'],
            'ESTADO' => $row['ESTADO'],
            'FECHA_RECEPCION_FACTURA' => $row['FECHA_RECEPCION_FACTURA'],
            'FECHA_ENTREGA_CLIENTE' => $row['FECHA_ENTREGA_CLIENTE'],
            'CHOFER_ASIGNADO' => $row['CHOFER_ASIGNADO'],
            'VENDEDOR' => $row['VENDEDOR'],
            'FACTURA' => $row['FACTURA'],
            'DIRECCION' => $row['DIRECCION'],
            'FECHA_MIN_ENTREGA' => $row['FECHA_MIN_ENTREGA'],
            'FECHA_MAX_ENTREGA' => $row['FECHA_MAX_ENTREGA'],
            'MIN_VENTANA_HORARIA_1' => $row['MIN_VENTANA_HORARIA_1'],
            'MAX_VENTANA_HORARIA_1' => $row['MAX_VENTANA_HORARIA_1'],
            'NOMBRE_CLIENTE' => $row['NOMBRE_CLIENTE'],
            'TELEFONO' => $row['TELEFONO'],
            'CONTACTO' => $row['CONTACTO'],
            'COMENTARIOS' => $row['COMENTARIOS'],
            'Ruta' => $row['Ruta'],
            'Coord_Origen' => $row['Coord_Origen'],
            'Coord_Destino' => $row['Coord_Destino']
        );
        // Devolver los detalles del pedido como JSON
        header('Content-Type: application/json');
        echo json_encode($pedido);
    } else {
        // Si no se encontró el pedido, devolver un mensaje de error
        echo json_encode(array('error' => 'Pedido no encontrado'));
    }
} else {
    // Si no se proporcionó un ID, devolver un mensaje de error
    echo json_encode(array('error' => 'No se proporcionó el ID del pedido'));
}

// Cerrar la conexión
$conn->close();
?>
