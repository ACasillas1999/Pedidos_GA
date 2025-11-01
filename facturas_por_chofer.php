<?php
/**
 * API: Facturas por Chofer
 * Retorna estadísticas de facturas agrupadas por chofer para una sucursal específica
 * Formato: JSON
 */

session_name("GA");
session_start();

// Verificar autenticación
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("HTTP/1.1 401 Unauthorized");
    header("Content-Type: application/json");
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// Aceptar parámetros por GET o POST
$method = $_SERVER['REQUEST_METHOD'];
$params = ($method === 'POST') ? $_POST : $_GET;

// Validar parámetros requeridos
if (!isset($params['sucursal']) || !isset($params['start_date']) || !isset($params['end_date'])) {
    header("HTTP/1.1 400 Bad Request");
    header("Content-Type: application/json");
    echo json_encode(['error' => 'Parámetros faltantes', 'method' => $method]);
    exit;
}

// Obtener parámetros
$sucursal = $params['sucursal'];
$start_date = $params['start_date'];
$end_date = $params['end_date'];

// Validar formato de fechas
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) {
    header("HTTP/1.1 400 Bad Request");
    header("Content-Type: application/json");
    echo json_encode(['error' => 'Formato de fecha inválido']);
    exit;
}

// Conexión a base de datos
require_once __DIR__ . "/Conexiones/Conexion.php";

// Consulta SQL con prepared statement para prevenir SQL injection
$sql = "SELECT
    CHOFER_ASIGNADO,
    COUNT(*) AS TotalFacturas,
    SUM(CASE WHEN ESTADO = 'Entregado' THEN 1 ELSE 0 END) AS Entregadas,
    SUM(CASE WHEN ESTADO = 'Cancelado' THEN 1 ELSE 0 END) AS Canceladas,
    SUM(CASE WHEN ESTADO = 'En Ruta' THEN 1 ELSE 0 END) AS EnRuta,
    SUM(CASE WHEN ESTADO = 'Activo' THEN 1 ELSE 0 END) AS Activas,
    SUM(CASE WHEN ESTADO = 'En Tienda' THEN 1 ELSE 0 END) AS EnTienda,
    SUM(CASE WHEN ESTADO = 'REPROGRAMADO' THEN 1 ELSE 0 END) AS REPROGRAMADO,
    COALESCE(SUM(kilometros), 0) AS TotalKilometros
FROM
    pedidos
WHERE
    SUCURSAL = ?
    AND FECHA_RECEPCION_FACTURA BETWEEN ? AND ?
GROUP BY
    CHOFER_ASIGNADO
ORDER BY
    TotalFacturas DESC";

// Preparar y ejecutar consulta
$stmt = $conn->prepare($sql);

if (!$stmt) {
    header("HTTP/1.1 500 Internal Server Error");
    header("Content-Type: application/json");
    echo json_encode(['error' => 'Error al preparar consulta: ' . $conn->error]);
    exit;
}

// Bind de parámetros
$stmt->bind_param("sss", $sucursal, $start_date, $end_date);

// Ejecutar
if (!$stmt->execute()) {
    header("HTTP/1.1 500 Internal Server Error");
    header("Content-Type: application/json");
    echo json_encode(['error' => 'Error al ejecutar consulta: ' . $stmt->error]);
    $stmt->close();
    $conn->close();
    exit;
}

// Obtener resultados
$result = $stmt->get_result();
$data = array();

while($row = $result->fetch_assoc()) {
    $data[] = array(
        'chofer' => $row['CHOFER_ASIGNADO'] ?? 'Sin asignar',
        'total_facturas' => (int)$row['TotalFacturas'],
        'entregadas' => (int)$row['Entregadas'],
        'canceladas' => (int)$row['Canceladas'],
        'en_ruta' => (int)$row['EnRuta'],
        'activas' => (int)$row['Activas'],
        'En_Tienda' => (int)$row['EnTienda'],
        'REPROGRAMADO' => (int)$row['REPROGRAMADO'],
        'Total_Kilometros' => (int)$row['TotalKilometros']
    );
}

// Cerrar statement y conexión
$stmt->close();
$conn->close();

// Retornar JSON
header('Content-Type: application/json');
echo json_encode($data);
?>
