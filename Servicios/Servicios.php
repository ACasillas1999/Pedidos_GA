<?php
// Iniciar la sesión de forma segura
ini_set('session.cookie_httponly', true);
ini_set('session.cookie_secure', true);
session_name("GA");
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>

    
  <meta charset="utf-8">
  <title>Detalles Chofer</title>
  <link rel="stylesheet" href="../styles.css">
  <link rel="icon" type="image/png" href="/Pedidos_GA/Img/Botones%20entregas/ICONOSPAG/ICONOPEDIDOS.png">

  <script>
    window.PEDIDOS_CHOFER = <?= json_encode($pedidosAll ?? [], JSON_UNESCAPED_UNICODE) ?>;
    window.EVENTOS_PEDIDOS = <?= json_encode($eventosAll ?? [], JSON_UNESCAPED_UNICODE) ?>;
  </script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />

</head>

<body>
  <script>
    document.addEventListener("DOMContentLoaded", function () {
      var iconoAddChofer = document.querySelector(".icono-AddChofer");
      if (iconoAddChofer) {
        var imgNormalAddChoferes = "/Pedidos_GA/Img/Botones%20entregas/Choferes/ADDSERVMECNA.png";
        var imgHoverCAddhoferes  = "/Pedidos_GA/Img/Botones%20entregas/Choferes/ADDSERVMECBLANC.png";
        iconoAddChofer.addEventListener("mouseover", function(){ this.src = imgHoverCAddhoferes; });
        iconoAddChofer.addEventListener("mouseout",  function(){ this.src = imgNormalAddChoferes; });
      }

      var iconoVolver = document.querySelector(".icono-Volver");
      if (iconoVolver) {
        var imgNormalVolver = "/Pedidos_GA/Img/Botones%20entregas/Usuario/VOLVAZ.png";
        var imgHoverVolver  = "/Pedidos_GA/Img/Botones%20entregas/Usuario/VOLVNA.png";
        iconoVolver.addEventListener("mouseover", function(){ this.src = imgHoverVolver; });
        iconoVolver.addEventListener("mouseout",  function(){ this.src = imgNormalVolver; });
      }

      var iconoEstadisticas = document.querySelector(".icono-estadisticas");
      if (iconoEstadisticas) {
        var imgNormalEstadisticas = "/Pedidos_GA/Img/Botones%20entregas/Pedidos_GA/ESTNA2.png";
        var imgHoverEstadisticas  = "/Pedidos_GA/Img/Botones%20entregas/Pedidos_GA/ESTBL2.png";
        iconoEstadisticas.addEventListener("mouseover", function(){ this.src = imgHoverEstadisticas; });
        iconoEstadisticas.addEventListener("mouseout",  function(){ this.src = imgNormalEstadisticas; });
      }
    });
  </script>

  <div class="sidebar">
    <ul>
      <li><a href="inventario.php"><i ></i> Inventario</a></li>
      <li><a href="agregar_servicio.php">Crear Servicios</a></li>
      <li class="corner-left-bottom">
        <a href="../vehiculos.php">
          <img src="/Pedidos_GA/Img/Botones%20entregas/Usuario/VOLVAZ.png" alt="Volver" class="icono-Volver" style="max-width:35%;height:auto;">
        </a>
      </li>
    </ul>
  </div>

  <div class="container">
    <div id="mantto-root"></div>

    <style>
      :root{
        --bg:#f6f8fb; --panel:#ffffff; --muted:#475569; --text:#0f172a;
        --accent:#005996; --accent-2:#ED6C24; --ok:#16a34a; --warn:#d97706; --danger:#dc2626;
        --border:#e5e7eb; --shadow:0 8px 20px rgba(15,23,42,.08); --radius:16px;
        --tint-pend:#FEF2F2; --tint-prog:#FFFBEB; --tint-taller:#FFF7ED; --tint-comp:#F0FDF4;
        --tint-pend-b:#FECACA; --tint-prog-b:#FDE68A; --tint-taller-b:#FED7AA; --tint-comp-b:#BBF7D0;
      }
      body{background:var(--bg); color:var(--text); font-family:system-ui,-apple-system,Segoe UI,Roboto,Inter,Arial,sans-serif}
      .mantto-shell{background:transparent}
      .mantto-tabs{display:flex; gap:.5rem; align-items:center; margin:0 0 1rem; flex-wrap:wrap}
      .mantto-tabs .btn{border:1px solid var(--border); background:#fff; color:var(--text); padding:.55rem .9rem; border-radius:12px; cursor:pointer; transition:.2s; font-weight:600; box-shadow:var(--shadow)}
      .mantto-tabs .btn[aria-pressed="true"]{background:linear-gradient(135deg,#e6f0ff,#ffffff); border-color:#c7d2fe}
      .mantto-tabs .meta{margin-left:auto; color:var(--muted); font-size:.9rem}
      .mantto-wrap{display:block}
      .kanban{display:grid; gap:1rem; grid-template-columns:repeat(4,minmax(260px,1fr))}
      .col{border:1px solid var(--border); border-radius:var(--radius); padding:.75rem; min-height:360px; box-shadow:var(--shadow); background:var(--panel)}
      .col h3{display:flex; align-items:center; justify-content:space-between; gap:.5rem; font-size:1rem; margin:0 0 .5rem}
      .badge{display:inline-flex; align-items:center; gap:.35rem; font-size:.8rem; color:#fff; padding:.2rem .5rem; border-radius:999px}
      .b-pendiente{background:var(--danger)} .b-programado{background:var(--warn)} .b-taller{background:var(--accent-2)} .b-completado{background:var(--ok)}
      .col-Pendiente{background:linear-gradient(180deg,var(--tint-pend),#fff)} .col-Pendiente .drop{background:#fff; border-color:var(--tint-pend-b)}
      .col-Programado{background:linear-gradient(180deg,var(--tint-prog),#fff)} .col-Programado .drop{background:#fff; border-color:var(--tint-prog-b)}
      .col-EnTaller{background:linear-gradient(180deg,var(--tint-taller),#fff)} .col-EnTaller .drop{ background:#fff; border-color: var(--tint-taller-b) }
      .col-Completado{background:linear-gradient(180deg,var(--tint-comp),#fff)} .col-Completado .drop{background:#fff; border-color:var(--tint-comp-b)}
      .drop{display:flex; flex-direction:column; gap:.6rem; min-height:290px; padding:.5rem; border:1px dashed var(--border); border-radius:12px; transition:.2s}
      .drop.over{box-shadow:inset 0 0 0 3px rgba(59,130,246,.15)}
      .card{background:#fff; border:1px solid var(--border); border-radius:14px; padding:.75rem .75rem; box-shadow:var(--shadow); cursor:grab; user-select:none; transition:transform .15s ease, box-shadow .2s}
      .card:active{cursor:grabbing; transform:scale(.99)}
      .card .top{display:flex; align-items:center; justify-content:space-between; margin-bottom:.25rem}
      .card .title{font-weight:700; letter-spacing:.2px}
      .card .meta{color:var(--muted); font-size:.9rem}
      .tags{display:flex; gap:.4rem; margin-top:.45rem; flex-wrap:wrap}
      .tag{font-size:.75rem; border:1px solid var(--border); color:var(--muted); padding:.15rem .45rem; border-radius:999px; background:#fff}
      .prio-alta{border-color:var(--danger); color:#b91c1c} .prio-media{border-color:var(--warn); color:#b45309} .prio-baja{border-color:var(--ok); color:#166534}
      .goto{margin-left:.5rem; font-size:.8rem; color:#1d4ed8; text-decoration:underline; cursor:pointer}
      .list-wrap{display:none}
      .list-tools{display:flex; gap:.5rem; margin:.25rem 0 .75rem}
      .list-tools input{background:#fff; border:1px solid var(--border); color:var(--text); border-radius:10px; padding:.5rem .7rem; width:260px; box-shadow:var(--shadow)}
      table{width:100%; border-collapse:separate; border-spacing:0 10px}
      thead th{font-size:.85rem; color:var(--muted); font-weight:700; text-align:left; padding:.4rem .6rem}
      tbody td{background:#fff; border:1px solid var(--border); padding:.6rem .7rem; color:var(--text)}
      tbody tr{box-shadow:var(--shadow)}
      tbody td:first-child{border-top-left-radius:12px; border-bottom-left-radius:12px}
      tbody td:last-child{border-top-right-radius:12px; border-bottom-right-radius:12px}
      .status-pill{display:inline-block; padding:.15rem .5rem; border-radius:999px; font-size:.75rem; font-weight:700}
      .status-pill.s-pend{background:#fff6d6; color:#8a6d00; border:1px solid #fde9a8}
      .status-pill.s-prog{background:#eef2ff; color:#1e3a8a; border:1px solid #e5e7eb}
      .status-pill.s-taller{background:#fff1e6; color:#c2410c; border:1px solid #fed7aa}
      .status-pill.s-comp{background:#e7f9e7; color:#217a21; border:1px solid #cfeacf}
      @media (max-width:1200px){ .kanban{grid-template-columns:repeat(3,minmax(260px,1fr))} }
      @media (max-width:900px){  .kanban{grid-template-columns:repeat(2,minmax(260px,1fr))} }
      @media (max-width:640px){  .kanban{grid-template-columns:1fr} .list-tools input{width:100%} }

      /* estilos mínimos del modal si tu styles.css no los tiene */
      .modal-backdrop{position:fixed; inset:0; background:rgba(0,0,0,.35); display:none; align-items:center; justify-content:center; z-index:50}
      .modal{background:#fff; border-radius:16px; width:min(720px, 92vw); box-shadow:var(--shadow); padding:12px 16px}
      .modal header{display:flex; align-items:center; justify-content:space-between; border-bottom:1px solid var(--border); margin:-12px -16px 12px; padding:12px 16px}
      .modal .close{background:#f1f5f9; border:1px solid var(--border); border-radius:10px; padding:.25rem .55rem; cursor:pointer}
      .grid{display:grid; grid-template-columns:repeat(2, 1fr); gap:12px}
      .grid-1{display:block}
      .field label{display:block; font-size:.85rem; color:#475569; margin-bottom:4px}
      .field input,.field select,.field textarea{width:100%; border:1px solid var(--border); border-radius:10px; padding:.5rem .6rem; background:#fff}
      .actions{display:flex; gap:.5rem; justify-content:flex-end; margin-top:10px}
      .btn-primary{background:#0a66c2; color:#fff; border:none; border-radius:10px; padding:.55rem .9rem; cursor:pointer}
      .btn-ghost{background:#fff; color:#0f172a; border:1px solid var(--border); border-radius:10px; padding:.55rem .9rem; cursor:pointer}
      .toast{position:fixed; right:16px; bottom:16px; background:#0f172a; color:#fff; padding:.6rem .9rem; border-radius:10px; box-shadow:var(--shadow); z-index:60}

      /* Mejora de estilos para la vista de lista */
      .table-wrap{background:#fff; border:1px solid var(--border); border-radius:12px; box-shadow:var(--shadow); overflow:auto}
      .list-wrap thead th{position:sticky; top:0; background:#f8fafc; z-index:1; border-bottom:1px solid var(--border)}
      .list-wrap tbody tr:hover td{background:#f1f5f9}
      .list-wrap td button{border:1px solid var(--border); background:#fff; border-radius:8px; padding:.25rem .5rem; cursor:pointer}
      .list-wrap td button:hover{background:#0a66c2; color:#fff; border-color:#0a66c2}


    </style>

    <!-- ===== Script del kanban + modal, envuelto en DOMContentLoaded ===== -->
    <script>
    document.addEventListener('DOMContentLoaded', function () {
      const API_URL = 'api_mantto.php';
      let COSTO_MINUTO = 0; // costo por minuto para MO auto

      const root = document.getElementById('mantto-root');
      if (!root) { console.error('No se encontro #mantto-root'); return; }
      root.classList.add('mantto-shell');

      const ESTATUS = [
        { key: 'Pendiente',  label: 'Pendiente',  badge: 'b-pendiente'  },
        { key: 'Programado', label: 'Programado', badge: 'b-programado' },
        { key: 'EnTaller',   label: 'En Taller',  badge: 'b-taller'     },
        { key: 'Completado', label: 'Completado', badge: 'b-completado' },
      ];
      const state = { tab:'board', items: [], draggingId:null, options:{vehiculos:[], servicios:[], inventario:[]} };

      const fmt = n => new Intl.NumberFormat('es-MX').format(n ?? 0);
      const prioClass = p => p==='Alta' ? 'prio-alta' : (p==='Media' ? 'prio-media' : 'prio-baja');
      const humanStatus = k => (ESTATUS.find(e => e.key===k)?.label || k);
      const toast = (msg, ok=true) => {
        const t = document.getElementById('toast');
        if (!t) { alert(msg); return; }            // <- guarda por si no existe el toast
        t.textContent = msg;
        t.style.background = ok ? '#0f172a' : '#9f1239';
        t.style.display = 'block';
        setTimeout(()=> t.style.display='none', 2500);
      };

      async function apiGet(action){ const r=await fetch(`${API_URL}?action=${encodeURIComponent(action)}`,{credentials:'same-origin'}); return r.json(); }
     // Reemplaza tu apiPost actual por esto:
async function apiPost(action, data) {
  const r = await fetch(`${API_URL}?action=${encodeURIComponent(action)}`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    credentials: 'same-origin',
    body: JSON.stringify(data)   //  ya NO mandamos "action" dentro
  });
  return r.json();
}


      root.innerHTML = `
        <div class="mantto-tabs">
          <button class="btn" data-tab="board" aria-pressed="true">Tablero (Drag & Drop)</button>
          <button class="btn" data-tab="list"  aria-pressed="false">Lista</button>
          <span class="meta" id="mantto-count"></span>
          <button class="btn" id="btn-add">Agregar servicio</button>
        </div>

        <div class="mantto-wrap">
          <section class="kanban" id="mantto-board"></section>

          <section class="list-wrap" id="mantto-list">
            <div class="list-tools">
              <input type="search" id="mantto-q" placeholder="Filtrar por placa, servicio, estatus...">
            </div>
            <div class="table-wrap">
              <table>
                <thead>
                  <tr>
                    <th>ID</th><th>Placa</th><th>Tipo</th><th>Servicio</th><th>Km</th><th>Fecha</th><th>Prog.</th><th>Estatus</th><th>Prioridad</th><th>Acciones</th>
                  </tr>
                </thead>
                <tbody id="mantto-tbody"></tbody>
              </table>
            </div>
          </section>
        </div>
      `;

      const el = {
        count:   root.querySelector('#mantto-count'),
        board:   root.querySelector('#mantto-board'),
        listWrap:root.querySelector('#mantto-list'),
        q:       root.querySelector('#mantto-q'),
        tabs:    Array.from(root.querySelectorAll('.mantto-tabs .btn[data-tab]')),
        tbody:   root.querySelector('#mantto-tbody'),
        btnAdd:  root.querySelector('#btn-add'),
      };

      // Modal de confirmación de cambio de estatus
      const confirmEl = (function(){
        const html = document.createElement('div');
        html.innerHTML = `
          <div class="modal-backdrop" id="confirm-modal" style="display:none">
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
                  <input type="date" id="confirm-date" />
                </div>
                <div class="actions" style="grid-column:1/-1">
                  <button class="btn-ghost" data-cancel>Cancelar</button>
                  <button class="btn-primary" data-ok>Confirmar</button>
                </div>
              </div>
            </div>
          </div>`;
        document.body.appendChild(html.firstElementChild);
        const wrap = document.getElementById('confirm-modal');
        return {
          wrap,
          title: wrap.querySelector('#confirm-title'),
          msg: wrap.querySelector('#confirm-msg'),
          dateWrap: wrap.querySelector('#confirm-date-wrap'),
          date: wrap.querySelector('#confirm-date'),
          btnOk: wrap.querySelector('[data-ok]'),
          btnCancel: wrap.querySelector('[data-cancel]'),
          btnX: wrap.querySelector('[data-x]')
        };
      })();

      function openConfirm({id, to, onConfirm}){
        const human = humanStatus(to);
        confirmEl.msg.textContent = `Confirmas mover la orden ${id} a ${human}? Esta operación no se podrá revertir.`;
        const needDate = (to === 'Programado');
        confirmEl.dateWrap.style.display = needDate ? 'block' : 'none';
        if (needDate) {
          const hoy = new Date().toISOString().slice(0,10);
          confirmEl.date.value = hoy;
        }
        function close(){ confirmEl.wrap.style.display='none'; cleanup(); }
        function cleanup(){
          confirmEl.btnOk.removeEventListener('click', onOk);
          confirmEl.btnCancel.removeEventListener('click', close);
          confirmEl.btnX.removeEventListener('click', close);
          confirmEl.wrap.removeEventListener('click', onBackdrop);
        }
        function onBackdrop(e){ if (e.target===confirmEl.wrap) close(); }
        function onOk(){
          const payload = { id, estatus: to };
          if (to==='Programado') {
            const val = (confirmEl.date.value||'').slice(0,10);
            const hoy = new Date().toISOString().slice(0,10);
            if (!val || val < hoy) { toast('La fecha no puede ser anterior a hoy', false); return; }
            payload.fecha_programada = val;
          }
          close();
          onConfirm(payload);
        }
        confirmEl.btnOk.addEventListener('click', onOk);
        confirmEl.btnCancel.addEventListener('click', close);
        confirmEl.btnX.addEventListener('click', close);
        confirmEl.wrap.addEventListener('click', onBackdrop);
        confirmEl.wrap.style.display='flex';
      }

      function card(it){
        const div = document.createElement('div');
        div.className = 'card';
        div.draggable = true;
        div.dataset.id = it.id;

        div.addEventListener('dragstart', e => {
          state.draggingId = it.id;
          div.classList.add('dragging');
          e.dataTransfer.setData('text/plain', String(it.id));
        });
        div.addEventListener('dragend', () => { state.draggingId = null; div.classList.remove('dragging'); });

        div.innerHTML = `
          <div class="top">
            <div class="title">ID ${it.id} - ${it.servicio || it.tipo || ''}</div>
            <div class="meta">${it.fecha_programada ? ('Prog: ' + it.fecha_programada) : (it.fecha || '')}</div>
          </div>
          <div class="meta">Placa: <b>${it.placa || ''}</b> - Suc: <b>${it.suc || ''}</b> - Km: <b>${fmt(it.km)}</b></div>
          <div class="tags">
            <span class="tag ${prioClass(it.prio || 'Media')}">Prio: ${it.prio || 'Media'}</span>
            <span class="tag">Estatus: ${humanStatus(it.status || 'Pendiente')}</span>
            ${ (Number(it.faltantes||0) > 0) ? '<span class="tag" style="background:#fee2e2; color:#991b1b; border:1px solid #fecaca">Faltantes</span>' : '' }
            ${it.fecha_programada ? `<span class="tag">Prog: ${it.fecha_programada}</span>` : ''}
            <span class="goto" data-goto="${it.id}" title="Ver detalle">ver detalle</span>
          </div>
        `;
        div.querySelector('.goto').addEventListener('click', () => {
          window.location.href = `/Pedidos_GA/Servicios/detalles_orden.php?id=${it.id}`;
        });
        return div;
      }

      function onDrop(e){
        e.preventDefault();
        this.classList.remove('over');
        const id   = Number(e.dataTransfer.getData('text/plain')) || state.draggingId;
        const dest = this.dataset.status;
        if (!id || !dest) return;
        const cur = state.items.find(x=> x.id===id);
        if (cur && cur.status === dest) return;
        const order = { 'Pendiente':0, 'Programado':1, 'EnTaller':2, 'Completado':3 };
        const curIdx = order[cur?.status || 'Pendiente'] ?? 0;
        const destIdx = order[dest] ?? 0;
        if (destIdx < curIdx) { toast('No puedes retroceder estatus', false); return; }
        if (destIdx > curIdx + 1) { toast('Sigue la secuencia: Pendiente ? Programado ? En Taller ? Completado', false); return; }
        if (dest === 'Programado' && cur && cur.status !== 'Pendiente') { toast('Solo se puede reprogramar desde Programado (usa el detalle).', false); return; }

        openConfirm({ id, to: dest, onConfirm: (payload)=>{
          apiPost('update_status', payload).then(res=>{
            if (!res.ok) {
              toast(res.msg || 'No se pudo actualizar estatus', false);
              loadList();
              return;
            }
            toast(res.msg || 'Estatus actualizado');
            loadList();
          }).catch(()=> toast('Error de red', false));
        }});
      }

      function renderBoard(){
        el.board.innerHTML = '';
        el.count.textContent = `${state.items.length} Ordenes`;
        ESTATUS.forEach(s => {
          const items = state.items.filter(i => (i.status || 'Pendiente') === s.key);
          const col = document.createElement('div');
          col.className = 'col col-' + s.key;
          col.innerHTML = `
            <h3>
              <span>${s.label}</span>
              <span class="badge ${s.badge}">${items.length}</span>
            </h3>
            <div class="drop" data-status="${s.key}" aria-label="${s.label}"></div>
          `;
          const drop = col.querySelector('.drop');
          drop.addEventListener('dragover', e => { e.preventDefault(); drop.classList.add('over'); });
          drop.addEventListener('dragleave', () => drop.classList.remove('over'));
          drop.addEventListener('drop', onDrop);
          items.forEach(it => drop.appendChild(card(it)));
          el.board.appendChild(col);
        });
      }

      function renderList(){
        const q = (el.q?.value || '').trim().toLowerCase();
        const rows = state.items
          .filter(it => !q || [it.placa, it.tipo, it.servicio, it.status, it.prio, String(it.id)].join(' ').toLowerCase().includes(q))
          .sort((a,b)=> a.id - b.id);

        el.tbody.innerHTML = rows.map(it => `
          <tr>
            <td>${it.id}</td>
            <td>${it.placa || ''}</td>
            <td>${it.tipo || ''}</td>
            <td>${it.servicio || ''}</td>
            <td>${fmt(it.km)}</td>
            <td>${it.fecha || ''}</td>
            <td>${it.fecha_programada || ''}</td>
            <td><span class="status-pill ${ (it.status==='Completado'?'s-comp':(it.status==='EnTaller'?'s-taller':(it.status==='Programado'?'s-prog':'s-pend'))) }">${humanStatus(it.status || 'Pendiente')}</span></td>
            <td>${it.prio || 'Media'}</td>
            <td>
              ${it.status==='Pendiente' ? `<button data-move="${it.id}" data-to="Programado">? Programado</button>` : ''}
              ${it.status==='Programado' ? `<button data-move="${it.id}" data-to="EnTaller">? En Taller</button>` : ''}
              ${it.status==='EnTaller' ? `<button data-move="${it.id}" data-to="Completado">? Completado</button>` : ''}
              <button data-goto="${it.id}">Detalle</button>
            </td>
          </tr>
        `).join('');

        el.tbody.querySelectorAll('button[data-move]').forEach(btn=>{
          btn.addEventListener('click', ()=>{
            const id = Number(btn.getAttribute('data-move'));
            const to = btn.getAttribute('data-to');
            const cur = state.items.find(x=> x.id===id);
            if (to==='Programado' && cur && cur.status !== 'Pendiente') { toast('No puedes volver a Programado aqui. Usa reprogramacion en el detalle.', false); return; }
            openConfirm({ id, to, onConfirm: (payload)=>{
              apiPost('update_status', payload).then(res=>{
                if (!res.ok) {
                  toast(res.msg || 'No se pudo actualizar', false);
                  loadList();
                  return;
                }
                toast(res.msg || 'Estatus actualizado');
                loadList();
              }).catch(()=> toast('Error de red', false));
            }});
          });
        });
        el.tbody.querySelectorAll('button[data-goto]').forEach(btn=>{
          btn.addEventListener('click', ()=>{
            const id = Number(btn.getAttribute('data-goto'));
            window.location.href = `/Pedidos_GA/detalles_orden.php?id=${id}`;
          });
        });
      }

      function setTab(tab){
        state.tab = tab;
        el.tabs.forEach(b => b.setAttribute('aria-pressed', String(b.dataset.tab === tab)));
        if(tab === 'board'){
          root.querySelector('.kanban').style.display = 'grid';
          el.listWrap.style.display = 'none';
        }else{
          root.querySelector('.kanban').style.display = 'none';
          el.listWrap.style.display = 'block';
        }
      }
      el.tabs.forEach(b => b.addEventListener('click', ()=> setTab(b.dataset.tab)));
      root.querySelector('#mantto-q').addEventListener('input', renderList);

      // ===== Modal (ahora sí, después de que TODO el DOM existe) =====
      const mb = document.getElementById('modal-os');
      const f  = document.getElementById('form-os');
      const selVeh = document.getElementById('vehiculo');
      const selSrv = document.getElementById('servicio');
      const containerMats = document.getElementById('mat-rows');
      
      const btnAddMat = document.getElementById('btn-add-mat');

      if (!mb || !f) { console.error('Modal o formulario no encontrados'); }

      function addMatRow(inv = []) {
        if (!containerMats) return;
        const row = document.createElement('div');
        row.className = 'grid';
        row.innerHTML = `
          <div class="field">
            <label>Insumo</label>
            <select class="mat-inv">
              <option value="" disabled selected>Seleccionado</option>
              ${inv.map(i=>`<option value="${i.id}" ${Number(i.cantidad||0)<=0?'disabled':''}>${i.nombre} (disp: ${i.cantidad||0}${(i.stock_minimo!=null&&Number(i.cantidad)<=Number(i.stock_minimo)?' abajo':'' )})</option>`).join('')}
            </select>
          </div>
          <div class="field">
            <label>Cantidad</label>
            <input type="number" class="mat-cant" min="0.001" step="0.001" value="1">
          </div>
          <div class="field" style="align-self:end">
            <button type="button" class="btn-ghost mat-del">Quitar</button>
          </div>
        `;
        row.querySelector('.mat-del').addEventListener('click', ()=> row.remove());
        containerMats.appendChild(row);
      }

      function openModal(){
        if (!mb) return;
        mb.style.display = 'flex';
        if (!state.options.vehiculos.length || !state.options.servicios.length) {
          Promise.all([
            apiGet('options'),
            fetch('servicios_api.php?action=config', {credentials:'same-origin'}).then(r=>r.json()).catch(()=>({ok:true,data:{costo_minuto_mo:0}}))
          ]).then(([res, cfg])=>{
            if (!res.ok) { toast('No se pudieron cargar catalogos', false); return; }
            state.options = { vehiculos:res.vehiculos||[], servicios:res.servicios||[], inventario:res.inventario||[] };
            if (cfg && cfg.ok && cfg.data && typeof cfg.data.costo_minuto_mo !== 'undefined') {
              COSTO_MINUTO = Number(cfg.data.costo_minuto_mo)||0;
            }
            if (selVeh) selVeh.innerHTML = '<option value="" disabled selected>Seleccionado</option>' +
              state.options.vehiculos.map(v => `<option value="${v.id}">${v.placa} - ${v.tipo} - ${fmt(v.km)}</option>`).join('');
            // servicios se filtran al elegir vehículo
            if (selSrv) selSrv.innerHTML = '<option value="" disabled selected>Seleccionado</option>';
            renderMoAuto2();
          }).catch(()=> toast('Error de red (catalogos)', false));
        } else {
          // ya tenemos catálogos; al abrir solo recalcula el MO con lo actual
          renderMoAuto2();
        }
      }
      function closeModal(){
        if (!mb || !f) return;
        mb.style.display = 'none';
        f.reset();
        if (containerMats) containerMats.innerHTML='';
      }

      document.querySelectorAll('[data-close]').forEach(x => x.addEventListener('click', closeModal));
      el.btnAdd.addEventListener('click', openModal);
      mb?.addEventListener('click', (e)=> { if (e.target === mb) closeModal(); });

      selVeh?.addEventListener('change', ()=>{
        const vid = Number(selVeh.value||0);
        const servicios = (state.options.servicios||[]).filter(s => !Array.isArray(s.vehiculos) || s.vehiculos.length===0 || s.vehiculos.includes(vid));
        selSrv.innerHTML = '<option value="" disabled selected>Seleccionado</option>' + servicios.map(s=>`<option value="${s.id}" data-d="${s.duracion_minutos}">${s.nombre}</option>`).join('');
        // limpiar materiales y preparar inventario filtrado
        if (containerMats) containerMats.innerHTML='';
      });

      selSrv?.addEventListener('change', ()=>{
        // Al seleccionar servicio, cargar materiales del catalogo en la lista editable
        const vid = Number(selVeh.value||0);
        const inv = (state.options.inventario||[]).filter(i => !Array.isArray(i.vehiculos) || i.vehiculos.length===0 || i.vehiculos.includes(vid));
        // No adjuntar listener aqui para evitar duplicados al cambiar servicio
        const srvId = Number(selSrv.value||0);
        if (srvId) {
          fetch('servicios_api.php?action=get&id='+srvId, {credentials:'same-origin'})
            .then(r=>r.json())
            .then(j=>{
              if(!j || !j.ok) return;
              const mats = Array.isArray(j.data?.materiales) ? j.data.materiales : [];
              if (containerMats) containerMats.innerHTML='';
              const notCompat = [];
              mats.forEach(m=>{
                const exists = inv.find(x=>x.id===m.id_inventario);
                if (!exists) { notCompat.push(m.id_inventario); return; }
                addMatRow(inv);
                const last = containerMats.lastElementChild;
                if (!last) return;
                const sel = last.querySelector('.mat-inv');
                const qty = last.querySelector('.mat-cant');
                if (sel) sel.value = String(m.id_inventario);
                if (qty) qty.value = String(m.cantidad);
              });
              if (notCompat.length){
                const labels = notCompat.map(id=>{
                  const it=(state.options.inventario||[]).find(x=>x.id===id);
                  return it? it.nombre : ('ID '+id);
                }).join(', ');
                toast('Algunos insumos del servicio no aplican al vehiculo: '+labels, false);
              }
            }).catch(()=>{});
        }
      });

      if (btnAddMat) {
        // Garantiza un solo handler: evita acumulaciones por reabrir el modal
        btnAddMat.onclick = () => {
          const vid = Number(selVeh.value||0);
          const inv = (state.options.inventario||[]).filter(i => !Array.isArray(i.vehiculos) || i.vehiculos.length===0 || i.vehiculos.includes(vid));
          addMatRow(inv);
        };
      }

      // Cálculo automático de MO en este modal
      function renderMoAuto2(){
        const dur = document.getElementById('duracion_minutos');
        const span = document.getElementById('moAuto2');
        if (!dur || !span) return;
        const d = Number(dur.value||0);
        const mo = (d>0 ? d * (COSTO_MINUTO||0) : 0);
        span.textContent = mo.toFixed(2);
      }
      document.getElementById('duracion_minutos')?.addEventListener('input', renderMoAuto2);

      f?.addEventListener('submit', (e)=>{
        e.preventDefault();
        const mats = Array.from(containerMats?.querySelectorAll('.grid') || []).map(row=>{
          const id_inv = Number(row.querySelector('.mat-inv')?.value || 0);
          const cant = Number(row.querySelector('.mat-cant')?.value || 0);
          return (id_inv>0 && cant>0) ? {id_inventario:id_inv, cantidad:cant} : null;
        }).filter(Boolean);

        const data = {
          id_vehiculo: Number(f.id_vehiculo.value),
          id_servicio: Number(f.id_servicio.value),
          duracion_minutos: Number(f.duracion_minutos.value || 0),
          notas: f.notas.value || null,
          // Siempre crear como Pendiente; la programacion se hace en el tablero
          programar: false,
          materiales: mats
        };
        apiPost('create', data).then(res=>{
          if (!res.ok) { toast(res.msg || 'No se pudo guardar', false); return; }
          closeModal();
          toast('Orden creada correctamente');
          loadList();
        }).catch(()=> toast('Error de red al guardar', false));
      });

      async function loadList(){
        const res = await apiGet('list');
        if (!res.ok) { toast('No se pudo cargar la lista', false); return; }
        state.items = res.items || [];
        renderBoard(); renderList(); setTab('board');
      }

      loadList();
    });
    </script>
  </div>

  <!-- Modal Agregar servicio -->
  <div class="modal-backdrop" id="modal-os" style="display:none">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modal-title">
      <header>
        <h3 id="modal-title">Agregar servicio</h3>
        <button class="close" data-close>&times;</button>
      </header>

      <form id="form-os" class="grid">
        <div class="field">
          <label for="vehiculo">Vehiculo</label>
          <select id="vehiculo" name="id_vehiculo" required></select>
        </div>
        <div class="field">
          <label for="servicio">Servicio</label>
          <select id="servicio" name="id_servicio" required></select>
        </div>
        <div class="field">
          <label for="duracion_minutos">Duracion estimada (min)</label>
          <input type="number" id="duracion_minutos" name="duracion_minutos" min="0" value="0">
          <div style="color:#6b7280; font-size:.85rem; margin-top:4px;">Costo MO auto: $<span id="moAuto2">0.00</span></div>
        </div>
        <div class="field grid-1" style="grid-column:1/-1">
          <label for="notas">Notas</label>
          <textarea id="notas" name="notas" rows="3" placeholder="Detalles del servicio..."></textarea>
        </div>

        <!-- Eliminado: la orden siempre inicia Pendiente y se programa despues en el tablero -->
        <div class="field grid-1" style="grid-column:1/-1">
          
        </div>

        <div class="grid-1" style="grid-column:1/-1">
          <div style="display:flex;justify-content:space-between;align-items:center">
            <h4 style="margin:6px 0">Materiales (opcional)</h4>
            <button type="button" class="btn-ghost" id="btn-add-mat">+ Añadir material</button>
          </div>
          <div id="mat-rows"></div>
        </div>

        <div class="actions" style="grid-column:1/-1">
          <button type="button" class="btn-ghost" data-close>Cancelar</button>
          <button type="submit" class="btn-primary">Guardar</button>
        </div>
      </form>
      
    </div>
  </div>

  <div class="toast" id="toast" style="display:none"></div>
</body>
</html>



