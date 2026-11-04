/**
 * Módulo de Incidencias en Escritorio (Pedidos_GA)
 * Permite a los vendedores consultar y registrar incidencias operativas desde la tabla principal.
 */

function svgIconJS(name, size = 14) {
    const paths = {
        'package': '<path d="M16.5 9.4 7.55 4.24"/><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.29 7 12 12 20.71 7"/><line x1="12" y1="22" x2="12" y2="12"/>',
        'plus': '<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>',
        'alert': '<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
        'refresh': '<polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>',
        'ban': '<circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/>',
        'calendar': '<rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>',
        'tool': '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>',
        'cloud-rain': '<line x1="16" y1="13" x2="16" y2="21"/><line x1="8" y1="13" x2="8" y2="21"/><line x1="12" y1="15" x2="12" y2="23"/><path d="M20 16.58A5 5 0 0 0 18 7h-1.26A8 8 0 1 0 4 15.25"/>',
        'user': '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'truck': '<rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>',
        'clock': '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
        'paperclip': '<path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"/>',
        'image': '<rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/>',
        'file-text': '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>',
        'video': '<polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/>',
        'check': '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>'
    };
    const p = paths[name] || paths['alert'];
    return `<svg width="${size}" height="${size}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-0.15em;flex-shrink:0;display:inline-block;">${p}</svg>`;
}

function obtenerEstiloMotivoJS(motivo) {
    switch (motivo) {
        case 'Faltante':
            return { bg: '#fee2e2', color: '#991b1b', border: '#fca5a5', icono: svgIconJS('package', 12) };
        case 'Sobrante':
            return { bg: '#ede9fe', color: '#5b21b6', border: '#c4b5fd', icono: svgIconJS('plus', 12) };
        case 'Error de entrega':
            return { bg: '#ffedd5', color: '#9a3412', border: '#fdba74', icono: svgIconJS('alert', 12) };
        case 'Producto erróneo':
            return { bg: '#ffedd5', color: '#9a3412', border: '#fdba74', icono: svgIconJS('refresh', 12) };
        case 'Cliente no recibe pedido':
            return { bg: '#ffe4e6', color: '#9f1239', border: '#fda4af', icono: svgIconJS('ban', 12) };
        case 'Cliente reprograma pedido':
            return { bg: '#e0f2fe', color: '#075985', border: '#7dd3fc', icono: svgIconJS('calendar', 12) };
        case 'Cliente no cuenta con maniobra para descarga':
            return { bg: '#fef3c7', color: '#92400e', border: '#fcd34d', icono: svgIconJS('tool', 12) };
        case 'Cuestiones climáticas':
            return { bg: '#cffafe', color: '#155e75', border: '#67e8f9', icono: svgIconJS('cloud-rain', 12) };
        default:
            return { bg: '#f1f5f9', color: '#334155', border: '#cbd5e1', icono: svgIconJS('alert', 12) };
    }
}

