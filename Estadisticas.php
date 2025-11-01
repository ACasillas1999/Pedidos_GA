<?php
/**
 * API: Estadísticas por Sucursal
 * Retorna la cantidad de facturas agrupadas por estado para una sucursal específica
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
if (!isset($params['start_date']) || !isset($params['end_date']) || !isset($params['sucursal'])) {
    header("HTTP/1.1 400 Bad Request");
    header("Content-Type: application/json");
    echo json_encode(['error' => 'Parámetros faltantes', 'method' => $method]);
    exit;
}

// Obtener parámetros
$start_date = $params['start_date'];
$end_date = $params['end_date'];
$sucursal = $params['sucursal'];

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

// Preparar consulta
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
}

// Cerrar statement y conexión
$stmt->close();
$conn->close();

// Retornar JSON
header('Content-Type: application/json');
echo json_encode($data_circular);
?>
