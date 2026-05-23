<?php
// Iniciar la sesión de forma segura
ini_set('session.cookie_httponly', true);
ini_set('session.cookie_secure', true);
session_name("GA");
session_start();

// Verificar si el usuario no está logeado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: /Pedidos_GA/Sesion/login.html");
    exit;
}

// Conexión a la base de datos
require_once __DIR__ . "/Conexiones/Conexion.php";

$sucursalSesion = strtoupper($_SESSION["Sucursal"] ?? "");
$rolSesion = $_SESSION["Rol"] ?? "";

// Determinar sucursales a consultar
$sucursalCondition = "";
if ($sucursalSesion === "TODAS") {
    // Admin puede ver todas
    $sucursalCondition = "";
} elseif (strtoupper($rolSesion) === 'JC' && $sucursalSesion === 'TAPATIA') {
    $sucursalCondition = "AND SUCURSAL IN ('TAPATIA','ILUMINACION')";
} else {
    if ($sucursalSesion != 'TODAS' && $sucursalSesion != '') {
        $sucursalCondition = "AND SUCURSAL='$sucursalSesion'";
    }
}

// Obtener filtros de fecha si existen
$fechaInicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : '';
$fechaFin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : '';

$fechaCondition = "";
if (!empty($fechaInicio) && !empty($fechaFin)) {
    $fechaCondition = " AND DATE(FECHA_RECEPCION_FACTURA) BETWEEN '$fechaInicio' AND '$fechaFin' ";
} elseif (!empty($fechaInicio)) {
    $fechaCondition = " AND DATE(FECHA_RECEPCION_FACTURA) >= '$fechaInicio' ";
} elseif (!empty($fechaFin)) {
    $fechaCondition = " AND DATE(FECHA_RECEPCION_FACTURA) <= '$fechaFin' ";
}

// Consulta para estadísticas generales
$sqlStats = "SELECT 
    COUNT(*) as total_pedidos,
    SUM(CASE WHEN tipo_zona = 'LOCAL' THEN 1 ELSE 0 END) as total_local,
    SUM(CASE WHEN tipo_zona = 'FORANEO' THEN 1 ELSE 0 END) as total_foraneo,
    SUM(CASE WHEN tipo_zona IS NULL THEN 1 ELSE 0 END) as sin_clasificar,
    SUM(CASE WHEN tipo_zona = 'LOCAL' AND precio_factura_real > 0 THEN precio_factura_real ELSE 0 END) as monto_local,
    SUM(CASE WHEN tipo_zona = 'FORANEO' AND precio_factura_real > 0 THEN precio_factura_real ELSE 0 END) as monto_foraneo
FROM pedidos 
WHERE 1=1 $sucursalCondition $fechaCondition";

$resultStats = $conn->query($sqlStats);
$stats = $resultStats->fetch_assoc();

// Consulta para estadísticas por sucursal
$sqlPorSucursal = "SELECT 
    SUCURSAL,
    COUNT(*) as total,
    SUM(CASE WHEN tipo_zona = 'LOCAL' THEN 1 ELSE 0 END) as locales,
    SUM(CASE WHEN tipo_zona = 'FORANEO' THEN 1 ELSE 0 END) as foraneos
FROM pedidos 
WHERE tipo_zona IS NOT NULL $sucursalCondition $fechaCondition
GROUP BY SUCURSAL
ORDER BY total DESC";

$resultPorSucursal = $conn->query($sqlPorSucursal);

// Consulta para estadísticas por estado
$sqlPorEstado = "SELECT 
    ESTADO,
    COUNT(*) as total,
    SUM(CASE WHEN tipo_zona = 'LOCAL' THEN 1 ELSE 0 END) as locales,
    SUM(CASE WHEN tipo_zona = 'FORANEO' THEN 1 ELSE 0 END) as foraneos
FROM pedidos 
WHERE tipo_zona IS NOT NULL $sucursalCondition $fechaCondition
GROUP BY ESTADO
ORDER BY total DESC";

$resultPorEstado = $conn->query($sqlPorEstado);