async function abrirModalIncidenciasDesktop(pedidoId, factura) {
    if (typeof Swal === 'undefined') {
        window.location.href = `Inicio.php?id=${pedidoId}#seccion-incidencias`;
        return;
    }

    Swal.fire({
        title: 'Cargando incidencias...',
        text: 'Obteniendo historial de incidencias del pedido...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    try {
        const response = await fetch(`/Pedidos_GA/App/obtener_incidencias.php?pedido_id=${pedidoId}`);
        const data = await response.json();

        if (!data.ok) {
            throw new Error(data.error || 'Error al obtener incidencias');
        }

        const incidencias = data.incidencias || [];
        const labelFactura = factura ? `Factura: ${factura}` : `Pedido #${pedidoId}`;

        if (incidencias.length === 0) {
            Swal.fire({
                title: `Incidencias - ${labelFactura}`,
                html: `
                    <div style="text-align: center; padding: 20px 10px;">
                        <div style="color: #16a34a; margin-bottom: 8px;">${svgIconJS('check', 38)}</div>
                        <div style="font-size: 15px; font-weight: bold; color: #334155; margin-bottom: 6px;">Sin incidencias reportadas</div>
                        <p style="font-size: 13px; color: #64748b; margin: 0;">Este pedido no tiene incidencias operativas registradas.</p>
                    </div>
                `,
                showCancelButton: true,
                showDenyButton: true,
                confirmButtonText: '+ Registrar Incidencia',
                confirmButtonColor: '#ea580c',
                denyButtonText: 'Ver Detalle',
                denyButtonColor: '#0f4c81',
                cancelButtonText: 'Cerrar',
                cancelButtonColor: '#94a3b8'
            }).then((result) => {
                if (result.isConfirmed) {
                    abrirFormularioNuevaIncidenciaQuick(pedidoId, factura);
                } else if (result.isDenied) {
                    window.location.href = `Inicio.php?id=${pedidoId}#seccion-incidencias`;
                }
            });
            return;
        }

        // Construir lista HTML de incidencias
        let htmlLista = `
            <div style="text-align: left; max-height: 380px; overflow-y: auto; padding-right: 6px;">
        `;

        incidencias.forEach(inc => {
            const estilo = obtenerEstiloMotivoJS(inc.motivo);
            const esVendedor = (inc.chofer && (inc.chofer.toLowerCase().includes('vendedor') || inc.chofer.toLowerCase().includes('venta')));
            const iconoRol = esVendedor ? svgIconJS('user', 12) : svgIconJS('truck', 12);
            const autorTxt = inc.chofer ? inc.chofer : (esVendedor ? 'Ventas' : 'Chofer en ruta');
            const fechaTxt = inc.fecha_formato || inc.fecha_registro;

            let adjuntoHtml = '';
            if (inc.archivo_ruta) {
                const cleanPath = inc.archivo_ruta.replace(/^[.\/\\]+/, '').replace(/\\/g, '/');
                const url = '/Pedidos_GA/' + cleanPath;
                const fallbackUrl = 'https://pedidos.grupoascencio.com.mx/Pedidos_GA/' + cleanPath;
                const nombre = inc.archivo_nombre || 'Archivo adjunto';
                const esImg = inc.archivo_tipo === 'imagen' || /\.(jpg|jpeg|png|webp|gif|bmp|jfif|heic)$/i.test(inc.archivo_ruta);
                const esPdf = inc.archivo_tipo === 'pdf' || /\.pdf$/i.test(inc.archivo_ruta);
                const esVid = inc.archivo_tipo === 'video' || /\.(mp4|mov|avi|webm)$/i.test(inc.archivo_ruta);

                let badgeIcon = svgIconJS('paperclip', 13);
                let btnTxt = 'Ver Documento';
                let tipoModal = 'otro';
                if (esImg) { badgeIcon = svgIconJS('image', 13); btnTxt = 'Ver Fotografía'; tipoModal = 'imagen'; }
                else if (esPdf) { badgeIcon = svgIconJS('file-text', 13); btnTxt = 'Ver PDF'; tipoModal = 'pdf'; }
                else if (esVid) { badgeIcon = svgIconJS('video', 13); btnTxt = 'Ver Video'; tipoModal = 'video'; }

                const safeUrl = url.replace(/'/g, "\\'");
                const safeFallbackUrl = fallbackUrl.replace(/'/g, "\\'");
                const safeNombre = nombre.replace(/'/g, "\\'");
                const safeMotivo = (inc.motivo || 'Incidencia').replace(/'/g, "\\'");
                const safeFactura = (factura || '').replace(/'/g, "\\'");

                const thumbHtml = esImg
                    ? `<div style="margin-bottom: 6px;">
                           <img src="${url}" alt="${nombre}" onclick="verAdjuntoIncidenciaQuick(this.src, '${safeNombre}', '${tipoModal}', '${safeMotivo}', ${pedidoId}, '${safeFactura}')" onerror="if(this.src !== '${safeFallbackUrl}'){ this.src = '${safeFallbackUrl}'; }" style="max-width: 180px; max-height: 110px; border-radius: 8px; border: 1px solid #cbd5e1; cursor: pointer; display: block; object-fit: cover;">
                       </div>`
                    : '';

                adjuntoHtml = `
                    <div style="margin-top: 8px;">
                        ${thumbHtml}
                        <button type="button" onclick="verAdjuntoIncidenciaQuick('${safeUrl}', '${safeNombre}', '${tipoModal}', '${safeMotivo}', ${pedidoId}, '${safeFactura}')" style="display: inline-flex; align-items: center; gap: 4px; padding: 5px 11px; background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; border-radius: 6px; font-size: 11.5px; font-weight: 700; cursor: pointer;">
                            ${badgeIcon} ${btnTxt} (${nombre})
                        </button>
                    </div>
                `;
            }

            htmlLista += `
                <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 14px; margin-bottom: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 6px; margin-bottom: 6px; padding-bottom: 6px; border-bottom: 1px solid #f1f5f9;">
                        <span style="display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; background: ${estilo.bg}; color: ${estilo.color}; border: 1px solid ${estilo.border};">
                            ${estilo.icono} ${inc.motivo}
                        </span>
                        <div style="font-size: 11px; color: #64748b; display: flex; align-items: center; gap: 8px;">
                            <span style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-weight: 600; color: #1e293b; display: inline-flex; align-items: center; gap: 4px;">
                                ${iconoRol} ${autorTxt}
                            </span>
                            <span style="display: inline-flex; align-items: center; gap: 3px;">${svgIconJS('clock', 12)} ${fechaTxt}</span>
                        </div>
                    </div>
                    ${inc.comentarios ? `<div style="font-size: 13px; color: #334155; line-height: 1.4; background: #f8fafc; padding: 8px 10px; border-radius: 6px; border-left: 3px solid #005aa3; margin: 4px 0; white-space: pre-line;">${inc.comentarios}</div>` : ''}
                    ${adjuntoHtml}
                </div>
            `;
        });

        htmlLista += `</div>`;

        Swal.fire({
            title: `Incidencias (${incidencias.length}) - ${labelFactura}`,
            html: htmlLista,
            width: '620px',
            showCancelButton: true,
            showDenyButton: true,
            confirmButtonText: '+ Registrar Incidencia',
            confirmButtonColor: '#ea580c',
            denyButtonText: 'Ver Detalle',
            denyButtonColor: '#0f4c81',
            cancelButtonText: 'Cerrar',
            cancelButtonColor: '#94a3b8'
        }).then((result) => {
            if (result.isConfirmed) {
                abrirFormularioNuevaIncidenciaQuick(pedidoId, factura);
            } else if (result.isDenied) {
                window.location.href = `Inicio.php?id=${pedidoId}#seccion-incidencias`;
            }
        });

    } catch (err) {
        console.error('Error cargando incidencias:', err);
        Swal.fire({
            icon: 'error',
            title: 'Error al consultar',
            text: err.message || 'No se pudo cargar el historial de incidencias.'
        });
    }
}

function verAdjuntoIncidenciaQuick(url, nombre, tipo, motivo, pedidoId, factura) {
    let previewHtml = '';
    const fallbackUrl = url.startsWith('/Pedidos_GA/')
        ? ('https://pedidos.grupoascencio.com.mx' + url)
        : url;
    if (tipo === 'imagen') {
        previewHtml = `
            <div style="background: #0f172a; border-radius: 12px; padding: 12px; display: flex; justify-content: center; align-items: center; max-height: 62vh; overflow: auto;">
                <img src="${url}" alt="${nombre}" style="max-width: 100%; max-height: 58vh; border-radius: 8px;" onerror="if(this.src !== '${fallbackUrl}'){ this.src = '${fallbackUrl}'; }">
            </div>
        `;
    } else if (tipo === 'pdf') {
        previewHtml = `
            <iframe src="${url}" style="width: 100%; height: 60vh; border: 1px solid #cbd5e1; border-radius: 12px; background: #ffffff;"></iframe>
        `;
    } else if (tipo === 'video') {
        previewHtml = `
            <div style="background: #0f172a; border-radius: 12px; padding: 12px;">
                <video src="${url}" controls autoplay style="max-width: 100%; max-height: 58vh; border-radius: 8px;"></video>
            </div>
        `;
    } else {
        previewHtml = `
            <div style="padding: 30px 16px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; text-align: center;">
                <div style="color: #0f4c81; margin-bottom: 8px;">${svgIconJS('file-text', 40)}</div>
                <div style="font-size: 15px; font-weight: 700; color: #0f172a; margin-bottom: 4px;">${nombre}</div>
                <p style="font-size: 13px; color: #64748b; margin: 0;">Haz clic en "Descargar / Abrir" para consultar el documento.</p>
            </div>
        `;
    }

    Swal.fire({
        title: `${motivo} · ${nombre}`,
        html: previewHtml,
        width: '760px',
        showCancelButton: true,
        showDenyButton: true,
        confirmButtonText: '← Volver a Incidencias',
        confirmButtonColor: '#0f4c81',
        denyButtonText: 'Descargar / Abrir',
        denyButtonColor: '#ea580c',
        cancelButtonText: 'Cerrar',
        cancelButtonColor: '#94a3b8'
    }).then((result) => {
        if (result.isConfirmed) {
            abrirModalIncidenciasDesktop(pedidoId, factura);
        } else if (result.isDenied) {
            window.open(url, '_blank');
            abrirModalIncidenciasDesktop(pedidoId, factura);
        }
    });
}

function abrirFormularioNuevaIncidenciaQuick(pedidoId, factura) {
    const labelFactura = factura ? `Factura: ${factura}` : `Pedido #${pedidoId}`;

    Swal.fire({
        title: `Registrar Incidencia - ${labelFactura}`,
        html: `
            <div style="text-align: left; padding: 5px 0;">
                <div style="margin-bottom: 12px;">
                    <label style="display: block; font-size: 12px; font-weight: bold; color: #1e293b; margin-bottom: 4px;">Motivo de la Incidencia *</label>
                    <select id="quick_motivo" class="swal2-input" style="width: 100%; margin: 0; padding: 8px 10px; font-size: 13px; height: auto;">
                        <option value="">-- Selecciona el motivo --</option>
                        <option value="Faltante">Faltante</option>
                        <option value="Sobrante">Sobrante</option>
                        <option value="Error de entrega">Error de entrega</option>
                    </select>
                </div>
                <div style="margin-bottom: 12px;">
                    <label style="display: block; font-size: 12px; font-weight: bold; color: #1e293b; margin-bottom: 4px;">Comentarios adicionales</label>
                    <textarea id="quick_comentarios" class="swal2-textarea" style="width: 100%; margin: 0; padding: 8px 10px; font-size: 13px; height: 85px; box-sizing: border-box;" placeholder="Escribe una breve explicación de la incidencia..."></textarea>
                </div>
                <div style="margin-bottom: 5px;">
                    <label style="display: block; font-size: 12px; font-weight: bold; color: #1e293b; margin-bottom: 4px;">Evidencia o Documento Adjunto (Opcional)</label>
                    <input type="file" id="quick_archivo" accept=".jpg,.jpeg,.png,.webp,.gif,.bmp,.heic,.pdf,.doc,.docx,.xls,.xlsx,.txt,.csv,.ppt,.pptx,.mp4,.mov,.avi,.webm" style="width: 100%; padding: 8px; border: 1.5px dashed #cbd5e1; border-radius: 8px; background: #f8fafc; font-size: 12px; color: #334155; box-sizing: border-box;">
                    <div style="font-size: 11px; color: #64748b; margin-top: 4px;">Admite imágenes (JPG, PNG), documentos (PDF, Word, Excel, TXT) o video.</div>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Guardar Incidencia',
        confirmButtonColor: '#0f4c81',
        cancelButtonText: 'Cancelar',
        cancelButtonColor: '#94a3b8',
        focusConfirm: false,
        preConfirm: () => {
            const motivo = document.getElementById('quick_motivo').value.trim();
            const comentarios = document.getElementById('quick_comentarios').value.trim();
            const archivoInput = document.getElementById('quick_archivo');

            if (!motivo) {
                Swal.showValidationMessage('Por favor selecciona un motivo para la incidencia');
                return false;
            }

            const formData = new FormData();
            formData.append('pedido_id', pedidoId);
            formData.append('motivo', motivo);
            formData.append('comentarios', comentarios);

            if (archivoInput && archivoInput.files && archivoInput.files.length > 0) {
                formData.append('archivo', archivoInput.files[0]);
            }

            return formData;
        }
    }).then(async (result) => {
        if (result.isConfirmed && result.value) {
            Swal.fire({
                title: 'Guardando incidencia...',
                text: 'Registrando la incidencia en el sistema...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            try {
                const response = await fetch('/Pedidos_GA/App/guardar_incidencia.php', {
                    method: 'POST',
                    body: result.value
                });

                const res = await response.json();
                if (res.ok) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Incidencia registrada!',
                        text: 'La incidencia se guardó correctamente.',
                        confirmButtonColor: '#0f4c81'
                    }).then(() => {
                        if (typeof enviarFiltros === 'function') {
                            enviarFiltros();
                        }
                        // Refrescar modal de incidencias
                        abrirModalIncidenciasDesktop(pedidoId, factura);
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error al guardar',
                        text: res.error || 'Ocurrió un error al registrar la incidencia.'
                    });
                }
            } catch (err) {
                console.error('Error:', err);
                Swal.fire({
                    icon: 'error',
                    title: 'Error de conexión',
                    text: 'No se pudo comunicar con el servidor para guardar la incidencia.'
                });
            }
        }
    });
}