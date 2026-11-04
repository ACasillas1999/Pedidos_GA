<?php
header('Content-Type: application/json');

// Mostrar errores para depuración (desactivar en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/*/ Credenciales de la base de datos
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "pedidos_app";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar la conexión
if ($conn->connect_error) {
    die(json_encode(["error" => "Conexión fallida: " . $conn->connect_error]));
}*/

require_once __DIR__ . "/Conexiones/Conexion.php";

// Recuperar el ID del pedido
$id_pedido = isset($_GET['id_pedido']) ? intval($_GET['id_pedido']) : 0;
if ($id_pedido <= 0) {
    die(json_encode(["error" => "ID de pedido inválido"]));
}

// Recuperar la ruta de la imagen desde la base de datos
$query = "SELECT Ruta_fotos FROM pedidos WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id_pedido);
$stmt->execute();
$resultado = $stmt->get_result();

$response = [];
if ($resultado && $resultado->num_rows > 0) {
    $fila = $resultado->fetch_assoc();
    $ruta_imagen = trim((string)($fila["Ruta_fotos"] ?? ''));
    if (!empty($ruta_imagen)) {
        $ruta_limpia = ltrim(str_replace('\\', '/', $ruta_imagen), './');
        $archivo_local = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $ruta_limpia);
        if (file_exists($archivo_local)) {
            $url_imagen_completa = "/Pedidos_GA/" . $ruta_limpia;
        } else {
            $url_imagen_completa = "https://pedidos.grupoascencio.com.mx/Pedidos_GA/" . $ruta_limpia;
        }
        $response[] = ["ruta_imagen" => $url_imagen_completa, "found" => true];
    } else {
        $response[] = ["ruta_imagen" => '/Pedidos_GA/Img/Error.ico', "found" => false];
    }
} else {
    $response[] = ["ruta_imagen" => '/Pedidos_GA/Img/Error.ico', "found" => false];
}

// Cerrar la conexión
$stmt->close();
$conn->close();

// Devolver la respuesta en formato JSON
echo json_encode($response);

?>
