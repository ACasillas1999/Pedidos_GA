<?php
session_name("GA");
session_start();

// Verificar si el usuario está logeado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: /Pedidos_GA/Sesion/login.html");
    exit;
}

require_once __DIR__ . "/Conexiones/Conexion.php";

// Obtener parámetros de filtro
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : null;
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : null;

// Validar y limpiar fechas
if ($start_date !== null && $end_date !== null) {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) {
        $start_date = null;
        $end_date = null;
    }
}

$usar_filtro_fechas = ($start_date !== null && $end_date !== null);

// Lista de sucursales
$sucursales = ['AIESA', 'DEASA', 'GABSA', 'ILUMINACION', 'DIMEGSA', 'SEGSA', 'FESA', 'TAPATIA', 'VALLARTA', 'CODI', 'QUERETARO'];

// --- SECCIÓN 1: PROCESAR RESUMEN GENERAL ---
$resumen_general = [];
if ($usar_filtro_fechas) {
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
        while ($row = $result->fetch_assoc()) {
            $resumen_general[] = $row;
        }
        $stmt->close();
    }
} else {
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
    GROUP BY SUCURSAL
    ORDER BY Total_Facturas DESC";

    $stmt = $conn->prepare($sql_resumen);
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $resumen_general[] = $row;
        }
        $stmt->close();
    }
}

// --- SECCIÓN 2: PROCESAR DETALLE DE CHOFERES POR SUCURSAL ---
$choferes_data = [];
foreach ($sucursales as $sucursal) {
    if ($usar_filtro_fechas) {
        $sql_choferes = "SELECT
            ? AS SUCURSAL,
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
            $stmt->bind_param("ssss", $sucursal, $sucursal, $start_date, $end_date);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $choferes_data[] = $row;
            }
            $stmt->close();
        }
    } else {
        $sql_choferes = "SELECT
            ? AS SUCURSAL,
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
        GROUP BY
            CHOFER_ASIGNADO
        ORDER BY
            TotalFacturas DESC";

        $stmt = $conn->prepare($sql_choferes);
        if ($stmt) {
            $stmt->bind_param("ss", $sucursal, $sucursal);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $choferes_data[] = $row;
            }
            $stmt->close();
        }
    }
}

// --- SECCIÓN 3: DETALLE DE TIEMPOS DE ENTREGA ---
$detalle_entregas = [];
$sql_detalle = "SELECT 
        p.ID AS ID_Pedido,
        p.SUCURSAL,
        p.NOMBRE_CLIENTE,
        p.CHOFER_ASIGNADO,
        p.FECHA_MIN_ENTREGA,
        p.MIN_VENTANA_HORARIA_1,
        p.FECHA_MAX_ENTREGA,
        p.MAX_VENTANA_HORARIA_1,
        COALESCE(ep.Fecha, p.FECHA_ENTREGA_CLIENTE) AS Fecha_Real_Entrega,
        ep.Hora AS Hora_Real_Entrega,
        CASE 
            WHEN ep.Fecha IS NOT NULL THEN
                CASE 
                    WHEN ep.Fecha < p.FECHA_MIN_ENTREGA THEN 'Antes de Tiempo'
                    WHEN ep.Fecha = p.FECHA_MIN_ENTREGA AND ep.Hora < p.MIN_VENTANA_HORARIA_1 THEN 'Antes de Tiempo'
                    WHEN ep.Fecha > p.FECHA_MAX_ENTREGA THEN 'Atrasado'
                    WHEN ep.Fecha = p.FECHA_MAX_ENTREGA AND ep.Hora > p.MAX_VENTANA_HORARIA_1 THEN 'Atrasado'
                    ELSE 'A Tiempo'
                END
            ELSE
                CASE 
                    WHEN p.FECHA_ENTREGA_CLIENTE < p.FECHA_MIN_ENTREGA THEN 'Antes de Tiempo'
                    WHEN p.FECHA_ENTREGA_CLIENTE > p.FECHA_MAX_ENTREGA THEN 'Atrasado'
                    WHEN p.FECHA_ENTREGA_CLIENTE IS NOT NULL THEN 'A Tiempo'
                    ELSE 'Sin Datos'
                END
        END AS Evaluacion_Entrega
    FROM pedidos p
    LEFT JOIN (
        SELECT ID_Pedido, MAX(Fecha) AS Fecha, MAX(Hora) AS Hora 
        FROM EstadoPedido 
        WHERE Estado = 'ENTREGADO' 
        GROUP BY ID_Pedido
    ) ep ON p.ID = ep.ID_Pedido
    WHERE p.ESTADO = 'Entregado'";

