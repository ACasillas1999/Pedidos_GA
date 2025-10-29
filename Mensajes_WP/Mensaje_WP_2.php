<?php
// Iniciar la sesión de forma segura
ini_set('session.cookie_httponly', true); // Sólo permitir cookies de sesión vía HTTP
ini_set('session.cookie_secure', true); // Solo enviar cookies de sesión a través de conexiones HTTPS
session_name("RH");
session_start();

// Verificar si el usuario no está logeado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    // Si no está logeado, redirigir al formulario de inicio de sesión
    header("location: /RH%20VACACIONES/Sesion/login.html");
    exit;
}
?>

<?php
require_once __DIR__ . "/../Conexiones/Conexion.php";

// Verifica si se recibió un valor válido para Numero_empleado
if (isset($_GET['Numero_empleado'])) {
    // Obtén el número de empleado del GET
    $numero_empleado = $_GET['Numero_empleado'];
    
    // Prepara la consulta SQL para obtener el número de teléfono del empleado
    $query_empleado = "SELECT numero, jefe_directo FROM empleado WHERE Numero_Empleado = ?";
    
    // Prepara la declaración SQL utilizando la conexión establecida
    $stmt = $conn->prepare($query_empleado);
    
    // Vincula los parámetros y ejecuta la consulta
    $stmt->bind_param("s", $numero_empleado);
    $stmt->execute();
    
    // Obtiene el resultado de la consulta
    $stmt->bind_result($telefono_empleado, $nombre_jefe_directo);
    $stmt->fetch();
    $stmt->close();
    
    // Si se encontró el empleado, procede a buscar el número del jefe directo
    if ($telefono_empleado && $nombre_jefe_directo) {
        // Prepara la consulta SQL para obtener el número del jefe directo
        $query_jefe = "SELECT Numero FROM jefe_directo WHERE Nombre = ?";
        $stmt = $conn->prepare($query_jefe);
        $stmt->bind_param("s", $nombre_jefe_directo);
        $stmt->execute();
        $stmt->bind_result($telefono_jefe_directo);
        $stmt->fetch();
        $stmt->close();
    } else {
        $telefono_jefe_directo = null;
    }

    // Cierra la conexión a la base de datos
    $conn->close();
    
    // Función para enviar mensaje
    function enviarMensaje($telefono, $token, $url) {
        $mensaje = array(
            'messaging_product' => 'whatsapp',
            'to' => '52' . $telefono, // Concatena el código del país si es necesario
            'type' => 'template',
            'template' => array(
                'name' => 'rh_activo',
                'language' => array('code' => 'en_US') // Cambia a 'es' si prefieres español
            )
        );
        
        $header = array(
            "Authorization: Bearer " . $token,
            "Content-Type: application/json"
        );
        
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($mensaje));
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        
        $response = json_decode(curl_exec($curl), true);
        curl_close($curl);
        
        return $response;
    }
    
    // TOKEN QUE NOS DA FACEBOOK
    $token = 'EAAKqHiB2Q8gBO1LahaHC8C5gKURitiiYszAaQqrk4h3NsG5V2YKeJZAnq0K6WJ3jw5qZAUKI3Cdm5hQdjgakvCjaVguFdZBFGCpZBRzk5Mfukv5cL5O2pj5JCcHYoZAIPVoTGN1atalZAaClPxeEI9HKDlIetbMcf4H0CZCwqKAH4JB6zRuxJEBuT2bu1GzCtysjhXeg0Jni7owgI8OO9MUSWnVLdAC0fCFl1QZD';
    $url = 'https://graph.facebook.com/v19.0/335894526282507/messages';
    
    // Enviar mensaje al empleado
    if ($telefono_empleado) {
        $response_empleado = enviarMensaje($telefono_empleado, $token, $url);
       // print_r($response_empleado);
    } else {
        echo "Error: No se encontró un número de teléfono asociado al número de empleado proporcionado.";
    }
    
    // Enviar mensaje al jefe directo
    if ($telefono_jefe_directo) {
        $response_jefe = enviarMensaje($telefono_jefe_directo, $token, $url);
       // print_r($response_jefe);
    } else {
        echo "Error: No se encontró un número de teléfono asociado al jefe directo del empleado proporcionado.";
    }
} else {
    echo "Error: No se recibió el parámetro Numero_empleado.";
}
?>
