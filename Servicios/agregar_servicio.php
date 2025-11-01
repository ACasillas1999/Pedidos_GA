<?php
ini_set('session.cookie_httponly', true);
ini_set('session.cookie_secure', true);
session_name("GA");
session_start();
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <title>Servicios</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" type="image/png" href="/Pedidos_GA/Img/Botones%20entregas/ICONOSPAG/ICONOPEDIDOS.png">
  <link rel="stylesheet" href="../styles.css">
  <style>
    :root {
      --ga-bg: #f6f7fb;
      --ga-card: #fff;
      --ga-ink: #1f2937;
      --ga-muted: #6b7280;
      --ga-line: #e5e7eb;
      --ga-accent: #2563eb;
      --ga-accent-2: #60a5fa;
      --shadow: 0 6px 20px rgba(17, 24, 39, .08);
    }

    body {
      background: var(--ga-bg);
      color: var(--ga-ink);
    }

    .wrapper {
      display: flex;
      min-height: 100vh;
    }

    .content {
      flex: 1;
      padding: 18px clamp(12px, 2vw, 24px);
      margin-left: var(--sidebar-width, 6%);
    }

    .ga-card {
      background: var(--ga-card);
      border: 1px solid var(--ga-line);
      border-radius: 14px;
      padding: 16px;
      box-shadow: var(--shadow);
      margin-bottom: 16px;
    }

    .ga-title {
      margin: 0 0 8px;
      font-size: 1.25rem;
      font-weight: 700;
      color: #0f172a;
    }

    .ga-input,
    .ga-number {
      width: 75%;
      background: #fff;
      color: var(--ga-ink);
      border: 1px solid var(--ga-line);
      border-radius: 10px;
      padding: 10px 12px;
      outline: 0;
    }

    .ga-input:focus,
    .ga-number:focus {
      border-color: var(--ga-accent);
      box-shadow: 0 0 0 3px rgba(37, 99, 235, .15);
    }

    .ga-btn {
      background: linear-gradient(135deg, var(--ga-accent), var(--ga-accent-2));
      color: #fff;
      border: none;
      border-radius: 10px;
      padding: 10px 14px;
      font-weight: 700;
      cursor: pointer;
      box-shadow: 0 4px 10px rgba(37, 99, 235, .25);
    }

    .ga-btn.secondary {
      background: #eef2ff;
      color: #1e293b;
      border: 1px solid #dbeafe;
      box-shadow: none;
    }

    .ga-btn.danger {
      background: linear-gradient(135deg, #ef4444, #f87171);
    }

    .table-wrap {
      overflow: auto;
      border-radius: 12px;
      border: 1px solid var(--ga-line);
    }

    table {
      width: 100%;
      border-collapse: collapse;
      min-width: 980px;
    }

    thead th {
      background: #f3f4f6;
      color: #111827;
      text-align: left;
      padding: 12px;
      border-bottom: 1px solid var(--ga-line);
      position: sticky;
      top: 0;
      z-index: 1;
    }

    tbody td {
      padding: 12px;
      border-top: 1px solid var(--ga-line);
    }

    .row-actions {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
    }

    .badge {
      font-size: .78rem;
      padding: 4px 8px;
      border-radius: 999px;
      border: 1px solid currentColor;
      display: inline-block;
    }

    .chip {
      background: #eef2ff;
      color: #374151;
      border: 1px solid #dbeafe;
      padding: 4px 8px;
      border-radius: 999px;
      font-size: .8rem;
    }

    .backdrop {
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, .35);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 1000;
    }

    .backdrop.show {
      display: flex;
    }

    .modal {
      background: #fff;
      width: min(1020px, 96vw);
      border: 1px solid var(--ga-line);
      border-radius: 14px;
      padding: 16px;
      box-shadow: 0 24px 80px rgba(0, 0, 0, .15);
    }

    .grid {
      display: grid;
      gap: 10px;
    }

    .cols-4 {
      grid-template-columns: 1.2fr 1fr 1fr 1fr;
    }

    @media(max-width:1000px) {
      .cols-4 {
        grid-template-columns: 1fr 1fr;
      }
    }

    .catalog {
      background: #fff;
      border: 1px solid var(--ga-line);
      border-radius: 10px;
      padding: 10px;
      max-height: 260px;
      overflow: auto;
    }

    .item-row {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 6px 4px;
      border-bottom: 1px dashed var(--ga-line);
    }

    .item-row:last-child {
      border-bottom: none;
    }

    .qty {
      width: 90px;
    }

    .chips {
      display: flex;
      gap: 6px;
      flex-wrap: wrap;
    }
  </style>
