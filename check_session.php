<?php
/**
 * Check Session Status
 * Retorna el estado de la sesión del usuario
 */

// No output before this
ob_start();

session_name("GA");
session_start();

// Clear any output buffer
ob_end_clean();

// Set headers
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

$response = [
    'logged_in' => isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true,
    'timestamp' => date('Y-m-d H:i:s')
];

if ($response['logged_in']) {
    $response['user'] = [
        'username' => $_SESSION["username"] ?? 'desconocido',
        'nombre' => $_SESSION["Nombre"] ?? 'desconocido',
        'rol' => $_SESSION["Rol"] ?? 'desconocido',
        'sucursal' => $_SESSION["Sucursal"] ?? 'desconocido'
    ];
}

echo json_encode($response);
exit;