if ($usar_filtro_fechas) {
    $sql_detalle .= " AND p.FECHA_RECEPCION_FACTURA BETWEEN ? AND ? ORDER BY p.ID DESC";
    $stmt = $conn->prepare($sql_detalle);
    if ($stmt) {
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $detalle_entregas[] = $row;
        }
        $stmt->close();
    }
} else {
    $sql_detalle .= " ORDER BY p.ID DESC";
    $stmt = $conn->prepare($sql_detalle);
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $detalle_entregas[] = $row;
        }
        $stmt->close();
    }
}

// Configurar descarga del archivo Excel
$filename = "Reporte_Estadisticas_GA_" . date('Y-m-d_His') . ".xls";
header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

echo "\xEF\xBB\xBF"; // UTF-8 BOM
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
            font-family: Calibri, sans-serif;
            font-size: 11pt;
            margin-bottom: 30px;
        }
        th, td {
            border: 1px solid #cbd5e1;
            padding: 8px 12px;
            text-align: left;
        }
        th {
            background-color: #005aa3;
            color: #ffffff;
            font-weight: bold;
        }
        .section-header {
            background-color: #003c6c;
            color: #ffffff;
            font-size: 14pt;
            font-weight: bold;
            padding: 10px;
            margin-top: 25px;
            margin-bottom: 15px;
        }
        .total-row {
            background-color: #e2e8f0;
            font-weight: bold;
        }
        .header-main {
            font-family: 'Segoe UI', Calibri, sans-serif;
            color: #005aa3;
            margin-bottom: 5px;
        }
        .badge-suc {
            background-color: #bae6fd;
            color: #0369a1;
            font-weight: bold;
            text-align: center;
        }
    </style>
</head>
<body>

<h2 class="header-main">Reporte de Estadísticas y Productividad - Pedidos GA</h2>
<p><strong>Rango de Fechas:</strong> <?php echo $usar_filtro_fechas ? ($start_date . " al " . $end_date) : "Todos los registros"; ?></p>
<p><strong>Generado el:</strong> <?php echo date('d/m/Y H:i:s'); ?></p>
<p><strong>Generado por:</strong> <?php echo htmlspecialchars($_SESSION['Nombre'] ?? $_SESSION['username']); ?></p>

<hr>

<!-- SECCIÓN 1: RESUMEN GENERAL POR SUCURSAL -->
<div class="section-header">1. RESUMEN GENERAL POR SUCURSAL</div>
<table>
    <thead>
        <tr>
            <th>Sucursal</th>
            <th style="text-align: right;">Entregadas</th>
            <th style="text-align: right;">Canceladas</th>
            <th style="text-align: right;">En Ruta</th>
            <th style="text-align: right;">Activas</th>
            <th style="text-align: right;">En Tienda</th>
            <th style="text-align: right;">Reprogramadas</th>
            <th style="text-align: right;">Total Facturas</th>
            <th style="text-align: right;">Total Kilómetros</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $sum_entregadas = 0;
        $sum_canceladas = 0;
        $sum_en_ruta = 0;
        $sum_activas = 0;
        $sum_en_tienda = 0;
        $sum_reprogramadas = 0;
        $sum_total = 0;
        $sum_kms = 0;

        foreach ($resumen_general as $row):
            $sum_entregadas += (int)$row['Entregadas'];
            $sum_canceladas += (int)$row['Canceladas'];
            $sum_en_ruta += (int)$row['EnRuta'];
            $sum_activas += (int)$row['Activas'];
            $sum_en_tienda += (int)$row['EnTienda'];
            $sum_reprogramadas += (int)$row['REPROGRAMADO'];
            $sum_total += (int)$row['Total_Facturas'];
            $sum_kms += (float)$row['Total_Kilometros'];
        ?>
        <tr>
            <td class="badge-suc"><?php echo htmlspecialchars($row['SUCURSAL']); ?></td>
            <td style="text-align: right;"><?php echo number_format($row['Entregadas']); ?></td>
            <td style="text-align: right;"><?php echo number_format($row['Canceladas']); ?></td>
            <td style="text-align: right;"><?php echo number_format($row['EnRuta']); ?></td>
            <td style="text-align: right;"><?php echo number_format($row['Activas']); ?></td>
            <td style="text-align: right;"><?php echo number_format($row['EnTienda']); ?></td>
            <td style="text-align: right;"><?php echo number_format($row['REPROGRAMADO']); ?></td>
            <td style="text-align: right; font-weight: bold;"><?php echo number_format($row['Total_Facturas']); ?></td>
            <td style="text-align: right;"><?php echo number_format($row['Total_Kilometros'], 2); ?> km</td>
        </tr>
        <?php endforeach; ?>
        
        <!-- Fila de Totales -->
        <tr class="total-row">
            <td>TOTAL GENERAL</td>
            <td style="text-align: right;"><?php echo number_format($sum_entregadas); ?></td>
            <td style="text-align: right;"><?php echo number_format($sum_canceladas); ?></td>
            <td style="text-align: right;"><?php echo number_format($sum_en_ruta); ?></td>
            <td style="text-align: right;"><?php echo number_format($sum_activas); ?></td>
            <td style="text-align: right;"><?php echo number_format($sum_en_tienda); ?></td>
            <td style="text-align: right;"><?php echo number_format($sum_reprogramadas); ?></td>
            <td style="text-align: right;"><?php echo number_format($sum_total); ?></td>
            <td style="text-align: right;"><?php echo number_format($sum_kms, 2); ?> km</td>
        </tr>
    </tbody>
