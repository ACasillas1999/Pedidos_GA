const checklistEstructura = [
    {
        seccion: "Sistema de Luces",
        items: ["Luces frontales (NN)**", "Luces traseras (NN)**", "Luces de freno", "Direccionales (NN)**", "Luces de reversa", "Luces internas"]
    },
    {
        seccion: "Parte Externa",
        items: ["Estado de carrocería", "Espejos laterales", "Parabrisas delantero", "Parabrisas trasero", "Limpiaparabrisas", "Antena", "Placa delantera", "Placa trasera"]
    },
    {
        seccion: "Parte Interna",
        items: ["Cinturones de seguridad (NN)**", "Tablero de instrumentos", "Aire acondicionado", "Calefacción", "Radio/audio", "Claxon", "Frenos (NN)**", "Volante"]
    },
    {
        seccion: "Estado de Llantas",
        items: ["Desgaste de llantas", "Presión de llantas"]
    },
    {
        seccion: "Accesorios de Seguridad",
        items: ["Extintor", "Botiquín de primeros auxilios"]
    },
    {
        seccion: "Tapas y Otros",
        items: ["Tapa de combustible", "Capó"]
    },
    {
        seccion: "Rotulado",
        items: ["Logos y rotulación del vehículo"]
    }
];

let pedidosData = [];

// ---- Mapa de logos por sucursal ----
const SUCURSAL_LOGOS = {
    'aiesa'  : 'img/Sucursales/aiesa.png',
    'deasa'  : 'img/Sucursales/deasaazz.png',
    'azz'    : 'img/Sucursales/deasaazz.png',
    'dimegsa': 'img/Sucursales/dimegsa.png',
    'eitsa'  : 'img/Sucursales/eitsa.png',
    'fesa'   : 'img/Sucursales/fesa.png',
    'ilum'   : 'img/Sucursales/ilum.png',
    'segsa'  : 'img/Sucursales/segsa.png',
};
const LOGO_DEFAULT = 'img/Sucursales/logoga.png';

function getSucursalLogo(sucursal) {
    if (!sucursal) return LOGO_DEFAULT;
    const key = sucursal.toLowerCase().trim();
    // Buscar coincidencia parcial para cubrir nombres como "GA AIESA" o "Sucursal FESA"
    for (const [k, v] of Object.entries(SUCURSAL_LOGOS)) {
        if (key.includes(k)) return v;
    }
    return LOGO_DEFAULT;
}

document.addEventListener('DOMContentLoaded', () => {
    const isLoggedIn = localStorage.getItem('isLoggedIn');
    const username = localStorage.getItem('username');

    if (isLoggedIn !== 'true' || !username) {
        window.location.href = 'index.html';
        return;
    }

    const logoutBtnEl = document.getElementById('logoutBtn');
    if (logoutBtnEl) logoutBtnEl.addEventListener('click', handleLogout);

    const kmForm = document.getElementById('kmForm');
    if (kmForm) kmForm.addEventListener('submit', handleKmSubmit);

    const checklistForm = document.getElementById('checklistForm');
    if (checklistForm) checklistForm.addEventListener('submit', handleChecklistSubmit);

    // Registrar escuchadores de conexión y sincronización
    window.addEventListener('online', syncOfflineQueue);
    window.addEventListener('offline', () => updateConnectionBanner());
    
    const syncBtn = document.getElementById('connectionSyncBtn');
    if (syncBtn) {
        syncBtn.addEventListener('click', syncOfflineQueue);
    }

    // Ejecutar verificación inicial de conexión y sincronizar si hay cola pendiente
    setTimeout(() => {
        updateConnectionBanner();
        syncOfflineQueue();
    }, 1000);

    // Iniciar el flujo de verificación secuencial: KM -> Inspección -> Pedidos
    verificarKilometraje(username);
});

// ---- Sidebar: inicialización independiente ----
// Se registra en su propio listener para que funcione aunque haya
// un return anticipado en el bloque de autenticación.
document.addEventListener('DOMContentLoaded', () => {
    const menuBtn      = document.getElementById('menuBtn');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const navActivos   = document.getElementById('navActivos');
    const navHistorial = document.getElementById('navHistorial');
    const navPerfil    = document.getElementById('navPerfil');
    const sidebarUsername = document.getElementById('sidebarUsername');

    // Mostrar nombre de usuario en el sidebar
    const username = localStorage.getItem('username');
    if (sidebarUsername && username) sidebarUsername.textContent = username;

    if (menuBtn)        menuBtn.addEventListener('click', () => toggleSidebar(true));
    if (sidebarOverlay) sidebarOverlay.addEventListener('click', () => toggleSidebar(false));

    if (navActivos) navActivos.addEventListener('click', () => {
        currentFilter = 'activos';
        toggleSidebar(false);
        if (pedidosData && pedidosData.length > 0) renderListaPedidos(pedidosData);
    });
    if (navHistorial) navHistorial.addEventListener('click', () => {
        currentFilter = 'historial';
        toggleSidebar(false);
        if (pedidosData && pedidosData.length > 0) renderListaPedidos(pedidosData);
    });
    if (navPerfil) navPerfil.addEventListener('click', () => {
        toggleSidebar(false);
        renderPerfilView();
    });
});


function handleLogout() {
    localStorage.clear();
    window.location.href = 'index.html';
}

function showView(viewId) {
    ['loadingView', 'pedidosList', 'pedidoDetail', 'perfilView'].forEach(id => {
        const el = document.getElementById(id);
        if(el) el.classList.add('hidden');
    });
    const activeEl = document.getElementById(viewId);
    if(activeEl) activeEl.classList.remove('hidden');
}

function mostrarMensajeCentral(mensaje, icono = 'info', color = 'gray') {
    const loadingView = document.getElementById('loadingView');
    loadingView.innerHTML = `
        <div class="flex flex-col items-center justify-center h-full w-full text-center">
            <i data-lucide="${icono}" class="w-16 h-16 mb-4 opacity-80 text-${color}-500"></i>
            <h2 class="text-xl font-bold mb-2 text-gray-800">Aviso</h2>
            <p class="text-gray-600">${mensaje}</p>
            ${icono === 'wifi-off' || icono === 'alert-triangle' ? `<button onclick="window.location.reload()" class="mt-6 px-6 py-2 text-white rounded-full font-bold" style="background-color:#0f4c81;">Reintentar</button>` : ''}
        </div>
    `;
    lucide.createIcons();
    showView('loadingView');
}

async function verificarKilometraje(username) {
    try {
        const response = await fetch(`${CONFIG.API_URL}estado_kilometraje.php?username=${encodeURIComponent(username)}`);
        const data = await response.json();
        
        if (data.ok === false) {
            mostrarMensajeCentral('Error: ' + data.error, 'alert-triangle', 'red');
            return;
        }

        if (data.assigned === false) {
            mostrarMensajeCentral('No tienes un vehículo asignado.', 'alert-triangle', 'red');
            return;
        }

        if (data.needs_km === true) {
            document.getElementById('kmModal').classList.remove('hidden');
            document.body.classList.add('modal-open');
            const kmMinimo = data.Km_Total || data.last_km_final || 0;
            if (kmMinimo > 0) {
                document.getElementById('kmInput').setAttribute('min', kmMinimo);
                document.getElementById('lastKmHint').classList.remove('hidden');
                document.getElementById('lastKmValue').textContent = kmMinimo;
            }
        } else {
            verificarChecklist(username);
        }
    } catch (error) {
        mostrarMensajeCentral('Error de red. Recarga la página.', 'wifi-off', 'gray');
    }
}

