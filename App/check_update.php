<?php
header('Content-Type: application/json; charset=UTF-8');

// Opcional: si en el futuro quieres leer de BD,
// incluye la conexión:
// require_once __DIR__ . '/Conexiones/Conexion.php';

// Ajusta estos valores cada vez que publiques una nueva APK.
$latestVersionCode = 1;        // Debe ser mayor que BuildConfig.VERSION_CODE actual
$latestVersionName = '1.0';    // Texto que verá el usuario

// URL pública donde subes el APK de la nueva versión.
// EJEMPLO: https://pedidos.grupoascencio.com.mx/apk/app_pedidos_v1.apk
$apkUrl = 'http://107.21.163.64/Pedidos_GA/Pedidos_GA/App/Apks/app_pedidos_v4.apk';

$response = [
    'ok' => true,
    'versionCode' => $latestVersionCode,
    'versionName' => $latestVersionName,
    'apkUrl' => $apkUrl,
    'changelog' => "Mejoras y correcciones generales."
];

echo json_encode($response);

