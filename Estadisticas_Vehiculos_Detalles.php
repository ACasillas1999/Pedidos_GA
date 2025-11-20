<?php
require_once __DIR__ . "/Conexiones/Conexion.php";

// Obtener parámetros de la URL
$tipo = $_GET['tipo'] ?? '';
$nombre = $_GET['nombre'] ?? '';
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');

// Validar que se pasó un nombre válido
if (empty($nombre) || ($tipo !== 'chofer' && $tipo !== 'vehiculo')) {
    die("Error: Parámetros inválidos.");
}

// Construcción de la consulta SQL
if ($tipo === 'chofer') {
    $sql = "
    SELECT 
        v.placa, 
        v.Sucursal,
        COALESCE(km.km_recorridos, 0) AS km_recorridos,
        COALESCE(gas.total_litros, 0) AS total_litros,
        COALESCE(gas.total_costo, 0) AS total_costo
    FROM vehiculos v
    LEFT JOIN (
        -- Obtener la suma correcta de kilometraje por vehículo y chofer
        SELECT id_vehiculo, id_chofer, 
               SUM(kilometraje_final - kilometraje_inicial) AS km_recorridos
        FROM registro_kilometraje
        WHERE fecha_registro BETWEEN '$fecha_inicio' AND '$fecha_fin'
        GROUP BY id_vehiculo, id_chofer
    ) km ON v.id_vehiculo = km.id_vehiculo
    LEFT JOIN (
        -- Obtener la suma correcta de litros y costo de gasolina por vehículo y chofer
        SELECT id_vehiculo, id_chofer, 
               SUM(litros) AS total_litros, 
               SUM(costo) AS total_costo
        FROM registro_gasolina
        WHERE fecha_registro BETWEEN '$fecha_inicio' AND '$fecha_fin'
        GROUP BY id_vehiculo, id_chofer
    ) gas ON v.id_vehiculo = gas.id_vehiculo AND km.id_chofer = gas.id_chofer
    LEFT JOIN choferes c ON km.id_chofer = c.ID OR gas.id_chofer = c.ID
    WHERE c.username = '$nombre'
    GROUP BY v.placa, v.Sucursal, km.km_recorridos, gas.total_litros, gas.total_costo
    ORDER BY v.Sucursal;
    ";
} else if ($tipo === 'vehiculo') {
    $sql = "
    SELECT 
        v.placa,
        v.tipo,
        v.Sucursal,
        v.Km_Actual,
        v.Km_Total,
        v.Fecha_Ultimo_Servicio,
        c.username AS chofer, 
        COALESCE(km.km_recorridos, 0) AS km_recorridos,
        COALESCE(gas.total_litros, 0) AS total_litros,
        COALESCE(gas.total_costo, 0) AS total_costo
    FROM vehiculos v
    -- Subconsulta para kilometraje, asegurando que no haya duplicaciones
    LEFT JOIN (
        SELECT id_vehiculo, id_chofer, 
               SUM(kilometraje_final - kilometraje_inicial) AS km_recorridos
        FROM registro_kilometraje
        WHERE fecha_registro BETWEEN '$fecha_inicio' AND '$fecha_fin'
        GROUP BY id_vehiculo, id_chofer
    ) km ON v.id_vehiculo = km.id_vehiculo
    -- Subconsulta para gasolina, asegurando que no se multiplique por chofer
    LEFT JOIN (
        SELECT id_vehiculo, id_chofer, 
               SUM(litros) AS total_litros, 
               SUM(costo) AS total_costo
        FROM registro_gasolina
        WHERE fecha_registro BETWEEN '$fecha_inicio' AND '$fecha_fin'
        GROUP BY id_vehiculo, id_chofer
    ) gas ON v.id_vehiculo = gas.id_vehiculo AND km.id_chofer = gas.id_chofer
    -- Unir con choferes asegurando que solo se muestren los datos correctos
    LEFT JOIN choferes c ON km.id_chofer = c.ID OR gas.id_chofer = c.ID
    WHERE v.placa = '$nombre'
    GROUP BY v.placa, v.tipo, v.Sucursal, v.Km_Actual, v.Km_Total, v.Fecha_Ultimo_Servicio, c.username, km.km_recorridos, gas.total_litros, gas.total_costo
    ORDER BY v.placa;
    ";
}