async function handleKmSubmit(e) {
    e.preventDefault();
    const kmInput = document.getElementById('kmInput').value;
    const username = localStorage.getItem('username');
    const kmBtnText = document.getElementById('kmBtnText');
    const kmBtnLoading = document.getElementById('kmBtnLoading');
    const kmSubmitBtn = document.getElementById('kmSubmitBtn');
    
    kmSubmitBtn.disabled = true;
    kmBtnText.textContent = 'Guardando...';
    kmBtnLoading.classList.remove('hidden');

    try {
        const formData = new FormData();
        formData.append('username', username);
        formData.append('km', kmInput); 
        
        const response = await fetch(`${CONFIG.API_URL}registrar_kilometraje.php`, {
            method: 'POST',
            body: formData
        });

        const textResponse = await response.text();
        let data;
        try { data = JSON.parse(textResponse); } catch(e) { data = { success: true }; }

        if (data.success || data.ok) {
            document.getElementById('kmModal').classList.add('hidden');
            document.body.classList.remove('modal-open');
            verificarChecklist(username);
        } else {
            alert('Error: ' + (data.message || data.error));
        }
    } catch (error) {
        alert('Error de conexión.');
    } finally {
        kmSubmitBtn.disabled = false;
        kmBtnText.textContent = 'Guardar Kilometraje';
        kmBtnLoading.classList.add('hidden');
    }
}

async function verificarChecklist(username) {
    try {
        const response = await fetch(`${CONFIG.API_URL}obtener_checklist_hoy.php?username=${encodeURIComponent(username)}`);
        const textResponse = await response.text();
        let data;
        try { data = JSON.parse(textResponse); } catch(e) { data = { ok: false }; }

        if (data.ok === true) {
            cargarPedidos();
        } else if (data.error === 'SIN_DATOS') {
            mostrarFormularioChecklist();
        } else {
            mostrarMensajeCentral('Error verificando inspección: ' + data.error, 'alert-triangle', 'red');
        }
    } catch(error) {
        mostrarMensajeCentral('Error de conexión al verificar inspección.', 'wifi-off', 'gray');
    }
}

function mostrarFormularioChecklist() {
    const container = document.getElementById('checklistContainer');
    container.innerHTML = ''; 

    checklistEstructura.forEach((seccionObj, s_idx) => {
        let sectionHtml = `
            <div class="bg-gray-50 border border-gray-200 rounded-2xl overflow-hidden shadow-sm">
                <div class="bg-gray-100 px-4 py-3 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="font-bold text-gray-800 text-sm">${seccionObj.seccion}</h3>
                    <div class="flex space-x-1">
                        <button type="button" onclick="marcarSeccion(${s_idx}, 'Bien')" class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs font-bold hover:bg-green-200">Bien</button>
                        <button type="button" onclick="marcarSeccion(${s_idx}, 'Mal')" class="px-2 py-1 bg-red-100 text-red-700 rounded text-xs font-bold hover:bg-red-200">Mal</button>
                        <button type="button" onclick="marcarSeccion(${s_idx}, 'N/A')" class="px-2 py-1 bg-gray-200 text-gray-700 rounded text-xs font-bold hover:bg-gray-300">N/A</button>
                    </div>
                </div>
                <div class="divide-y divide-gray-200">
        `;

        seccionObj.items.forEach((item, i_idx) => {
            const inputName = `item_${s_idx}_${i_idx}`;
            sectionHtml += `
                <div class="p-4 bg-white" id="container_${inputName}">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-2">
                        <p class="text-sm font-medium text-gray-700 mb-2 sm:mb-0 ${item.includes('(NN)**') ? 'text-blue-700 font-bold' : ''}">${item}</p>
                        <div class="flex w-full sm:w-auto space-x-2">
                            <input type="radio" name="${inputName}" id="${inputName}_bien" value="Bien" class="hidden radio-btn-bien" onchange="toggleObs('${inputName}')">
                            <label for="${inputName}_bien" class="flex-1 sm:flex-none text-center px-4 py-2 border border-gray-300 rounded-lg cursor-pointer text-sm font-medium text-gray-600 transition-colors">Bien</label>
                            
                            <input type="radio" name="${inputName}" id="${inputName}_mal" value="Mal" class="hidden radio-btn-mal" onchange="toggleObs('${inputName}')">
                            <label for="${inputName}_mal" class="flex-1 sm:flex-none text-center px-4 py-2 border border-gray-300 rounded-lg cursor-pointer text-sm font-medium text-gray-600 transition-colors">Mal</label>
                            
                            <input type="radio" name="${inputName}" id="${inputName}_na" value="N/A" class="hidden radio-btn-na" onchange="toggleObs('${inputName}')">
                            <label for="${inputName}_na" class="flex-1 sm:flex-none text-center px-4 py-2 border border-gray-300 rounded-lg cursor-pointer text-sm font-medium text-gray-600 transition-colors">N/A</label>
                        </div>
                    </div>
                    <div id="obs_${inputName}" class="hidden mt-3">
                        <textarea name="obs_${inputName}" rows="2" class="w-full text-sm p-3 border border-red-300 rounded-xl focus:ring-red-500 focus:border-red-500 bg-red-50" placeholder="Describe el problema aquí... (Obligatorio)"></textarea>
                    </div>
                </div>
            `;
        });
        
        sectionHtml += `</div></div>`;
        container.innerHTML += sectionHtml;
    });

    document.getElementById('checklistModal').classList.remove('hidden');
    document.body.classList.add('modal-open');
}

function toggleObs(inputName) {
    const radioMal = document.getElementById(`${inputName}_mal`);
    const obsContainer = document.getElementById(`obs_${inputName}`);
    if (radioMal && radioMal.checked) {
        obsContainer.classList.remove('hidden');
        document.querySelector(`[name="obs_${inputName}"]`).required = true;
    } else {
        obsContainer.classList.add('hidden');
        document.querySelector(`[name="obs_${inputName}"]`).required = false;
        document.querySelector(`[name="obs_${inputName}"]`).value = '';
    }
}

window.marcarSeccion = function(s_idx, valor) {
    const section = checklistEstructura[s_idx];
    section.items.forEach((_, i_idx) => {
        const inputName = `item_${s_idx}_${i_idx}`;
        const radioBien = document.getElementById(`${inputName}_bien`);
        const radioMal = document.getElementById(`${inputName}_mal`);
        const radioNa = document.getElementById(`${inputName}_na`);
        
        if (valor === 'Bien') radioBien.checked = true;
        if (valor === 'Mal') radioMal.checked = true;
        if (valor === 'N/A') radioNa.checked = true;
        
        toggleObs(inputName);
    });
}

