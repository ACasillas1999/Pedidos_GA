// modal_destinatario.js
// Script para manejar el modal de captura de destinatario para pedidos de paquetería

// Verificar que Mapbox esté disponible
if (typeof mapboxgl === 'undefined') {
    console.error('Mapbox GL JS no está cargado. Asegúrate de incluir el script de Mapbox antes de este archivo.');
}

// Token de Mapbox (el mismo usado en mapa_calor.php)
if (typeof mapboxgl !== 'undefined') {
    mapboxgl.accessToken = 'pk.eyJ1IjoiYWNhc2lsbGFzNzY2IiwiYSI6ImNsdW12cTZyMjB4NnMya213MDdseXp6ZGgifQ.t7-l1lQfd8mgHILM5YrdNw';
}

// Manejar clic en botón "Capturar Destino" o "Plantilla"
document.addEventListener('click', async function(e) {
    const btn = e.target.closest('.btn-capturar-destino');
    if (!btn) return;

    const pedidoId = btn.dataset.pedidoId;
    const tieneDestinatario = btn.dataset.tieneDestinatario === '1';

    // Si ya tiene destinatario capturado, permitir edición Y descarga
    if (tieneDestinatario) {
        const { value: accion } = await Swal.fire({
            title: 'Plantilla de Paquetería',
            text: 'Este pedido ya tiene datos de destinatario capturados',
            icon: 'info',
            showCancelButton: true,
            showDenyButton: true,
            confirmButtonText: 'Descargar Plantilla',
            denyButtonText: 'Editar Destinatario',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#22a06b',
            denyButtonColor: '#2563eb',
        });

        if (accion === true) {
            // Descargar plantilla
            window.open('descargar_plantilla_paqueteria.php?id=' + pedidoId, '_blank');
            return;
        } else if (accion === false) {
            // Editar destinatario (continuar al modal)
        } else {
            // Cancelar
            return;
        }
    }

    // Mostrar modal de captura/edición
    await mostrarModalDestinatario(pedidoId, tieneDestinatario);
});

