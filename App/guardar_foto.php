<?php
// Mostrar errores para depuración (desactivar en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/*/ Credenciales de la base de datos
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "pedidos_app";*/

require_once __DIR__ . "/Conexiones/Conexion.php";

// Nombre de la tabla y columna para la ruta de las fotos
$tabla = "pedidos";
$columna_ruta_fotos = "Ruta_fotos";

// Ruta donde se guardarán las imágenes
$ruta = "../Fotos/";

/*/ Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar la conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}*/

// Si se recibió un archivo y un ID
if (isset($_FILES['image']) && isset($_POST['id'])) {
    $id = intval($_POST['id']); // Asegurarse de que el ID sea un entero
    
    // Subir la imagen al servidor
    $file_name = $_FILES['image']['name'];
    $file_tmp = $_FILES['image']['tmp_name'];
    
    // Verificar si la ruta existe
    if (!file_exists($ruta)) {
        // Intenta crear el directorio
        if (!mkdir($ruta, 0777, true)) {
            die("Error al crear el directorio de destino.");
        }
    }
    
    // Mover el archivo a la ubicación deseada
    if (move_uploaded_file($file_tmp, $ruta . $file_name)) {
        // Actualizar la ruta de la imagen en la base de datos
        $ruta_completa = $ruta . $file_name;
        
        $stmt = $conn->prepare("UPDATE $tabla SET $columna_ruta_fotos = ? WHERE id = ?");
        $stmt->bind_param("si", $ruta_completa, $id);
        
        if ($stmt->execute()) {
            echo "¡Imagen subida correctamente y ruta guardada en la base de datos!";
        } else {
            // Error al actualizar la base de datos
            echo "Error al actualizar la base de datos: " . $stmt->error;
        }
        
        $stmt->close();
    } else {
        echo "Error al mover el archivo al servidor.";
    }
} else {
    echo "No se recibieron los datos necesarios.";
}

$conn->close();
?>
