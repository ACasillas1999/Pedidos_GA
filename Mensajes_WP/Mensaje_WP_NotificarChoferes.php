<?php
// Iniciar la sesión de forma segura
ini_set('session.cookie_httponly', true); // Sólo permitir cookies de sesión vía HTTP
ini_set('session.cookie_secure', true); // Solo enviar cookies de sesión a través de conexiones HTTPS
session_name("GA");
session_start();

// Configurar cabeceras para la respuesta JSON
header('Content-Type: application/json');

// Verificar si el usuario no está logeado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    echo json_encode(['status' => 'error', 'message' => 'Usuario no logueado.']);
    exit;
}

// Conexión a la base de datos
require_once __DIR__ . "/../Conexiones/Conexion.php";

// Verificar si se ha establecido la sucursal en la sesión
if (isset($_SESSION["Sucursal"])) {
    $sucursal = $_SESSION["Sucursal"];

    // Consulta SQL para verificar si hay algún pedido sin chofer asignado
    $sqlCheck = "SELECT COUNT(*) as count
                 FROM pedidos
                 WHERE (ESTADO = 'EN RUTA' OR ESTADO = 'REPROGRAMADO' OR ESTADO = 'EN TIENDA' OR ESTADO = 'ACTIVO')
                 AND SUCURSAL = ?
                 AND (CHOFER_ASIGNADO IS NULL OR CHOFER_ASIGNADO = '')";
                 
    $stmtCheck = $conn->prepare($sqlCheck);
    $stmtCheck->bind_param("s", $sucursal);
    $stmtCheck->execute();
    $resultCheck = $stmtCheck->get_result();
    $rowCheck = $resultCheck->fetch_assoc();

    if ($rowCheck['count'] > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Asigne todos los pedidos para continuar.']);
    } else {
        // Consulta SQL para obtener los números de los choferes asignados a pedidos con estados específicos
        $sql = "SELECT DISTINCT c.Numero
                FROM choferes c
                JOIN pedidos p ON c.username = p.CHOFER_ASIGNADO
                WHERE (p.ESTADO = 'EN RUTA' OR p.ESTADO = 'REPROGRAMADO' OR p.ESTADO = 'EN TIENDA' OR p.ESTADO = 'ACTIVO')
                AND c.Sucursal = ?
                AND p.CHOFER_ASIGNADO IS NOT NULL
                AND p.CHOFER_ASIGNADO != ''";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $sucursal);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // TOKEN QUE NOS DA FACEBOOK
            $token = 'EAAGacaATjwEBOZBgqhohcVk1ZBGEAbiTl7i86qESvSPjdllaomwzIG7LmOOvyTFpzyIlXX6dtTYTVTLLuw6SjaLoh2rec07I8qu1nGNYSVZAmQTGNa3QCQjujTqfd7QuLLwFNQllnX2z1V7JvToDhEi5KVqUWXHSqgSETvGyU7S2SN2fpXW0NpQaRI48pwZAgGS7A1BQMjLl5ZBjy';
            // URL A DONDE SE MANDARA EL MENSAJE
            $url = 'https://graph.facebook.com/v19.0/335894526282507/messages';

            $errors = [];
            while ($row = $result->fetch_assoc()) {
                $numero_usuario = $row['Numero'];
                $telefono = '52' . $numero_usuario;

                // CONFIGURACION DEL MENSAJE
                $mensaje = json_encode([
                    "messaging_product" => "whatsapp",
                    "to" => $telefono,
                    "type" => "template",
                    "template" => [
                        "name" => "ga_notificarchofer",
                        "language" => ["code" => "en_US"],
                    ]
                ]);

                // DECLARAMOS LAS CABECERAS
                $header = ["Authorization: Bearer " . $token, "Content-Type: application/json"];
                // INICIAMOS EL CURL
                $curl = curl_init();
                curl_setopt($curl, CURLOPT_URL, $url);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $mensaje);
                curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                // OBTENEMOS LA RESPUESTA DEL ENVIO DE INFORMACION
                $response = json_decode(curl_exec($curl), true);
                // OBTENEMOS EL CODIGO DE LA RESPUESTA
                $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                // CERRAMOS EL CURL
                curl_close($curl);

                if ($status_code !== 200) {
                    $errors[] = "Error al enviar mensaje a $telefono: " . json_encode($response);
                }
            }

            if (empty($errors)) {
                echo json_encode(['status' => 'success', 'message' => 'Mensajes enviados correctamente.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Algunos mensajes no se pudieron enviar.', 'errors' => $errors]);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => "No se encontraron choferes asignados."]);
        }

        // Cerrar la consulta
        $stmt->close();
    }

    // Cerrar la consulta de verificación
    $stmtCheck->close();
} else {
    echo json_encode(['status' => 'error', 'message' => "Sucursal no establecida en la sesión."]);
}

// Cerrar la conexión
$conn->close();
?>
