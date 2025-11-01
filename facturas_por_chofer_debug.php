<?php
/**
 * API: Facturas por Chofer - VERSION DEBUG
 * Muestra todos los errores y detalles para debugging
 */

// Mostrar todos los errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_name("GA");
session_start();

// Array para recopilar información de debug
$debug = [];
$debug['timestamp'] = date('Y-m-d H:i:s');
$debug['request_method'] = $_SERVER['REQUEST_METHOD'];
$debug['session_active'] = isset($_SESSION["loggedin"]);

// Verificar autenticación
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Content-Type: application/json");
    echo json_encode([
        'error' => 'No autorizado',
        'debug' => $debug
    ], JSON_PRETTY_PRINT);
    exit;
}

$debug['user_logged'] = $_SESSION["username"] ?? 'desconocido';

// Aceptar parámetros por GET o POST
$method = $_SERVER['REQUEST_METHOD'];
$params = ($method === 'POST') ? $_POST : $_GET;

$debug['params_received'] = $params;

// Validar parámetros requeridos
if (!isset($params['sucursal']) || !isset($params['start_date']) || !isset($params['end_date'])) {
    header("Content-Type: application/json");
    echo json_encode([
        'error' => 'Parámetros faltantes',
        'required' => ['sucursal', 'start_date', 'end_date'],
        'received' => array_keys($params),
        'debug' => $debug
    ], JSON_PRETTY_PRINT);
    exit;
}

// Obtener parámetros
$sucursal = $params['sucursal'];
$start_date = $params['start_date'];
$end_date = $params['end_date'];

$debug['sucursal'] = $sucursal;
$debug['start_date'] = $start_date;
$debug['end_date'] = $end_date;
$debug['sucursal_length'] = strlen($sucursal);
$debug['sucursal_hex'] = bin2hex($sucursal);

// Validar formato de fechas
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) {
    header("Content-Type: application/json");
    echo json_encode([
        'error' => 'Formato de fecha inválido',
        'start_date' => $start_date,
        'end_date' => $end_date,
        'debug' => $debug
    ], JSON_PRETTY_PRINT);
    exit;
}

// Conexión a base de datos
try {
    require_once __DIR__ . "/Conexiones/Conexion.php";
    $debug['db_connected'] = true;
} catch (Exception $e) {
    header("Content-Type: application/json");
    echo json_encode([
        'error' => 'Error de conexión a BD',
        'message' => $e->getMessage(),
        'debug' => $debug
    ], JSON_PRETTY_PRINT);
    exit;
}

// Consulta SQL con prepared statement
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

$debug['sql_query'] = $sql;

// Preparar consulta
$stmt = $conn->prepare($sql);

if (!$stmt) {
    header("Content-Type: application/json");
    echo json_encode([
        'error' => 'Error al preparar consulta',
        'mysql_error' => $conn->error,
        'mysql_errno' => $conn->errno,
        'debug' => $debug
    ], JSON_PRETTY_PRINT);
    exit;
}

$debug['statement_prepared'] = true;

// Bind de parámetros
$stmt->bind_param("sss", $sucursal, $start_date, $end_date);
$debug['params_bound'] = true;

// Ejecutar
if (!$stmt->execute()) {
    header("Content-Type: application/json");
    echo json_encode([
        'error' => 'Error al ejecutar consulta',
        'mysql_error' => $stmt->error,
        'mysql_errno' => $stmt->errno,
        'debug' => $debug
    ], JSON_PRETTY_PRINT);
    $stmt->close();
    $conn->close();
    exit;
}

$debug['query_executed'] = true;

// Obtener resultados
$result = $stmt->get_result();
$debug['num_rows'] = $result->num_rows;

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

$debug['records_returned'] = count($data);

// Verificar si la sucursal existe en la BD
$check_sql = "SELECT DISTINCT SUCURSAL FROM pedidos WHERE SUCURSAL LIKE ? LIMIT 10";
$check_stmt = $conn->prepare($check_sql);
$like_param = "%{$sucursal}%";
$check_stmt->bind_param("s", $like_param);
$check_stmt->execute();
$check_result = $check_stmt->get_result();
$sucursales_encontradas = [];
while($row = $check_result->fetch_assoc()) {
    $sucursales_encontradas[] = $row['SUCURSAL'];
}
$debug['sucursales_similares'] = $sucursales_encontradas;
$check_stmt->close();

// Cerrar statement y conexión
$stmt->close();
$conn->close();

// Retornar JSON con debug
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'data' => $data,
    'debug' => $debug
], JSON_PRETTY_PRINT);
?>
