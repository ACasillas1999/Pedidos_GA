<?php
session_name("GA");
session_start();

// Verificar si el usuario está logeado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: /Pedidos_GA/Sesion/login.html");
    exit;
}

// Solo Admin y JC pueden exportar
if (!in_array($_SESSION["Rol"], ["Admin", "JC"])) {
    echo "<script>alert('No tienes permisos para exportar este reporte.'); window.history.back();</script>";
    exit;
}

require_once __DIR__ . "/Conexiones/Conexion.php";

// Obtener parámetros de filtro
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : date('Y-m-01');
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : date('Y-m-d');
$sucursal_filtro = isset($_GET['sucursal']) ? $_GET['sucursal'] : 'TODAS';
$vendedor_filtro = isset($_GET['vendedor']) ? $_GET['vendedor'] : 'TODOS';

// Construir condiciones de filtro
$where_conditions = ["FECHA_RECEPCION_FACTURA BETWEEN '$fecha_inicio' AND '$fecha_fin'"];

if ($sucursal_filtro != 'TODAS') {
    $where_conditions[] = "SUCURSAL = '$sucursal_filtro'";
}

if ($vendedor_filtro != 'TODOS') {
    $where_conditions[] = "VENDEDOR = '$vendedor_filtro'";
}

$where_clause = implode(" AND ", $where_conditions);

// Configurar headers para descarga de Excel
$filename = "Reporte_Precios_Facturas_" . date('Y-m-d_His') . ".xls";
header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// Comenzar el contenido Excel
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
        }
        th, td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #667eea;
            color: white;
            font-weight: bold;
        }
        .header-section {
            background-color: #f0f0f0;
            font-weight: bold;
            font-size: 14pt;
            padding: 10px;
            margin-top: 20px;
        }
        .precio-bajo {
            background-color: #fff3cd;
        }
        .precio-incorrecto {
            background-color: #f8d7da;
        }
        .precio-correcto {
            background-color: #d4edda;
        }
    </style>
</head>
<body>

<h1>Reporte de Precios de Facturas - Pedidos GA</h1>
<p><strong>Período:</strong> <?php echo $fecha_inicio; ?> al <?php echo $fecha_fin; ?></p>
<p><strong>Sucursal:</strong> <?php echo $sucursal_filtro; ?></p>
<p><strong>Vendedor:</strong> <?php echo $vendedor_filtro; ?></p>
<p><strong>Generado por:</strong> <?php echo $_SESSION['Nombre']; ?> (<?php echo $_SESSION['username']; ?>)</p>
<p><strong>Fecha de generación:</strong> <?php echo date('d/m/Y H:i:s'); ?></p>

<hr>

<!-- SECCIÓN 1: RESUMEN GENERAL -->
<div class="header-section">1. RESUMEN GENERAL</div>

<?php
$sql_stats = "SELECT
    COUNT(*) as total_pedidos,
    SUM(CASE WHEN precio_factura_real > 0 THEN 1 ELSE 0 END) as pedidos_con_precio,
    SUM(CASE WHEN precio_factura_real < 1000 AND precio_factura_real > 0 THEN 1 ELSE 0 END) as pedidos_bajo_1000,
    SUM(CASE WHEN precio_validado_jc = 1 THEN 1 ELSE 0 END) as pedidos_validados,
    SUM(CASE WHEN precio_factura_vendedor != precio_factura_real AND precio_factura_real > 0 THEN 1 ELSE 0 END) as pedidos_corregidos,
    SUM(precio_factura_real) as total_facturado,
    AVG(precio_factura_real) as promedio_factura
FROM pedidos
WHERE $where_clause";

$result_stats = $conn->query($sql_stats);
$stats = $result_stats->fetch_assoc();

$porcentaje_bajo_1000 = ($stats['pedidos_con_precio'] > 0) ?
    round(($stats['pedidos_bajo_1000'] / $stats['pedidos_con_precio']) * 100, 2) : 0;