$result = $conn->query($sql);

$labels = [];
$km_data = [];
$litros_data = [];
$costo_data = [];

while ($row = $result->fetch_assoc()) {
    $labels[] = $row[$tipo === 'chofer' ? 'placa' : 'chofer'];
    $km_data[] = $row['km_recorridos'];
    $litros_data[] = $row['total_litros'];
    $costo_data[] = $row['total_costo'];
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles de <?= htmlspecialchars($nombre) ?></title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="styles7.css" type="text/css">
<<<<<<< HEAD
    <link rel="icon" type="image/png" href="/Img/Botones%20entregas/ICONOSPAG/ICONOPEDIDOS.png">
=======
    <link rel="icon" type="image/png" href="/Pedidos_GA/Img/Botones%20entregas/ICONOSPAG/ICONOPEDIDOS.png">
>>>>>>> parent of 5e8b02c (parra amazon Update image paths and SQL table names)
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { width: 80%; margin: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #005996; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        .chart-container { width: 75%; margin: auto; }
        canvas { width: 75%; margin: 20px auto; }
        
        
    </style>
</head>
<body>


<header class="header">
        <div class="logo">
    
<h3>Estadística</h3>
      <!--  <img src="\Pedidos_GA\Img\Botones entregas\RegistrarChofer\REGCHOFTIT.png" alt="Estadísticas " style="max-width: 15%; height: auto;">
    -->
    </div>
        <nav class="navbar">
            <ul>
                <li class="nav-item"><a href='Estadisticas_Vehiculos.php' class="nav-link">
                    
                <img src="\Pedidos_GA\Img\Botones entregas\RegistrarChofer\VOLVAZ.png" alt="Choferes"class = "icono-Volver"style="max-width: 5%; height: auto; position:absolute; top: 70px; left: 25px;">
                

                </a></li>
            </ul>
        </nav>
    </header>

<div class="container">
    <h2>Vehiculo con placa <?= htmlspecialchars($nombre) ?></h2>
    <hr></hr>

    <h2 >Fecha Inicio: <?=  htmlspecialchars($fecha_inicio)?> | Fecha Fin: <?= htmlspecialchars($fecha_fin)?></h2>

    <div class="chart-container">
        <canvas id="detalleChart"></canvas>
    </div>

    <table>
        <tr>
            <th><?= $tipo === 'chofer' ? 'Vehículo' : 'Chofer' ?></th>
            <th>Kilómetros Recorridos</th>
            <th>Litros de Gasolina</th>
            <th>Costo de Gasolina</th>
        </tr>
        <?php foreach ($labels as $index => $label): ?>
        <tr>
            <td><?= htmlspecialchars($label) ?></td>
            <td><?= number_format($km_data[$index], 2) ?> km</td>
            <td><?= number_format($litros_data[$index], 2) ?> L</td>
            <td>$<?= number_format($costo_data[$index], 2) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<script>
    new Chart(document.getElementById('detalleChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($labels) ?>,
            datasets: [
                { label: 'Kilómetros Recorridos', data: <?= json_encode($km_data) ?>, backgroundColor: 'rgba(255, 99, 132, 0.7)' },
                { label: 'Litros de Gasolina', data: <?= json_encode($litros_data) ?>, backgroundColor: 'rgba(54, 162, 235, 0.7)' },
                { label: 'Costo de Gasolina', data: <?= json_encode($costo_data) ?>, backgroundColor: 'rgba(75, 192, 192, 0.7)' }
            ]
        }
    });
</script>

</body>
</html>

