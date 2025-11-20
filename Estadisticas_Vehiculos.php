<?php
require_once __DIR__ . "/Conexiones/Conexion.php";

// Obtener fechas desde el formulario
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : date('Y-m-01');
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : date('Y-m-d');

$sql = "
SELECT 
    v.Sucursal,
    v.placa,
    COALESCE(c.username, 'Sin Asignar') AS chofer,
    COALESCE(km.km_recorridos, 0) AS km_recorridos,
    COALESCE(gas.total_litros, 0) AS total_litros,
    COALESCE(gas.total_costo, 0) AS total_costo
FROM vehiculos v
LEFT JOIN (
    SELECT id_vehiculo, id_chofer, SUM(kilometraje_final - kilometraje_inicial) AS km_recorridos
    FROM registro_kilometraje
    WHERE fecha_registro BETWEEN '$fecha_inicio' AND '$fecha_fin'
    GROUP BY id_vehiculo, id_chofer
) km ON v.id_vehiculo = km.id_vehiculo
LEFT JOIN (
    SELECT id_vehiculo, id_chofer, SUM(litros) AS total_litros, SUM(costo) AS total_costo
    FROM registro_gasolina
    WHERE fecha_registro BETWEEN '$fecha_inicio' AND '$fecha_fin'
    GROUP BY id_vehiculo, id_chofer
) gas ON v.id_vehiculo = gas.id_vehiculo AND km.id_chofer = gas.id_chofer
LEFT JOIN choferes c ON km.id_chofer = c.ID OR gas.id_chofer = c.ID
ORDER BY v.Sucursal, v.placa;
";

$result = $conn->query($sql);

// Variables para gráficas y tablas
$sucursales = $km_por_sucursal = $litros_por_sucursal = $costo_por_sucursal = [];
$choferes = $km_por_chofer = $litros_por_chofer = $costo_por_chofer = [];
$vehiculos = $km_por_vehiculo = $litros_por_vehiculo = $costo_por_vehiculo = [];
// Nuevos acumuladores: costo de servicios (materiales + mano de obra)
$costo_srv_por_sucursal = $costo_srv_por_chofer = $costo_srv_por_vehiculo = [];

while ($row = $result->fetch_assoc()) {
    $chofer = $row['chofer'] ? $row['chofer'] : "Desconocido";
    $vehiculo = $row['placa'];
    $sucursal = $row['Sucursal'];

    // Datos por Sucursal
    $km_por_sucursal[$sucursal] = ($km_por_sucursal[$sucursal] ?? 0) + $row['km_recorridos'];
    $litros_por_sucursal[$sucursal] = ($litros_por_sucursal[$sucursal] ?? 0) + $row['total_litros'];
    $costo_por_sucursal[$sucursal] = ($costo_por_sucursal[$sucursal] ?? 0) + $row['total_costo'];

    // Datos por Chofer
    $km_por_chofer[$chofer] = ($km_por_chofer[$chofer] ?? 0) + $row['km_recorridos'];
    $litros_por_chofer[$chofer] = ($litros_por_chofer[$chofer] ?? 0) + $row['total_litros'];
    $costo_por_chofer[$chofer] = ($costo_por_chofer[$chofer] ?? 0) + $row['total_costo'];

    // Datos por Vehículo
    $km_por_vehiculo[$vehiculo] = ($km_por_vehiculo[$vehiculo] ?? 0) + $row['km_recorridos'];
    $litros_por_vehiculo[$vehiculo] = ($litros_por_vehiculo[$vehiculo] ?? 0) + $row['total_litros'];
    $costo_por_vehiculo[$vehiculo] = ($costo_por_vehiculo[$vehiculo] ?? 0) + $row['total_costo'];
}

// Calcular costo de servicios en el rango de fechas
$costoMinuto = 1.145833; // fallback por si no existe config
try {
    $resCfg = $conn->query("SELECT valor FROM servicios_config WHERE clave='costo_minuto_mo' LIMIT 1");
    if ($resCfg && $resCfg->num_rows) { $costoMinuto = (float)$resCfg->fetch_assoc()['valor']; }
} catch (Throwable $e) { /* continuar */ }

