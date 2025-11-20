<?php

date_default_timezone_set('America/Mexico_City');
$fechaActual = date('Y-m-d');



// Iniciar la sesión
session_name("GA");
session_start();

// Verificar si el usuario no está logeado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    // Si no está logeado, redirigir al formulario de inicio de sesión
    header("location: /Pedidos_GA/Sesion/login.html");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<<<<<<< HEAD
    <link rel="icon" type="image/png" href="/Img/Botones%20entregas/ICONOSPAG/ICONOPEDIDOS.png">
=======
    <link rel="icon" type="image/png" href="/Pedidos_GA/Img/Botones%20entregas/ICONOSPAG/ICONOPEDIDOS.png">
>>>>>>> parent of 5e8b02c (parra amazon Update image paths and SQL table names)
    <link rel="stylesheet" href="styles3.css">
    <title>Pedidos GA</title>

    <!-- Mapbox CSS -->
    <link href='https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css' rel='stylesheet' />
    <link rel="stylesheet" href="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v5.0.0/mapbox-gl-geocoder.css" type="text/css">

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
    
     <script>
   document.addEventListener("DOMContentLoaded", function() {  
    var iconoVolver = document.querySelector(".icono-Volver");
    var iconoFAP = document.querySelector(".icono-FAP-img");

<<<<<<< HEAD
    var imgNormalVolver = "/Img/Botones%20entregas/RegistrarChofer/VOLVAZ.png";
    var imgHoverVolver = "/Img/Botones%20entregas/RegistrarChofer/VOLVNA.png";
=======
    var imgNormalVolver = "/Pedidos_GA/Img/Botones%20entregas/RegistrarChofer/VOLVAZ.png";
    var imgHoverVolver = "/Pedidos_GA/Img/Botones%20entregas/RegistrarChofer/VOLVNA.png";
>>>>>>> parent of 5e8b02c (parra amazon Update image paths and SQL table names)

    // Cambiar la imagen al pasar el mouse para Volver
    if (iconoVolver) {
        iconoVolver.addEventListener("mouseover", function() {
            iconoVolver.src = imgHoverVolver;
        });

        iconoVolver.addEventListener("mouseout", function() {
            iconoVolver.src = imgNormalVolver;
        });
    }

<<<<<<< HEAD
    var imgNormalFAP = "/Img/Botones%20entregas/Inicio/DETPED/AGPEDNA.png";
    var imgHoverFAP = "/Img/Botones%20entregas/Inicio/DETPED/AGPEDAZ.png";
=======
    var imgNormalFAP = "/Pedidos_GA/Img/Botones%20entregas/Inicio/DETPED/AGPEDNA.png";
    var imgHoverFAP = "/Pedidos_GA/Img/Botones%20entregas/Inicio/DETPED/AGPEDAZ.png";
>>>>>>> parent of 5e8b02c (parra amazon Update image paths and SQL table names)

    // Cambiar la imagen al pasar el mouse para FAP
    if (iconoFAP) {
        iconoFAP.addEventListener("mouseover", function() {
            iconoFAP.src = imgHoverFAP;
        });

        iconoFAP.addEventListener("mouseout", function() {
            iconoFAP.src = imgNormalFAP;
        });
    }
});

    </script>
    
    
