




<!--<?php

session_name("GA");
session_start();

// Verificar si el usuario no está logeado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    // Si no está logeado, redirigir al formulario de inicio de sesión
    header("location: /Pedidos_GA/Sesion/login.html");
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

use Resend\Client;
use Resend\ValueObjects\ApiKey;
use Resend\ValueObjects\Transporter\BaseUri;
use Resend\ValueObjects\Transporter\Headers;
use GuzzleHttp\Client as GuzzleClient;
use Resend\Transporters\HttpTransporter;

try {
    // Configura el cliente Resend
    $apiKey = ApiKey::from('re_YaJMAUkJ_BvpYqUGPLfd6fwtp4naAnzpj');
    $baseUri = BaseUri::from('api.resend.com');
    $headers = Headers::withAuthorization($apiKey);

    $guzzleClient = new GuzzleClient();
    $transporter = new HttpTransporter($guzzleClient, $baseUri, $headers);

    $resendClient = new Client($transporter);

    // Asegúrate de proporcionar los datos necesarios, incluyendo el remitente y el destinatario
    $options = [
        'from' => 'acasillas.work@gmail.com',
        'to' => 'acasillas.work@gmail.com',
        'subject' => '¡Hola mundo!',
        'html' => '<strong>¡Funciona Ahora si!!</strong>',
    ];

    // Envía el correo electrónico con las opciones especificadas
    $response = $resendClient->sendEmail($options);

    echo 'Correo electrónico enviado correctamente.';

} catch (\Exception $e) {
    echo 'Error al enviar el correo electrónico: ' . $e->getMessage();
}

?> -->