$porcentaje_validados = ($stats['pedidos_con_precio'] > 0) ?
    round(($stats['pedidos_validados'] / $stats['pedidos_con_precio']) * 100, 2) : 0;

$porcentaje_corregidos = ($stats['pedidos_con_precio'] > 0) ?
    round(($stats['pedidos_corregidos'] / $stats['pedidos_con_precio']) * 100, 2) : 0;
?>

<table>
    <tr>
        <th>Métrica</th>
        <th>Valor</th>
        <th>Porcentaje</th>
    </tr>
    <tr>
        <td>Total de Pedidos</td>
        <td><?php echo number_format($stats['total_pedidos']); ?></td>
        <td>-</td>
    </tr>
    <tr>
        <td>Pedidos con Precio</td>
        <td><?php echo number_format($stats['pedidos_con_precio']); ?></td>
        <td>-</td>
    </tr>
    <tr>
        <td>Total Facturado</td>
        <td>$<?php echo number_format($stats['total_facturado'], 2); ?></td>
        <td>-</td>
    </tr>
    <tr>
        <td>Promedio por Factura</td>
        <td>$<?php echo number_format($stats['promedio_factura'], 2); ?></td>
        <td>-</td>
    </tr>
    <tr class="precio-bajo">
        <td>Pedidos Menores a $1000</td>
        <td><?php echo number_format($stats['pedidos_bajo_1000']); ?></td>
        <td><?php echo $porcentaje_bajo_1000; ?>%</td>
    </tr>
    <tr class="precio-correcto">
        <td>Pedidos Validados por JC</td>
        <td><?php echo number_format($stats['pedidos_validados']); ?></td>
        <td><?php echo $porcentaje_validados; ?>%</td>
    </tr>
    <tr class="precio-incorrecto">
        <td>Pedidos Corregidos por JC</td>
        <td><?php echo number_format($stats['pedidos_corregidos']); ?></td>
        <td><?php echo $porcentaje_corregidos; ?>%</td>
    </tr>
</table>

<br><br>

<!-- SECCIÓN 2: PEDIDOS MENORES A $1000 -->
<div class="header-section">2. PEDIDOS CON PRECIO MENOR A $1000 (NO CONVENIENTES)</div>

<?php
$sql_menores = "SELECT ID, SUCURSAL, VENDEDOR, FACTURA, NOMBRE_CLIENTE, DIRECCION,
                       precio_factura_real, precio_validado_jc, FECHA_RECEPCION_FACTURA
FROM pedidos
WHERE $where_clause AND precio_factura_real < 1000 AND precio_factura_real > 0
ORDER BY precio_factura_real ASC";

$result_menores = $conn->query($sql_menores);

if ($result_menores->num_rows > 0) {
    echo "<table>";
    echo "<tr>
            <th>ID</th>
            <th>Fecha</th>
            <th>Sucursal</th>
            <th>Vendedor</th>
            <th>Factura</th>
            <th>Cliente</th>
            <th>Dirección</th>
            <th>Precio</th>
            <th>Validado</th>
          </tr>";

    while ($row = $result_menores->fetch_assoc()) {
        $validado = ($row['precio_validado_jc'] == 1) ? 'Sí' : 'No';

        echo "<tr class='precio-bajo'>";
        echo "<td>{$row['ID']}</td>";
        echo "<td>{$row['FECHA_RECEPCION_FACTURA']}</td>";
        echo "<td>{$row['SUCURSAL']}</td>";
        echo "<td>{$row['VENDEDOR']}</td>";
        echo "<td>{$row['FACTURA']}</td>";
        echo "<td>{$row['NOMBRE_CLIENTE']}</td>";
        echo "<td>{$row['DIRECCION']}</td>";
        echo "<td>$" . number_format($row['precio_factura_real'], 2) . "</td>";
        echo "<td>{$validado}</td>";
        echo "</tr>";
    }

    echo "</table>";
} else {
    echo "<p>No hay pedidos con precio menor a $1000</p>";
}
?>

<br><br>