<body>
    
    
     <header class="header">
        <div class="logo"><img src="\Pedidos_GA\Img\Botones entregas\NuevoRegistroInicio\AGPED2.png" alt="iconoVPedidos "class= "icono-registro" style="max-width: 15%; height: auto; "></div>
        <nav class="navbar">
            <ul>
                <li class="nav-item"><a href='Pedidos_GA.php' class="nav-link">
                    
                    
                     <img src="\Pedidos_GA\Img\Botones entregas\RegistrarChofer\VOLVAZ.png" alt="Choferes"class = "icono-Volver"style="max-width: 5%; height: auto; position:absolute; top: 70px; left: 35px;">
                    </a></li>
            </ul>
        </nav>
    </header>

    <p></p>
    
    <form action="NuevoRegistro.php" method="POST" id="form-nuevo-pedido">
        
        <label for="sucursal">Sucursal:</label><br>

        <?php if ($_SESSION["Rol"] === "Admin"): ?>

        <select id="sucursal" name="sucursal" required>
            <option value="">Selecciona una sucursal</option>
            <option value="DIMEGSA">DIMEGSA</option>
            <option value="DEASA">DEASA</option>
            <option value="AIESA">AIESA</option>
            <option value="SEGSA">SEGSA</option>
            <option value="FESA">FESA</option>
            <option value="TAPATIA">TAPATIA</option>
            <option value="GABSA">GABSA</option>
            <option value="ILUMINACION">ILUMINACION</option>
            <option value="VALLARTA">VALLARTA</option>
            <option value="CODI">CODI</option>
            <option value="QUERETARO">QUERETARO</option>
        </select><br><br>

        <?php endif; ?>

        <?php if (($_SESSION["Rol"] === "JC")OR ($_SESSION["Rol"] === "VR") ): ?>

            <input type="text" id="sucursal" name="sucursal" value="<?php echo $_SESSION ["Sucursal"]; ?>" readonly><br><br>

        <?php endif; ?>



        <label for="estado">Estado:</label>
        <input type="text" id="estado" name="estado" value="ACTIVO" readonly><br><br>

       <!-- <label for="fecha_recepcion_factura">Fecha de Recepción de Factura:</label>
        <input type="date" id="fecha_recepcion_factura" name="fecha_recepcion_factura" required><br><br>

        <script>
            var fechaRecepcionFactura = document.getElementById('fecha_recepcion_factura');
            var fechaActual = new Date().toISOString().slice(0, 10);
            fechaRecepcionFactura.max = fechaActual;

            fechaRecepcionFactura.addEventListener('change', function() {
                if (this.value > fechaActual) {
                    this.value = fechaActual;
                }
            });
        </script>-->

        <label for="fecha_recepcion_factura">Fecha de Recepción de Factura:</label>
        
        
<!--<input type="date" id="fecha_recepcion_factura" name="fecha_recepcion_factura" readonly >-->

<input type="date" id="fecha_recepcion_factura" name="fecha_recepcion_factura" value="<?php echo $fechaActual; ?>" max="<?php echo $fechaActual; ?>" readonly>



<br><br>

<!--<script>
    document.addEventListener('DOMContentLoaded', (event) => {
        var fechaRecepcionFactura = document.getElementById('fecha_recepcion_factura');
        var fechaActual = new Date().toISOString().slice(0, 10);
        
        // Establecer el valor inicial del input con la fecha actual
        fechaRecepcionFactura.value = fechaActual;
        
        // Establecer el valor máximo permitido para el input con la fecha actual
        fechaRecepcionFactura.max = fechaActual;

        fechaRecepcionFactura.addEventListener('change', function() {
            if (this.value > fechaActual) {
                this.value = fechaActual;
            }
        });
    });
</script>-->




        <label for="fecha_entrega_cliente">Fecha de Entrega al Cliente:</label>
        <input type="date" id="fecha_entrega_cliente" name="fecha_entrega_cliente" ><br><br>
<!-------------------------------------------------------------------->

        <label for="chofer_asignado">Chofer Asignado:</label><br>
        <select id="chofer_asignado" name="chofer_asignado" readonly>
            <option value="">Selecciona un chofer</option>
        </select><br><br>