// Calcular porcentajes
$porcentajeLocal = $stats['total_pedidos'] > 0 ? ($stats['total_local'] / $stats['total_pedidos']) * 100 : 0;
$porcentajeForaneo = $stats['total_pedidos'] > 0 ? ($stats['total_foraneo'] / $stats['total_pedidos']) * 100 : 0;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Zonas - Pedidos GA</title>
    <link rel="stylesheet" type="text/css" href="styles.css">
    <link rel="icon" type="image/png" href="/Pedidos_GA/Img/Botones%20entregas/ICONOSPAG/ICONOPEDIDOS.png">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .reporte-container {
            max-width: 1400px;
            margin: 20px auto;
            padding: 20px;
        }

        .header-reporte {
            background: linear-gradient(135deg, #005aa3 0%, #0077cc 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .header-reporte h1 {
            margin: 0 0 10px 0;
            font-size: 32px;
        }

        .header-reporte p {
            margin: 0;
            opacity: 0.9;
        }

        .filtros-fecha {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .filtros-fecha form {
            display: flex;
            gap: 15px;
            align-items: end;
            flex-wrap: wrap;
        }

        .filtros-fecha label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }

        .filtros-fecha input[type="date"] {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }

        .filtros-fecha button {
            padding: 10px 25px;
            background: #005aa3;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.3s;
        }

        .filtros-fecha button:hover {
            background: #004080;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 5px solid #005aa3;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }

        .stat-card.local {
            border-left-color: #28a745;
        }

        .stat-card.foraneo {
            border-left-color: #ff9800;
        }

        .stat-number {
            font-size: 42px;
            font-weight: bold;
            color: #005aa3;
            margin: 10px 0;
        }

        .stat-card.local .stat-number {
            color: #28a745;
        }

        .stat-card.foraneo .stat-number {
            color: #ff9800;
        }

        .stat-label {
            font-size: 14px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .stat-icon {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .chart-container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .chart-container h2 {
            margin: 0 0 20px 0;
            color: #005aa3;
            font-size: 24px;
        }

        .progress-bar-container {
            margin: 20px 0;
        }

        .progress-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-weight: bold;
        }

        .progress-bar {
            height: 40px;
            background: #e0e0e0;
            border-radius: 20px;
            overflow: hidden;
            position: relative;
        }

        .progress-fill {
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            transition: width 1s ease-in-out;
        }

        .progress-fill.local {
            background: linear-gradient(90deg, #28a745, #20c997);
        }

        .progress-fill.foraneo {
            background: linear-gradient(90deg, #ff9800, #f57c00);
        }

        .table-container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            overflow-x: auto;
        }

        .table-container h2 {
            margin: 0 0 20px 0;
            color: #005aa3;
            font-size: 24px;
        }

        .reporte-table {
            width: 100%;
            border-collapse: collapse;
        }

        .reporte-table th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: bold;
            color: #333;
            border-bottom: 2px solid #dee2e6;
        }

        .reporte-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #dee2e6;
        }

        .reporte-table tr:hover {
            background: #f8f9fa;
        }

        .badge-mini {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            color: white;
            display: inline-block;
            margin: 0 3px;
        }

        .badge-mini.local {
            background: linear-gradient(135deg, #28a745, #20c997);
        }

        .badge-mini.foraneo {
            background: linear-gradient(135deg, #ff9800, #f57c00);
        }

        .btn-volver {
            display: inline-block;
            padding: 12px 30px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            transition: background 0.3s;
        }

        .btn-volver:hover {
            background: #5a6268;
        }

        .btn-excel {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 22px;
            background: linear-gradient(135deg, #1d6f42, #217346);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            font-size: 14px;
            border: none;
            cursor: pointer;
            transition: background 0.3s, transform 0.15s, box-shadow 0.15s;
            box-shadow: 0 2px 6px rgba(33,115,70,0.35);
        }

        .btn-excel:hover {
            background: linear-gradient(135deg, #155232, #1a5c38);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(33,115,70,0.45);
        }

        .btn-excel:active {
            transform: translateY(0);
        }
    </style>
</head>
<body>

<div style="text-align: center; margin-top: 30px;">
        <a href="Pedidos_GA.php" class="btn-volver">← Volver a Pedidos</a>
    </div>
    <div class="reporte-container">
        <div class="header-reporte">
            <h1>📊 Reporte de Zonas Geográficas</h1>
            <p>Análisis de pedidos locales vs foráneos</p>
        </div>

        <!-- Filtros de fecha -->
        <div class="filtros-fecha">
            <form method="GET" action="">
                <div>
                    <label for="fecha_inicio">Fecha Inicio:</label>
                    <input type="date" id="fecha_inicio" name="fecha_inicio" value="<?php echo htmlspecialchars($fechaInicio); ?>">
                </div>
                <div>
                    <label for="fecha_fin">Fecha Fin:</label>
                    <input type="date" id="fecha_fin" name="fecha_fin" value="<?php echo htmlspecialchars($fechaFin); ?>">
                </div>
                <div>
                    <button type="submit">Filtrar</button>
                    <a href="reporte_zonas.php" style="display: inline-block; padding: 10px 25px; background: #6c757d; color: white; text-decoration: none; border-radius: 6px; margin-left: 10px;">Limpiar</a>
                </div>
            </form>
            <!-- Botón exportar Excel (preserva los filtros de fecha activos) -->
            <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #e0e0e0;">
                <a id="btn-exportar-excel"
                   href="reporte_zonas_excel.php<?php
                       $params = [];
                       if (!empty($fechaInicio)) $params[] = 'fecha_inicio=' . urlencode($fechaInicio);
                       if (!empty($fechaFin))    $params[] = 'fecha_fin='    . urlencode($fechaFin);
                       echo count($params) ? '?' . implode('&', $params) : '';
                   ?>"
                   class="btn-excel">
                    📊 Exportar a Excel
                </a>
                <span style="font-size: 12px; color: #888; margin-left: 10px;">⬇️ Descarga el reporte con los filtros aplicados</span>
            </div>
        </div>

        <!-- Estadísticas principales -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📦</div>
                <div class="stat-label">Total de Pedidos</div>
                <div class="stat-number"><?php echo number_format($stats['total_pedidos']); ?></div>
            </div>

            <div class="stat-card local">
                <div class="stat-icon">🏠</div>
                <div class="stat-label">Pedidos Locales</div>
                <div class="stat-number"><?php echo number_format($stats['total_local']); ?></div>
                <div style="font-size: 14px; color: #666; margin-top: 5px;">
                    <?php echo number_format($porcentajeLocal, 1); ?>% del total
                </div>
            </div>

            <div class="stat-card foraneo">
                <div class="stat-icon">🌍</div>
                <div class="stat-label">Pedidos Foráneos</div>
                <div class="stat-number"><?php echo number_format($stats['total_foraneo']); ?></div>
                <div style="font-size: 14px; color: #666; margin-top: 5px;">
                    <?php echo number_format($porcentajeForaneo, 1); ?>% del total
                </div>
            </div>

            <?php if ($stats['sin_clasificar'] > 0): ?>
            <div class="stat-card">
                <div class="stat-icon">⚠️</div>
                <div class="stat-label">Sin Clasificar</div>
                <div class="stat-number"><?php echo number_format($stats['sin_clasificar']); ?></div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Montos -->
        <?php if ($stats['monto_local'] > 0 || $stats['monto_foraneo'] > 0): ?>
        <div class="stats-grid">
            <div class="stat-card local">
                <div class="stat-icon">💰</div>
                <div class="stat-label">Monto Local</div>
                <div class="stat-number" style="font-size: 28px;">$<?php echo number_format($stats['monto_local'], 2); ?></div>
            </div>

            <div class="stat-card foraneo">
                <div class="stat-icon">💵</div>
                <div class="stat-label">Monto Foráneo</div>
                <div class="stat-number" style="font-size: 28px;">$<?php echo number_format($stats['monto_foraneo'], 2); ?></div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Gráfico de barras -->
        <div class="chart-container">
            <h2>Distribución Local vs Foráneo</h2>
            
            <div class="progress-bar-container">
                <div class="progress-label">
                    <span>🏠 Local: <?php echo number_format($stats['total_local']); ?> pedidos</span>
                    <span><?php echo number_format($porcentajeLocal, 1); ?>%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill local" style="width: <?php echo $porcentajeLocal; ?>%">
                        <?php if ($porcentajeLocal > 10): ?>
                            <?php echo number_format($porcentajeLocal, 1); ?>%
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="progress-bar-container">
                <div class="progress-label">
                    <span>🌍 Foráneo: <?php echo number_format($stats['total_foraneo']); ?> pedidos</span>
                    <span><?php echo number_format($porcentajeForaneo, 1); ?>%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill foraneo" style="width: <?php echo $porcentajeForaneo; ?>%">
                        <?php if ($porcentajeForaneo > 10): ?>
                            <?php echo number_format($porcentajeForaneo, 1); ?>%
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla por sucursal -->
        <div class="table-container">
            <h2>Desglose por Sucursal</h2>
            <table class="reporte-table">
                <thead>
                    <tr>
                        <th>Sucursal</th>
                        <th>Total</th>
                        <th>Locales</th>
                        <th>Foráneos</th>
                        <th>% Local</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $resultPorSucursal->fetch_assoc()): ?>
                        <?php $pctLocal = $row['total'] > 0 ? ($row['locales'] / $row['total']) * 100 : 0; ?>
                        <tr>
                            <td><strong><?php echo $row['SUCURSAL']; ?></strong></td>
                            <td><?php echo number_format($row['total']); ?></td>
                            <td>
                                <span class="badge-mini local"><?php echo number_format($row['locales']); ?></span>
                            </td>
                            <td>
                                <span class="badge-mini foraneo"><?php echo number_format($row['foraneos']); ?></span>
                            </td>
                            <td><?php echo number_format($pctLocal, 1); ?>%</td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Tabla por estado -->
        <div class="table-container">
            <h2>Desglose por Estado de Pedido</h2>
            <table class="reporte-table">
                <thead>
                    <tr>
                        <th>Estado</th>
                        <th>Total</th>
                        <th>Locales</th>
                        <th>Foráneos</th>
                        <th>% Local</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $resultPorEstado->fetch_assoc()): ?>
                        <?php $pctLocal = $row['total'] > 0 ? ($row['locales'] / $row['total']) * 100 : 0; ?>
                        <tr>
                            <td><strong><?php echo $row['ESTADO']; ?></strong></td>
                            <td><?php echo number_format($row['total']); ?></td>
                            <td>
                                <span class="badge-mini local"><?php echo number_format($row['locales']); ?></span>
                            </td>
                            <td>
                                <span class="badge-mini foraneo"><?php echo number_format($row['foraneos']); ?></span>
                            </td>
                            <td><?php echo number_format($pctLocal, 1); ?>%</td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

    <!-- Sección del Mapa -->
    <div class="chart-container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2>🗺️ Mapa de Zona Metropolitana</h2>
            <?php if (in_array($rolSesion, ["Admin", "JC"])): ?>
            <div id="controles-edicion">
                <button id="btn-editar-zona" class="btn-volver" style="background: #ffc107; color: #333;">✏️ Editar Zona</button>
                <button id="btn-guardar-zona" class="btn-volver" style="background: #28a745; display: none;">💾 Guardar Cambios</button>
                <button id="btn-cancelar-zona" class="btn-volver" style="background: #dc3545; display: none;">❌ Cancelar</button>
            </div>
            <?php endif; ?>
        </div>
        
        <div id="map-container" style="height: 500px; border-radius: 12px; overflow: hidden; position: relative;">
            <div id="map" style="width: 100%; height: 100%;"></div>
        </div>
        <p style="font-size: 12px; color: #666; margin-top: 10px; text-align: center;">
            El área sombreada representa la Zona LOCAL. Todo lo que esté fuera se considera FORÁNEO.
        </p>
    </div>

    

    <!-- Mapbox GL JS -->
    <link href='https://api.mapbox.com/mapbox-gl-js/v2.6.1/mapbox-gl.css' rel='stylesheet' />
    <script src='https://api.mapbox.com/mapbox-gl-js/v2.6.1/mapbox-gl.js'></script>
    <script src="https://cdn.jsdelivr.net/npm/@turf/turf@6/turf.min.js"></script>

    <script>

        mapboxgl.accessToken = 'pk.eyJ1IjoiYWNhc2lsbGFzNzY2IiwiYSI6ImNsdW12cTZyMjB4NnMya213MDdseXp6ZGgifQ.t7-l1lQfd8mgHILM5YrdNw';


        const map = new mapboxgl.Map({
            container: 'map',
            style: 'mapbox://styles/mapbox/streets-v11',
            center: [-103.3494, 20.6597], // Guadalajara
            zoom: 10
        });

        // Controles de navegación
        map.addControl(new mapboxgl.NavigationControl());

        let zonaId = null;
        let modoEdicion = false;
        let markers = []; // Para los vértices editables
        let poligonoCoords = [];

        map.on('load', function() {
            cargarZona();
        });

        async function cargarZona() {
            try {
                const response = await fetch('api_zonas.php');
                const data = await response.json();

                if (data.success && data.zonas.length > 0) {
                    const zona = data.zonas[0]; // Usamos la primera zona activa
                    zonaId = zona.id;
                    poligonoCoords = zona.coordenadas;
                    dibujarPoligono(poligonoCoords);
                }
            } catch (error) {
                console.error('Error al cargar zona:', error);
            }
        }

        function dibujarPoligono(coords) {
            const geojson = {
                'type': 'Feature',
                'geometry': {
                    'type': 'Polygon',
                    'coordinates': [coords.map(p => [p.lng, p.lat])]
                }
            };
            
            // Cerrar el polígono si no está cerrado (primer y último punto iguales)
            const first = geojson.geometry.coordinates[0][0];
            const last = geojson.geometry.coordinates[0][geojson.geometry.coordinates[0].length - 1];
            if (first[0] !== last[0] || first[1] !== last[1]) {
                geojson.geometry.coordinates[0].push(first);
            }

            if (map.getSource('zona-source')) {
                map.getSource('zona-source').setData(geojson);
            } else {
                map.addSource('zona-source', {
                    'type': 'geojson',
                    'data': geojson
                });

                map.addLayer({
                    'id': 'zona-fill',
                    'type': 'fill',
                    'source': 'zona-source',
                    'layout': {},
                    'paint': {
                        'fill-color': '#ed6b1f',
                        'fill-opacity': 0.3
                    }
                });

                map.addLayer({
                    'id': 'zona-outline',
                    'type': 'line',
                    'source': 'zona-source',
                    'layout': {},
                    'paint': {
                        'line-color': '#ed6b1f',
                        'line-width': 3
                    }
                });
            }
        }

        // --- Lógica de Edición ---
        const btnEditar = document.getElementById('btn-editar-zona');
        const btnGuardar = document.getElementById('btn-guardar-zona');
        const btnCancelar = document.getElementById('btn-cancelar-zona');

        if (btnEditar) {
            btnEditar.addEventListener('click', iniciarEdicion);
            btnGuardar.addEventListener('click', guardarZona);
            btnCancelar.addEventListener('click', cancelarEdicion);
        }

        function iniciarEdicion() {
            modoEdicion = true;
            btnEditar.style.display = 'none';
            btnGuardar.style.display = 'inline-block';
            btnCancelar.style.display = 'inline-block';

            // Crear marcadores arrastrables en cada vértice
            poligonoCoords.forEach((coord, index) => {
                const el = document.createElement('div');
                el.className = 'marker-vertice';
                el.style.backgroundColor = 'white';
                el.style.border = '2px solid #005aa3';
                el.style.width = '12px';
                el.style.height = '12px';
                el.style.borderRadius = '50%';
                el.style.cursor = 'move';

                const marker = new mapboxgl.Marker(el, { draggable: true })
                    .setLngLat([coord.lng, coord.lat])
                    .addTo(map);

                marker.on('drag', () => onDragVertice(index, marker));
                markers.push(marker);
            });
        }

        function onDragVertice(index, marker) {
            const lngLat = marker.getLngLat();
            poligonoCoords[index] = { lat: lngLat.lat, lng: lngLat.lng };
            dibujarPoligono(poligonoCoords);
        }

        function cancelarEdicion() {
            modoEdicion = false;
            btnEditar.style.display = 'inline-block';
            btnGuardar.style.display = 'none';
            btnCancelar.style.display = 'none';
            
            // Eliminar marcadores
            markers.forEach(m => m.remove());
            markers = [];
            
            // Recargar original
            cargarZona();
        }

        async function guardarZona() {
            if (!zonaId) return;

            // Preguntar si se desea recalcular
            const resultConfirm = await Swal.fire({
                title: '¿Guardar cambios?',
                text: "Se actualizará la zona geográfica.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Guardar',
                cancelButtonText: 'Cancelar',
                showDenyButton: true,
                denyButtonText: 'Guardar y Actualizar Pedidos',
                denyButtonColor: '#005aa3'
            });

            if (!resultConfirm.isConfirmed && !resultConfirm.isDenied) return;

            const recalcular = resultConfirm.isDenied; // Si presiona "Guardar y Actualizar Pedidos"

            // Mostrar loading si se va a recalcular (puede tardar un poco)
            if (recalcular) {
                Swal.fire({
                    title: 'Procesando...',
                    text: 'Actualizando clasificación de pedidos históricos...',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });
            }

            try {
                const response = await fetch('api_zonas.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        id: zonaId,
                        coordenadas: poligonoCoords,
                        recalcular: recalcular
                    })
                });

                const result = await response.json();

                if (result.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: result.message
                    }).then(() => {
                        cancelarEdicion();
                        // Recargar la página para ver las nuevas estadísticas
                        if (recalcular) window.location.reload(); 
                    });
                } else {
                    Swal.fire('Error', result.error, 'error');
                }
            } catch (error) {
                console.error('Error al guardar:', error);
                Swal.fire('Error', 'Error de conexión', 'error');
            }
        }

    </script>
    </div>
    
    <?php $conn->close(); ?>
</body>
</html>