async function mostrarModalDestinatario(pedidoId, esEdicion) {
    // Obtener datos existentes si es edición
    let datosExistentes = null;
    if (esEdicion) {
        try {
            const response = await fetch('obtener_destinatario.php?pedido_id=' + pedidoId);
            const result = await response.json();
            if (result.success && result.existe) {
                datosExistentes = result.data;
            }
        } catch (error) {
            console.error('Error al cargar datos:', error);
        }
    }

    const tituloModal = esEdicion ? 'Editar Datos del Destinatario' : 'Capturar Datos del Destinatario';

    const { value: formValues } = await Swal.fire({
        title: tituloModal,
        html: `
            <div class="form-destinatario">
                <div class="form-section">
                    <h4>📍 Dirección de Entrega</h4>

                    <div class="mapbox-search-wrapper">
                        <div id="geocoder-destinatario"></div>
                    </div>

                    <div id="map-destinatario"></div>
                    <div class="coordenadas-info" id="coordenadas-display">
                        Haz clic en el mapa o busca una dirección para seleccionar la ubicación
                    </div>

                    <input type="hidden" id="input-lat" value="${datosExistentes?.lat || ''}">
                    <input type="hidden" id="input-lng" value="${datosExistentes?.lng || ''}">

                    <div class="form-row" style="margin-top: 15px;">
                        <div>
                            <label>Calle *</label>
                            <input type="text" id="input-calle" value="${datosExistentes?.calle || ''}" required>
                        </div>
                        <div>
                            <label>Colonia *</label>
                            <input type="text" id="input-colonia" value="${datosExistentes?.colonia || ''}" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div>
                            <label>No. Exterior</label>
                            <input type="text" id="input-no-exterior" value="${datosExistentes?.no_exterior || ''}">
                        </div>
                        <div>
                            <label>No. Interior</label>
                            <input type="text" id="input-no-interior" value="${datosExistentes?.no_interior || ''}">
                        </div>
                    </div>

                    <div class="form-row">
                        <div>
                            <label>Entre Calles</label>
                            <input type="text" id="input-entre-calles" value="${datosExistentes?.entre_calles || ''}">
                        </div>
                        <div>
                            <label>Código Postal</label>
                            <input type="text" id="input-cp" value="${datosExistentes?.codigo_postal || ''}">
                        </div>
                    </div>

                    <div class="form-row">
                        <div>
                            <label>Ciudad *</label>
                            <input type="text" id="input-ciudad" value="${datosExistentes?.ciudad || ''}" required>
                        </div>
                        <div>
                            <label>Estado *</label>
                            <input type="text" id="input-estado" value="${datosExistentes?.estado_destino || ''}" required>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h4>👤 Datos del Contacto</h4>

                    <div class="form-row">
                        <div>
                            <label>Nombre Destinatario *</label>
                            <input type="text" id="input-nombre" value="${datosExistentes?.nombre_destinatario || ''}" required>
                        </div>
                        <div>
                            <label>Teléfono *</label>
                            <input type="text" id="input-telefono" value="${datosExistentes?.telefono_destino || ''}" required>
                        </div>
                    </div>

                    <div class="form-row full">
                        <div>
                            <label>Contacto Adicional</label>
                            <input type="text" id="input-contacto" value="${datosExistentes?.contacto_destino || ''}">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h4>📦 Datos de Paquetería</h4>

                    <div class="form-row">
                        <div>
                            <label>Nombre Paquetería</label>
                            <input type="text" id="input-paqueteria" placeholder="Ej: D8A, Estafeta, FedEx" value="${datosExistentes?.nombre_paqueteria || ''}">
                        </div>
                        <div>
                            <label>Tipo de Cobro</label>
                            <input type="text" id="input-tipo-cobro" placeholder="Ej: OCURRE X COBRAR" value="${datosExistentes?.tipo_cobro || ''}">
                        </div>
                    </div>

                    <div class="form-row">
                        <div>
                            <label>ATN (Atención a)</label>
                            <input type="text" id="input-atn" value="${datosExistentes?.atn || ''}">
                        </div>
                        <div>
                            <label>Número de Cliente</label>
                            <input type="text" id="input-num-cliente" value="${datosExistentes?.num_cliente || ''}">
                        </div>
                    </div>

                    <div class="form-row full">
                        <div>
                            <label>Clave SAT</label>
                            <input type="text" id="input-clave-sat" value="${datosExistentes?.clave_sat || ''}">
                        </div>
                    </div>
                </div>
            </div>
        `,
        width: '900px',
        showCancelButton: true,
        confirmButtonText: 'Guardar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#22a06b',
        showLoaderOnConfirm: true,
        didOpen: () => {
            // Inicializar mapa
            const map = new mapboxgl.Map({
                container: 'map-destinatario',
                style: 'mapbox://styles/mapbox/streets-v12',
                center: datosExistentes?.lng && datosExistentes?.lat
                    ? [datosExistentes.lng, datosExistentes.lat]
                    : [-103.3496, 20.6597], // Guadalajara por defecto
                zoom: datosExistentes ? 15 : 11
            });

            // Marcador
            let marker = null;
            if (datosExistentes?.lng && datosExistentes?.lat) {
                marker = new mapboxgl.Marker({ draggable: true })
                    .setLngLat([datosExistentes.lng, datosExistentes.lat])
                    .addTo(map);

                marker.on('dragend', () => {
                    const lngLat = marker.getLngLat();
                    document.getElementById('input-lat').value = lngLat.lat.toFixed(8);
                    document.getElementById('input-lng').value = lngLat.lng.toFixed(8);
                    document.getElementById('coordenadas-display').textContent =
                        `Lat: ${lngLat.lat.toFixed(6)}, Lng: ${lngLat.lng.toFixed(6)}`;
                });
            }

            // Geocoder (búsqueda de direcciones)
            const geocoder = new MapboxGeocoder({
                accessToken: mapboxgl.accessToken,
                mapboxgl: mapboxgl,
                marker: false,
                placeholder: 'Buscar dirección...',
                countries: 'mx'
            });

            document.getElementById('geocoder-destinatario').appendChild(geocoder.onAdd(map));

            // Cuando se selecciona una dirección del geocoder
            geocoder.on('result', (e) => {
                console.log('🎯 EVENTO GEOCODER DISPARADO');
                console.log('📍 Resultado completo:', e.result);
                console.log('📝 place_name:', e.result.place_name);
                console.log('🏷️ text:', e.result.text);
                console.log('📦 context:', e.result.context);
                console.log('🔢 properties:', e.result.properties);

                const coords = e.result.geometry.coordinates;

                // Remover marcador anterior
                if (marker) marker.remove();

                // Crear nuevo marcador
                marker = new mapboxgl.Marker({ draggable: true })
                    .setLngLat(coords)
                    .addTo(map);

                marker.on('dragend', () => {
                    const lngLat = marker.getLngLat();
                    document.getElementById('input-lat').value = lngLat.lat.toFixed(8);
                    document.getElementById('input-lng').value = lngLat.lng.toFixed(8);
                    document.getElementById('coordenadas-display').textContent =
                        `Lat: ${lngLat.lat.toFixed(6)}, Lng: ${lngLat.lng.toFixed(6)}`;
                });

                // Guardar coordenadas
                document.getElementById('input-lat').value = coords[1].toFixed(8);
                document.getElementById('input-lng').value = coords[0].toFixed(8);
                document.getElementById('coordenadas-display').textContent =
                    `Lat: ${coords[1].toFixed(6)}, Lng: ${coords[0].toFixed(6)}`;

                // Auto-llenar campos con información de la dirección seleccionada
                const context = e.result.context || [];
                const placeName = e.result.place_name || '';
                const properties = e.result.properties || {};

                // Extraer información de la dirección completa
                // Ejemplo: "Calle de la Brida 148, Colonia Jardines de la Patria, 45140 Zapopan, Jalisco, México"
                const fullAddress = e.result.place_name;
                const addressParts = e.result.text || '';

                console.log('🔍 Parseando dirección completa:', fullAddress);

                // Intentar extraer calle y número del text (primera parte)
                let calle = addressParts;
                let numeroExterior = '';

                // Buscar número en el text
                const addressMatch = addressParts.match(/^(.+?)\s+(\d+[a-zA-Z]*)$/);
                if (addressMatch) {
                    calle = addressMatch[1].trim();
                    numeroExterior = addressMatch[2].trim();
                    console.log('✅ Calle extraída del text:', calle);
                    console.log('✅ Número extraído del text:', numeroExterior);
                } else {
                    console.log('⚠️ No se pudo separar calle y número de:', addressParts);
                }

                // Si no se encontró número, buscarlo en properties.address (SIEMPRE revisar)
                if (!numeroExterior && properties && properties.address) {
                    numeroExterior = properties.address;
                    console.log('✅ Número encontrado en properties.address:', numeroExterior);
                }

                // También intentar extraerlo del place_name completo
                if (!numeroExterior) {
                    const fullMatch = fullAddress.match(/\b(\d+[a-zA-Z]*)\b/);
                    if (fullMatch) {
                        numeroExterior = fullMatch[1];
                        console.log('✅ Número encontrado en place_name:', numeroExterior);
                    }
                }

                // Autocompletar calle
                document.getElementById('input-calle').value = calle;
                console.log('📝 Campo calle rellenado:', calle);

                // Autocompletar número exterior
                if (numeroExterior) {
                    document.getElementById('input-no-exterior').value = numeroExterior;
                    console.log('📝 Campo no. exterior rellenado:', numeroExterior);
                }

                // Parsear el contexto para obtener colonia, CP, ciudad, estado
                let coloniaEncontrada = '';
                let cpEncontrado = '';
                let ciudadEncontrada = '';
                let estadoEncontrado = '';

                console.log('🔍 Parseando contexto...');
                context.forEach(item => {
                    console.log('  - Item contexto:', item.id, '→', item.text);

                    // Colonia (neighborhood o locality)
                    if (item.id.includes('neighborhood') || item.id.includes('locality')) {
                        if (!coloniaEncontrada) {
                            coloniaEncontrada = item.text;
                            console.log('  ✅ Colonia encontrada:', coloniaEncontrada);
                        }
                    }
                    // Código Postal
                    if (item.id.includes('postcode')) {
                        cpEncontrado = item.text;
                        console.log('  ✅ CP encontrado:', cpEncontrado);
                    }
                    // Ciudad
                    if (item.id.includes('place')) {
                        ciudadEncontrada = item.text;
                        console.log('  ✅ Ciudad encontrada:', ciudadEncontrada);
                    }
                    // Estado
                    if (item.id.includes('region')) {
                        estadoEncontrado = item.text;
                        console.log('  ✅ Estado encontrado:', estadoEncontrado);
                    }
                });

                // Rellenar campos
                if (coloniaEncontrada) {
                    document.getElementById('input-colonia').value = coloniaEncontrada;
                    console.log('📝 Campo colonia rellenado:', coloniaEncontrada);
                } else {
                    // Si no se encontró colonia, intentar extraerla del place_name
                    // Formato: "Calle XXX, Colonia YYY, CP Ciudad, Estado, País"
                    // o: "Calle XXX, CP Ciudad, Estado, País"
                    console.log('⚠️ No se encontró colonia en el contexto, intentando extraer del place_name');

                    // Dividir por comas
                    const partes = fullAddress.split(',').map(p => p.trim());
                    console.log('  Partes del address:', partes);

                    // Si hay más de 3 partes, la segunda podría ser la colonia
                    // Ejemplo: ["Calle de la Brida 148", "Colonia Jardines", "45140 Zapopan", "Jalisco", "México"]
                    if (partes.length >= 4) {
                        // La segunda parte podría ser la colonia (si no empieza con número)
                        const posibleColonia = partes[1];
                        if (posibleColonia && !/^\d/.test(posibleColonia)) {
                            coloniaEncontrada = posibleColonia;
                            document.getElementById('input-colonia').value = coloniaEncontrada;
                            console.log('  ✅ Colonia extraída del place_name:', coloniaEncontrada);
                        }
                    }

                    if (!coloniaEncontrada) {
                        console.log('  ⚠️ No se pudo extraer colonia del place_name');
                    }
                }

                if (cpEncontrado) {
                    document.getElementById('input-cp').value = cpEncontrado;
                    console.log('📝 Campo CP rellenado:', cpEncontrado);
                }

                if (ciudadEncontrada) {
                    document.getElementById('input-ciudad').value = ciudadEncontrada;
                    console.log('📝 Campo ciudad rellenado:', ciudadEncontrada);
                }

                if (estadoEncontrado) {
                    document.getElementById('input-estado').value = estadoEncontrado;
                    console.log('📝 Campo estado rellenado:', estadoEncontrado);
                }

                console.log('✨ Autocompletado finalizado');
            });

            // Click en el mapa para colocar marcador
            map.on('click', (e) => {
                const coords = [e.lngLat.lng, e.lngLat.lat];

                if (marker) marker.remove();

                marker = new mapboxgl.Marker({ draggable: true })
                    .setLngLat(coords)
                    .addTo(map);

                marker.on('dragend', () => {
                    const lngLat = marker.getLngLat();
                    document.getElementById('input-lat').value = lngLat.lat.toFixed(8);
                    document.getElementById('input-lng').value = lngLat.lng.toFixed(8);
                    document.getElementById('coordenadas-display').textContent =
                        `Lat: ${lngLat.lat.toFixed(6)}, Lng: ${lngLat.lng.toFixed(6)}`;
                });

                document.getElementById('input-lat').value = coords[1].toFixed(8);
                document.getElementById('input-lng').value = coords[0].toFixed(8);
                document.getElementById('coordenadas-display').textContent =
                    `Lat: ${coords[1].toFixed(6)}, Lng: ${coords[0].toFixed(6)}`;
            });
        },
        preConfirm: async () => {
            // Validar campos requeridos
            const lat = document.getElementById('input-lat').value;
            const lng = document.getElementById('input-lng').value;
            const calle = document.getElementById('input-calle').value.trim();
            const colonia = document.getElementById('input-colonia').value.trim();
            const ciudad = document.getElementById('input-ciudad').value.trim();
            const estado = document.getElementById('input-estado').value.trim();
            const nombre = document.getElementById('input-nombre').value.trim();
            const telefono = document.getElementById('input-telefono').value.trim();

            if (!lat || !lng) {
                Swal.showValidationMessage('Por favor selecciona una ubicación en el mapa');
                return false;
            }

            if (!calle || !colonia || !ciudad || !estado) {
                Swal.showValidationMessage('Por favor completa todos los campos de dirección marcados con *');
                return false;
            }

            if (!nombre || !telefono) {
                Swal.showValidationMessage('Por favor completa el nombre y teléfono del destinatario');
                return false;
            }

            // Preparar datos para enviar
            const datos = {
                pedido_id: pedidoId,
                lat: parseFloat(lat),
                lng: parseFloat(lng),
                calle: calle,
                no_exterior: document.getElementById('input-no-exterior').value.trim(),
                no_interior: document.getElementById('input-no-interior').value.trim(),
                entre_calles: document.getElementById('input-entre-calles').value.trim(),
                colonia: colonia,
                codigo_postal: document.getElementById('input-cp').value.trim(),
                ciudad: ciudad,
                estado_destino: estado,
                contacto_destino: document.getElementById('input-contacto').value.trim(),
                telefono_destino: telefono,
                nombre_destinatario: nombre,
                nombre_paqueteria: document.getElementById('input-paqueteria').value.trim(),
                tipo_cobro: document.getElementById('input-tipo-cobro').value.trim(),
                atn: document.getElementById('input-atn').value.trim(),
                num_cliente: document.getElementById('input-num-cliente').value.trim(),
                clave_sat: document.getElementById('input-clave-sat').value.trim()
            };

            // Enviar a servidor
            try {
                const response = await fetch('guardar_destinatario.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(datos)
                });

                const result = await response.json();

                if (!result.success) {
                    throw new Error(result.message || 'Error al guardar');
                }

                return result;
            } catch (error) {
                Swal.showValidationMessage(`Error: ${error.message}`);
                return false;
            }
        },
        allowOutsideClick: () => !Swal.isLoading()
    });

    if (formValues) {
        // Datos guardados exitosamente
        await Swal.fire({
            title: 'Datos Guardados',
            text: '¿Deseas descargar la plantilla ahora?',
            icon: 'success',
            showCancelButton: true,
            confirmButtonText: 'Descargar Plantilla',
            cancelButtonText: 'Cerrar',
            confirmButtonColor: '#22a06b'
        }).then((result) => {
            if (result.isConfirmed) {
                window.open('descargar_plantilla_paqueteria.php?id=' + pedidoId, '_blank');
            }
        });

        // Recargar la tabla para mostrar el botón actualizado
        location.reload();
    }
}