<!--------------------------------------------------------------------->

        <label for="vendedor">Vendedor:</label>
         <input type="text" id="vendedor" name="vendedor" value="<?php echo $_SESSION ["Nombre"];?>" readonly><br><br>

         <label for="tipo_envio">Tipo de Envío:</label>
        <select id="tipo_envio" name="tipo_envio" required>
            <option value="">Selecciona una opción</option>
            <option value="Programado">Domicilio</option>
            <option value="Paquetería">Paquetería</option>
        </select><br><br>

        <label>Facturas y Precios:</label>
        <div id="facturas_container">
            <div class="factura-item" data-index="0">
                <div style="display: flex; gap: 10px; align-items: center; margin-bottom: 10px;">
                    <div style="flex: 1;">
                        <label for="factura_0">Factura:</label>
                        <input type="text" id="factura_0" name="factura[]" required>
                    </div>
                    <div style="flex: 1;">
                        <label for="precio_factura_vendedor_0">Precio:</label>
                        <input type="number" id="precio_factura_vendedor_0" name="precio_factura_vendedor[]" class="precio-input" step="0.01" min="0.01" placeholder="0.00" required>
                        <span class="alerta_precio_bajo" style="display:none; color: #856404; font-weight: bold; margin-left: 10px;">
                            ⚠️ Precio menor a $1000
                        </span>
                    </div>
                    <div style="flex: 0;">
                        <button type="button" class="btn-remove-factura" onclick="removeFactura(0)" style="background-color: #dc3545; color: white; border: none; padding: 5px 10px; cursor: pointer; border-radius: 4px; margin-top: 20px;">-</button>
                    </div>
                </div>
            </div>
        </div>
        <button type="button" onclick="addFactura()" style="background-color: #28a745; color: white; border: none; padding: 8px 15px; cursor: pointer; border-radius: 4px; margin-bottom: 15px;">+ Agregar Factura</button>
        <br><br>

        <label for="direccion">Dirección:</label>
        <input type="text" id="direccion" name="direccion" required><br><br>

        <label for="fecha_min_entrega">Fecha Mínima de Entrega:</label>
        <input type="date" id="fecha_min_entrega" name="fecha_min_entrega" required><br><br>

        <label for="fecha_max_entrega">Fecha Máxima de Entrega:</label>
        <input type="date" id="fecha_max_entrega" name="fecha_max_entrega" required><br><br>

        <label for="min_ventana_horaria_1">Hora Mínima de Ventana Horaria :</label>
        <input type="time" id="min_ventana_horaria_1" name="min_ventana_horaria_1" required><br><br>

        <label for="max_ventana_horaria_1">Hora Máxima de Ventana Horaria :</label>
        <input type="time" id="max_ventana_horaria_1" name="max_ventana_horaria_1" required><br><br>

        <label for="nombre_cliente">Nombre del Cliente:</label>
        <input type="text" id="nombre_cliente" name="nombre_cliente" required><br><br>

        <label for="telefono">Teléfono:</label>
        <input type="text" id="telefono" name="telefono" required><br><br>

        <label for="contacto">Contacto:</label>
        <input type="text" id="contacto" name="contacto" required><br><br>

        <label for="comentarios">Comentarios:</label>
        <textarea id="comentarios" name="comentarios"></textarea><br><br>

        <label for="coord_origen">Coordenadas de Origen: </label><br>
        <select id="coord_origen" name="coord_origen" required>
            <option value="">Selecciona una sucursal de Origen</option>
            <option value="20.66086566989181, -103.35498266582181">DIMEGSA</option>
            <option value="20.6626029222324, -103.35564905319303">DEASA</option>
            <option value="20.647442138464317, -103.35303451136468">AIESA</option>
            <option value="20.68075274311637, -103.36740059908436">SEGSA</option>
            <option value="20.680876941064735, -103.36717413301479">FESA</option>
            <option value="20.660080259250343, -103.35598648741274">TAPATIA</option>
            <option value="21.1017480510419, -101.68149017883717">GABSA</option>
            <option value="20.660385131570916, -103.35616304721444">ILUMINACION</option>
            <option value="20.708876281913774, -105.27453124524618">VALLARTA</option>
            <option value="20.652475013556035, -100.43190554642169">QUERETARO</option>
            <option value="20.660511385115246, -103.35846367710215">CODI</option>
        </select><br><br>

        <label for="coord_destino">Coordenadas de Destino:</label>
        <div style="display: flex; gap: 10px; align-items: center;">
            <input type="text" id="coord_destino" name="coord_destino" readonly required placeholder="Haz clic en el botón del mapa" style="flex: 1;">
            <button type="button" id="btn_abrir_mapa" style="background-color: #005aa3; color: white; border: none; padding: 10px 20px; cursor: pointer; border-radius: 4px; font-weight: bold;">
                📍 Seleccionar en Mapa
            </button>
        </div>
        <small style="color: #666;">Usa el mapa para buscar y seleccionar la ubicación de entrega</small>
        <br><br>

        
        <button type="submit" class="icono-FAP" style="background: none; border: none; padding: 0;">
<<<<<<< HEAD
    <img src="/Img/Botones%20entregas/Inicio/DETPED/AGPEDNA.png" class="icono-FAP-img" alt="Actualizar Pedido" style="max-width: 50%; height: auto; display: flex;">
=======
    <img src="/Pedidos_GA/Img/Botones%20entregas/Inicio/DETPED/AGPEDNA.png" class="icono-FAP-img" alt="Actualizar Pedido" style="max-width: 50%; height: auto; display: flex;">
