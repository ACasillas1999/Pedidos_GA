<?php

// Definir los datos de conexión como constantes
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'gpoascen_pedidos_app');

// Conectar a la base de datos
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
$conn->set_charset("utf8mb4");


// Verificar la conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

//require_once __DIR__ . "/Conexiones/Conexion.php";

// Verificar si se recibió el parámetro 'username'
if (!isset($_GET['username'])) {
    die("Error: Falta el parámetro 'username'");
}

// Obtener el nombre de usuario de los parámetros GET
$username = $_GET['username'];

// Validar el nombre de usuario
if (empty($username)) {
    die("Error: El nombre de usuario no puede estar vacío");
}

// Preparar la consulta con un placeholder para evitar inyección SQL
$sql = "SELECT 
            pedidos.ID,
            pedidos.SUCURSAL,
            pedidos.ESTADO,
            pedidos.FECHA_RECEPCION_FACTURA,
            pedidos.FECHA_ENTREGA_CLIENTE,
            pedidos.CHOFER_ASIGNADO,
            pedidos.VENDEDOR,
            pedidos.FACTURA,
            pedidos.DIRECCION,
            pedidos.FECHA_MIN_ENTREGA,
            pedidos.FECHA_MAX_ENTREGA,
            pedidos.MIN_VENTANA_HORARIA_1,
            pedidos.MAX_VENTANA_HORARIA_1,
            pedidos.NOMBRE_CLIENTE,
            pedidos.TELEFONO,
            pedidos.CONTACTO,
            pedidos.COMENTARIOS,
            pedidos.Ruta,
            pedidos.Coord_Origen,
            pedidos.Coord_Destino
        FROM pedidos
        JOIN
        choferes ON pedidos.CHOFER_ASIGNADO = choferes.username
        WHERE
            choferes.username = ? 
        ORDER BY 
            CASE 
                WHEN pedidos.ESTADO = 'Activo' THEN 1
                WHEN pedidos.ESTADO = 'En Ruta' THEN 2
                WHEN pedidos.ESTADO = 'En Tienda' THEN 3
                WHEN pedidos.ESTADO = 'Reprogramado' THEN 4
                WHEN pedidos.ESTADO = 'En Ruta' THEN 5
                WHEN pedidos.ESTADO = 'Entregado' THEN 6
                WHEN pedidos.ESTADO = 'Cancelado' THEN 7
                
                ELSE 8
            END,
            pedidos.ESTADO"; // Placeholder

// Preparar la declaración
$stmt = $conn->prepare($sql);

// Verificar si la preparación de la consulta tuvo éxito
if (!$stmt) {
    die("Error al preparar la consulta: " . $conn->error);
}

// Vincular el parámetro con el valor del nombre de usuario
$stmt->bind_param("s", $username);

// Ejecutar la consulta
if (!$stmt->execute()) {
    die("Error al ejecutar la consulta: " . $stmt->error);
}

// Obtener el resultado
$result = $stmt->get_result();



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
   
     $pedidos = [];
}
ob_clean();
// Devolver los pedidos como JSON
header('Content-Type: application/json');
$json = json_encode($pedidos);
if ($json === false) {
    echo json_encode(["error" => "Error al codificar JSON", "detalle" => json_last_error_msg()]);
} else {
    echo $json;
}


// Cerrar la conexión
$stmt->close();
$conn->close();
?>