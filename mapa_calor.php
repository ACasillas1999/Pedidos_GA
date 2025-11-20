<?php
session_name("GA");
session_start();

// Verificación de autenticación
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: /Pedidos_GA/Sesion/login.html");
    exit;
}

// Control de acceso: Solo Admin y JC

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mapa de Calor - Pedidos GA</title>
<<<<<<< HEAD
    <link rel="icon" type="image/png" href="/Img/logo empresa/LOGO_GPO_A.png">
=======
    <link rel="icon" type="image/png" href="/Pedidos_GA/Img/logo empresa/LOGO_GPO_A.png">
>>>>>>> parent of 5e8b02c (parra amazon Update image paths and SQL table names)
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="styles_mapa.css">

    <!-- Mapbox GL JS -->
    <link href='https://api.mapbox.com/mapbox-gl-js/v2.6.1/mapbox-gl.css' rel='stylesheet' />
    <script src='https://api.mapbox.com/mapbox-gl-js/v2.6.1/mapbox-gl.js'></script>
    <script src="https://cdn.jsdelivr.net/npm/@turf/turf@6/turf.min.js"></script>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <ul>
            <li>
                <a href="Pedidos_GA.php">
                    <img src="\Pedidos_GA\Img\Botones entregas\Pedidos_GA\INICIO_NA.png" alt="Inicio" style="max-width: 80%; height: auto;">
                </a>
            </li>
        </ul>
    </div>

    <!-- Panel de Filtros -->
    <div class="filter-panel">
        <div class="filter-header">
            <h2>Filtros de Mapa de Calor</h2>
        </div>

        <div class="filter-section">
            <h3>Período</h3>
            <label class="checkbox-label-inline">
                <input type="checkbox" id="todos-los-tiempos" style="margin-right: 8px;">
                <span>Todos los tiempos</span>
            </label>

            <div id="rango-fechas">
                <label>Desde:</label>
                <input type="date" id="fecha-desde" value="<?php echo date('Y-01-01'); ?>">

                <label>Hasta:</label>
                <input type="date" id="fecha-hasta" value="<?php echo date('Y-m-d'); ?>">
            </div>
        </div>

        <div class="filter-section">
            <h3>Tipo de Envío</h3>
            <div class="toggle-container">
                <label class="toggle-label">
                    <input type="checkbox" id="toggle-domicilio" checked>
                    <span class="toggle-switch domicilio"></span>
                    <span class="toggle-text">Domicilio</span>
                </label>

                <label class="toggle-label">
                    <input type="checkbox" id="toggle-paqueteria" checked>
                    <span class="toggle-switch paqueteria"></span>
                    <span class="toggle-text">Paquetería</span>
                </label>
            </div>
        </div>

        <div class="filter-section">
            <h3>Filtrar por Sucursal</h3>
            <select id="sucursal-filter">
                <option value="">Todas las sucursales</option>
                <option value="AIESA">AIESA</option>
                <option value="CODI">CODI</option>
                <option value="DEASA">DEASA</option>
                <option value="DIMEGSA">DIMEGSA</option>
                <option value="FESA">FESA</option>
                <option value="GABSA">GABSA</option>
                <option value="ILUMINACION">ILUMINACION</option>
                <option value="QUERETARO">QUERETARO</option>
                <option value="SEGSA">SEGSA</option>
                <option value="TAPATIA">TAPATIA</option>
            </select>
        </div>

        <div class="filter-section">
            <h3>Visualizar Sucursales</h3>
            <div class="sucursales-toggles" id="sucursales-container">
                <!-- Se generarán dinámicamente los checkboxes de sucursales -->
            </div>
        </div>

        <div class="filter-section">
            <h3>Análisis por zona</h3>
            <div style="display:flex; gap:8px; align-items:center; flex-wrap: wrap;">
                <button id="seleccionar-centro" class="btn-secondary">Elegir centro en mapa</button>
                <label for="radio-circulo" style="font-size:12px;">Radio (m):</label>
                <input type="number" id="radio-circulo" value="1000" min="100" max="20000" step="100" style="width:100px;">
                <button id="limpiar-zona" class="btn-secondary">Limpiar zona</button>
            </div>
            <div id="resultado-zona" style="margin-top:8px; font-size:13px; color:#333; background:#fff; padding:8px; border-radius:6px; border-left:3px solid #ed6b1f; display:none;"></div>
        </div>

        <div class="filter-section">
            <button id="aplicar-filtros" class="btn-primary">
                Aplicar Filtros
            </button>
            <button id="limpiar-filtros" class="btn-secondary">
                Limpiar
            </button>
        </div>

        <!-- Panel de Estadísticas -->
        <div class="stats-panel">
            <h3>Estadísticas Generales</h3>
            <div id="stats-content">
                <div class="stat-item">
                    <span class="stat-label">Total Pedidos:</span>
                    <span id="stat-total" class="stat-value">0</span>
                </div>
                <div class="stat-item domicilio-stat">
                    <span class="stat-label">Domicilio:</span>
                    <span id="stat-domicilio" class="stat-value">0</span>
                </div>
                <div class="stat-item paqueteria-stat">
                    <span class="stat-label">Paquetería:</span>
                    <span id="stat-paqueteria" class="stat-value">0</span>
                </div>
            </div>

            <div id="stats-por-sucursal" style="margin-top: 15px; max-height: 300px; overflow-y: auto;">
                <!-- Se generarán dinámicamente las estadísticas por sucursal -->
            </div>
        </div>

        <!-- Leyenda -->
        <div class="legend-panel">
            <h3>Leyenda de Intensidad</h3>
            <div class="legend-gradient">
                <div class="legend-labels">
                    <span>Baja</span>
                    <span>Media</span>
                    <span>Alta</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Contenedor del Mapa -->
    <div id="map-container">
        <div id="loading-spinner" class="spinner">
            <div class="spinner-icon"></div>
            <p>Cargando datos del mapa...</p>
        </div>
        <div id="map"></div>
    </div>

    <script>
        // Configuración de Mapbox
        mapboxgl.accessToken = 'pk.eyJ1IjoiYWNhc2lsbGFzNzY2IiwiYSI6ImNsdW12cTZyMjB4NnMya213MDdseXp6ZGgifQ.t7-l1lQfd8mgHILM5YrdNw';

        let map;
        let heatmapData = {
            domicilio: [],
            paqueteria: []
        };
        let sucursalesActivas = new Set(); // Set de sucursales visibles en el mapa
        let todasLasSucursales = []; // Array de todas las sucursales disponibles
        let statsPorSucursal = {}; // Objeto con estadísticas por sucursal

        // Variables para análisis por zona
        let datosVisibles = [];
        let seleccionCentroActiva = false;
        let centroZona = null; // [lng, lat]
        let radioZonaM = 1000; // metros
        const idFuenteZona = 'zona-circulo';
        const idCapaZonaRelleno = 'zona-circulo-fill';
        const idCapaZonaBorde = 'zona-circulo-line';

        // Inicializar mapa
        function initMap() {
            map = new mapboxgl.Map({
                container: 'map',
                style: 'mapbox://styles/mapbox/streets-v11',
                center: [-103.3494, 20.6597], // Centro de Guadalajara
                zoom: 11
            });

            map.on('load', function() {
                // Agregar controles de navegación
                map.addControl(new mapboxgl.NavigationControl());

                // Cargar datos iniciales
                cargarDatos();
            });
        }

        // Cargar datos desde la API
        async function cargarDatos() {
            const spinner = document.getElementById('loading-spinner');
            spinner.style.display = 'flex';

            const todosLosTiempos = document.getElementById('todos-los-tiempos').checked;
            const params = new URLSearchParams();

            // Solo agregar fechas si NO está marcado "Todos los tiempos"
            if (!todosLosTiempos) {
                params.append('fecha_desde', document.getElementById('fecha-desde').value);
                params.append('fecha_hasta', document.getElementById('fecha-hasta').value);
            }

            // Agregar sucursal si está seleccionada
            const sucursal = document.getElementById('sucursal-filter').value;
            if (sucursal) {
                params.append('sucursal', sucursal);
            }

            try {
                const response = await fetch(`api_mapa_datos.php?${params}`);
                const data = await response.json();

                if (data.success) {
                    heatmapData.domicilio = data.domicilio || [];
                    heatmapData.paqueteria = data.paqueteria || [];
                    statsPorSucursal = data.stats.por_sucursal || {};

                    // Obtener lista de sucursales únicas
                    const sucursalesSet = new Set();
                    [...heatmapData.domicilio, ...heatmapData.paqueteria].forEach(coord => {
                        if (coord.sucursal) {
                            sucursalesSet.add(coord.sucursal);
                        }
                    });
                    todasLasSucursales = Array.from(sucursalesSet).sort();

                    // Inicialmente mostrar todas las sucursales
                    sucursalesActivas = new Set(todasLasSucursales);

                    generarTogglesSucursales();
                    actualizarEstadisticas(data.stats);
                    actualizarMapa();
                } else {
                    console.error('Error al cargar datos:', data.error);
                    alert('Error al cargar los datos del mapa');
                }
            } catch (error) {
                console.error('Error en la petición:', error);
                alert('Error de conexión al servidor');
            } finally {
                spinner.style.display = 'none';
            }
        }

        // Generar toggles de sucursales dinámicamente
        function generarTogglesSucursales() {
            const container = document.getElementById('sucursales-container');
            container.innerHTML = '';

            todasLasSucursales.forEach(sucursal => {
                const label = document.createElement('label');
                label.className = 'sucursal-checkbox-label';

                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.value = sucursal;
                checkbox.checked = true;
                checkbox.className = 'sucursal-checkbox';
                checkbox.addEventListener('change', function() {
                    if (this.checked) {
                        sucursalesActivas.add(sucursal);
                    } else {
                        sucursalesActivas.delete(sucursal);
                    }
                    actualizarMapa();
                });

                const span = document.createElement('span');
                span.textContent = sucursal;
                span.className = 'sucursal-text';

                label.appendChild(checkbox);
                label.appendChild(span);
                container.appendChild(label);
            });
        }

        // Actualizar capas del mapa
        function actualizarMapa() {
            const mostrarDomicilio = document.getElementById('toggle-domicilio').checked;
            const mostrarPaqueteria = document.getElementById('toggle-paqueteria').checked;

            // Remover capas existentes si existen
            if (map.getLayer('heatmap-domicilio')) {
                map.removeLayer('heatmap-domicilio');
                map.removeSource('pedidos-domicilio');
            }
            if (map.getLayer('heatmap-paqueteria')) {
                map.removeLayer('heatmap-paqueteria');
                map.removeSource('pedidos-paqueteria');
            }

            // Filtrar coordenadas por sucursales activas
            const domicilioFiltrado = heatmapData.domicilio.filter(coord =>
                sucursalesActivas.has(coord.sucursal)
            );
            const paqueteriaFiltrado = heatmapData.paqueteria.filter(coord =>
                sucursalesActivas.has(coord.sucursal)
            );
            // Cache de puntos visibles para análisis por zona
            datosVisibles = [];
            if (mostrarDomicilio) datosVisibles = datosVisibles.concat(domicilioFiltrado);
            if (mostrarPaqueteria) datosVisibles = datosVisibles.concat(paqueteriaFiltrado);
            if (centroZona) { dibujarZonaYCacular(); }

            // Agregar capa de Domicilio
            if (mostrarDomicilio && domicilioFiltrado.length > 0) {
                map.addSource('pedidos-domicilio', {
                    type: 'geojson',
                    data: {
                        type: 'FeatureCollection',
                        features: domicilioFiltrado.map(coord => ({
                            type: 'Feature',
                            geometry: {
                                type: 'Point',
                                coordinates: [coord.lng, coord.lat]
                            },
                            properties: {
                                tipo: 'domicilio',
                                sucursal: coord.sucursal,
                                precio: coord.precio || 0,
                                validado: coord.validado || 0,
                                factura: coord.factura || '',
                                cliente: coord.cliente || ''
                            }
                        }))
                    }
                });

                map.addLayer({
                    id: 'heatmap-domicilio',
                    type: 'heatmap',
                    source: 'pedidos-domicilio',
                    paint: {
                        // Intensidad del heatmap
                        'heatmap-weight': 1,
                        'heatmap-intensity': [
                            'interpolate',
                            ['linear'],
                            ['zoom'],
                            0, 1,
                            15, 3
                        ],
                        // Color del heatmap (naranja)
                        'heatmap-color': [
                            'interpolate',
                            ['linear'],
                            ['heatmap-density'],
                            0, 'rgba(237, 107, 31, 0)',
                            0.2, 'rgba(237, 107, 31, 0.2)',
                            0.4, 'rgba(237, 107, 31, 0.4)',
                            0.6, 'rgba(237, 107, 31, 0.6)',
                            0.8, 'rgba(237, 107, 31, 0.8)',
                            1, 'rgba(237, 107, 31, 1)'
                        ],
                        // Radio del heatmap
                        'heatmap-radius': [
                            'interpolate',
                            ['linear'],
                            ['zoom'],
                            0, 2,
                            15, 20
                        ],
                        // Opacidad
                        'heatmap-opacity': 0.7
                    }
                });
            }

            // Agregar capa de Paquetería
            if (mostrarPaqueteria && paqueteriaFiltrado.length > 0) {
                map.addSource('pedidos-paqueteria', {
                    type: 'geojson',
                    data: {
                        type: 'FeatureCollection',
                        features: paqueteriaFiltrado.map(coord => ({
                            type: 'Feature',
                            geometry: {
                                type: 'Point',
                                coordinates: [coord.lng, coord.lat]
                            },
                            properties: {
                                tipo: 'paqueteria',
                                sucursal: coord.sucursal,
                                precio: coord.precio || 0,
                                validado: coord.validado || 0,
                                factura: coord.factura || '',
                                cliente: coord.cliente || ''
                            }
                        }))
                    }
                });

                map.addLayer({
                    id: 'heatmap-paqueteria',
                    type: 'heatmap',
                    source: 'pedidos-paqueteria',
                    paint: {
                        'heatmap-weight': 1,
                        'heatmap-intensity': [
                            'interpolate',
                            ['linear'],
                            ['zoom'],
                            0, 1,
                            15, 3
                        ],
                        // Color del heatmap (azul)
                        'heatmap-color': [
                            'interpolate',
                            ['linear'],
                            ['heatmap-density'],
                            0, 'rgba(0, 90, 163, 0)',
                            0.2, 'rgba(0, 90, 163, 0.2)',
                            0.4, 'rgba(0, 90, 163, 0.4)',
                            0.6, 'rgba(0, 90, 163, 0.6)',
                            0.8, 'rgba(0, 90, 163, 0.8)',
                            1, 'rgba(0, 90, 163, 1)'
                        ],
                        'heatmap-radius': [
                            'interpolate',
                            ['linear'],
                            ['zoom'],
                            0, 2,
                            15, 20
                        ],
                        'heatmap-opacity': 0.7
                    }
                });
            }
        }

        // Actualizar estadísticas
        function actualizarEstadisticas(stats) {
            document.getElementById('stat-total').textContent = stats.total;
            document.getElementById('stat-domicilio').textContent = stats.domicilio;
            document.getElementById('stat-paqueteria').textContent = stats.paqueteria;

            // Actualizar estadísticas por sucursal
            const statsSucursalContainer = document.getElementById('stats-por-sucursal');
            statsSucursalContainer.innerHTML = '';

            if (stats.por_sucursal && Object.keys(stats.por_sucursal).length > 0) {
                const titulo = document.createElement('h4');
                titulo.textContent = 'Por Sucursal';
                titulo.style.cssText = 'margin: 10px 0; color: #005aa3; font-size: 14px;';
                statsSucursalContainer.appendChild(titulo);

                // Ordenar sucursales por total de pedidos (descendente)
                const sucursalesOrdenadas = Object.entries(stats.por_sucursal)
                    .sort((a, b) => b[1].total - a[1].total);

                sucursalesOrdenadas.forEach(([sucursal, datos]) => {
                    const sucursalDiv = document.createElement('div');
                    sucursalDiv.className = 'sucursal-stat-item';
                    sucursalDiv.style.cssText = 'margin-bottom: 12px; padding: 10px; background: #fff; border-radius: 5px; border-left: 3px solid #005aa3;';

                    const nombreSucursal = document.createElement('div');
                    nombreSucursal.style.cssText = 'font-weight: 600; color: #333; margin-bottom: 5px; font-size: 13px;';
                    nombreSucursal.textContent = sucursal;

                    const detalles = document.createElement('div');
                    detalles.style.cssText = 'display: flex; justify-content: space-between; font-size: 12px;';
                    detalles.innerHTML = `
                        <span>Total: <strong>${datos.total}</strong></span>
                        <span style="color: #ed6b1f;">Dom: <strong>${datos.domicilio}</strong></span>
                        <span style="color: #005aa3;">Paq: <strong>${datos.paqueteria}</strong></span>
                    `;

                    sucursalDiv.appendChild(nombreSucursal);
                    sucursalDiv.appendChild(detalles);
                    statsSucursalContainer.appendChild(sucursalDiv);
                });
            }
        }

        // Función para habilitar/deshabilitar campos de fecha
        function toggleRangoFechas() {
            const todosLosTiempos = document.getElementById('todos-los-tiempos').checked;
            const rangoFechas = document.getElementById('rango-fechas');
            const fechaDesde = document.getElementById('fecha-desde');
            const fechaHasta = document.getElementById('fecha-hasta');

            if (todosLosTiempos) {
                rangoFechas.style.opacity = '0.5';
                rangoFechas.style.pointerEvents = 'none';
                fechaDesde.disabled = true;
                fechaHasta.disabled = true;
            } else {
                rangoFechas.style.opacity = '1';
                rangoFechas.style.pointerEvents = 'auto';
                fechaDesde.disabled = false;
                fechaHasta.disabled = false;
            }
        }

        // Event Listeners
        document.getElementById('todos-los-tiempos').addEventListener('change', toggleRangoFechas);
        document.getElementById('aplicar-filtros').addEventListener('click', cargarDatos);

        document.getElementById('limpiar-filtros').addEventListener('click', function() {
            document.getElementById('fecha-desde').value = '<?php echo date('Y-01-01'); ?>';
            document.getElementById('fecha-hasta').value = '<?php echo date('Y-m-d'); ?>';
            document.getElementById('sucursal-filter').value = '';
            document.getElementById('todos-los-tiempos').checked = false;
            document.getElementById('toggle-domicilio').checked = true;
            document.getElementById('toggle-paqueteria').checked = true;
            toggleRangoFechas();
            cargarDatos();
        });

        document.getElementById('toggle-domicilio').addEventListener('change', actualizarMapa);
        document.getElementById('toggle-paqueteria').addEventListener('change', actualizarMapa);

        // Inicializar al cargar la página
        // --- Lógica Análisis por Zona ---
        const btnSeleccionarCentro = document.getElementById('seleccionar-centro');
        const inputRadio = document.getElementById('radio-circulo');
        const btnLimpiarZona = document.getElementById('limpiar-zona');
        const resultadoZona = document.getElementById('resultado-zona');

        if (btnSeleccionarCentro && inputRadio && btnLimpiarZona) {
            btnSeleccionarCentro.addEventListener('click', () => {
                seleccionCentroActiva = true;
                if (map && map.getCanvas) map.getCanvas().style.cursor = 'crosshair';
                btnSeleccionarCentro.textContent = 'Click en el mapa...';
                btnSeleccionarCentro.disabled = true;
            });

            inputRadio.addEventListener('change', () => {
                const val = parseInt(inputRadio.value, 10);
                if (!isNaN(val)) {
                    radioZonaM = Math.max(100, Math.min(20000, val));
                    inputRadio.value = radioZonaM;
                    if (centroZona) dibujarZonaYCacular();
                }
            });

            btnLimpiarZona.addEventListener('click', limpiarZona);
        }

        function limpiarZona() {
            centroZona = null;
            if (resultadoZona) resultadoZona.style.display = 'none';
            if (map && map.getLayer && map.getSource) {
                if (map.getLayer(idCapaZonaRelleno)) map.removeLayer(idCapaZonaRelleno);
                if (map.getLayer(idCapaZonaBorde)) map.removeLayer(idCapaZonaBorde);
                if (map.getSource(idFuenteZona)) map.removeSource(idFuenteZona);
            }
        }

        function onMapClickSeleccion(e) {
            if (!seleccionCentroActiva) return;
            seleccionCentroActiva = false;
            if (map && map.getCanvas) map.getCanvas().style.cursor = '';
            if (btnSeleccionarCentro) {
                btnSeleccionarCentro.textContent = 'Elegir centro en mapa';
                btnSeleccionarCentro.disabled = false;
            }

            centroZona = [e.lngLat.lng, e.lngLat.lat];
            dibujarZonaYCacular();
        }

        function dibujarZonaYCacular() {
            if (!centroZona || typeof turf === 'undefined') return;
            const radioKm = radioZonaM / 1000.0;
            const circle = turf.circle(centroZona, radioKm, {steps: 64, units: 'kilometers'});
            const geo = { type: 'FeatureCollection', features: [circle] };

            if (map && map.getSource && map.getLayer) {
                if (map.getSource(idFuenteZona)) {
                    map.getSource(idFuenteZona).setData(geo);
                } else {
                    map.addSource(idFuenteZona, { type: 'geojson', data: geo });
                    map.addLayer({
                        id: idCapaZonaRelleno,
                        type: 'fill',
                        source: idFuenteZona,
                        paint: {
                            'fill-color': '#ed6b1f',
                            'fill-opacity': 0.12
                        }
                    });
                    map.addLayer({
                        id: idCapaZonaBorde,
                        type: 'line',
                        source: idFuenteZona,
                        paint: {
                            'line-color': '#ed6b1f',
                            'line-width': 2
                        }
                    });
                }
            }

            calcularTotalesZona(circle);
        }

        function calcularTotalesZona(circle) {
            let conteo = 0;
            let total = 0;
            let totalValidados = 0;

            for (const p of (datosVisibles || [])) {
                const pt = turf.point([p.lng, p.lat]);
                if (turf.booleanPointInPolygon(pt, circle)) {
                    conteo += 1;
                    const precio = Number(p.precio || 0);
                    total += precio;
                    if (Number(p.validado) === 1) totalValidados += precio;
                }
            }

            if (resultadoZona) {
                resultadoZona.style.display = 'block';
                resultadoZona.textContent = `Pedidos: ${conteo} | Total: $${total.toLocaleString('es-MX', {minimumFractionDigits:2, maximumFractionDigits:2})} | Validados: $${totalValidados.toLocaleString('es-MX', {minimumFractionDigits:2, maximumFractionDigits:2})}`;
            }
        }

        window.addEventListener('load', initMap);
        // Suscribir click al mapa cuando esté listo
        (function esperarMapaYLigarClick(){
            if (typeof map !== 'undefined' && map && map.on) {
                try { map.off('click', onMapClickSeleccion); } catch(e){}
                map.on('click', onMapClickSeleccion);
            } else {
                setTimeout(esperarMapaYLigarClick, 300);
            }
        })();
    </script>
</body>
</html>
