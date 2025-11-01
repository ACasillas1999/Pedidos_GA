<?php
/**
 * API: Estadísticas por Sucursal - VERSION DEBUG
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
if (!isset($params['start_date']) || !isset($params['end_date']) || !isset($params['sucursal'])) {
    header("Content-Type: application/json");
    echo json_encode([
        'error' => 'Parámetros faltantes',
        'required' => ['start_date', 'end_date', 'sucursal'],
        'received' => array_keys($params),
        'debug' => $debug
    ], JSON_PRETTY_PRINT);
    exit;
}

// Obtener parámetros
$start_date = $params['start_date'];
$end_date = $params['end_date'];
$sucursal = $params['sucursal'];

$debug['sucursal'] = $sucursal;
$debug['start_date'] = $start_date;
$debug['end_date'] = $end_date;
$debug['sucursal_length'] = strlen($sucursal);
$debug['sucursal_hex'] = bin2hex($sucursal);
$debug['sucursal_chars'] = [];
for ($i = 0; $i < strlen($sucursal); $i++) {
    $debug['sucursal_chars'][] = [
        'char' => $sucursal[$i],
        'ord' => ord($sucursal[$i]),
        'hex' => dechex(ord($sucursal[$i]))
    ];
}

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
    $debug['db_charset'] = $conn->character_set_name();
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
    ESTADO,
    COUNT(*) AS Cantidad_Facturas
FROM
    pedidos
WHERE
    SUCURSAL = ?
    AND FECHA_RECEPCION_FACTURA BETWEEN ? AND ?
GROUP BY
    ESTADO
ORDER BY
    Cantidad_Facturas DESC";

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

// Array para almacenar los datos del gráfico circular
$data_circular = array();
$data_circular[] = ['Estado', 'Cantidad de Facturas']; // Encabezado

// Verificar si se encontraron resultados
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $data_circular[] = [
            $row['ESTADO'],
            (int)$row['Cantidad_Facturas']
        ];
    }
    $debug['records_returned'] = count($data_circular) - 1; // -1 por el encabezado
} else {
    $debug['records_returned'] = 0;
}

// Verificar si la sucursal existe en la BD (búsqueda exacta)
$check_sql = "SELECT COUNT(*) as total FROM pedidos WHERE SUCURSAL = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("s", $sucursal);
$check_stmt->execute();
$check_result = $check_stmt->get_result();
$check_row = $check_result->fetch_assoc();
$debug['total_registros_sucursal'] = $check_row['total'];
$check_stmt->close();

// Verificar sucursales similares
$similar_sql = "SELECT DISTINCT SUCURSAL FROM pedidos WHERE SUCURSAL LIKE ? LIMIT 10";
$similar_stmt = $conn->prepare($similar_sql);
$like_param = "%{$sucursal}%";
$similar_stmt->bind_param("s", $like_param);
$similar_stmt->execute();
$similar_result = $similar_stmt->get_result();
$sucursales_encontradas = [];
while($row = $similar_result->fetch_assoc()) {
    $sucursales_encontradas[] = [
        'nombre' => $row['SUCURSAL'],
        'length' => strlen($row['SUCURSAL']),
        'hex' => bin2hex($row['SUCURSAL'])
    ];
}
$debug['sucursales_similares'] = $sucursales_encontradas;
$similar_stmt->close();

// Verificar todas las sucursales en la BD
$all_sql = "SELECT DISTINCT SUCURSAL FROM pedidos ORDER BY SUCURSAL";
$all_result = $conn->query($all_sql);
$todas_sucursales = [];
while($row = $all_result->fetch_assoc()) {
    $todas_sucursales[] = $row['SUCURSAL'];
}
$debug['todas_las_sucursales'] = $todas_sucursales;

// Cerrar statement y conexión
$stmt->close();
$conn->close();

// Retornar JSON con debug
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'data' => $data_circular,
    'debug' => $debug
], JSON_PRETTY_PRINT);
?>
