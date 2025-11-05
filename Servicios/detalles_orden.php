<?php
// Iniciar la sesión de forma segura
ini_set('session.cookie_httponly', true);
ini_set('session.cookie_secure', true);
session_name("GA");
session_start();


?>
<!DOCTYPE html>
<html>
<script>
    window.PEDIDOS_CHOFER = <?= json_encode($pedidosAll, JSON_UNESCAPED_UNICODE) ?>;
    window.EVENTOS_PEDIDOS = <?= json_encode($eventosAll, JSON_UNESCAPED_UNICODE) ?>;
</script>


<head>
    <title>Detalles Chofer</title>
    <meta charset="utf-8">
  <link rel="stylesheet" href="../styles.css">
    <link rel="icon" type="image/png" href="/Pedidos_GA/Img/Botones%20entregas/ICONOSPAG/ICONOPEDIDOS.png">
    <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dom-to-image/2.6.0/dom-to-image.min.js"></script>

    <style>
        /* ===== Estilos del perfil ===== */
        .driver-profile {
            position: relative;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .08);
            overflow: hidden;
            font-family: system-ui, Segoe UI, Roboto, Arial, sans-serif
        }

        .driver-cover {
            height: 180px;
            background: linear-gradient(135deg, #0a66c290, #0f172a);
            position: relative
        }

        .driver-avatar {
            position: absolute;
            left: 24px;
            bottom: -48px;
            width: 96px;
            height: 96px;
            border-radius: 999px;
            background: #e2e8f0;
            display: grid;
            place-items: center;
            font-size: 40px;
            font-weight: 700;
            color: #0f172a;
            border: 5px solid #fff;
            user-select: none
        }

        .driver-head {
            padding: 64px 24px 16px 24px;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
            justify-content: space-between
        }

        .driver-title {
            display: flex;
            gap: 14px;
            align-items: center
        }

        .driver-name {
            font-size: 22px;
            font-weight: 800;
            color: #0f172a;
            margin: 0
        }

        .driver-meta {
            font-size: 13px;
            color: #475569
        }

        .driver-actions a {
            display: inline-block;
            padding: 10px 14px;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            text-decoration: none;
            color: #0f172a;
            font-weight: 600
        }

        .driver-actions a+a {
            margin-left: 8px
        }

        .driver-actions a.primary {
            background: #0a66c2;
            color: #fff;
            border-color: #0a66c2
        }

        .driver-stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
            padding: 0 24px 14px
        }

        .stat {
            background: #f8fafc;
            border: 1px solid #eef2f7;
            border-radius: 12px;
            padding: 12px
        }

        .stat strong {
            display: block;
            font-size: 20px
        }

        .badges {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 8px
        }

        .badge {
            font-size: 12px;
            background: #eef2ff;
            color: #1e3a8a;
            border-radius: 999px;
            padding: 6px 10px;
            border: 1px solid #e5e7eb
        }

        .driver-tabs {
            display: flex;
            gap: 8px;
            padding: 8px 8px 0 8px;
            border-top: 1px solid #eef2f7;
            background: #fff;
            position: sticky;
            top: 0;
            z-index: 1
        }

        .driver-tabs button {
            flex: 1;
            padding: 12px;
            border: 0;
            background: #f1f5f9;
            border-radius: 10px;
            font-weight: 700;
            color: #334155;
            cursor: pointer
        }

        .driver-tabs button.active {
            background: #0a66c2;
            color: #fff
        }

        .tab {
            display: none;
            padding: 16px 20px 24px
        }

        .tab.active {
            display: block
        }

        .table {
            width: 100%;
            border-collapse: collapse
        }

        .table th,
        .table td {
            padding: 10px;
            border-bottom: 1px solid #eef2f7;
            text-align: left;
            font-size: 14px
        }

        .table th {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: #64748b
        }

        .kpi {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px
        }

        .kpi .card {
            background: #f8fafc;
            border: 1px solid #eef2f7;
            border-radius: 12px;
            padding: 12px
        }

        .vehis {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px
        }

        .vehi {
            border: 1px solid #eef2f7;
            background: #f8fafc;
            border-radius: 12px;
            padding: 12px
        }

        @media (max-width:900px) {
            .driver-stats {
                grid-template-columns: repeat(2, 1fr)
            }

            .vehis {
                grid-template-columns: repeat(2, 1fr)
            }
        }

        @media (max-width:600px) {
            .driver-stats {
                grid-template-columns: 1fr
            }

            .vehis {
                grid-template-columns: 1fr
            }

            .driver-actions {
                width: 100%
            }
        }

        /* Estados (si tus ESTADO vienen en mayúsculas, agrega variantes) */
        .badge.estado-Entregado,
        .badge.estado-ENTREGADO {
            background: #e7f9e7;
            color: #217a21;
            border-color: #cfeacf
        }

        .badge.estado-Pendiente,
        .badge.estado-PENDIENTE {
            background: #fff6d6;
            color: #8a6d00;
            border-color: #fde9a8
        }

        .badge.estado-Cancelado,
        .badge.estado-CANCELADO {
            background: #ffe4e6;
            color: #9f1239;
            border-color: #fecdd3
        }

        /* Gráfica */
        #barchart {
            min-height: 380px;
        }

        .driver-avatar {
            position: absolute;
            left: 24px;
            bottom: -48px;
            width: 96px;
            height: 96px;
            border-radius: 999px;
            background: #e2e8f0;
            display: grid;
            place-items: center;
            font-size: 40px;
            font-weight: 700;
            color: #0f172a;
            border: 5px solid #fff;
            user-select: none;
            cursor: pointer;
            /* <- clic para subir/cambiar */
        }

        .driver-avatar img {
            width: 100%;
            height: 100%;
            display: block;
            border-radius: 999px;
            object-fit: cover;
        }

        .driver-avatar:hover {
            box-shadow: 0 0 0 3px rgba(10, 102, 194, .25) inset;
        }
    </style>
