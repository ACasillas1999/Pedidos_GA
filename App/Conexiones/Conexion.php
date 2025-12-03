
<?php
// Conexion MySQLi centralizada para los endpoints
// Intenta obtener credenciales de variables de entorno y luego usa defaults

if (!isset($conn) || !($conn instanceof mysqli)) {
    $dbHost = getenv('DB_HOST') !== false ? getenv('DB_HOST') : '18.211.75.118';
    $dbUser = getenv('DB_USER') !== false ? getenv('DB_USER') : 'pedidos_app';
    $dbPass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : 'TuContraseaSegura123';
    $dbName = getenv('DB_NAME') !== false ? getenv('DB_NAME') : 'gpoascen_pedidos_app';

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    try {
        $conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
        $conn->set_charset('utf8mb4');
    } catch (Throwable $e) {
        http_response_code(500);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['ok' => false, 'error' => 'DB_CONN', 'detail' => '']);
        exit;
    }
}
?>


<?php
/*define('DB_SERVER', 'localhost'); // Aseg��rate de que esto es correcto
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'gpoascen_pedidos_app');
// Conectar a la base de datos
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Verificar la conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}*/
?>