async function handleChecklistSubmit(e) {
    e.preventDefault();
    const errorBox = document.getElementById('chkError');
    const errorText = document.getElementById('chkErrorText');
    errorBox.classList.add('hidden');

    let itemsPayload = [];
    let faltan = false;

    checklistEstructura.forEach((seccionObj, s_idx) => {
        seccionObj.items.forEach((itemTexto, i_idx) => {
            const inputName = `item_${s_idx}_${i_idx}`;
            const selected = document.querySelector(`input[name="${inputName}"]:checked`);
            
            if (!selected) {
                faltan = true;
            } else {
                let calificacion = selected.value;
                let observacion = document.querySelector(`[name="obs_${inputName}"]`).value.trim();
                
                if (calificacion === 'Mal' && observacion === '') {
                    faltan = true;
                }

                itemsPayload.push({
                    seccion: seccionObj.seccion,
                    item: itemTexto,
                    calificacion: calificacion,
                    observacion: observacion
                });
            }
        });
    });

    if (faltan) {
        errorText.textContent = "Por favor responde TODOS los ítems y agrega observaciones a los marcados como 'Mal'.";
        errorBox.classList.remove('hidden');
        return;
    }

    const username = localStorage.getItem('username');
    const btnText = document.getElementById('chkBtnText');
    const btnLoading = document.getElementById('chkBtnLoading');
    const submitBtn = document.getElementById('chkSubmitBtn');
    submitBtn.disabled = true;
    btnText.textContent = 'Enviando...';
    btnLoading.classList.remove('hidden');

    try {
        const payload = {
            username: username,
            items: itemsPayload
        };

        const response = await fetch(`${CONFIG.API_URL}guardar_checklist.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const data = await response.json();
        
        if (data.ok) {
            document.getElementById('checklistModal').classList.add('hidden');
            document.body.classList.remove('modal-open');
            cargarPedidos(); 
        } else {
            errorText.textContent = data.error || 'Error al guardar la inspección.';
            errorBox.classList.remove('hidden');
        }
    } catch (error) {
        errorText.textContent = 'Error de conexión.';
        errorBox.classList.remove('hidden');
    } finally {
        submitBtn.disabled = false;
        btnText.textContent = 'Enviar Inspección';
        btnLoading.classList.add('hidden');
    }
}

// -----------------------------------------------------
// FASE 3: GESTIÓN DE PEDIDOS Y ESTATUS
// -----------------------------------------------------

let currentFilter = 'activos';

function toggleSidebar(show) {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    if (show) {
        if (sidebar) sidebar.classList.add('sidebar-open');
        if (overlay) overlay.classList.add('sidebar-open');
        document.body.style.overflow = 'hidden';
    } else {
        if (sidebar) sidebar.classList.remove('sidebar-open');
        if (overlay) overlay.classList.remove('sidebar-open');
        document.body.style.overflow = '';
    }
}

async function cargarPedidos() {
    const loadingView = document.getElementById('loadingView');
    
    if (!pedidosData || pedidosData.length === 0) {
        showView('loadingView');
        loadingView.innerHTML = `
            <div class="flex flex-col items-center justify-center h-full w-full text-center">
                <i data-lucide="loader-2" class="w-12 h-12 mb-4 animate-spin text-orange-500"></i>
                <p class="text-gray-600 font-medium">Cargando tus pedidos activos...</p>
            </div>
        `;
        lucide.createIcons();
    } else {
        const headerTitle = document.getElementById('headerTitle');
        if (headerTitle) {
            const titleText = currentFilter === 'activos' ? 'Mis Pedidos' : 'Historial';
            headerTitle.innerHTML = titleText + ' <i data-lucide="loader-2" class="w-4 h-4 ml-2 animate-spin inline"></i>';
            lucide.createIcons();
        }
    }

    const username = localStorage.getItem('username');
    const sidebarUsername = document.getElementById('sidebarUsername');
    if (sidebarUsername && username) {
        sidebarUsername.textContent = username;
    }
    try {
        const response = await fetch(`${CONFIG.API_URL}Consultar.php?username=${encodeURIComponent(username)}&v2=1`);
        const textResponse = await response.text();
        
        let data;
        let pedidos = [];
        try {
            data = JSON.parse(textResponse);
            if (data.ok) {
                pedidos = data.pedidos || [];
                window.vehiculoData = data.vehiculo;
                localStorage.setItem('cached_pedidos', JSON.stringify(pedidos));
                localStorage.setItem('cached_vehiculo', JSON.stringify(data.vehiculo));
            } else {
                pedidos = data; // Fallback
                localStorage.setItem('cached_pedidos', JSON.stringify(pedidos));
            }
            updateConnectionBanner();
        } catch(e) {
            console.error("Error al parsear JSON:", textResponse);
            mostrarMensajeCentral('Error interno del servidor al consultar pedidos.', 'alert-circle', 'red');
            return;
        }

        if (!Array.isArray(pedidos)) {
            mostrarMensajeCentral('Formato de respuesta incorrecto del servidor.', 'alert-circle', 'red');
            return;
        }

        // Solo renderizar la lista de nuevo si estamos viendo la lista (no si estamos viendo el detalle de un pedido)
        const detailContainer = document.getElementById('pedidoDetail');
        if (detailContainer && detailContainer.classList.contains('hidden')) {
            renderListaPedidos(pedidos);
        } else {
            // Si estamos viendo un detalle, actualizamos los datos internamente pero no interrumpimos al usuario
            pedidosData = pedidos;
        }

        // Restaurar título del header
        const headerTitle = document.getElementById('headerTitle');
        if (headerTitle) {
            headerTitle.innerHTML = currentFilter === 'activos' ? 'Mis Pedidos' : 'Historial';
        }

    } catch(err) {
        console.error("Error de red al cargar pedidos, intentando desde caché local:", err);
        const cachedPedidosStr = localStorage.getItem('cached_pedidos');
        const cachedVehiculoStr = localStorage.getItem('cached_vehiculo');
        
        updateConnectionBanner();

        if (cachedPedidosStr) {
            const cachedPedidos = JSON.parse(cachedPedidosStr);
            window.vehiculoData = cachedVehiculoStr ? JSON.parse(cachedVehiculoStr) : null;
            
            const detailContainer = document.getElementById('pedidoDetail');
            if (detailContainer && detailContainer.classList.contains('hidden')) {
                renderListaPedidos(cachedPedidos);
            } else {
                pedidosData = cachedPedidos;
            }
            
            const headerTitle = document.getElementById('headerTitle');
            if (headerTitle) {
                headerTitle.innerHTML = currentFilter === 'activos' ? 'Mis Pedidos' : 'Historial';
            }
        } else {
            if (!pedidosData || pedidosData.length === 0) {
                mostrarMensajeCentral('Error de red al cargar pedidos.', 'wifi-off', 'gray');
            }
        }
    }
}

function renderListaPedidos(pedidos) {
    pedidosData = pedidos;
    const listContainer = document.getElementById('pedidosList');
    listContainer.innerHTML = '';
    
    const filteredPedidos = pedidos.filter(pedido => {
        const estado = pedido.ESTADO.toUpperCase();
        const isCompleted = estado === 'ENTREGADO' || estado === 'CANCELADO';
        return currentFilter === 'historial' ? isCompleted : !isCompleted;
    });

    if (filteredPedidos.length === 0) {
        const msg = currentFilter === 'historial' 
            ? 'No tienes pedidos en el historial.' 
            : '¡Todo al día! No tienes pedidos activos.';
        mostrarMensajeCentral(msg, currentFilter === 'historial' ? 'history' : 'check-circle', 'gray');
        return;
    }

    filteredPedidos.forEach((pedido, index) => {
        let bgEstado = 'bg-gray-100 text-gray-700 border-gray-500';
        if(pedido.ESTADO === 'Activo' || pedido.ESTADO === 'ACTIVO') bgEstado = 'bg-blue-100 text-blue-700 border-blue-500'; // Azul
        else if(pedido.ESTADO === 'En Ruta' || pedido.ESTADO === 'EN RUTA') bgEstado = 'bg-orange-100 text-orange-700 border-orange-500'; // Naranja
        else if(pedido.ESTADO === 'En Tienda' || pedido.ESTADO === 'EN TIENDA') bgEstado = 'bg-yellow-100 text-yellow-800 border-yellow-500'; // Amarillo
        else if(pedido.ESTADO === 'Entregado' || pedido.ESTADO === 'ENTREGADO') bgEstado = 'bg-green-100 text-green-700 border-green-500'; // Verde
        else if(pedido.ESTADO === 'Reprogramado' || pedido.ESTADO === 'REPROGRAMADO') bgEstado = 'bg-purple-100 text-purple-700 border-purple-500'; // Morado
        else if(pedido.ESTADO === 'Cancelado' || pedido.ESTADO === 'CANCELADO') bgEstado = 'bg-red-100 text-red-700 border-red-500'; // Rojo

        const borderColor = bgEstado.split(' ')[2]; 

        let badgeGrupo = '';
        if(pedido.grupo) {
            badgeGrupo = `<div class="bg-indigo-100 text-indigo-700 text-[10px] font-bold px-2 py-0.5 rounded-full ml-2 flex items-center shadow-sm"><i data-lucide="layers" class="w-3 h-3 mr-1"></i> Grupo: ${pedido.grupo.nombre} ${pedido.grupo.orden_entrega ? `(Parada ${pedido.grupo.orden_entrega})` : ''}</div>`;
        }

        const logoUrl = getSucursalLogo(pedido.SUCURSAL);

        const card = document.createElement('div');
        card.className = "bg-white rounded-2xl shadow-sm border border-gray-100 p-4 relative overflow-hidden transition-all hover:shadow-md cursor-pointer";
        card.onclick = () => verDetallePedido(index);
        
        card.innerHTML = `
            <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-white ${borderColor} border-l-4"></div>

            <!-- Fila superior: Factura + badge estado -->
            <div class="flex justify-between items-start pl-3 mb-3">
                <div class="min-w-0 flex-1 pr-2">
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Factura</span>
                        ${badgeGrupo}
                    </div>
                    <h3 class="text-lg font-black text-gray-800 leading-none mt-0.5 truncate">${pedido.FACTURA || 'S/N'}</h3>
                </div>
                <span class="shrink-0 px-2.5 py-1 rounded-full text-xs font-bold ${bgEstado.split(' ').slice(0,2).join(' ')} text-center leading-tight whitespace-nowrap shadow-sm">${pedido.ESTADO}</span>
            </div>

            <!-- Cuerpo: info a la izquierda, logo a la derecha -->
            <div class="pl-3 flex items-center gap-2">
                <div class="flex-1 min-w-0 space-y-1.5">
                    <div class="flex items-center">
                        <i data-lucide="user" class="w-4 h-4 mr-2 text-gray-400 shrink-0"></i>
                        <p class="text-sm text-gray-700 font-medium truncate">${pedido.NOMBRE_CLIENTE || 'Sin Cliente'}</p>
                    </div>
                    <div class="flex items-start">
                        <i data-lucide="map-pin" class="w-4 h-4 mr-2 text-gray-400 mt-0.5 shrink-0"></i>
                        <p class="text-sm text-gray-500 leading-tight" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">${pedido.DIRECCION || 'Sin Dirección'}</p>
                    </div>
                </div>
                <!-- Logo sucursal: tamaño fijo via style para garantizar responsive -->
                <div class="shrink-0 flex items-center justify-center bg-gray-50 rounded-xl border border-gray-100" style="width:100px;height:64px;padding:8px;">
                    <img src="${logoUrl}" alt="${pedido.SUCURSAL || 'GA'}" title="${pedido.SUCURSAL || ''}"
                         style="max-width:100%;max-height:100%;object-fit:contain;display:block;"
                         onerror="this.src='${LOGO_DEFAULT}'">
                </div>
            </div>

            <!-- Pie: sucursal + botón -->
            <div class="mt-3 pt-3 border-t border-gray-50 flex justify-between items-center">
                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide pl-3 truncate mr-2 flex-1">${pedido.SUCURSAL || ''}</p>
                <button class="shrink-0 text-orange-600 text-sm font-bold flex items-center bg-orange-50 px-3 py-1.5 rounded-lg">
                    Ver Detalles <i data-lucide="chevron-right" class="w-4 h-4 ml-1"></i>
                </button>
            </div>
        `;
        listContainer.appendChild(card);
    });

    lucide.createIcons();
    showView('pedidosList');
}

function renderPerfilView() {
    showView('perfilView');
    const headerTitle = document.getElementById('headerTitle');
    if (headerTitle) headerTitle.textContent = 'Mi Perfil';

    const username = localStorage.getItem('username');
    document.getElementById('perfilUsername').textContent = username || 'Chofer';

    const vehContainer = document.getElementById('perfilVehiculoContent');
    const obsContainer = document.getElementById('perfilObsContent');

    if (window.vehiculoData) {
        const v = window.vehiculoData;
        vehContainer.innerHTML = `
            <div class="flex justify-between mb-2 pb-2 border-b border-gray-100">
                <span class="text-gray-500">Placas:</span>
                <span class="font-bold text-gray-900">${v.placa || 'N/A'}</span>
            </div>
            <div class="flex justify-between mb-2 pb-2 border-b border-gray-100">
                <span class="text-gray-500">Modelo/Tipo:</span>
                <span class="font-bold text-gray-900">${v.tipo || 'N/A'}</span>
            </div>
            <div class="flex justify-between mb-2 pb-2 border-b border-gray-100">
                <span class="text-gray-500">Kilometraje:</span>
                <span class="font-bold text-gray-900">${v.Km_Actual ? parseInt(v.Km_Actual).toLocaleString() + ' km' : '0 km'}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Sucursal:</span>
                <span class="font-bold text-gray-900">${v.Sucursal || 'N/A'}</span>
            </div>
        `;
        
        if (v.ultima_observacion) {
            obsContainer.innerHTML = `
                <p class="text-gray-800">"${v.ultima_observacion}"</p>
                <p class="text-xs text-gray-400 mt-2 text-right">Fecha: ${v.fecha_observacion}</p>
            `;
        } else {
            obsContainer.innerHTML = `<p class="text-gray-400 text-center py-2">Sin observaciones registradas en checklist.</p>`;
        }
    } else {
        vehContainer.innerHTML = `<p class="text-gray-400 text-center py-2">No tienes un vehículo asignado actualmente.</p>`;
        obsContainer.innerHTML = `<p class="text-gray-400 text-center py-2">-</p>`;
    }
}

function formatHora(hora) {
    if(!hora) return '--:--';
    return hora.substring(0,5); // Corta HH:mm:ss a HH:mm
}

function verDetallePedido(index) {
    const pedido = pedidosData[index];
    const detailContainer = document.getElementById('pedidoDetail');
    
    let bgEstado = 'bg-gray-200 text-gray-700';
        if(pedido.ESTADO === 'Activo' || pedido.ESTADO === 'ACTIVO') bgEstado = 'bg-blue-100 text-blue-700';
        else if(pedido.ESTADO === 'En Ruta' || pedido.ESTADO === 'EN RUTA') bgEstado = 'bg-orange-100 text-orange-700';
        else if(pedido.ESTADO === 'En Tienda' || pedido.ESTADO === 'EN TIENDA') bgEstado = 'bg-yellow-100 text-yellow-800';
        else if(pedido.ESTADO === 'Entregado' || pedido.ESTADO === 'ENTREGADO') bgEstado = 'bg-green-100 text-green-700';
        else if(pedido.ESTADO === 'Reprogramado' || pedido.ESTADO === 'REPROGRAMADO') bgEstado = 'bg-purple-100 text-purple-700';
        else if(pedido.ESTADO === 'Cancelado' || pedido.ESTADO === 'CANCELADO') bgEstado = 'bg-red-100 text-red-700';

    let grupoHtml = '';
    if (pedido.grupo) {
        grupoHtml = `
            <div class="mt-4 pt-4 border-t border-gray-100">
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider flex items-center mb-2">
                    <i data-lucide="layers" class="w-4 h-4 mr-1.5 text-indigo-500"></i> Información de Grupo
                </h3>
                <div class="bg-indigo-50 rounded-2xl p-3 border border-indigo-100 text-sm text-indigo-800">
                    <p><b>Nombre:</b> ${pedido.grupo.nombre}</p>
                    <p><b>Parada:</b> ${pedido.grupo.orden_entrega || 'N/A'}</p>
                </div>
            </div>
        `;
    }

    detailContainer.innerHTML = `
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden flex flex-col h-auto min-h-full pb-10">
            <!-- Header Sticky -->
            <div class="bg-white p-4 border-b border-gray-100 flex items-center sticky top-0 z-10 shadow-sm">
                <button onclick="showView('pedidosList')" class="p-2 mr-3 bg-gray-100 rounded-full hover:bg-gray-200 text-gray-600 transition-colors">
                    <i data-lucide="arrow-left" class="w-5 h-5"></i>
                </button>
                <div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Factura</p>
                    <h2 class="text-xl font-black text-gray-800 leading-tight">${pedido.FACTURA || 'S/N'}</h2>
                </div>
                <div class="ml-auto">
                    <span class="px-3 py-1.5 rounded-full text-xs font-bold ${bgEstado} shadow-sm">${pedido.ESTADO}</span>
                </div>
            </div>
            
            <!-- Contenido Escroleable -->
            <div class="p-5 space-y-6 flex-1">
                
                <!-- ID y Sucursal con logo -->
                <div class="flex space-x-3">
                    <div class="flex-1 bg-gray-50 rounded-2xl p-3 border border-gray-100">
                        <p class="text-[10px] font-bold text-gray-400 uppercase">ID Pedido</p>
                        <p class="font-bold text-gray-800">#${pedido.ID}</p>
                    </div>
                    <div class="flex-[2] rounded-2xl p-3 border flex items-center justify-between" style="background:rgba(15,76,129,0.04); border-color:rgba(15,76,129,0.15);">
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Sucursal</p>
                            <p class="font-bold text-gray-800 text-sm leading-tight">${pedido.SUCURSAL || 'N/A'}</p>
                        </div>
                        <img src="${getSucursalLogo(pedido.SUCURSAL)}" alt="${pedido.SUCURSAL || 'GA'}" class="h-10 w-auto object-contain ml-2" onerror="this.src='${LOGO_DEFAULT}'">
                    </div>
                </div>

                <!-- Sección Cliente -->
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider flex items-center mb-2">
                        <i data-lucide="user" class="w-4 h-4 mr-1.5" style="color:#0f4c81;"></i> Datos del Cliente
                    </h3>
                    <div class="bg-blue-50/50 rounded-2xl p-4 border border-blue-100">
                        <p class="text-base font-bold text-gray-800 mb-2">${pedido.NOMBRE_CLIENTE || 'No especificado'}</p>
                        ${pedido.CONTACTO ? `<p class="text-sm text-gray-600 mb-2"><span class="font-medium">Contacto:</span> ${pedido.CONTACTO}</p>` : ''}
                        ${pedido.TELEFONO ? `
                        <a href="tel:${pedido.TELEFONO}" class="inline-flex items-center bg-white shadow-sm border px-3 py-2 rounded-xl font-bold text-sm mt-1" style="color:#0f4c81; border-color:rgba(15,76,129,0.3);">
                            <i data-lucide="phone" class="w-4 h-4 mr-2 fill-current"></i> Llamar al ${pedido.TELEFONO}
                        </a>
                        ` : '<p class="text-sm text-gray-500 italic">Sin teléfono registrado</p>'}
                    </div>
                </div>
                
                <!-- Sección Dirección -->
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider flex items-center mb-2">
                        <i data-lucide="map-pin" class="w-4 h-4 mr-1.5 text-red-500"></i> Dirección de Entrega
                    </h3>
                    <div class="bg-gray-50 rounded-2xl p-4 border border-gray-100">
                        <p class="text-sm font-medium text-gray-700 mb-4 leading-relaxed">${pedido.DIRECCION || 'No especificada'}</p>
                        <button class="w-full flex items-center justify-center text-sm font-bold text-orange-600 bg-orange-100 hover:bg-orange-200 px-4 py-3 rounded-xl active:scale-95 transition-all shadow-sm" onclick="abrirMapa('${pedido.Coord_Destino}', '${pedido.DIRECCION}')">
                            <i data-lucide="navigation" class="w-4 h-4 mr-2"></i> Iniciar Navegación en Maps
                        </button>
                    </div>
                </div>

                <!-- Fechas y Horarios (Nuevos campos agregados) -->
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider flex items-center mb-2">
                        <i data-lucide="calendar" class="w-4 h-4 mr-1.5 text-emerald-500"></i> Programación
                    </h3>
                    <div class="bg-gray-50 rounded-2xl p-4 border border-gray-100 text-sm space-y-2">
                        <div class="flex justify-between"><span class="text-gray-500">Fecha de entrega:</span> <span class="font-bold text-gray-800">${pedido.FECHA_ENTREGA_CLIENTE || 'Sin fecha'}</span></div>
                        <div class="flex justify-between"><span class="text-gray-500">Ventana Horaria:</span> <span class="font-bold text-gray-800">${formatHora(pedido.MIN_VENTANA_HORARIA_1)} - ${formatHora(pedido.MAX_VENTANA_HORARIA_1)}</span></div>
                        <div class="flex justify-between"><span class="text-gray-500">Vendedor:</span> <span class="font-bold text-gray-800">${pedido.VENDEDOR || 'N/A'}</span></div>
                    </div>
                    ${grupoHtml}
                </div>
                
                <!-- Evidencia Fotográfica -->
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider flex items-center mb-2">
                        <i data-lucide="camera" class="w-4 h-4 mr-1.5 text-pink-500"></i> Evidencia Fotográfica
                    </h3>
                    <div class="bg-pink-50 rounded-2xl p-4 border border-pink-100 flex flex-col items-center justify-center text-center shadow-sm relative overflow-hidden">
                        ${pedido.Ruta_Fotos ? `
                            <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center mb-2 shadow-inner">
                                <i data-lucide="check" class="w-8 h-8 text-green-600"></i>
                            </div>
                            <p class="text-green-800 font-bold mb-1">Evidencia Subida</p>
                        ` : `
                            <div class="w-16 h-16 rounded-full bg-white flex items-center justify-center mb-2 shadow-sm border border-pink-200">
                                <i data-lucide="camera-off" class="w-8 h-8 text-pink-300"></i>
                            </div>
                            <p class="text-pink-800 font-bold mb-1">Sin Evidencia</p>
                            <p class="text-xs text-pink-600 mb-3">Sube una foto de la entrega.</p>
                        `}
                        
                        <input type="file" id="fotoInput_${pedido.ID}" accept="image/*" capture="environment" class="hidden" onchange="subirFoto(${pedido.ID})">
                        <button onclick="document.getElementById('fotoInput_${pedido.ID}').click()" class="w-full mt-3 py-2.5 bg-white border border-pink-200 text-pink-700 rounded-xl font-bold hover:bg-pink-100 active:scale-95 transition-all flex items-center justify-center shadow-sm">
                            <i data-lucide="upload" class="w-4 h-4 mr-2"></i> ${pedido.Ruta_Fotos ? 'Reemplazar Foto' : 'Tomar / Subir Foto'}
                        </button>
                    </div>
                </div>
                
                <!-- Documento / Factura -->
                ${pedido.Ruta && pedido.Ruta !== 'No disponible' && pedido.Ruta.trim() !== '' ? `
                <div class="mb-4">
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider flex items-center mb-2">
                        <i data-lucide="file-text" class="w-4 h-4 mr-1.5 text-blue-900"></i> Documento / Factura
                    </h3>
                    <a href="${CONFIG.API_URL.replace('/app/', '/')}${pedido.Ruta}" target="_blank" class="bg-blue-50 border border-blue-100 rounded-2xl p-4 flex items-center justify-between shadow-sm active:scale-95 transition-all" style="background:rgba(15,76,129,0.04); border-color:rgba(15,76,129,0.15);">
                        <div class="flex items-center">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center mr-3" style="background-color:#0f4c81;">
                                <i data-lucide="download" class="w-5 h-5 text-white"></i>
                            </div>
                            <div>
                                <p class="font-bold text-sm" style="color:#0f4c81;">Descargar PDF</p>
                                <p class="text-xs" style="color:rgba(15,76,129,0.7);">Factura o remisión</p>
                            </div>
                        </div>
                        <i data-lucide="chevron-right" class="w-5 h-5" style="color:rgba(15,76,129,0.3);"></i>
                    </a>
                </div>
                ` : ''}
                
                <!-- Sección Comentarios -->
                ${pedido.COMENTARIOS ? `
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider flex items-center mb-2">
                        <i data-lucide="message-square" class="w-4 h-4 mr-1.5 text-amber-500"></i> Notas y Comentarios
                    </h3>
                    <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 shadow-sm">
                        <p class="text-sm font-medium text-amber-800 italic">${pedido.COMENTARIOS}</p>
                    </div>
                </div>
                ` : ''}
            </div>
            
            <!-- Acciones Fijas Inferiores -->
            <div class="bg-white p-5 border-t border-gray-100 shadow-[0_-10px_20px_rgba(0,0,0,0.05)] sticky bottom-0 z-20">
                <h3 class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-3 text-center">Actualizar Estado (Requiere GPS)</h3>
                
                <div class="space-y-3" id="accionesPedidoContenedor">
                    ${pedido.ESTADO === 'Activo' || pedido.ESTADO === 'Reprogramado' ? `
                        <button onclick="cambiarEstadoPedido(${pedido.ID}, 'En Ruta')" class="w-full py-4 bg-orange-500 text-white font-bold rounded-xl shadow-lg shadow-orange-500/30 hover:bg-orange-600 flex items-center justify-center active:scale-95 transition-all text-lg">
                            <i data-lucide="truck" class="w-6 h-6 mr-2"></i> Iniciar Ruta
                        </button>
                    ` : ''}
                    
                    ${pedido.ESTADO === 'En Ruta' ? `
                        <button onclick="cambiarEstadoPedido(${pedido.ID}, 'En Tienda')" class="w-full py-4 bg-yellow-400 text-yellow-900 font-bold rounded-xl shadow-lg shadow-yellow-400/30 hover:bg-yellow-500 flex items-center justify-center active:scale-95 transition-all text-lg">
                            <i data-lucide="store" class="w-6 h-6 mr-2"></i> Llegué a la Tienda
                        </button>
                    ` : ''}
                    
                    ${pedido.ESTADO === 'En Tienda' ? `
                        <button onclick="cambiarEstadoPedido(${pedido.ID}, 'Entregado')" class="w-full py-4 bg-green-600 text-white font-bold rounded-xl shadow-lg shadow-green-600/30 hover:bg-green-700 flex items-center justify-center active:scale-95 transition-all text-lg mb-3">
                            <i data-lucide="check-circle" class="w-6 h-6 mr-2"></i> Entregado Exitosamente
                        </button>
                        <button onclick="cambiarEstadoPedido(${pedido.ID}, 'Reprogramado')" class="w-full py-3.5 bg-purple-100 text-purple-700 font-bold rounded-xl hover:bg-purple-200 flex items-center justify-center active:scale-95 transition-all">
                            <i data-lucide="clock" class="w-5 h-5 mr-2"></i> Reprogramar (Incidencia)
                        </button>
                    ` : ''}
                    
                    ${pedido.ESTADO === 'Entregado' || pedido.ESTADO === 'Cancelado' ? `
                        <div class="text-center text-sm font-bold text-gray-400 bg-gray-50 py-3 rounded-xl border border-gray-100">
                            Pedido cerrado y procesado.
                        </div>
                    ` : ''}
                </div>
            </div>
        </div>
    `;
    
    lucide.createIcons();
    showView('pedidoDetail');
}

function abrirMapa(coords, direccion) {
    let url = '';
    if (coords && coords.includes(',')) {
        url = `https://www.google.com/maps/search/?api=1&query=${coords}`;
    } else {
        url = `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(direccion)}`;
    }
    window.open(url, '_blank');
}

// Lógica de Geocoding y Actualización de Estado
async function cambiarEstadoPedido(pedidoId, nuevoEstado) {
    if (!confirm(`¿Seguro que deseas actualizar el pedido a "${nuevoEstado}"?`)) {
        return;
    }

    const cont = document.getElementById('accionesPedidoContenedor');
    const originalHtml = cont.innerHTML;
    
    cont.innerHTML = `
        <div class="py-4 flex items-center justify-center text-orange-600 font-bold bg-orange-50 rounded-xl">
            <i data-lucide="loader-2" class="w-6 h-6 mr-2 animate-spin"></i> Obteniendo GPS y actualizando...
        </div>
    `;
    lucide.createIcons();

    // 1. Obtener GPS
    let coordenada = "0.000000,0.000000"; // Fallback
    try {
        coordenada = await obtenerUbicacionGps();
    } catch (err) {
        console.warn("No se pudo obtener el GPS, usando 0,0 por defecto.", err);
        // Mostrar aviso temporal pero continuar
        alert("Atención: No se pudo obtener la ubicación GPS (permiso denegado o apagado). Se registrará sin ubicación.");
    }

    // Si estamos sin conexión, guardar directamente en la cola local
    if (!navigator.onLine) {
        queueOfflineStatusUpdate(pedidoId, nuevoEstado, coordenada);
        return;
    }

    // 2. Enviar a servidor
    try {
        const formData = new FormData();
        formData.append('id', pedidoId);
        formData.append('estado', nuevoEstado);
        formData.append('coordenada', coordenada);
        formData.append('username', localStorage.getItem('username') || '');

        const res = await fetch(`${CONFIG.API_URL}actualizar_estado.php`, {
            method: 'POST',
            body: formData
        });

        const textResponse = await res.text();
        let jsonResponse;
        try {
            jsonResponse = JSON.parse(textResponse);
        } catch(e) {
            throw new Error("El servidor no devolvió JSON válido.");
        }

        if (jsonResponse.error) {
            throw new Error(jsonResponse.error);
        }

        // Sincronizar estado_factura_caja según el nuevo estado del pedido
        const facturaCajaMap = { 'EN RUTA': 5, 'ENTREGADO': 6 };
        if (facturaCajaMap[nuevoEstado] !== undefined) {
            // Llamada en paralelo, no bloqueante
            fetch(`${CONFIG.API_URL}actualizar_factura_caja_chofer.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ 
                    id_pedido: pedidoId, 
                    nuevo_estado: facturaCajaMap[nuevoEstado],
                    username: localStorage.getItem('username') || ''
                })
            }).catch(() => {}); // Silencioso si falla
        }

        // Si se actualizó, recargamos toda la lista
        cargarPedidos();
        
    } catch (error) {
        console.warn("Fallo en la red o servidor. Guardando actualización offline:", error);
        queueOfflineStatusUpdate(pedidoId, nuevoEstado, coordenada);
    }
}

// Función Promesa para el GPS
function obtenerUbicacionGps() {
    return new Promise((resolve, reject) => {
        if (!navigator.geolocation) {
            reject("Geolocation no soportado");
        } else {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    resolve(`${lat},${lng}`);
                },
                (error) => {
                    reject(error);
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }
            );
        }
    });
}

// Lógica para subida de fotos de evidencia
async function subirFoto(pedidoId) {
    const fileInput = document.getElementById(`fotoInput_${pedidoId}`);
    const file = fileInput.files[0];
    if (!file) return;

    // Cambiar la UI a modo cargando
    const btn = fileInput.nextElementSibling;
    const originalHtml = btn.innerHTML;
    btn.innerHTML = `<i data-lucide="loader-2" class="w-4 h-4 mr-2 animate-spin"></i> Subiendo...`;
    btn.disabled = true;
    lucide.createIcons();

    const formData = new FormData();
    formData.append('id', pedidoId);
    formData.append('image', file);

    try {
        const response = await fetch(`${CONFIG.API_URL}guardar_foto.php`, {
            method: 'POST',
            body: formData
        });

        const text = await response.text();
        if (text.includes("correctamente")) {
            // Recargar para que aparezca la palomita verde
            cargarPedidos(); 
        } else {
            alert("Error al subir foto: " + text);
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        }
    } catch(err) {
        alert("Error de red al subir foto.");
        btn.innerHTML = originalHtml;
        btn.disabled = false;
    }
}

// Iniciar auto-actualización cada 30 segundos
setInterval(() => {
    const isLoggedIn = localStorage.getItem('isLoggedIn');
    const kmModalHidden = document.getElementById('kmModal').classList.contains('hidden');
    const checklistModalHidden = document.getElementById('checklistModal').classList.contains('hidden');
    
    // Solo actualizar si está logueado y no está llenando un formulario bloqueante
    if (isLoggedIn === 'true' && kmModalHidden && checklistModalHidden) {
        cargarPedidos();
    }
}, 30000);

// --- Lógica del Sidebar ---
document.addEventListener('DOMContentLoaded', () => {
    const menuBtn = document.getElementById('menuBtn');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const navActivos = document.getElementById('navActivos');
    const navHistorial = document.getElementById('navHistorial');
    const navPerfil = document.getElementById('navPerfil');

    function toggleSidebar() {
        if (!sidebar || !sidebarOverlay) return;
        sidebar.classList.toggle('-translate-x-full');
        sidebarOverlay.classList.toggle('hidden');
    }

    if (menuBtn) menuBtn.addEventListener('click', toggleSidebar);
    if (sidebarOverlay) sidebarOverlay.addEventListener('click', toggleSidebar);

    function updateNavStyles(activeBtn) {
        [navActivos, navHistorial, navPerfil].forEach(btn => {
            if (!btn) return;
            if (btn === activeBtn) {
                btn.classList.add('text-orange-600', 'bg-orange-50');
                btn.classList.remove('text-gray-600', 'hover:bg-gray-50');
            } else {
                btn.classList.remove('text-orange-600', 'bg-orange-50');
                btn.classList.add('text-gray-600', 'hover:bg-gray-50');
            }
        });
    }

    if (navActivos) {
        navActivos.addEventListener('click', () => {
            currentFilter = 'activos';
            updateNavStyles(navActivos);
            document.getElementById('headerTitle').innerHTML = 'Mis Pedidos';
            toggleSidebar();
            if (pedidosData) renderListaPedidos(pedidosData);
        });
    }

    if (navHistorial) {
        navHistorial.addEventListener('click', () => {
            currentFilter = 'historial';
            updateNavStyles(navHistorial);
            document.getElementById('headerTitle').innerHTML = 'Historial';
            toggleSidebar();
            if (pedidosData) renderListaPedidos(pedidosData);
        });
    }

    if (navPerfil) {
        navPerfil.addEventListener('click', () => {
            updateNavStyles(navPerfil);
            toggleSidebar();
            renderPerfilView();
        });
    }
});

// =========================================================================
// --- SISTEMA AUTOMÁTICO DE SOPORTE SIN CONEXIÓN Y SIN DATOS (OFFLINE) ---
// =========================================================================

// Obtener la cola de sincronización de localStorage
function getOfflineQueue() {
    try {
        return JSON.parse(localStorage.getItem('offline_status_queue')) || [];
    } catch (e) {
        return [];
    }
}

// Guardar la cola en localStorage
function saveOfflineQueue(queue) {
    localStorage.setItem('offline_status_queue', JSON.stringify(queue));
}

// Agregar un cambio a la cola offline
function queueOfflineStatusUpdate(pedidoId, nuevoEstado, coordenada) {
    const queue = getOfflineQueue();
    // Evitar duplicar la misma transición del mismo pedido en la cola
    const exists = queue.some(item => String(item.id) === String(pedidoId) && item.estado === nuevoEstado);
    if (!exists) {
        queue.push({
            id: pedidoId,
            estado: nuevoEstado,
            coordenada: coordenada,
            timestamp: Date.now()
        });
        saveOfflineQueue(queue);
    }
    
    // Actualizar visualmente de inmediato en caché y pantalla local
    actualizarEstadoPedidoLocal(pedidoId, nuevoEstado);
    updateConnectionBanner();
}

// Actualizar el estado del pedido en caché para respuesta instantánea (Cero Lag)
function actualizarEstadoPedidoLocal(pedidoId, nuevoEstado) {
    if (Array.isArray(pedidosData)) {
        const idx = pedidosData.findIndex(p => String(p.ID) === String(pedidoId));
        if (idx !== -1) {
            pedidosData[idx].ESTADO = nuevoEstado;
            localStorage.setItem('cached_pedidos', JSON.stringify(pedidosData));
        }
    }
    
    // Volver a la lista de pedidos para simular recarga normal
    showView('pedidosList');
    renderListaPedidos(pedidosData || []);
}

// Actualizar visualmente el banner de conexión superior
function updateConnectionBanner() {
    const banner = document.getElementById('connectionBanner');
    const icon = document.getElementById('connectionIcon');
    const msg = document.getElementById('connectionMessage');
    const syncBtn = document.getElementById('connectionSyncBtn');
    
    if (!banner) return;
    
    const queue = getOfflineQueue();
    
    if (!navigator.onLine) {
        // Sin conexión a Internet
        banner.className = "bg-red-600 text-white px-4 py-2 flex items-center justify-between shadow-md transition-all duration-300";
        banner.classList.remove('hidden');
        if (icon) icon.innerHTML = '<i data-lucide="wifi-off" class="w-4 h-4"></i>';
        if (msg) msg.textContent = queue.length > 0 
            ? `Sin conexión (${queue.length} cambios pendientes de envío)` 
            : "Trabajando sin conexión / sin datos";
        if (syncBtn) syncBtn.classList.add('hidden');
    } else if (queue.length > 0) {
        // Conexión disponible pero con cambios en espera de ser enviados
        banner.className = "bg-amber-500 text-white px-4 py-2 flex items-center justify-between shadow-md transition-all duration-300 animate-pulse";
        banner.classList.remove('hidden');
        if (icon) icon.innerHTML = '<i data-lucide="cloud-lightning" class="w-4 h-4"></i>';
        if (msg) msg.textContent = `Tienes ${queue.length} cambios pendientes de sincronizar`;
        if (syncBtn) syncBtn.classList.remove('hidden');
    } else {
        // Todo sincronizado y en línea
        banner.classList.add('hidden');
    }
    
    if (typeof lucide !== 'undefined' && lucide.createIcons) {
        lucide.createIcons();
    }
}

// Sincronizar todos los cambios pendientes
let isSyncing = false;
async function syncOfflineQueue() {
    if (isSyncing) return;
    const queue = getOfflineQueue();
    if (queue.length === 0) {
        updateConnectionBanner();
        return;
    }
    
    if (!navigator.onLine) {
        updateConnectionBanner();
        return;
    }
    
    isSyncing = true;
    console.log(`Iniciando sincronización de ${queue.length} cambios pendientes...`);
    
    const banner = document.getElementById('connectionBanner');
    const msg = document.getElementById('connectionMessage');
    const syncBtn = document.getElementById('connectionSyncBtn');
    
    if (banner) banner.className = "text-white px-4 py-2 flex items-center justify-between shadow-md transition-all" ; banner.style.backgroundColor = '#0f4c81';
    if (msg) msg.textContent = "Sincronizando cambios con la central...";
    if (syncBtn) syncBtn.classList.add('hidden');
    
    const remainingQueue = [];
    let successCount = 0;
    
    for (const item of queue) {
        try {
            const formData = new FormData();
            formData.append('id', item.id);
            formData.append('estado', item.estado);
            formData.append('coordenada', item.coordenada);
            formData.append('username', localStorage.getItem('username') || '');

            const res = await fetch(`${CONFIG.API_URL}actualizar_estado.php`, {
                method: 'POST',
                body: formData
            });

            const textResponse = await res.text();
            let jsonResponse;
            try {
                jsonResponse = JSON.parse(textResponse);
            } catch(e) {
                throw new Error("Respuesta inválida");
            }

            if (jsonResponse.error) {
                throw new Error(jsonResponse.error);
            }

            // Sincronizar estado_factura_caja
            const facturaCajaMap = { 'EN RUTA': 5, 'ENTREGADO': 6 };
            if (facturaCajaMap[item.estado] !== undefined) {
                await fetch(`${CONFIG.API_URL}actualizar_factura_caja_chofer.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ 
                        id_pedido: item.id, 
                        nuevo_estado: facturaCajaMap[item.estado],
                        username: localStorage.getItem('username') || ''
                    })
                }).catch(() => {});
            }
            
            successCount++;
            console.log(`Pedido ${item.id} sincronizado exitosamente!`);
        } catch (err) {
            console.error(`Error de sincronización para el pedido ${item.id}:`, err);
            remainingQueue.push(item);
        }
    }
    
    saveOfflineQueue(remainingQueue);
    isSyncing = false;
    
    if (remainingQueue.length === 0) {
        // Sincronización exitosa total
        if (banner) {
            banner.className = "bg-green-600 text-white px-4 py-2 flex items-center justify-between shadow-md transition-all";
            if (msg) msg.textContent = `¡Sincronizado! Se subieron ${successCount} cambios con éxito`;
            setTimeout(() => {
                banner.classList.add('hidden');
            }, 4000);
        }
        cargarPedidos();
    } else {
        // Quedaron algunos pendientes
        updateConnectionBanner();
    }
}
