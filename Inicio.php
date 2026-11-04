<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_name("GA");
session_start();

// Verificar si el usuario no está logeado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: /Pedidos_GA/Sesion/login.html");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Detalle de Pedido | Pedidos GA</title>
    <link rel="icon" type="image/png" href="/Pedidos_GA/Img/Botones%20entregas/ICONOSPAG/ICONOPEDIDOS.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="styles1.css">
    <link href='https://api.mapbox.com/mapbox-gl-js/v2.6.1/mapbox-gl.css' rel='stylesheet' />
    <script src='https://api.mapbox.com/mapbox-gl-js/v2.6.1/mapbox-gl.js'></script>
    <style>
        body {
            margin: 0 !important;
            padding: 0 0 24px 0 !important;
        }
        .ga-topbar {
            background: linear-gradient(135deg, #0f4c81 0%, #005aa3 100%);
            padding: 16px 0;
            border-radius: 0;
            box-shadow: 0 6px 20px rgba(15, 76, 129, 0.18);
            width: 100%;
            margin: 0 0 22px 0;
            box-sizing: border-box;
        }
        .ga-topbar-inner {
            width: 95%;
            max-width: 1520px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            box-sizing: border-box;
        }
        .ga-topbar-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #ffffff;
        }
        .ga-topbar-brand-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.16);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        .ga-topbar-brand-text h2 {
            margin: 0;
            font-size: 18px;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: -0.01em;
            text-align: left;
        }
        .ga-topbar-brand-text span {
            display: block;
            font-size: 12px;
            color: rgba(255, 255, 255, 0.78);
            text-align: left;
        }
        .ga-topbar-nav {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .ga-nav-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 9px 16px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none !important;
            transition: all 0.18s ease;
            cursor: pointer;
            border: 1px solid rgba(255, 255, 255, 0.22);
            background: rgba(255, 255, 255, 0.12);
            color: #ffffff !important;
        }
        .ga-nav-btn:hover {
            background: rgba(255, 255, 255, 0.24);
            transform: translateY(-1px);
            color: #ffffff !important;
        }

        /* Sección Mapa Moderna */
        .ga-map-section {
            width: 95%;
            max-width: 1520px;
            margin: 20px auto;
            background: #ffffff;
            border-radius: 18px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.06);
            overflow: hidden;
            text-align: left;
            box-sizing: border-box;
        }
        .ga-map-header {
            padding: 16px 26px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 14px;
        }
        .ga-map-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 16px;
            font-weight: 800;
            color: #0f4c81;
            margin: 0;
        }
        .ga-map-kpis {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 14px;
            padding: 18px 26px 10px 26px;
        }
        .ga-map-kpi-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px 16px;
        }
        .ga-map-kpi-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #64748b;
            margin-bottom: 4px;
        }
        .ga-map-kpi-val {
            font-size: 14.5px;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
        }
        .ga-map-kpi-input {
            width: 100% !important;
            margin: 0 !important;
            padding: 6px 10px !important;
            font-size: 13px !important;
            font-family: monospace;
            background: #ffffff !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: 8px !important;
            color: #334155 !important;
            text-align: left !important;
        }
        .ga-map-canvas-wrap {
            padding: 12px 26px 22px 26px;
        }
        .ga-map-canvas {
            width: 100%;
            height: 460px;
            border-radius: 14px;
            overflow: hidden;
            border: 1px solid #cbd5e1;
            box-shadow: 0 4px 14px rgba(15, 23, 42, 0.08);
        }
        .btn-abrir-gmaps {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important;
            color: #ffffff !important;
            border: none !important;
            border-radius: 10px !important;
            padding: 9px 18px !important;
            margin: 0 !important;
            font-size: 13px !important;
            font-weight: 700 !important;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(234, 88, 12, 0.3);
            transition: all 0.18s ease;
        }
        .btn-abrir-gmaps:hover {
            background: linear-gradient(135deg, #c2410c 0%, #b45309 100%) !important;
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(234, 88, 12, 0.4);
        }
        .ga-footer {
            width: 95%;
            max-width: 1520px;
            margin: 24px auto 10px auto;
            background: linear-gradient(135deg, #0f4c81 0%, #005aa3 100%);
            color: rgba(255, 255, 255, 0.85);
            border-radius: 14px;
            padding: 14px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
            font-size: 13px;
            box-sizing: border-box;
        }
        .ga-footer a {
            color: #ffffff;
            text-decoration: none;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <header class="ga-topbar">
        <div class="ga-topbar-inner">
            <div class="ga-topbar-brand">
                <div class="ga-topbar-brand-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                </div>
                <div class="ga-topbar-brand-text">
                    <h2>Pedidos GA · Expediente de Pedido</h2>
                    <span>Consulta operativa, documentación, incidencias CRM y rastreo GPS</span>
                </div>
            </div>
            <nav class="ga-topbar-nav">
                <a href="Pedidos_GA.php" class="ga-nav-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                    <span>Tablero de Pedidos</span>
                </a>
                <a href="Choferes.php" class="ga-nav-btn back-button">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
                    <span>Vista Choferes</span>
                </a>
            </nav>
        </div>
    </header>

    <script>
        const backButton = document.querySelector('.back-button');
        if (backButton) {
            backButton.addEventListener('click', (e) => {
                if (window.history.length > 1) {
                    e.preventDefault();
                    window.history.back();
                }
            });
        }
    </script>

    <?php include('detalle_pedido.php'); ?>

    <?php if (!empty($row) && isset($row['ID'])): ?>
    <input type="hidden" id="pedido-id" value="<?php echo htmlspecialchars($row['ID']); ?>">

    <section class="ga-map-section">
        <div class="ga-map-header">
            <h3 class="ga-map-title">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"></polygon><line x1="8" y1="2" x2="8" y2="18"></line><line x1="16" y1="6" x2="16" y2="22"></line></svg>
                <span>Mapa de Ruta y Telemetría de Traslado</span>
            </h3>
            <button type="button" class="btn-abrir-gmaps" onclick="openGoogleMaps()">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                <span>Abrir Ruta en Google Maps</span>
            </button>
        </div>

        <div class="ga-map-kpis">
            <div class="ga-map-kpi-card">
                <div class="ga-map-kpi-label" style="display:flex;align-items:center;gap:5px;">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12h20"></path><path d="M6 8v8"></path><path d="M18 8v8"></path><path d="M10 10v4"></path><path d="M14 10v4"></path></svg>
                    <span>Distancia Estimada</span>
                </div>
                <p class="ga-map-kpi-val" id="distance-label">Calculando distancia...</p>
            </div>
            <div class="ga-map-kpi-card">
                <div class="ga-map-kpi-label" style="display:flex;align-items:center;gap:5px;">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                    <span>Tiempo de Traslado</span>
                </div>
                <p class="ga-map-kpi-val" id="duration-label">Calculando tiempo...</p>
            </div>
            <div class="ga-map-kpi-card">
                <div class="ga-map-kpi-label" style="display:flex;align-items:center;gap:5px;">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="2" width="16" height="20" rx="2" ry="2"></rect><line x1="9" y1="22" x2="9" y2="12"></line><line x1="15" y1="22" x2="15" y2="12"></line><line x1="9" y1="6" x2="9.01" y2="6"></line><line x1="15" y1="6" x2="15.01" y2="6"></line></svg>
                    <span>Coordenada Origen</span>
                </div>
                <input type="text" id="start-coordinates" class="ga-map-kpi-input" placeholder="Coordenadas inicio" value="<?php echo htmlspecialchars($row["Coord_Origen"] ?? ''); ?>" disabled>
            </div>
            <div class="ga-map-kpi-card">
                <div class="ga-map-kpi-label" style="display:flex;align-items:center;gap:5px;">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                    <span>Coordenada Destino</span>
                </div>
                <input type="text" id="end-coordinates" class="ga-map-kpi-input" placeholder="Coordenadas fin" value="<?php echo htmlspecialchars($row["Coord_Destino"] ?? ''); ?>" disabled>
            </div>
        </div>

        <div class="ga-map-canvas-wrap">
            <div class="ga-map-canvas">
                <div id="map" style="width: 100%; height: 100%;"></div>
            </div>
        </div>
    </section>

    <footer class="ga-footer">
        <div>&copy; <?= date('Y') ?> Grupo Ascencio · Pedidos GA</div>
        <div><a href="#">↑ Ir arriba</a></div>
    </footer>

    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>

    <script>
        mapboxgl.accessToken = 'pk.eyJ1IjoiYWNhc2lsbGFzNzY2IiwiYSI6ImNsdW12cTZyMjB4NnMya213MDdseXp6ZGgifQ.t7-l1lQfd8mgHILM5YrdNw';

        var map = new mapboxgl.Map({
            container: 'map',
            style: 'mapbox://styles/mapbox/streets-v11',
            zoom: 9
        });

        var startMarker = new mapboxgl.Marker({ color: "#FF5733" });
        var endMarker = new mapboxgl.Marker({ color: "#337DFF" });

        function calcularDistancia(start, end) {
            var R = 6371;
            var dLat = (end[1] - start[1]) * Math.PI / 180;
            var dLon = (end[0] - start[0]) * Math.PI / 180;
            var a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                    Math.cos(start[1] * Math.PI / 180) * Math.cos(end[1] * Math.PI / 180) *
                    Math.sin(dLon / 2) * Math.sin(dLon / 2);
            var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            var distance = R * c;
            return distance.toFixed(2);
        }

        function updateRoute() {
            var startInput = document.getElementById("start-coordinates").value.trim();
            var endInput = document.getElementById("end-coordinates").value.trim();
            if (!startInput || !endInput) return;
            var start = startInput.split(',').map(parseFloat).reverse();
            var end = endInput.split(',').map(parseFloat).reverse();

            var url = 'https://api.mapbox.com/directions/v5/mapbox/driving/' + start[0] + ',' + start[1] + ';' + end[0] + ',' + end[1] + '?steps=true&geometries=geojson&access_token=' + mapboxgl.accessToken;

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (!data.routes || !data.routes[0]) return;
                    var route = data.routes[0].geometry.coordinates;
                    map.getSource('route').setData({
                        'type': 'Feature',
                        'properties': {},
                        'geometry': {
                            'type': 'LineString',
                            'coordinates': route
                        }
                    });

                    startMarker.setLngLat(start).addTo(map);
                    endMarker.setLngLat(end).addTo(map);

                    var distancia = calcularDistancia(start, end);
                    document.getElementById("distance-label").textContent = distancia + " km";

                    var duration = data.routes[0].duration;
                    var tiempoTraslado = Math.round(duration / 60);
                    document.getElementById("duration-label").textContent = tiempoTraslado + " minutos aprox.";

                    var bounds = [start, end];
                    map.fitBounds(bounds, { padding: 80 });

                    enviarDistancia(distancia);
                });
        }

        function enviarDistancia(distancia) {
            var pedido_id = document.getElementById("pedido-id").value;
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "update_distance.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.send("distancia=" + distancia + "&pedido_id=" + pedido_id);
        }

        function openGoogleMaps() {
            var startInput = document.getElementById("start-coordinates").value.trim();
            var endInput = document.getElementById("end-coordinates").value.trim();
            var start = startInput.split(',').map(parseFloat).reverse();
            var end = endInput.split(',').map(parseFloat).reverse();
            var url = "https://www.google.com/maps/dir/?api=1&origin=" + start[1] + "," + start[0] + "&destination=" + end[1] + "," + end[0];
            window.open(url, "_blank");
        }

        map.on('load', function() {
            map.addSource('route', {
                'type': 'geojson',
                'data': {
                    'type': 'Feature',
                    'properties': {},
                    'geometry': {
                        'type': 'LineString',
                        'coordinates': []
                    }
                }
            });

            map.addLayer({
                'id': 'route',
                'type': 'line',
                'source': 'route',
                'layout': {
                    'line-join': 'round',
                    'line-cap': 'round'
                },
                'paint': {
                    'line-color': '#005aa3',
                    'line-width': 5
                }
            });

            updateRoute();
        });
    </script>
    <?php endif; ?>
</body>
</html>