<!-- SECCIÓN 3: CORRECCIONES POR JC -->
<div class="header-section">3. CORRECCIONES DE PRECIO POR JEFE DE CHOFERES</div>

<?php
$sql_correcciones = "SELECT ID, SUCURSAL, VENDEDOR, FACTURA, NOMBRE_CLIENTE,
                            precio_factura_vendedor, precio_factura_real,
                            (precio_factura_vendedor - precio_factura_real) as diferencia,
                            FECHA_RECEPCION_FACTURA, usuario_validacion_precio, fecha_validacion_precio
FROM pedidos
WHERE $where_clause
AND precio_factura_vendedor != precio_factura_real
AND precio_factura_real > 0
ORDER BY ABS(precio_factura_vendedor - precio_factura_real) DESC";

$result_correcciones = $conn->query($sql_correcciones);

if ($result_correcciones->num_rows > 0) {
    echo "<table>";
    echo "<tr>
            <th>ID</th>
            <th>Fecha</th>
            <th>Sucursal</th>
            <th>Vendedor</th>
            <th>Factura</th>
            <th>Cliente</th>
            <th>Precio Vendedor</th>
            <th>Precio Real (JC)</th>
            <th>Diferencia</th>
            <th>% Error</th>
            <th>Tipo Error</th>
            <th>Validado Por</th>
            <th>Fecha Validación</th>
          </tr>";

    while ($row = $result_correcciones->fetch_assoc()) {
        $diferencia = $row['diferencia'];
        $porcentaje_error = ($row['precio_factura_vendedor'] > 0) ?
            round(($diferencia / $row['precio_factura_vendedor']) * 100, 2) : 0;

        $tipo_error = ($diferencia > 0) ? 'Cobró de más' : 'Cobró de menos';

        echo "<tr class='precio-incorrecto'>";
        echo "<td>{$row['ID']}</td>";
        echo "<td>{$row['FECHA_RECEPCION_FACTURA']}</td>";
        echo "<td>{$row['SUCURSAL']}</td>";
        echo "<td>{$row['VENDEDOR']}</td>";
        echo "<td>{$row['FACTURA']}</td>";
        echo "<td>{$row['NOMBRE_CLIENTE']}</td>";
        echo "<td>$" . number_format($row['precio_factura_vendedor'], 2) . "</td>";
        echo "<td>$" . number_format($row['precio_factura_real'], 2) . "</td>";
        echo "<td>$" . number_format(abs($diferencia), 2) . "</td>";
        echo "<td>" . abs($porcentaje_error) . "%</td>";
        echo "<td>{$tipo_error}</td>";
        echo "<td>{$row['usuario_validacion_precio']}</td>";
        echo "<td>{$row['fecha_validacion_precio']}</td>";
        echo "</tr>";
    }

    echo "</table>";
} else {
    echo "<p>No hay correcciones de precio</p>";
}
?>

<br><br>

<!-- SECCIÓN 4: ESTADÍSTICAS POR VENDEDOR -->
<div class="header-section">4. ESTADÍSTICAS POR VENDEDOR</div>

<?php
$sql_vendedores_stats = "SELECT
    VENDEDOR,
    COUNT(*) as total_pedidos,
    SUM(CASE WHEN precio_factura_vendedor = precio_factura_real THEN 1 ELSE 0 END) as precios_correctos,
    SUM(CASE WHEN precio_factura_vendedor != precio_factura_real AND precio_factura_real > 0 THEN 1 ELSE 0 END) as precios_incorrectos,
    SUM(ABS(precio_factura_vendedor - precio_factura_real)) as total_diferencias,
    AVG(precio_factura_real) as promedio_precio,
    SUM(CASE WHEN precio_factura_real < 1000 AND precio_factura_real > 0 THEN 1 ELSE 0 END) as pedidos_bajo_1000,
    SUM(precio_factura_real) as total_facturado
FROM pedidos
WHERE $where_clause AND precio_factura_real > 0
GROUP BY VENDEDOR
ORDER BY total_pedidos DESC";

