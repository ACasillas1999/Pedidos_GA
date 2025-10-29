<?php
session_name("GA");
session_start();

// Verificar si el usuario no está logeado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    // Si no está logeado, redirigir al formulario de inicio de sesión
    header("location: /Pedidos_GA/Sesion/login.html");
    exit;
}
// Función para cargar archivos
function cargarArchivo($pedidoId) {
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['archivo'])) {
        $file = $_FILES['archivo'];

        // Verificar si se cargó un archivo correctamente
        if ($file['error'] === UPLOAD_ERR_OK) {
            $fileName = $file['name'];
            $fileTmpName = $file['tmp_name'];
            $fileSize = $file['size'];
            $fileType = $file['type'];

            // Obtener la extensión del archivo
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            // Permitir solo ciertos tipos de archivos
            $allowedExtensions = array("pdf", "doc", "docx", "txt");
            if (in_array($fileExt, $allowedExtensions)) {
                // Ruta donde se guardarán los archivos
                $uploadDir = "archivos/";

                // Crear un nombre único para el archivo
                $uniqueName = uniqid("pedido_" . $pedidoId . "_") . "." . $fileExt;

                // Mover el archivo al directorio de carga
                $filePath = $uploadDir . $uniqueName;
                if (move_uploaded_file($fileTmpName, $filePath)) {
                    // Guardar la ruta del archivo en la base de datos
                    $servername = "localhost";
                    $username = "root";
                    $password = "";
                    $database = "pedidos_app";

                    $conn = new mysqli($servername, $username, $password, $database);

                    if ($conn->connect_error) {
                        die("Error de conexión: " . $conn->connect_error);
                    }

                    // Actualizar la ruta del archivo en la base de datos
                    $sql = "UPDATE Pedidos SET Ruta = ? WHERE ID = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("si", $filePath, $pedidoId);
                    $stmt->execute();

                    echo "El archivo se cargó correctamente.";
                } else {
                    echo "Hubo un error al cargar el archivo.";
                }
            } else {
                echo "Solo se permiten archivos PDF, DOC, DOCX y TXT.";
            }
        } else {
            echo "Hubo un error al cargar el archivo.";
        }
    }
}

// Función para descargar archivos
function descargarArchivo($pedidoId) {
    // Consultar la ruta del archivo en la base de datos
    $servername = "localhost";
    $username = "root";
    $password = "";
    $database = "pedidos_app";

    $conn = new mysqli($servername, $username, $password, $database);

    if ($conn->connect_error) {
        die("Error de conexión: " . $conn->connect_error);
    }

    $sql = "SELECT Ruta FROM Pedidos WHERE ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $pedidoId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $rutaArchivo = $row['Ruta'];

        // Descargar el archivo
        if (file_exists($rutaArchivo)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($rutaArchivo) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($rutaArchivo));
            readfile($rutaArchivo);
            exit;
        } else {
            echo "El archivo no existe.";
        }
    } else {
        echo "No se encontró el pedido.";
    }

    $conn->close();
}

// Función para consultar archivos
function consultarArchivo($pedidoId) {
    // Consultar la ruta del archivo en la base de datos
    $servername = "localhost";
    $username = "root";
    $password = "";
    $database = "pedidos_app";

    $conn = new mysqli($servername, $username, $password, $database);

    if ($conn->connect_error) {
        die("Error de conexión: " . $conn->connect_error);
    }

    $sql = "SELECT Ruta FROM Pedidos WHERE ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $pedidoId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $rutaArchivo = $row['Ruta'];

        // Mostrar la ruta del archivo
        echo "La ruta del archivo es: " . $rutaArchivo;
    } else {
        echo "No se encontró el pedido.";
    }

    $conn->close();
}

// Utilizar las funciones según la acción requerida
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion']) && isset($_POST['id'])) {
    $pedidoId = $_POST['id'];
    $accion = $_POST['accion'];

    if ($accion === "cargar") {
        cargarArchivo($pedidoId);
    } elseif ($accion === "descargar") {
        descargarArchivo($pedidoId);
    } elseif ($accion === "consultar") {
        consultarArchivo($pedidoId);
    }
}

?>
