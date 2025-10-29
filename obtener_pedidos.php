<?php


session_name("GA");
session_start();

// Verificar si el usuario no está logeado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    // Si no está logeado, redirigir al formulario de inicio de sesión
    header("location: /Pedidos_GA/Sesion/login.html");
    exit;
}
// Definir los datos de conexión como constantes
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'pedidos_app');

// Conectar a la base de datos
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Verificar la conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Obtener los pedidos
$sql = "SELECT * FROM pedidos";
$result = $conn->query($sql);

$pedidos = array();

if ($result->num_rows > 0) {
    // Recorrer los resultados y agregarlos al array de pedidos
    while($row = $result->fetch_assoc()) {
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
        array_push($pedidos, $pedido);
    }
} else {
    echo "No se encontraron pedidos";
}

// Devolver los pedidos como JSON
header('Content-Type: application/json');
echo json_encode($pedidos);

// Cerrar la conexión
$conn->close();
?>
