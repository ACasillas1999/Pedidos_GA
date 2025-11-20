<?php
// Iniciar la sesión de forma segura
ini_set('session.cookie_httponly', true); // Sólo permitir cookies de sesión vía HTTP
ini_set('session.cookie_secure', true); // Solo enviar cookies de sesión a través de conexiones HTTPS
session_name("GA");
session_start();

// Verificar si el usuario no está logeado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    // Si no está logeado, redirigir al formulario de inicio de sesión
    header("location: /Pedidos_GA/Sesion/login.html");
    exit;
}

// Conexión a la base de datos
require_once __DIR__ . "/../Conexiones/Conexion.php";

// Obtener el ID del pedido desde una petición GET o POST
$pedido_id = $_GET['pedido_id'] ?? null;

if ($pedido_id) {
    // Consulta SQL para obtener los usuarios con el rol de "JC" en la sucursal del pedido
    $sql = "SELECT Numero 
            FROM usuarios 
            WHERE Rol = 'JC' 
            AND Sucursal = (SELECT SUCURSAL FROM pedidos WHERE ID = ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $pedido_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        //TOKEN QUE NOS DA FACEBOOK
        $token = 'EAAGacaATjwEBOZBgqhohcVk1ZBGEAbiTl7i86qESvSPjdllaomwzIG7LmOOvyTFpzyIlXX6dtTYTVTLLuw6SjaLoh2rec07I8qu1nGNYSVZAmQTGNa3QCQjujTqfd7QuLLwFNQllnX2z1V7JvToDhEi5KVqUWXHSqgSETvGyU7S2SN2fpXW0NpQaRI48pwZAgGS7A1BQMjLl5ZBjy';
        //URL A DONDE SE MANDARA EL MENSAJE
        $url = 'https://graph.facebook.com/v19.0/335894526282507/messages';

        while ($row = $result->fetch_assoc()) {
            $numero_usuario = $row['Numero'];
            $telefono = '52' . $numero_usuario;

            //CONFIGURACION DEL MENSAJE
            $mensaje = json_encode([
                "messaging_product" => "whatsapp",
                "to" => $telefono,
                "type" => "template",
                "template" => [
                    "name" => "ga_notificacion",
                    "language" => ["code" => "en_US"],
                ]
            ]);

            //DECLARAMOS LAS CABECERAS
            $header = ["Authorization: Bearer " . $token, "Content-Type: application/json"];
            //INICIAMOS EL CURL
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $mensaje);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            //OBTENEMOS LA RESPUESTA DEL ENVIO DE INFORMACION
            $response = json_decode(curl_exec($curl), true);
            //IMPRIMIMOS LA RESPUESTA 
            print_r($response);
            //OBTENEMOS EL CODIGO DE LA RESPUESTA
            $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            //CERRAMOS EL CURL
            curl_close($curl);


            echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';
            echo '<script>';
            echo 'Swal.fire({';
            echo '  icon: "success",';
            echo '  title: "Pedido creado",';
            echo '  text: "El pedido se ha agregado correctamente y se han enviado las notificaciones.",';
            echo '  timer: 2000,';
            echo '  showConfirmButton: false';
            echo '}).then(() => {';
            echo '  window.location.href = "/Inicio.php?id=' . $pedido_id . '";';
            echo '});';
            echo '</script>';
        }
    } else {
        echo "No se encontraron usuarios con el rol de 'JC' en la sucursal del pedido.";
    }

    // Cerrar la consulta
    $stmt->close();
} else {
    echo "Por favor, proporcione un ID de pedido válido.";
}

// Cerrar la conexión
$conn->close();
?>