</head>

<body>
  <script>
    document.addEventListener("DOMContentLoaded", function () {
      var iconoInventario = document.querySelector(".icono-inventario");
      if (iconoInventario) {
        var imgNormalInventario = "/Pedidos_GA/Img/SVG/InventarioN.svg";
        var imgHoverInventario  = "/Pedidos_GA/Img/SVG/InventarioB.svg";
        iconoInventario.addEventListener("mouseover", function(){ this.src = imgHoverInventario; });
        iconoInventario.addEventListener("mouseout",  function(){ this.src = imgNormalInventario ; });
      }

      var iconoEstadisticaServ = document.querySelector(".icono-estadisticaServ");
      if (iconoEstadisticaServ ) {
        var imgNormalEstadisticaServ = "/Pedidos_GA/Img/SVG/EstadisticasServN.svg";
        var imgHoverEstadisticaServ  = "/Pedidos_GA/Img/SVG/EstadisticasServB.svg";
        iconoEstadisticaServ .addEventListener("mouseover", function(){ this.src = imgHoverEstadisticaServ ; });
        iconoEstadisticaServ .addEventListener("mouseout",  function(){ this.src = imgNormalEstadisticaServ ; });
      }

     
    });
  </script>
  <div class="wrapper">

    <div class="sidebar">
      <ul>
        <li>
          <a href="inventario.php">
             <img src="/Pedidos_GA/Img/SVG/InventarioN.svg" class="icono-inventario sidebar-icon" alt="Inventario">
          </a>
        </li>
        <li><a href="servicios_estadisticas.php">
          <img src="/Pedidos_GA/Img/SVG/EstadisticasServN.svg" class="icono-estadisticaServ sidebar-icon" alt="Inventario">
        </a>
      </li>
        <li class="corner-left-bottom">
          <a href="../Servicios/Servicios.php">
            <img src="/Pedidos_GA/Img/Botones%20entregas/Usuario/VOLVAZ.png" alt="Volver" class="icono-Volver" style="max-width:35%;height:auto;">
          </a>
        </li>
      </ul>
    </div>

    <main class="content">
      
      <div class="ga-card" style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
        <div>
          <h1 class="ga-title">Servicios</h1>
          <div style="color:#6b7280">Crea servicios: materiales del inventario, tiempo estimado y vehiculos que acepta.</div>
        </div>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
          <input id="search" class="ga-input" placeholder="Buscar servicio..." style="min-width:260px;">
          <button id="btnNuevo" class="ga-btn">Nuevo servicio</button>
        </div>
      </div>

      <div class="ga-card">
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th style="width:70px">ID</th>
                <th>Nombre</th>
                <th style="width:120px">Tiempo (min)</th>
                <th style="width:120px">Costo MO</th>
                <th style="width:120px">Costo Mat.</th>
                <th>Materiales</th>
                <th>Carros que acepta</th>
                <th style="width:240px">Acciones</th>
              </tr>
            </thead>
            <tbody id="tbody"></tbody>
          </table>
        </div>
      </div>
    </main>
  </div>

  <!-- Modal Crear/Editar servicio -->
  <div id="modalForm" class="backdrop" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true">
      <h3 id="modalTitle" style="margin:0 0 8px;">Nuevo servicio</h3>
      <div class="grid cols-4" style="margin-bottom:10px;">
        <div><label>Nombre del servicio</label><input id="f_nombre" class="ga-input" type="text"></div>
        <div>
          <label>Tiempo estimado (min)</label>
          <input id="f_duracion" class="ga-number" type="number" min="0" step="5" value="0">
          <div style="color:#6b7280; font-size:.85rem; margin-top:4px;">Costo MO auto: $<span id="moAuto">0.00</span></div>
        </div>
        <div><label>Costo mano de obra</label><input id="f_cmo" class="ga-number" type="number" min="0" step="0.01" value="0"></div>
      </div>

      <div class="grid" style="grid-template-columns:1fr 1fr; gap:14px;">
        <div>
          <label>Materiales del inventario</label>
          <div style="display:flex; gap:10px; margin:6px 0; align-items:center;">
            <input id="matSearch" class="ga-input" placeholder="Buscar material...">
            <div style="margin-left:auto; color:#6b7280; font-weight:600;">Costo materiales: $<span id="matCosto">0.00</span></div>
          </div>
          <div id="matBox" class="catalog"></div>
          <div class="chips" id="matChips" style="margin-top:8px;"></div>
        </div>
        <div>
          <label>Vehiculos que acepta</label>
          <div style="display:flex; gap:10px; margin:6px 0;">
            <input id="vehSearch" class="ga-input" placeholder="Buscar por placa / tipo / sucursal">
            <button id="btnSelTodosVeh" class="ga-btn secondary" type="button">Todos</button>
            <button id="btnSelNingunoVeh" class="ga-btn secondary" type="button">Ninguno</button>
          </div>
          <div id="vehBox" class="catalog"></div>
          <div class="chips" id="vehChips" style="margin-top:8px;"></div>
        </div>
      </div>

      <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:12px;">
        <button class="ga-btn secondary" data-close>Cancelar</button>
        <button id="btnGuardar" class="ga-btn">Guardar</button>
      </div>
    </div>
  </div>

  <!-- Modal Confirm -->
  <div id="modalConfirm" class="backdrop" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" style="max-width:520px;">
      <h3 style="margin:0 0 8px;">Confirmar</h3>
      <p id="confirmMsg">¿Seguro?</p>
      <div style="display:flex; gap:10px; justify-content:flex-end;">
        <button class="ga-btn secondary" data-close>Cancelar</button>
        <button id="btnConfirmYes" class="ga-btn danger">continuar</button>
      </div>
    </div>
  </div>

  <script>
    const $ = (q, ctx = document) => ctx.querySelector(q);
    const $$ = (q, ctx = document) => [...ctx.querySelectorAll(q)];

    let rows = [];
    let VEHICULOS = [];
    let INVENTARIO = [];
    let selectedVeh = new Set();
    let selectedMat = new Map(); // id_inventario -> cantidad
    let COSTO_MINUTO = 0; // costo por minuto para MO auto

    async function api(action, payload = null) {
      const opt = payload ? {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
      } : {};
      const r = await fetch('servicios_api.php?action=' + action, opt);
      let j;
      try {
        j = await r.json();
      } catch (e) {
        const txt = await r.text().catch(() => '');
        throw new Error((txt && txt.length < 400 ? txt : '') || ('Respuesta no válida en ' + action));
      }
      if (!j.ok) throw new Error(j.error || ('Error ' + action));
      return j.data;
    }

    async function loadCatalogs() {
      const [veh, inv, cfg] = await Promise.all([
        api('vehiculos'), api('inventario'), fetch('servicios_api.php?action=config').then(r => r.json()).catch(() => ({
          ok: true,
          data: {
            costo_minuto_mo: 0
          }
        }))
      ]);
      VEHICULOS = veh || [];
      INVENTARIO = inv || [];
      if (cfg && cfg.ok && cfg.data && typeof cfg.data.costo_minuto_mo !== 'undefined') COSTO_MINUTO = Number(cfg.data.costo_minuto_mo) || 0;
    }
    async function loadData() {
      rows = await api('list');
      render();
    }

    function matLabel(m) { // m: {id_inventario,cantidad}
      const it = INVENTARIO.find(x => x.id === m.id_inventario);
      return (it ? it.label : ('ID ' + m.id_inventario)) + ' — ' + m.cantidad;
    }

    function vehResumen(ids) {
      if (!ids || !ids.length) return '';
      const nombres = ids.map(id => {
        const v = VEHICULOS.find(x => x.id === id);
        return v ? (v.placa || ('ID ' + id)) : ('ID ' + id);
      });
      return `${nombres.slice(0,3).join(', ')}${nombres.length>3? ' ('+nombres.length+' total)': ''}`;
    }

    function render() {
      const q = ($('#search').value || '').toLowerCase().trim();
      const tbody = $('#tbody');
      tbody.innerHTML = '';
      rows
        .filter(r => [r.nombre, String(r.id)].some(x => (x || '').toLowerCase().includes(q)))
        .forEach(r => {
          const tr = document.createElement('tr');
          tr.innerHTML = `
       <td>${r.id}</td>
       <td>${r.nombre}</td>
       <td>${r.duracion_minutos}</td>
       <td>$${Number(r.costo_mano_obra||0).toFixed(2)}</td>
       <td>$${Number(r.costo_materiales||0).toFixed(2)}</td>
       <td title="${(r.materiales||[]).map(matLabel).join('\n')}">
         ${(r.materiales||[]).slice(0,3).map(matLabel).join(', ')}${(r.materiales||[]).length>3?'':''}
       </td>
       <td title="${(r.vehiculos||[]).map(id=>((VEHICULOS.find(v=>v.id===id)||{}).placa||('ID '+id))).join('\n')}">
         ${vehResumen(r.vehiculos||[])}
       </td>
       <td>
         <div class="row-actions">
           <button class="ga-btn secondary" data-edit="${r.id}">Editar</button>
           <button class="ga-btn danger" data-del="${r.id}">Eliminar</button>
         </div>
       </td>`;
          tbody.appendChild(tr);
        });
    }

    function show(el, on = true) {
      el.classList.toggle('show', !!on);
      el.setAttribute('aria-hidden', on ? 'false' : 'true');
    }

    function openForm(title, data) {
      $('#modalTitle').textContent = title || 'Nuevo servicio';
      $('#f_nombre').value = data?.nombre || '';
      $('#f_duracion').value = data?.duracion_minutos ?? 0;
      $('#f_cmo').value = (data?.costo_mano_obra ?? 0);
      $('#btnGuardar').dataset.editId = data?.id ?? '';

      selectedVeh = new Set(data?.vehiculos || []);
      selectedMat = new Map((data?.materiales || []).map(m => [m.id_inventario, m.cantidad]));

      $('#vehSearch').value = '';
      $('#matSearch').value = '';
      renderVehBox();
      renderVehChips();
      renderMatBox();
      renderMatChips();
      renderMatCosto();
      renderMoAuto();
      renderTotalCosto();

      show($('#modalForm'), true);
      $('#f_nombre').focus();
    }

    function confirmDialog(msg, onYes) {
      $('#confirmMsg').textContent = msg || '¿Seguro?';
      $('#btnConfirmYes').onclick = () => {
        show($('#modalConfirm'), false);
        onYes?.();
      };
      show($('#modalConfirm'), true);
    }

    function renderVehBox() {
      const box = $('#vehBox');
      box.innerHTML = '';
      const q = ($('#vehSearch').value || '').toLowerCase();
      VEHICULOS
        .filter(v => [v.placa, v.tipo, v.suc, v.label].some(s => (s || '').toLowerCase().includes(q)))
        .forEach(v => {
          const row = document.createElement('div');
          row.className = 'item-row';
          row.innerHTML = `
        <input type="checkbox" data-veh="${v.id}" ${selectedVeh.has(v.id)?'checked':''}>
        <div>${v.placa||'SN-PLACA'} · <span style="color:#6b7280">${v.tipo}</span> <small style="color:#9ca3af">· ${v.suc||''}</small></div>`;
          box.appendChild(row);
        });
    }

    function renderVehChips() {
      const chips = $('#vehChips');
      chips.innerHTML = '';
      [...selectedVeh].slice(0, 12).forEach(id => {
        const v = VEHICULOS.find(x => x.id === id);
        const c = document.createElement('span');
        c.className = 'chip';
        c.textContent = (v?.placa || ('ID ' + id)) + ' · ' + (v?.suc || '');
        chips.appendChild(c);
      });
      if (selectedVeh.size > 12) {
        const more = document.createElement('span');
        more.className = 'chip';
        more.textContent = `+${selectedVeh.size-12} mas`;
        chips.appendChild(more);
      }
    }
    $('#vehSearch').addEventListener('input', renderVehBox);
    document.addEventListener('change', e => {
      if (e.target.matches('[data-veh]')) {
        const id = Number(e.target.dataset.veh);
        if (e.target.checked) selectedVeh.add(id);
        else selectedVeh.delete(id);
        renderVehChips();
      }
    });
    $('#btnSelTodosVeh').addEventListener('click', () => {
      VEHICULOS.forEach(v => selectedVeh.add(v.id));
      renderVehBox();
      renderVehChips();
    });
    $('#btnSelNingunoVeh').addEventListener('click', () => {
      selectedVeh.clear();
      renderVehBox();
      renderVehChips();
    });

    /* ---------- Materiales picker ---------- */
    function renderMatBox() {
      const box = $('#matBox');
      box.innerHTML = '';
      const q = ($('#matSearch').value || '').toLowerCase();
      INVENTARIO
        .filter(i => [i.nombre, i.marca, i.modelo, i.label].some(s => (s || '').toLowerCase().includes(q)))
        .forEach(i => {
          const qty = selectedMat.get(i.id) || 0;
          const row = document.createElement('div');
          row.className = 'item-row';
          const badge = (Number(i.cantidad || 0) <= 0) ? '<span class="badge low" style="color:#b91c1c; border-color:#fecaca; background:#fee2e2;">Sin stock</span>' :
            (Number(i.cantidad) <= Number(i.stock_minimo || 0) ? '<span class="badge low" style="color:#b45309; border-color:#fcd34d; background:#fef3c7;">Stock bajo</span>' : '');
          row.innerHTML = `
        <input type="checkbox" data-mat="${i.id}" ${qty>0?'checked':''} ${Number(i.cantidad||0)<=0?'disabled':''}>
        <div style="flex:1">${i.label} ${badge} <small style="color:#6b7280">disp: ${i.cantidad||0}  $${Number(i.costo||0).toFixed(2)}</small></div>
        <input class="ga-number qty" type="number" min="1" step="1" value="${qty||1}" data-qty="${i.id}">
      `;
          box.appendChild(row);
        });
    }

    function renderMatChips() {
      const chips = $('#matChips');
      chips.innerHTML = '';
      [...selectedMat.entries()].slice(0, 12).forEach(([id, qty]) => {
        const it = INVENTARIO.find(x => x.id === id);
        const c = document.createElement('span');
        c.className = 'chip';
        c.textContent = (it?.label || ('ID ' + id)) + ' — ' + qty;
        chips.appendChild(c);
      });
      if (selectedMat.size > 12) {
        const more = document.createElement('span');
        more.className = 'chip';
        more.textContent = `+${selectedMat.size-12} mas`;
        chips.appendChild(more);
      }
    }
    $('#matSearch').addEventListener('input', renderMatBox);
    document.addEventListener('change', e => {
      if (e.target.matches('[data-mat]')) {
        const id = Number(e.target.dataset.mat);
        const qtyInput = $(`[data-qty="${id}"]`);
        let qty = parseFloat(qtyInput.value || '1');
        if (e.target.checked) {
          if (!isFinite(qty) || qty <= 0) qty = 1;
          selectedMat.set(id, qty);
          qtyInput.value = qty;
        } else {
          selectedMat.delete(id);
        }
        renderMatChips();
        renderMatCosto();
        renderTotalCosto();
      }
      if (e.target.matches('[data-qty]')) {
        const id = Number(e.target.dataset.qty);
        let qty = parseFloat(e.target.value || '1');
        if (!isFinite(qty) || qty <= 0) qty = 1;
        e.target.value = qty;
        if (selectedMat.has(id)) selectedMat.set(id, qty);
        renderMatChips();
        renderMatCosto();
        renderTotalCosto();
      }
    });

    function getMatCosto() {
      let total = 0;
      for (const [id, qty] of selectedMat.entries()) {
        const it = INVENTARIO.find(x => x.id === id);
        const c = Number(it?.costo ?? 0);
        total += Number(qty || 0) * c;
      }
      return total;
    }

    function renderMatCosto() {
      const m = getMatCosto();
      const el = document.getElementById('matCosto');
      if (el) el.textContent = m.toFixed(2);
    }

    function renderMoAuto() {
      const dur = parseInt($('#f_duracion').value || 0, 10);
      const mo = (dur > 0 ? dur * (COSTO_MINUTO || 0) : 0);
      $('#moAuto').textContent = mo.toFixed(2);
      if (!$('#f_cmo').dataset.userEdited) {
        $('#f_cmo').value = mo.toFixed(2);
      }
    }

    function renderTotalCosto() {
      const mo = parseFloat($('#f_cmo').value || 0);
      const mats = getMatCosto();
      renderMatCosto();
      const totalEl = document.querySelector('#totalCosto');
      if (totalEl) totalEl.textContent = (mo + mats).toFixed(2);
    }

    $('#f_duracion').addEventListener('input', () => {
      renderMoAuto();
      renderTotalCosto();
    });
    $('#f_cmo').addEventListener('input', () => {
      $('#f_cmo').dataset.userEdited = '1';
      renderTotalCosto();
    });

    document.addEventListener('click', (e) => {
      const t = e.target;
      if (t.matches('[data-edit]')) {
        const id = Number(t.dataset.edit);
        const item = rows.find(r => r.id === id);
        if (item) openForm('Editar servicio', item);
      }
      if (t.matches('[data-del]')) {
        const id = Number(t.dataset.del);
        const item = rows.find(r => r.id === id);
        if (!item) return;
        confirmDialog(`¿Eliminar "${item.nombre}" (ID ${item.id})?`, async () => {
          try {
            await api('delete', {
              id
            });
            await loadData();
          } catch (e) {
            alert(e.message);
          }
        });
      }
    });

    $('#btnNuevo').addEventListener('click', () => openForm('Nuevo servicio'));
    $$('[data-close]').forEach(b => b.addEventListener('click', () => {
      show($('#modalForm'), false);
      show($('#modalConfirm'), false);
    }));

    $('#btnGuardar').addEventListener('click', async () => {
      // Validación de stock antes de enviar
      const faltantes = [];
      for (const [id, qty] of selectedMat.entries()) {
        const it = INVENTARIO.find(x => x.id === id);
        const disp = Number(it?.cantidad ?? 0);
        const req = Number(qty || 0);
        if (req > disp) faltantes.push(`${it?.label||('ID '+id)} (disp: ${disp}, req: ${req})`);
      }
      if (faltantes.length) {
        alert('No se puede guardar: materiales sin stock suficiente:\n- ' + faltantes.join('\n- '));
        return;
      }

      // Validación de compatibilidad materiales-vehículos
      if (selectedVeh.size > 0 && selectedMat.size > 0) {
        const incompatibles = [];
        const vehSelected = [...selectedVeh];
        for (const [id, qty] of selectedMat.entries()) {
          const it = INVENTARIO.find(x => x.id === id);
          const allowed = Array.isArray(it?.vehiculos) ? it.vehiculos : [];
          if (allowed.length === 0) continue; // sin restricción => aplica a todos
          const noAplica = vehSelected.filter(v => !allowed.includes(v));
          if (noAplica.length) {
            const nombres = noAplica.map(v => (VEHICULOS.find(x => x.id === v)?.placa) || ('ID ' + v));
            incompatibles.push(`${it?.label || ('ID ' + id)} no aplica a: ${nombres.join(', ')}`);
          }
        }
        if (incompatibles.length) {
          alert('No se puede guardar: materiales incompatibles con los vehículos seleccionados:\n- ' + incompatibles.join('\n- '));
          return;
        }
      }

      const data = {
        id: $('#btnGuardar').dataset.editId || undefined,
        nombre: $('#f_nombre').value.trim(),
        duracion_minutos: parseInt($('#f_duracion').value || 0, 10),
        costo_mano_obra: parseFloat($('#f_cmo').value || 0),
        vehiculos: [...selectedVeh],
        materiales: [...selectedMat.entries()].map(([id, qty]) => ({
          id_inventario: id,
          cantidad: parseFloat(qty)
        }))
      };
      if (!data.nombre) {
        alert('El nombre es obligatorio');
        return;
      }
      if (data.duracion_minutos < 0) {
        alert('Tiempo invalido');
        return;
      }
      if (data.costo_mano_obra < 0) {
        alert('Costo invalido');
        return;
      }
      try {
        if (data.id) await api('update', data);
        else {
          const resp = await api('create', data);
          data.id = resp.id;
        }
        show($('#modalForm'), false);
        await loadData();
      } catch (e) {
        alert(e.message);
      }
    });

    (async function init() {
      try {
        await loadCatalogs();
        await loadData();
      } catch (e) {
        alert(e.message);
      }
    })();
  </script>
</body>

</html>
