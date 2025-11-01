<?php
/**
 * API: Estadísticas de Todas las Sucursales
 * Retorna datos completos de todas las sucursales en una sola petición
 * Incluye: estadísticas por estado y facturas por chofer
 */

// Prevenir output no deseado
ob_start();

session_name("GA");
session_start();

// Limpiar buffer
ob_end_clean();

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
    echo json_encode(['error' => 'Parámetros faltantes: start_date, end_date']);
    exit;
}

$start_date = $_GET['start_date'];
$end_date = $_GET['end_date'];

// Validar formato de fechas
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) {
    header("HTTP/1.1 400 Bad Request");
    header("Content-Type: application/json");
    echo json_encode(['error' => 'Formato de fecha inválido. Use YYYY-MM-DD']);
    exit;
}

// Conexión a base de datos
require_once __DIR__ . "/Conexiones/Conexion.php";

// Lista de sucursales
$sucursales = ['AIESA', 'DEASA', 'GABSA', 'ILUMINACION', 'DIMEGSA', 'SEGSA', 'FESA', 'TAPATIA', 'VALLARTA', 'CODI', 'QUERETARO'];

$response = [
    'success' => true,
    'timestamp' => date('Y-m-d H:i:s'),
    'date_range' => [
        'start' => $start_date,
        'end' => $end_date
    ],
    'sucursales' => []
];

// ============================================================
// OBTENER DATOS POR SUCURSAL
// ============================================================

foreach ($sucursales as $sucursal) {
    $sucursal_data = [
        'nombre' => $sucursal,
        'estadisticas_por_estado' => [],
        'facturas_por_chofer' => [],
        'totales' => [
            'total_facturas' => 0,
            'total_kilometros' => 0,
            'total_choferes' => 0
        ]
    ];

    // --------------------------------------------------------
    // 1. ESTADÍSTICAS POR ESTADO (para gráfico circular)
    // --------------------------------------------------------
    $sql_estados = "SELECT
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

    $stmt = $conn->prepare($sql_estados);
    if ($stmt) {
        $stmt->bind_param("sss", $sucursal, $start_date, $end_date);
        $stmt->execute();
        $result = $stmt->get_result();

        // Agregar encabezado para Google Charts
        $sucursal_data['estadisticas_por_estado'][] = ['Estado', 'Cantidad de Facturas'];

        while ($row = $result->fetch_assoc()) {
            $sucursal_data['estadisticas_por_estado'][] = [
                $row['ESTADO'],
                (int)$row['Cantidad_Facturas']
            ];
        }

        $stmt->close();
    }

    // --------------------------------------------------------
    // 2. FACTURAS POR CHOFER (para tabla)
    // --------------------------------------------------------
    $sql_choferes = "SELECT
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

    $stmt = $conn->prepare($sql_choferes);
    if ($stmt) {
        $stmt->bind_param("sss", $sucursal, $start_date, $end_date);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $chofer_data = [
                'chofer' => $row['CHOFER_ASIGNADO'] ?? 'Sin asignar',
                'total_facturas' => (int)$row['TotalFacturas'],
                'entregadas' => (int)$row['Entregadas'],
                'canceladas' => (int)$row['Canceladas'],
                'en_ruta' => (int)$row['EnRuta'],
                'activas' => (int)$row['Activas'],
                'En_Tienda' => (int)$row['EnTienda'],
                'REPROGRAMADO' => (int)$row['REPROGRAMADO'],
                'Total_Kilometros' => (int)$row['TotalKilometros']
            ];

            $sucursal_data['facturas_por_chofer'][] = $chofer_data;

            // Acumular totales
            $sucursal_data['totales']['total_facturas'] += $chofer_data['total_facturas'];
            $sucursal_data['totales']['total_kilometros'] += $chofer_data['Total_Kilometros'];
        }

        $sucursal_data['totales']['total_choferes'] = count($sucursal_data['facturas_por_chofer']);

        $stmt->close();
    }

    // Agregar datos de esta sucursal al response
    $response['sucursales'][$sucursal] = $sucursal_data;
}

// ============================================================
// RESUMEN GENERAL (para gráfico de barras)
// ============================================================

$sql_resumen = "SELECT
    SUCURSAL,
    SUM(CASE WHEN ESTADO = 'Entregado' THEN 1 ELSE 0 END) AS Entregadas,
    SUM(CASE WHEN ESTADO = 'Cancelado' THEN 1 ELSE 0 END) AS Canceladas,
    SUM(CASE WHEN ESTADO = 'En Ruta' THEN 1 ELSE 0 END) AS EnRuta,
    SUM(CASE WHEN ESTADO = 'Activo' THEN 1 ELSE 0 END) AS Activas,
    SUM(CASE WHEN ESTADO = 'En Tienda' THEN 1 ELSE 0 END) AS EnTienda,
    SUM(CASE WHEN ESTADO = 'REPROGRAMADO' THEN 1 ELSE 0 END) AS REPROGRAMADO,
    COUNT(*) AS Total_Facturas,
    COALESCE(SUM(kilometros), 0) AS Total_Kilometros
FROM pedidos
WHERE FECHA_RECEPCION_FACTURA BETWEEN ? AND ?
GROUP BY SUCURSAL
ORDER BY Total_Facturas DESC";

$stmt = $conn->prepare($sql_resumen);
if ($stmt) {
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();

    // Formato para Google Charts
    $response['resumen_general'] = [
        ['Sucursal', 'Entregadas', 'Canceladas', 'En Ruta', 'Activas', 'En Tienda', 'Reprogramado', 'Total Facturas', 'Total Kilometros']
    ];

    while ($row = $result->fetch_assoc()) {
        $response['resumen_general'][] = [
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

    $stmt->close();
}

// Cerrar conexión
$conn->close();

// Retornar JSON
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
echo json_encode($response, JSON_PRETTY_PRINT);
exit;
?>