>>>>>>> parent of 5e8b02c (parra amazon Update image paths and SQL table names)
</button>
    </form>

    <script>
        document.getElementById('sucursal').addEventListener('change', function() {
            const sucursalSeleccionada = this.value;
            const choferSelect = document.getElementById('chofer_asignado');
            choferSelect.innerHTML = '<option value="">Selecciona un chofer</option>';

            if (sucursalSeleccionada) {
                fetch('obtener_choferes.php?sucursal=' + sucursalSeleccionada)
                    .then(response => response.json())
                    .then(data => {
                        data.forEach(chofer => {
                            const option = document.createElement('option');
                            option.value = chofer;
                            option.textContent = chofer;
                            choferSelect.appendChild(option);
                        });
                    })
                    .catch(error => console.error('Error al obtener los choferes:', error));
            }
        });

        // Contador para los índices de facturas
        let facturaIndex = 1;

        // Función para agregar una nueva factura
        function addFactura() {
            const container = document.getElementById('facturas_container');
            const newItem = document.createElement('div');
            newItem.className = 'factura-item';
            newItem.setAttribute('data-index', facturaIndex);

            newItem.innerHTML = `
                <div style="display: flex; gap: 10px; align-items: center; margin-bottom: 10px;">
                    <div style="flex: 1;">
                        <label for="factura_${facturaIndex}">Factura:</label>
                        <input type="text" id="factura_${facturaIndex}" name="factura[]" required>
                    </div>
                    <div style="flex: 1;">
                        <label for="precio_factura_vendedor_${facturaIndex}">Precio:</label>
                        <input type="number" id="precio_factura_vendedor_${facturaIndex}" name="precio_factura_vendedor[]" class="precio-input" step="0.01" min="0.01" placeholder="0.00" required>
                        <span class="alerta_precio_bajo" style="display:none; color: #856404; font-weight: bold; margin-left: 10px;">
                            ⚠️ Precio menor a $1000
                        </span>
                    </div>
                    <div style="flex: 0;">
                        <button type="button" class="btn-remove-factura" onclick="removeFactura(${facturaIndex})" style="background-color: #dc3545; color: white; border: none; padding: 5px 10px; cursor: pointer; border-radius: 4px; margin-top: 20px;">-</button>
                    </div>
                </div>
            `;

            container.appendChild(newItem);

            // Agregar event listener al nuevo campo de precio
            const nuevoPrecioInput = document.getElementById(`precio_factura_vendedor_${facturaIndex}`);
            agregarValidacionPrecio(nuevoPrecioInput);

            facturaIndex++;
            actualizarBotonesEliminar();
        }

        // Función para eliminar una factura
        function removeFactura(index) {
            const items = document.querySelectorAll('.factura-item');
            if (items.length > 1) {
                const item = document.querySelector(`.factura-item[data-index="${index}"]`);
                if (item) {
                    item.remove();
                }
            }
            actualizarBotonesEliminar();
        }

        // Función para actualizar la visibilidad de los botones de eliminar
        function actualizarBotonesEliminar() {
            const items = document.querySelectorAll('.factura-item');
            const botones = document.querySelectorAll('.btn-remove-factura');

            if (items.length === 1) {
                botones.forEach(btn => btn.style.display = 'none');
            } else {
                botones.forEach(btn => btn.style.display = 'inline-block');
            }
        }

        // Función para agregar validación de precio a un input
        function agregarValidacionPrecio(input) {
            input.addEventListener('input', function() {
                const precio = parseFloat(this.value);
                const alerta = this.parentElement.querySelector('.alerta_precio_bajo');

                if (!isNaN(precio) && precio > 0 && precio < 1000) {
                    alerta.style.display = 'inline';
                    this.style.backgroundColor = '#fff3cd';
                } else {
                    alerta.style.display = 'none';
                    this.style.backgroundColor = '';
                }
            });
        }

        // Inicializar validación de precios en campos existentes
        document.addEventListener('DOMContentLoaded', function() {
            const preciosInputs = document.querySelectorAll('.precio-input');
            preciosInputs.forEach(input => {
                agregarValidacionPrecio(input);
            });
            actualizarBotonesEliminar();
        });
    </script>

    <!-- Modal para seleccionar coordenadas -->
    <div id="modal_mapa" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.7); z-index: 9999; overflow: auto;">
        <div style="background-color: white; margin: 2% auto; padding: 20px; width: 90%; max-width: 900px; border-radius: 10px; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="margin: 0; color: #005aa3;">📍 Seleccionar Ubicación de Entrega</h2>
                <button type="button" id="btn_cerrar_mapa" style="background-color: #dc3545; color: white; border: none; padding: 8px 15px; cursor: pointer; border-radius: 4px; font-weight: bold;">
                    ✕ Cerrar
                </button>
            </div>

            <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 15px;">
                <p style="margin: 0; font-size: 14px; color: #666;">
                    <strong>Instrucciones:</strong> Usa el buscador para encontrar la dirección o haz clic directamente en el mapa para seleccionar la ubicación.
                </p>
            </div>

            <!-- Buscador de Mapbox -->
            <div id="geocoder_mapa" style="margin-bottom: 10px;"></div>

            <!-- Contenedor del mapa -->
            <div id="mapa_coordenadas" style="width: 100%; height: 500px; border-radius: 8px; border: 2px solid #005aa3;"></div>

            <!-- Mostrar coordenadas seleccionadas -->
            <div style="margin-top: 15px; padding: 15px; background-color: #e7f3ff; border-radius: 5px; border-left: 4px solid #005aa3;">
                <p style="margin: 0; font-weight: bold; color: #005aa3;">Coordenadas seleccionadas:</p>
                <p id="coordenadas_display" style="margin: 5px 0 0 0; font-family: monospace; font-size: 16px; color: #333;">
                    Sin seleccionar
                </p>
            </div>

            <!-- Botón para confirmar -->
            <div style="text-align: center; margin-top: 20px;">
                <button type="button" id="btn_confirmar_coords" style="background-color: #28a745; color: white; border: none; padding: 12px 30px; cursor: pointer; border-radius: 4px; font-weight: bold; font-size: 16px;">
                    ✓ Confirmar Ubicación
                </button>
            </div>
        </div>
    </div>

    <!-- Mapbox Scripts -->
    <script src='https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js'></script>
    <script src="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v5.0.0/mapbox-gl-geocoder.min.js"></script>

    <script>
        // Variables globales para el mapa
        let map = null;
        let marker = null;
        let coordenadasSeleccionadas = null;

        // Verificar que Mapbox esté cargado
        if (typeof mapboxgl === 'undefined') {
            console.error('❌ Mapbox GL JS no está cargado');
        } else {
            console.log('✅ Mapbox GL JS cargado correctamente');
        }

        // Botón para abrir el modal
        document.getElementById('btn_abrir_mapa').addEventListener('click', function() {
            // Verificar que Mapbox esté disponible
            if (typeof mapboxgl === 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error al cargar el mapa',
                    text: 'Las librerías de mapas no se han cargado correctamente. Por favor, recarga la página.',
                    confirmButtonColor: '#005aa3'
                });
                return;
            }
            document.getElementById('modal_mapa').style.display = 'block';

            // Inicializar el mapa solo la primera vez
            if (!map) {
                // Esperar un momento para que el modal se renderice
                setTimeout(() => {
                    inicializarMapa();
                }, 100);
            } else {
                // Si el mapa ya existe, forzar redimensionamiento
                map.resize();
            }
        });

        // Botón para cerrar el modal
        document.getElementById('btn_cerrar_mapa').addEventListener('click', function() {
            document.getElementById('modal_mapa').style.display = 'none';
        });

        // Cerrar modal al hacer clic fuera
        document.getElementById('modal_mapa').addEventListener('click', function(e) {
            if (e.target === this) {
                this.style.display = 'none';
            }
        });

        // Función para inicializar el mapa
        function inicializarMapa() {
            console.log('🗺️ Inicializando mapa...');

            try {
                mapboxgl.accessToken = 'pk.eyJ1IjoiYWNhc2lsbGFzNzY2IiwiYSI6ImNsdW12cTZyMjB4NnMya213MDdseXp6ZGgifQ.t7-l1lQfd8mgHILM5YrdNw';

                // Crear mapa centrado en Guadalajara
                map = new mapboxgl.Map({
                    container: 'mapa_coordenadas',
                    style: 'mapbox://styles/mapbox/streets-v12',
                    center: [-103.3494, 20.6597], // Guadalajara
                    zoom: 12
                });

                console.log('✅ Mapa creado');

                // Esperar a que el mapa cargue antes de agregar controles
                map.on('load', () => {
                    console.log('✅ Mapa cargado completamente');
                });

                // Agregar controles de navegación
                map.addControl(new mapboxgl.NavigationControl());

            // Inicializar geocoder (buscador)
            const geocoder = new MapboxGeocoder({
                accessToken: mapboxgl.accessToken,
                mapboxgl: mapboxgl,
                marker: false,
                placeholder: 'Buscar dirección...',
                countries: 'mx'
            });

            document.getElementById('geocoder_mapa').appendChild(geocoder.onAdd(map));

            // Cuando se selecciona una dirección del geocoder
            geocoder.on('result', (e) => {
                const coords = e.result.geometry.coordinates;
                agregarMarcador(coords);
            });

                // Click en el mapa para seleccionar ubicación
                map.on('click', (e) => {
                    const coords = [e.lngLat.lng, e.lngLat.lat];
                    agregarMarcador(coords);
                });

            } catch (error) {
                console.error('❌ Error al inicializar mapa:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error al cargar el mapa',
                    text: 'No se pudo cargar el mapa. Por favor, recarga la página e intenta de nuevo.',
                    confirmButtonColor: '#005aa3'
                });
            }
        }

        // Función para agregar o mover el marcador
        function agregarMarcador(coords) {
            // Remover marcador anterior si existe
            if (marker) {
                marker.remove();
            }

            // Crear nuevo marcador
            marker = new mapboxgl.Marker({
                draggable: true,
                color: '#005aa3'
            })
            .setLngLat(coords)
            .addTo(map);

            // Actualizar coordenadas cuando se arrastra el marcador
            marker.on('dragend', () => {
                const lngLat = marker.getLngLat();
                actualizarCoordenadasDisplay(lngLat.lat, lngLat.lng);
            });

            // Actualizar display
            actualizarCoordenadasDisplay(coords[1], coords[0]);
        }

        // Función para actualizar el display de coordenadas
        function actualizarCoordenadasDisplay(lat, lng) {
            coordenadasSeleccionadas = { lat, lng };
            document.getElementById('coordenadas_display').textContent =
                `Latitud: ${lat.toFixed(8)}, Longitud: ${lng.toFixed(8)}`;
        }

        // Botón para confirmar coordenadas
        document.getElementById('btn_confirmar_coords').addEventListener('click', function() {
            if (coordenadasSeleccionadas) {
                // Formato: "lat, lng" como esperaba el sistema
                const coordsTexto = `${coordenadasSeleccionadas.lat.toFixed(8)}, ${coordenadasSeleccionadas.lng.toFixed(8)}`;
                document.getElementById('coord_destino').value = coordsTexto;

                // Cerrar modal
                document.getElementById('modal_mapa').style.display = 'none';

                // Mostrar confirmación
                Swal.fire({
                    icon: 'success',
                    title: 'Coordenadas guardadas',
                    text: 'Las coordenadas se han guardado correctamente',
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                Swal.fire({
                    icon: 'warning',
                    title: 'Ubicación no seleccionada',
                    text: 'Por favor, selecciona una ubicación en el mapa primero',
                    confirmButtonColor: '#005aa3'
                });
            }
        });
    </script>

    <!-- Validación del formulario -->
    <script>
        document.getElementById('form-nuevo-pedido').addEventListener('submit', function(e) {
            const coordDestino = document.getElementById('coord_destino').value.trim();

            if (coordDestino === '') {
                e.preventDefault();

                Swal.fire({
                    icon: 'warning',
                    title: 'Campo obligatorio',
                    html: 'El campo <strong>"Coordenadas de Destino"</strong> es obligatorio.<br><br>Por favor, haz clic en el botón <strong>"📍 Seleccionar en Mapa"</strong> para elegir la ubicación de entrega.',
                    confirmButtonColor: '#005aa3',
                    confirmButtonText: 'Entendido'
                }).then(() => {
                    // Hacer scroll al campo
                    document.getElementById('coord_destino').scrollIntoView({ behavior: 'smooth', block: 'center' });
                });

                return false;
            }
        });
    </script>

</body>
</html>