try {
    $fi = $conn->real_escape_string($fecha_inicio . ' 00:00:00');
    $ff = $conn->real_escape_string($fecha_fin   . ' 23:59:59');
    $q = "
      SELECT v.Sucursal, v.placa,
             COALESCE(ch.username,'Sin Asignar') AS chofer,
             SUM(COALESCE(sub.costo_mat,0) + (COALESCE(os.duracion_minutos,0) * {$costoMinuto})) AS costo_servicio
      FROM orden_servicio os
      JOIN vehiculos v ON v.id_vehiculo = os.id_vehiculo
      LEFT JOIN (
        SELECT m.id_orden, SUM(m.cantidad * COALESCE(i.costo,0)) AS costo_mat
        FROM orden_servicio_material m
        JOIN inventario i ON i.id = m.id_inventario
        GROUP BY m.id_orden
      ) sub ON sub.id_orden = os.id
      LEFT JOIN choferes ch ON ch.ID = v.id_chofer_asignado
      WHERE os.creado_en BETWEEN '{$fi}' AND '{$ff}'
      GROUP BY v.Sucursal, v.placa, chofer
    ";
    if ($rs = $conn->query($q)) {
        while($r = $rs->fetch_assoc()){
            $s = (string)($r['Sucursal'] ?? '');
            $v = (string)($r['placa'] ?? '');
            $c = (string)($r['chofer'] ?? 'Sin Asignar');
            $cost = (float)($r['costo_servicio'] ?? 0);
            $costo_srv_por_sucursal[$s] = ($costo_srv_por_sucursal[$s] ?? 0) + $cost;
            $costo_srv_por_chofer[$c]   = ($costo_srv_por_chofer[$c]   ?? 0) + $cost;
            $costo_srv_por_vehiculo[$v] = ($costo_srv_por_vehiculo[$v] ?? 0) + $cost;
        }
    }
} catch (Throwable $e) { /* continuar */ }

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estadísticas de Vehículos</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <link rel="icon" type="image/png" href="/Img/Botones%20entregas/ICONOSPAG/ICONOPEDIDOS.png">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { width: 80%; margin: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #005996; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        .chart-container { width: 100%; margin: auto; }
        .form-container { margin-bottom: 20px; }


        <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 0;
        }

                .header {
        background-color: #005aa3;
        color: #fff;
        padding: 20px 0;
        text-align: center;
            width: 100%;
        }


        .logo {
        font-size: 28px;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 2px;
        }

        .navbar ul {
        list-style: none;
        padding: 0;
        margin: 0;
        }

        .nav-item {
        display: inline-block;
        margin: 0 15px;
        }

        .nav-link {
        color: #fff;
        text-decoration: none;
        font-size: 18px;
        }

        .nav-link:hover {
        color: #ffcc00;
        }
        
        h1 {
            text-align: center;
            color: #005996;
        }
        h2 {
            color: #333;
            border-bottom: 2px solid #005996;
            padding-bottom: 5px;
            margin-bottom: 20px;
        }
        .form-container {
            text-align: center;
            margin-bottom: 20px;
            padding: 10px;
            background: #005996;
            color: white;
            border-radius: 5px;
        }
        .form-container form {
            display: flex;
            justify-content: center;
            gap: 10px;
        }
        input[type="date"], button {
            padding: 8px 12px;
            font-size: 16px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        button {
            background: #ff6600;
            color: white;
            border: none;
            cursor: pointer;
            transition: 0.3s;
        }
        button:hover {
            background: #cc5200;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0px 2px 8px rgba(0, 0, 0, 0.1);
        }
        th, td {
            padding: 12px;
            text-align: center;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #005996;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .chart-container {
            width: 100%;
            height: 350px;
            margin-top: 20px;
        }
        .table-container {
            margin-top: 20px;
        }
        a {
            color: #005996;
            font-weight: bold;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
    
</head>
<body>


<header class="header">
        <div class="logo">
    
<h3>Estadísticas de Vehículos</h3>
      <!--  <img src="/Img/Botones entregas\RegistrarChofer\REGCHOFTIT.png" alt="Estadísticas " style="max-width: 15%; height: auto;">
    -->
    </div>
        <nav class="navbar">
            <ul>
                <li class="nav-item"><a href='vehiculos.php' class="nav-link">
                    
                <img src="/Img/Botones entregas\RegistrarChofer\VOLVAZ.png" alt="Choferes"class = "icono-Volver"style="max-width: 5%; height: auto; position:absolute; top: 70px; left: 25px;">
                

                </a></li>
            </ul>
        </nav>
    </header>

<div class="container">
    <!-- Título de la gráfica -->
<p></p>
    <!-- Formulario de rango de fechas -->
    <div class="form-container">
        <form method="GET">
            <label for="fecha_inicio">Fecha Inicio:</label>
            <input type="date" id="fecha_inicio" name="fecha_inicio" value="<?= $fecha_inicio ?>" required>
            <label for="fecha_fin">Fecha Fin:</label>
            <input type="date" id="fecha_fin" name="fecha_fin" value="<?= $fecha_fin ?>" required>
            <button type="submit">Filtrar</button>
        </form>
    </div>

    <?php
    function renderChartAndTable($title, $canvasID, $labels, $kmData, $litrosData, $costoData, $fecha_inicio, $fecha_fin) {
        echo "<h2>$title</h2>";
        echo "<div class='chart-container'><canvas id='$canvasID'></canvas></div>";
        echo "<table><tr><th>Nombre</th><th>Kilómetros Recorridos</th><th>Litros de Gasolina</th><th>Costo de Gasolina</th></tr>";
        foreach ($labels as $index => $name) {
            echo "<tr>
                    <td><a href='Estadisticas_Vehiculos_Detalles.php?tipo=" . ($title === 'Comparación por Chofer' ? 'chofer' : 'vehiculo') . "&nombre=" . urlencode($name) . "&fecha_inicio=$fecha_inicio&fecha_fin=$fecha_fin'>" . htmlspecialchars($name) . "</a></td>
                    <td>" . number_format($kmData[$index], 2) . " km</td>
                    <td>" . number_format($litrosData[$index], 2) . " L</td>
                    <td>$" . number_format($costoData[$index], 2) . "</td>
                  </tr>";
        }
        echo "</table>";

        echo "<script>
            new Chart(document.getElementById('$canvasID').getContext('2d'), {
                type: 'bar',
                data: {
                    labels: " . json_encode($labels) . ",
                    datasets: [
                        { label: 'Kilómetros Recorridos', data: " . json_encode($kmData) . ", backgroundColor: 'rgba(255, 99, 132, 0.7)' },
                        { label: 'Litros de Gasolina', data: " . json_encode($litrosData) . ", backgroundColor: 'rgba(54, 162, 235, 0.7)' },
                        { label: 'Costo de Gasolina', data: " . json_encode($costoData) . ", backgroundColor: 'rgba(75, 192, 192, 0.7)' }
                    ]
                }
            });
        </script>";
    }

    renderChartAndTable("Comparación por Sucursal", "sucursalChart", array_keys($km_por_sucursal), array_values($km_por_sucursal), array_values($litros_por_sucursal), array_values($costo_por_sucursal), $fecha_inicio, $fecha_fin);
    renderChartAndTable("Comparación por Chofer", "choferChart", array_keys($km_por_chofer), array_values($km_por_chofer), array_values($litros_por_chofer), array_values($costo_por_chofer), $fecha_inicio, $fecha_fin);
    renderChartAndTable("Comparación por Vehículo", "vehiculoChart", array_keys($km_por_vehiculo), array_values($km_por_vehiculo), array_values($litros_por_vehiculo), array_values($costo_por_vehiculo), $fecha_inicio, $fecha_fin);
    // ====== Reporte adicional: Costo de Servicios ======
    function renderServicios($title,$canvasID,$labels,$costos){
        echo "<h2>$title (Costo de Servicios)</h2>";
        echo "<div class='chart-container'><canvas id='$canvasID'></canvas></div>";
        echo "<table><tr><th>Nombre</th><th>Costo de Servicios</th></tr>";
        foreach($labels as $i=>$n){ echo "<tr><td>".htmlspecialchars($n)."</td><td>$".number_format((float)($costos[$i]??0),2)."</td></tr>"; }
        echo "</table>";
        echo "<script>new Chart(document.getElementById('$canvasID').getContext('2d'),{type:'bar',data:{labels:".json_encode($labels).",datasets:[{label:'Costo de Servicios',data:".json_encode(array_map('floatval',$costos)).",backgroundColor:'rgba(237,108,36,0.7)'}]}});</script>";
    }

    // Sucursales
    $labSrvS = array_keys($costo_srv_por_sucursal); $valSrvS = array_values($costo_srv_por_sucursal);
    if (!empty($labSrvS)) renderServicios('Por Sucursal','srvSucursalChart',$labSrvS,$valSrvS);
    // Choferes
    $labSrvC = array_keys($costo_srv_por_chofer); $valSrvC = array_values($costo_srv_por_chofer);
    if (!empty($labSrvC)) renderServicios('Por Chofer','srvChoferChart',$labSrvC,$valSrvC);
    // Vehículos
    $labSrvV = array_keys($costo_srv_por_vehiculo); $valSrvV = array_values($costo_srv_por_vehiculo);
    if (!empty($labSrvV)) renderServicios('Por Vehículo','srvVehiculoChart',$labSrvV,$valSrvV);

    ?>
</div>

</body>
</html>
