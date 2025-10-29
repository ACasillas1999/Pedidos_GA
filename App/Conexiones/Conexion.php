<?php
define('DB_SERVER', 'localhost'); // Aseg��rate de que esto es correcto
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'gpoascen_pedidos_app');
// Conectar a la base de datos
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Verificar la conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
?>
