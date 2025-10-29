<?php
/*define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'pedidos_app');
// Conexión a la base de datos
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Verificar la conexión
if ($conn->connect_error) {
    die("Error de conexión a la base de datos: " . $conn->connect_error);
}*/


// Conexión a la base de datos
require_once __DIR__ . "/Conexiones/Conexion.php";

// Obtener las credenciales del usuario del POST
$username = $_POST['username'];
$password = $_POST['password'];

// Consultar la base de datos para verificar las credenciales
$query = "SELECT * FROM choferes WHERE username='$username' AND password='$password'";
$result = $conn->query($query);

// Comprobar si se encontraron resultados
if ($result->num_rows > 0) {
    // Usuario autenticado correctamente
    echo "OK";
} else {
    // Usuario o contraseña incorrectos
    echo "ERROR";
}

// Cerrar la conexión a la base de datos
$conn->close();
?>