</head>

<body>

 

    <div class="sidebar">
        <ul>



            <li class="corner-left-bottom">
                <a href="Servicios.php">
                    <img src="/Pedidos_GA/Img/Botones%20entregas/Usuario/VOLVAZ.png" alt="Volver" class="icono-Volver" style="max-width: 35%; height: auto;">
                </a>
            </li>
        </ul>
    </div>

  <div class="container" id="os-root" style="padding:12px 16px;">
    <div id="os-card"></div>
    <div id="os-sections" style="margin-top:12px"></div>
  </div>

  <style>
    .os-head{display:flex;gap:16px;align-items:flex-start;justify-content:space-between;background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:16px 20px;box-shadow:0 8px 20px rgba(15,23,42,.08);flex-wrap:wrap}
    .os-head-left{flex:1;min-width:300px}
    .os-head-right{display:flex;flex-direction:column;align-items:flex-end;gap:8px}
    .os-title{font-weight:800;font-size:20px;margin:0 0 8px 0;color:#0f172a}
    .os-vehicle-info{color:#475569;font-size:14px;line-height:1.6}
    .os-date-info{font-size:13px;color:#64748b;margin-top:6px}
    .pill{display:inline-flex;align-items:center;gap:.35rem;background:#eef2ff;color:#1e3a8a;border:1px solid #e5e7eb;border-radius:999px;padding:.35rem .75rem;font-weight:700;font-size:13px}
    .pill.ok{background:#e7f9e7;color:#217a21;border-color:#cfeacf}
    .pill.warn{background:#fff6d6;color:#8a6d00;border-color:#fde9a8}
    .pill.dang{background:#ffe4e6;color:#9f1239;border-color:#fecdd3}
    .pill.info{background:#e0f2fe;color:#0369a1;border-color:#bae6fd}
    .grid2{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin-top:12px}
    .card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:16px;box-shadow:0 8px 20px rgba(15,23,42,.06)}
    .card h3{margin:0 0 12px;font-size:16px;font-weight:700;color:#0f172a}
    .table{width:100%;border-collapse:collapse;font-size:14px}
    .table th,.table td{padding:10px 8px;border-bottom:1px solid #eef2f7;text-align:left}
    .table th{font-weight:600;color:#64748b;font-size:12px;text-transform:uppercase;letter-spacing:.03em}
    .table tbody tr:hover{background:#f8fafc}
    .actions{display:flex;gap:8px;flex-wrap:wrap;margin:12px 0;padding:12px 16px;background:#f8fafc;border-radius:12px}
    .btn{border:1px solid #cbd5e1;border-radius:10px;padding:.5rem 1rem;background:#fff;cursor:pointer;font-weight:600;font-size:14px;transition:all .2s}
    .btn:hover:not(:disabled){background:#f1f5f9;border-color:#94a3b8}
    .btn:disabled{opacity:.5;cursor:not-allowed}
    .btn.primary{background:#0a66c2;border-color:#0a66c2;color:#fff}
    .btn.primary:hover:not(:disabled){background:#085a9e}

    /* Modal confirmación */
    .modal-backdrop{position:fixed;inset:0;background:rgba(15,23,42,.4);display:none;align-items:center;justify-content:center;z-index:100;backdrop-filter:blur(2px)}
    .modal{background:#fff;border:1px solid #e5e7eb;border-radius:16px;box-shadow:0 20px 40px rgba(15,23,42,.2);padding:12px 16px;max-width:520px;width:92%}
    .modal header{display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #e5e7eb;margin:-12px -16px 12px;padding:12px 16px}
    .modal header h3{margin:0;font-size:16px;font-weight:700}
    .modal .close{background:#f1f5f9;border:1px solid #e5e7eb;border-radius:10px;padding:.25rem .55rem;cursor:pointer;font-weight:700}
    .modal .close:hover{background:#e2e8f0}
    .field{margin:12px 0}
    .field label{display:block;font-size:.85rem;color:#475569;margin-bottom:6px;font-weight:600}
    .field input[type="text"]{width:100%;border:1px solid #cbd5e1;border-radius:10px;padding:.6rem;background:#fff;font-size:14px}
    .field input[type="checkbox"]{margin-right:6px}

    @media (max-width:768px){
      .os-head{flex-direction:column;align-items:stretch}
      .os-head-right{align-items:flex-start}
      .grid2{grid-template-columns:1fr}
    }
  </style>

  <script>
    (function(){
      const API_URL = 'api_mantto.php';
      const params = new URLSearchParams(window.location.search);
      const ID = Number(params.get('id')||0);

      const card = document.getElementById('os-card');
      const secs = document.getElementById('os-sections');
      const fmt = n => new Intl.NumberFormat('es-MX').format(n ?? 0);
      const human = s => ({Pendiente:'Pendiente',Programado:'Programado',EnTaller:'En Taller',Completado:'Completado'})[s]||s;
      const pillClass = s => s==='Completado'?'pill ok':(s==='Programado'?'pill warn':(s==='EnTaller'?'pill':'pill dang'));

      if(!ID){ card.innerHTML = '<div class="card">ID inválido</div>'; return; }

      async function getOrder(){
        const r = await fetch(`${API_URL}?action=order&id=${ID}`,{credentials:'same-origin'});
        return r.json();
      }
      async function post(action,data){
        const r = await fetch(`${API_URL}?action=${encodeURIComponent(action)}`,{
          method:'POST',headers:{'Content-Type':'application/json'},credentials:'same-origin',body:JSON.stringify(data)
        });
        return r.json();
      }

      function render(d){
        const o = d.order||{}; const mats = d.materiales||[]; const movs = d.movimientos||[];
        const curStatus = o.status || 'Pendiente';

        // Determinar qué botones mostrar según el estatus actual
        const statusOrder = { 'Pendiente':0,'Programado':1,'EnTaller':2,'Completado':3 };
        const curIdx = statusOrder[curStatus] ?? 0;

        card.innerHTML = `
          <div class="os-head">
            <div class="os-head-left">
              <h2 class="os-title">OS #${o.id} · ${o.servicio||''}</h2>
              <div class="os-vehicle-info">
                <div><strong>Vehículo:</strong> ${o.placa||''} · ${o.tipo||''}</div>
                <div><strong>Kilometraje:</strong> ${fmt(o.km)} km</div>
              </div>
            </div>
            <div class="os-head-right">
              <div class="${pillClass(curStatus)}">${human(curStatus)}</div>
              <div class="os-date-info">
                <div>Creado: ${o.fecha||''}</div>
                ${o.fecha_programada?`<div>Programado: <strong id="prog-label">${o.fecha_programada}</strong></div>`:''}
              </div>
              ${curStatus==='Programado'?`<button class="btn" id="btn-edit-date" style="margin-top:4px">Cambiar fecha</button>`:''}
            </div>
          </div>
          <div class="actions">
            ${curIdx < 1 ? `<button class="btn" data-to="Programado">⏳ Marcar como Programado</button>` : ''}
            ${curIdx < 2 ? `<button class="btn" data-to="EnTaller">🛠 Pasar a Taller</button>` : ''}
            ${curIdx < 3 ? `<button class="btn primary" data-to="Completado">✅ Marcar como Completado</button>` : ''}
            ${curIdx === 3 ? `<div style="padding:.5rem 1rem;color:#217a21;font-weight:600">✅ Esta orden está completada</div>` : ''}
          </div>
        `;

        secs.innerHTML = `
          <div class="grid2">
            <div class="card">
              <h3 style="margin:0 0 8px">Materiales requeridos</h3>
              <table class="table">
                <thead><tr><th>Insumo</th><th>Cant.</th><th>Stock</th></tr></thead>
                <tbody>
                  ${mats.length? mats.map(m=>`<tr><td>${m.nombre}${m.marca?(' · '+m.marca):''}${m.modelo?(' · '+m.modelo):''}</td><td>${fmt(m.cantidad)}</td><td>${fmt(m.stock)}</td></tr>`).join('') : '<tr><td colspan="3">Sin materiales</td></tr>'}
                </tbody>
              </table>
            </div>
            <div class="card">
              <h3 style="margin:0 0 8px">Movimientos de inventario</h3>
              <table class="table">
                <thead><tr><th>Insumo</th><th>Tipo</th><th>Cantidad</th><th>Ref</th></tr></thead>
                <tbody>
                  ${movs.length? movs.map(v=>`<tr><td>${v.id_inventario}</td><td>${v.tipo}</td><td>${fmt(v.cantidad)}</td><td>${v.referencia}</td></tr>`).join('') : '<tr><td colspan="4">Sin movimientos</td></tr>'}
                </tbody>
              </table>
            </div>
            <div class="card">
              <h3 style="margin:0 0 8px">Notas del servicio</h3>
              <div style="white-space:pre-wrap; color:#334155;">${(o.notas||'').trim()||'Sin notas'}</div>
            </div>
          </div>
          <div class="card" style="margin-top:12px">
            <h3 style="margin:0 0 8px">Historial de cambios</h3>
            <table class="table">
              <thead><tr><th>Fecha</th><th>De</th><th>A</th><th>Usuario</th><th>Comentario</th></tr></thead>
              <tbody>
                ${(d.historial||[]).length? d.historial.map(h=>`<tr><td>${h.hecho_en}</td><td>${h.de||''}</td><td>${h.a}</td><td>${h.usuario||''}</td><td>${h.comentario||''}</td></tr>`).join('') : '<tr><td colspan="5">Sin historial</td></tr>'}
              </tbody>
            </table>
          </div>
        `;

        // Modal confirmación (reutilizable)
        let modal = document.getElementById('confirm-modal');
        if (!modal) {
          const shell = document.createElement('div');
          shell.innerHTML = `
            <div class="modal-backdrop" id="confirm-modal">
              <div class="modal" role="dialog" aria-modal="true" aria-labelledby="confirm-title">
                <header>
                  <h3 id="confirm-title">Confirmar cambio de estatus</h3>
                  <button class="close" data-x>&times;</button>
                </header>
                <div class="grid">
                  <div class="grid-1" style="grid-column:1/-1">
                    <p id="confirm-msg" style="margin:0;color:#475569"></p>
                  </div>
                  <div class="field grid-1" style="grid-column:1/-1;display:none" id="confirm-date-wrap">
                    <label for="confirm-date">Fecha programada</label>
                    <input type="text" id="confirm-date" placeholder="YYYY-MM-DD" />
                  </div>
                  <div class="field grid-1" style="grid-column:1/-1;display:none" id="confirm-reset-wrap">
                    <label><input type="checkbox" id="confirm-reset"> Reiniciar Km_actual a 0</label>
                  </div>
                  <div class="actions" style="grid-column:1/-1">
                    <button class="btn" data-cancel>Cancelar</button>
                    <button class="btn primary" data-ok>Confirmar</button>
                  </div>
                </div>
              </div>
            </div>`;
          document.body.appendChild(shell.firstElementChild);
          modal = document.getElementById('confirm-modal');
        }
        const modalRefs = {
          wrap: modal,
          msg: modal.querySelector('#confirm-msg'),
          dateWrap: modal.querySelector('#confirm-date-wrap'),
          date: modal.querySelector('#confirm-date'),
          resetWrap: modal.querySelector('#confirm-reset-wrap'),
          reset: modal.querySelector('#confirm-reset'),
          ok: modal.querySelector('[data-ok]'),
          cancel: modal.querySelector('[data-cancel]'),
          close: modal.querySelector('[data-x]')
        };
        function openConfirm(to, cb){
          modalRefs.msg.textContent = `¿Confirmas mover la orden ${o.id} a “${human(to)}”? Esta operación no se podrá revertir.`;
          const needDate = (to==='Programado');
          modalRefs.dateWrap.style.display = needDate ? 'block' : 'none';
          const needReset = (to==='Completado');
          modalRefs.resetWrap.style.display = needReset ? 'block' : 'none';
          if (!needReset && modalRefs.reset) modalRefs.reset.checked = false;
          const today = new Date().toISOString().slice(0,10);
          modalRefs.date.value = (o.fecha_programada || today);
          if (window.jQuery && typeof jQuery.fn.datepicker==='function') {
            jQuery(modalRefs.date).datepicker('destroy').datepicker({ dateFormat:'yy-mm-dd' });
          }
          function close(){ modalRefs.wrap.style.display='none'; cleanup(); }
          function cleanup(){
            modalRefs.ok.removeEventListener('click', onOk);
            modalRefs.cancel.removeEventListener('click', close);
            modalRefs.close.removeEventListener('click', close);
            modalRefs.wrap.removeEventListener('click', onBack);
          }
          function onBack(e){ if (e.target===modalRefs.wrap) close(); }
          function onOk(){
            const payload = { id:o.id, estatus: to };
            if (to==='Programado') {
              const val = (modalRefs.date.value||'').slice(0,10);
              const today = new Date().toISOString().slice(0,10);
              if (!val) { alert('La fecha programada es requerida'); return; }
              if (val < today) { alert('No se puede programar antes de hoy'); return; }
              payload.fecha_programada = val;
            }
            if (to==='Completado' && modalRefs.reset && modalRefs.reset.checked) { payload.reset_km = true; }
            close(); cb(payload);
          }
          modalRefs.ok.addEventListener('click', onOk);
          modalRefs.cancel.addEventListener('click', close);
          modalRefs.close.addEventListener('click', close);
          modalRefs.wrap.addEventListener('click', onBack);
          modalRefs.wrap.style.display='flex';
        }

        // Botón para editar sólo fecha programada
        const btnEdit = card.querySelector('#btn-edit-date');
        if (btnEdit) btnEdit.addEventListener('click', ()=> {
          if ((o.status||'Pendiente') !== 'Programado') { alert('Solo puedes reprogramar cuando la orden está en Programado'); return; }
          openConfirm('Programado', async (payload)=>{
            const res = await post('reschedule', { id:o.id, fecha_programada: payload.fecha_programada });
            if (!res.ok) { alert(res.msg||'No se pudo actualizar'); return; }
            const fresh = await getOrder(); render(fresh);
          });
        });

        // Acciones de estatus
        card.querySelectorAll('button[data-to]').forEach(btn=>{
          btn.addEventListener('click', async ()=>{
            const to = btn.getAttribute('data-to');
            const statusOrder = { 'Pendiente':0,'Programado':1,'EnTaller':2,'Completado':3 };
            const curS = curStatus;
            const curI = statusOrder[curS] ?? 0;
            const toI = statusOrder[to] ?? 0;

            // Validación estricta: NO retroceder
            if (toI < curI) {
              alert('❌ No puedes retroceder el estatus de una orden de servicio');
              return;
            }

            // Validación: no saltar pasos (excepto desde Pendiente se puede ir directo a EnTaller o Completado)
            if (toI > curI + 1 && curS !== 'Pendiente') {
              alert('⚠️ Debes seguir la secuencia de estados en orden');
              return;
            }

            // Si ya está Completado, no permitir cambios
            if (curS === 'Completado') {
              alert('✅ Esta orden ya está completada y no puede ser modificada');
              return;
            }

            openConfirm(to, async (payload)=>{
              const res = await post('update_status', payload);
              if(!res.ok){ alert(res.msg||'No se pudo actualizar'); return; }
              const fresh = await getOrder(); render(fresh);
            });
          });
        });
      }

      getOrder().then(res=>{ if(res.ok) render(res); else card.innerHTML='<div class="card">No encontrado</div>'; })
                .catch(()=> card.innerHTML='<div class="card">Error de red</div>');
    })();
  </script>


</body>

</html>