$result_vend_stats = $conn->query($sql_vendedores_stats);

if ($result_vend_stats->num_rows > 0) {
    echo "<table>";
    echo "<tr>
            <th>Vendedor</th>
            <th>Total Pedidos</th>
            <th>Precios Correctos</th>
            <th>Precios Incorrectos</th>
            <th>% Precisión</th>
            <th>Total Diferencias</th>
            <th>Promedio Precio</th>
            <th>Total Facturado</th>
            <th>Pedidos < $1000</th>
          </tr>";

    while ($row = $result_vend_stats->fetch_assoc()) {
        $precision = ($row['total_pedidos'] > 0) ?
            round(($row['precios_correctos'] / $row['total_pedidos']) * 100, 2) : 0;

        $clase = ($precision >= 90) ? 'precio-correcto' : (($precision >= 70) ? 'precio-bajo' : 'precio-incorrecto');

        echo "<tr class='$clase'>";
        echo "<td>{$row['VENDEDOR']}</td>";
        echo "<td>{$row['total_pedidos']}</td>";
        echo "<td>{$row['precios_correctos']}</td>";
        echo "<td>{$row['precios_incorrectos']}</td>";
        echo "<td>{$precision}%</td>";
        echo "<td>$" . number_format($row['total_diferencias'], 2) . "</td>";
        echo "<td>$" . number_format($row['promedio_precio'], 2) . "</td>";
        echo "<td>$" . number_format($row['total_facturado'], 2) . "</td>";
        echo "<td>{$row['pedidos_bajo_1000']}</td>";
        echo "</tr>";
    }

    echo "</table>";
} else {
    echo "<p>No hay datos de vendedores</p>";
}
?>

<br><br>

<!-- SECCIÓN 5: PENDIENTES DE VALIDAR -->
<div class="header-section">5. PEDIDOS PENDIENTES DE VALIDACIÓN POR JC</div>

<?php
$sql_pendientes = "SELECT ID, SUCURSAL, VENDEDOR, FACTURA, NOMBRE_CLIENTE, DIRECCION,
                          precio_factura_real, FECHA_RECEPCION_FACTURA,
                          DATEDIFF(NOW(), FECHA_RECEPCION_FACTURA) as dias_antiguedad
FROM pedidos
WHERE $where_clause
AND precio_validado_jc = 0
AND precio_factura_real > 0
ORDER BY dias_antiguedad DESC";

$result_pendientes = $conn->query($sql_pendientes);

if ($result_pendientes->num_rows > 0) {
    echo "<table>";
    echo "<tr>
            <th>ID</th>
            <th>Días Antigüedad</th>
            <th>Fecha</th>
            <th>Sucursal</th>
            <th>Vendedor</th>
            <th>Factura</th>
            <th>Cliente</th>
            <th>Dirección</th>
            <th>Precio</th>
          </tr>";

    while ($row = $result_pendientes->fetch_assoc()) {
        $clase_precio = ($row['precio_factura_real'] < 1000) ? 'precio-bajo' : '';

        echo "<tr class='$clase_precio'>";
        echo "<td>{$row['ID']}</td>";
        echo "<td>{$row['dias_antiguedad']} días</td>";
        echo "<td>{$row['FECHA_RECEPCION_FACTURA']}</td>";
        echo "<td>{$row['SUCURSAL']}</td>";
        echo "<td>{$row['VENDEDOR']}</td>";
        echo "<td>{$row['FACTURA']}</td>";
        echo "<td>{$row['NOMBRE_CLIENTE']}</td>";
        echo "<td>{$row['DIRECCION']}</td>";
        echo "<td>$" . number_format($row['precio_factura_real'], 2) . "</td>";
        echo "</tr>";
    }

    echo "</table>";
} else {
    echo "<p>No hay pedidos pendientes de validación</p>";
}
?>

<br><br>

<p><em>Fin del reporte - Generado por Sistema de Pedidos GA</em></p>

</body>
</html>

<?php
$conn->close();
?>
