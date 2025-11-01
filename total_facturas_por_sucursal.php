<?php
/**
 * API: Total de Facturas por Sucursal
 * Retorna el total de facturas agrupadas por sucursal y estado
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

// Validar parámetros requeridos
if (!isset($_GET['start_date']) || !isset($_GET['end_date'])) {
    header("HTTP/1.1 400 Bad Request");
    header("Content-Type: application/json");
    echo json_encode(['error' => 'Parámetros faltantes']);
    exit;
}

// Obtener parámetros
$start_date = $_GET['start_date'];
$end_date = $_GET['end_date'];

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
    SUCURSAL,
    SUM(CASE WHEN ESTADO = 'Entregado' THEN 1 ELSE 0 END) AS Entregadas,
    SUM(CASE WHEN ESTADO = 'Cancelado' THEN 1 ELSE 0 END) AS Canceladas,
    SUM(CASE WHEN ESTADO = 'En Ruta' THEN 1 ELSE 0 END) AS EnRuta,
    SUM(CASE WHEN ESTADO = 'Activo' THEN 1 ELSE 0 END) AS Activas,
    SUM(CASE WHEN ESTADO = 'En Tienda' THEN 1 ELSE 0 END) AS EnTienda,
    SUM(CASE WHEN ESTADO = 'REPROGRAMADO' THEN 1 ELSE 0 END) AS REPROGRAMADO,
    COUNT(*) AS Total_Facturas,
    COALESCE(SUM(kilometros), 0) AS Total_Kilometros
FROM
    pedidos
WHERE
    FECHA_RECEPCION_FACTURA BETWEEN ? AND ?
GROUP BY
    SUCURSAL
ORDER BY
    Total_Facturas DESC";

// Preparar consulta
$stmt = $conn->prepare($sql);

if (!$stmt) {
    header("HTTP/1.1 500 Internal Server Error");
    header("Content-Type: application/json");
    echo json_encode(['error' => 'Error al preparar consulta: ' . $conn->error]);
    exit;
}

// Bind de parámetros
$stmt->bind_param("ss", $start_date, $end_date);

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

// Array para almacenar los resultados
$data = array();

// Agregar encabezado
$data[] = [
    'Sucursal',
    'Entregadas',
    'Canceladas',
    'En Ruta',
    'Activas',
    'En Tienda',
    'Reprogramado',
    'Total Facturas',
    'Total Kilometros'
];

// Agregar filas de datos
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $data[] = [
            $row['SUCURSAL'],
            (int)$row['Entregadas'],
            (int)$row['Canceladas'],
            (int)$row['EnRuta'],
            (int)$row['Activas'],
            (int)$row['EnTienda'],
            (int)$row['REPROGRAMADO'],
            (int)$row['Total_Facturas'],
            (float)$row['Total_Kilometros']
        ];
    }
}

// Cerrar statement y conexión
$stmt->close();
$conn->close();

// Retornar JSON
header('Content-Type: application/json');
echo json_encode($data);
?>