</table>

<!-- SECCIÓN 2: DESGLOSE DETALLADO POR CHOFER Y SUCURSAL -->
<div class="section-header">2. DESGLOSE DETALLADO POR CHOFER Y SUCURSAL</div>
<table>
    <thead>
        <tr>
            <th>Sucursal</th>
            <th>Chofer Asignado</th>
            <th style="text-align: right;">Total Facturas</th>
            <th style="text-align: right;">Entregadas</th>
            <th style="text-align: right;">Canceladas</th>
            <th style="text-align: right;">En Ruta</th>
            <th style="text-align: right;">En Tienda</th>
            <th style="text-align: right;">Reprogramadas</th>
            <th style="text-align: right;">Activas</th>
            <th style="text-align: right;">Total Kilómetros</th>
        </tr>
    </thead>
    <tbody>
        <?php
        foreach ($choferes_data as $row):
            $chofer_nombre = !empty($row['CHOFER_ASIGNADO']) ? $row['CHOFER_ASIGNADO'] : 'Sin asignar';
        ?>
        <tr>
            <td class="badge-suc"><?php echo htmlspecialchars($row['SUCURSAL']); ?></td>
            <td><strong><?php echo htmlspecialchars($chofer_nombre); ?></strong></td>
            <td style="text-align: right; font-weight: bold;"><?php echo number_format($row['TotalFacturas']); ?></td>
            <td style="text-align: right;"><?php echo number_format($row['Entregadas']); ?></td>
            <td style="text-align: right;"><?php echo number_format($row['Canceladas']); ?></td>
            <td style="text-align: right;"><?php echo number_format($row['EnRuta']); ?></td>
            <td style="text-align: right;"><?php echo number_format($row['EnTienda']); ?></td>
            <td style="text-align: right;"><?php echo number_format($row['REPROGRAMADO']); ?></td>
            <td style="text-align: right;"><?php echo number_format($row['Activas']); ?></td>
            <td style="text-align: right;"><?php echo number_format($row['TotalKilometros'], 2); ?> km</td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<!-- SECCIÓN 3: REPORTE DE TIEMPOS DE ENTREGA E INDICADORES DE USO DE APP -->
<div class="section-header">3. DETALLE DE TIEMPOS DE ENTREGA E INDICADORES</div>
<table>
    <thead>
        <tr>
            <th>ID Pedido</th>
            <th>Sucursal</th>
            <th>Cliente</th>
            <th>Chofer Asignado</th>
            <th>Ventana Prometida</th>
            <th>Fecha/Hora Entrega</th>
            <th>Evaluación de Tiempo</th>
            <th>Uso de App (Hora Registrada)</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($detalle_entregas as $row): 
            $ventana = $row['FECHA_MIN_ENTREGA'] . " al " . $row['FECHA_MAX_ENTREGA'] . " (" . $row['MIN_VENTANA_HORARIA_1'] . " a " . $row['MAX_VENTANA_HORARIA_1'] . ")";
            $horaStr = !empty($row['Hora_Real_Entrega']) ? $row['Hora_Real_Entrega'] : '(Sin Hora)';
            $entrega = $row['Fecha_Real_Entrega'] . " " . $horaStr;
            $usoApp = !empty($row['Hora_Real_Entrega']) ? 'Sí' : 'No (Manual)';
        ?>
        <tr>
            <td style="text-align: right;"><?php echo htmlspecialchars($row['ID_Pedido']); ?></td>
            <td class="badge-suc"><?php echo htmlspecialchars($row['SUCURSAL']); ?></td>
            <td><?php echo htmlspecialchars($row['NOMBRE_CLIENTE']); ?></td>
            <td><?php echo htmlspecialchars($row['CHOFER_ASIGNADO']); ?></td>
            <td><?php echo htmlspecialchars($ventana); ?></td>
            <td><?php echo htmlspecialchars($entrega); ?></td>
            <td style="font-weight: bold; <?php echo ($row['Evaluacion_Entrega'] === 'Atrasado' ? 'color: red;' : 'color: green;'); ?>"><?php echo htmlspecialchars($row['Evaluacion_Entrega']); ?></td>
            <td style="text-align: center; <?php echo ($usoApp === 'Sí' ? 'color: blue;' : 'color: gray;'); ?>"><?php echo htmlspecialchars($usoApp); ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

</body>
</html>
<?php
$conn->close();
?>
