<?php
// Definir los datos de conexión como constantes
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'pedidos_app');
define('DB_PASSWORD', 'TuContraseaSegura123');
define('DB_NAME', 'gpoascen_pedidos_app');

// Conectar a la base de datos
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

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

// Consulta SQL para obtener el estado y la cantidad de los pedidos
$sql = "SELECT 
    pedidos.Estado,
    COUNT(*) AS cantidad
        FROM 
            pedidos
        JOIN 
            choferes ON pedidos.CHOFER_ASIGNADO = choferes.username
        WHERE 
            choferes.username =  ?
        GROUP BY 
            pedidos.Estado"; // Agrupar por estado

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

$estadosPedidos = array();

if ($result->num_rows > 0) {
    // Recorrer los resultados y agregarlos al array de estados de pedidos
    while($row = $result->fetch_assoc()) {
        $estadoPedido = array(
            'estado' => $row['Estado'],
            'cantidad' => $row['cantidad']
        );
        array_push($estadosPedidos, $estadoPedido);
    }
} else {
    echo "No se encontraron pedidos asignados";
}

// Devolver los estados de pedidos como JSON
header('Content-Type: application/json');
echo json_encode($estadosPedidos);

// Cerrar la conexión
$stmt->close();
$conn->close();
?>
