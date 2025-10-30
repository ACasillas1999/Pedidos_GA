<?php
// Iniciar la sesión de forma segura
ini_set('session.cookie_httponly', true);
ini_set('session.cookie_secure', true);
session_name("GA");
session_start();

// Traer vehículos desde tu BD
require_once __DIR__ . "/../Conexiones/Conexion.php";

$qVehiculos = $conn->query("
  SELECT id_vehiculo, placa, tipo, Sucursal
  FROM vehiculos
  ORDER BY Sucursal ASC, placa ASC
");
$vehiculos = [];
while ($v = $qVehiculos->fetch_assoc()) {
  $vehiculos[] = [
    'id'   => (int)$v['id_vehiculo'],
    'placa'=> $v['placa'],
    'tipo' => $v['tipo'],
    'suc'  => $v['Sucursal'],
    'label'=> trim(($v['placa'] ?: 'SN-PLACA') . ' · ' . $v['tipo'] . ' · ' . $v['Sucursal'])
  ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Inventario</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" type="image/png" href="/Pedidos_GA/Img/Botones%20entregas/ICONOSPAG/ICONOPEDIDOS.png">
  <link rel="stylesheet" href="../styles.css">
<style>
  /* ===== Paleta
  
  tema claro ===== */
  :root{
    --ga-bg:#f6f7fb; 
    --ga-card:#ffffff; 
    --ga-ink:#1f2937; 
    --ga-muted:#6b7280;
    --ga-line:#e5e7eb;
    --ga-accent:#2563eb; 
    --ga-accent-2:#60a5fa;
    --ga-danger:#ef4444; 
    --ga-ok:#16a34a; 
    --ga-warn:#f59e0b;
    --shadow:0 6px 20px rgba(17,24,39,.08);
  }

  /* ===== Reset de la página ===== */
  body.theme-light{ background:var(--ga-bg); color:var(--ga-ink); }

  .wrapper{ display:flex; min-height:100vh;}
  .content{ flex:1; padding:18px clamp(12px,2vw,24px); margin-left: var(--sidebar-width,7%); }

  /* ===== Tarjetas / contenedores ===== */
  .ga-card{
    background:var(--ga-card);
    border-radius:14px;
    padding:16px;
    border:1px solid var(--ga-line);
    box-shadow:var(--shadow);
    margin-bottom:16px;
  }
  .ga-title{ margin:0 0 8px; font-size:1.25rem; font-weight:700; color:#0f172a; }

  /* ===== Inputs / selects / números ===== */
  .ga-input, .ga-number, .ga-select{
    width:80%;
    background:#ffffff;
    color:var(--ga-ink);
    border:1px solid var(--ga-line);
    border-radius:10px;
    padding:10px 12px;
    outline:0;
    transition:border-color .15s ease;
  }
  .ga-input:focus, .ga-number:focus, .ga-select:focus{
    border-color: var(--ga-accent);
    box-shadow: 0 0 0 3px rgba(37,99,235,.15);
  }

  /* ===== Botones ===== */
  .ga-btn{
    background:linear-gradient(135deg,var(--ga-accent),var(--ga-accent-2));
    color:#fff; border:none; border-radius:10px; padding:10px 14px; font-weight:700; cursor:pointer;
    box-shadow:0 4px 10px rgba(37,99,235,.25);
  }
  .ga-btn.secondary{
    background:#eef2ff; color:#1e293b; border:1px solid #dbeafe; box-shadow:none;
  }
  .ga-btn.warn{
    background:linear-gradient(135deg,#f59e0b,#fbbf24); color:#111827; box-shadow:0 4px 10px rgba(245,158,11,.25);
  }
  .ga-btn.danger{
    background:linear-gradient(135deg,#ef4444,#f87171); color:#fff; box-shadow:0 4px 10px rgba(239,68,68,.25);
  }
  .ga-btn:disabled{ opacity:.65; cursor:not-allowed; }

  /* ===== Tabla ===== */
  .table-wrap{ overflow:auto; border-radius:12px; border:1px solid var(--ga-line); }
  table{ width:100%; border-collapse:collapse; min-width:880px; }
  thead th{
    background:#f3f4f6; color:#111827; text-align:left; padding:12px; position:sticky; top:0; z-index:1;
    border-bottom:1px solid var(--ga-line);
  }
  tbody td{ padding:12px; border-top:1px solid var(--ga-line); color:var(--ga-ink); }
  .low-row{ background:#fff7f7; } /* fila con stock bajo */
  .row-actions{ display:flex; gap:8px; flex-wrap:wrap; }

  /* ===== Badges ===== */
  .badge{ font-size:.78rem; padding:4px 8px; border-radius:999px; border:1px solid currentColor; display:inline-block; }
  .badge.low{ color:var(--ga-danger); background:#ffeaea; }
  .badge.ok { color:var(--ga-ok);     background:#eaffea; }

  /* ===== Modal (claro) ===== */
  .backdrop{ position:fixed; inset:0; background:rgba(0,0,0,.35); display:none; align-items:center; justify-content:center; z-index:1000; }
  .backdrop.show{ display:flex; }
  .modal{ background:#ffffff; width:min(980px,95vw); border:1px solid var(--ga-line); border-radius:14px; padding:16px; box-shadow:0 24px 80px rgba(0,0,0,.15); }

  /* ===== Grid ===== */
  .grid{ display:grid; gap:10px; }
  .cols-4{ grid-template-columns: 1.2fr 1fr 1fr 1fr; }
  @media (max-width:1000px){ .cols-4{ grid-template-columns:1fr 1fr; } }

  /* ===== Picker de vehículos ===== */
  .vehiculos-box{ background:#ffffff; border:1px solid var(--ga-line); border-radius:10px; padding:10px; max-height:280px; overflow:auto; }
  .vehiculo-item{ display:flex; align-items:center; gap:8px; padding:8px 6px; border-bottom:1px dashed var(--ga-line); }
  .vehiculo-item:last-child{ border-bottom:none; }
  .vehiculo-item input{ transform:scale(1.1); }
  .vehiculo-label small{ color:var(--ga-muted); }
  .chips{ display:flex; gap:6px; flex-wrap:wrap; }
  .chip{ background:#eef2ff; color:#374151; border:1px solid #dbeafe; padding:4px 8px; border-radius:999px; font-size:.8rem; }

  /* ===== Sidebar (opcional aclararlo) =====
     Si tu sidebar es oscura por styles.css y quieres clara, descomenta:
  .sidebar{ background:#ffffff !important; border-right:1px solid var(--ga-line); }
  .sidebar a{ color:#1f2937 !important; }
  */

  #btnExport{ display:none !important; }

</style>

</head>
<body class="theme-light">
<div class="wrapper">

  <!-- Sidebar -->
  <div class="sidebar">
    <ul>
      <li><a href="agregar_servicio.php">Crear servicios</a></li>
      <li><a href="inventario_estadisticas.php">Estadísticas Inventario</a></li>
      <li class="corner-left-bottom">
        <a href="../Servicios/Servicios.php">
          <img src="/Pedidos_GA/Img/Botones%20entregas/Usuario/VOLVAZ.png" alt="Volver" class="icono-Volver" style="max-width:35%;height:auto;">
        </a>
      </li>
    </ul>
  </div>

  <!-- Contenido -->
  <main class="content">
    <div class="ga-card" style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
      <div>
        <h1 class="ga-title">📦 Inventario</h1>
        <div style="color:var(--ga-muted)">Administra existencias y define a qué vehículos aplica cada pieza</div>
      </div>
      <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <input id="search" class="ga-input" placeholder="🔎 Buscar por nombre / marca / modelo / ID" style="min-width:260px;">
        <button id="btnNuevo" class="ga-btn">➕ Nuevo producto</button>
        <button id="btnExport" class="ga-btn secondary">⬇️</button>
      </div>
    </div>

    <div class="ga-card">
      <div class="table-wrap">
        <table id="tabla">
          <thead>
            <tr>
              <th style="width:70px">ID</th>
              <th>Nombre</th>
              <th>Marca</th>
              <th>Modelo</th>
              <th style="width:110px">Cantidad</th>
              <th style="width:110px">Stock mín.</th>
              <th style="width:110px">Stock máx.</th>
              <th style="width:120px">SKU</th>
              <th style="width:120px">Costo unitario</th>
              <th style="width:140px">Presentación</th>
              <th>Vehículos aplicables</th>
              <th style="width:240px">Acciones</th>
            </tr>
          </thead>
          <tbody id="tbody"></tbody>
        </table>
      </div>
    </div>
  </main>
</div>

<!-- Modal Crear/Editar -->
<div id="modalForm" class="backdrop" aria-hidden="true">
  <div class="modal" role="dialog" aria-modal="true">
    <h3 id="modalTitle" style="margin:0 0 8px;">Nuevo producto</h3>
    <div class="grid cols-4" style="margin-bottom:10px;">
      <div><label>Nombre</label><input id="f_nombre" class="ga-input" type="text" autocomplete="off"></div>
      <div><label>Marca</label><input id="f_marca" class="ga-input" type="text" autocomplete="off"></div>
      <div><label>Modelo</label><input id="f_modelo" class="ga-input" type="text" autocomplete="off"></div>
      <div><label>Cantidad</label><input id="f_cantidad" class="ga-number" type="number" min="0" step="1" value="0"></div>
      <div><label>Stock mínimo</label><input id="f_min" class="ga-number" type="number" min="0" step="1" value="0"></div>
      <div><label>Stock máximo</label><input id="f_max" class="ga-number" type="number" min="0" step="1" value="0"></div>
      <div><label>SKU</label><input id="f_sku" class="ga-input" type="text" autocomplete="off"></div>
      <div><label>Costo unitario</label><input id="f_costo" class="ga-number" type="number" min="0" step="0.01" value="0"></div>
      <div><label>Presentación - Contenido</label><input id="f_cont" class="ga-number" type="number" min="0" step="0.001" value="1"></div>


      <div>
        <label>Presentación - Unidad</label>
        <select id="f_uni" class="ga-select">
          <option value="">— Seleccione unidad —</option>
          <option value="pieza">pieza</option>
          <option value="piezas">piezas</option>
          <option value="juego">juego</option>
          <option value="paquete">paquete</option>
          <option value="litro">litro</option>
          <option value="ml">ml</option>
          <option value="caja">caja</option>
          <option value="kit">kit</option>
          <option value="galón">galón</option>
          <option value="kg">kg</option>
          <option value="g">g</option>
        </select>
      </div>


      





      <div style="grid-column:1/-1">
        <label>Vehículos aplicables</label>
        <div style="display:flex; gap:10px; align-items:center; margin:6px 0;">
          <input id="vehSearch" class="ga-input" placeholder="🔎 Filtrar por placa / tipo / sucursal" style="max-width:360px;">
          <button id="btnSelTodos" class="ga-btn secondary" type="button">Seleccionar todos</button>
          <button id="btnSelNinguno" class="ga-btn secondary" type="button">Quitar selección</button>
        </div>
        <div id="vehiculosBox" class="vehiculos-box"></div>
        <div style="margin-top:8px;" class="chips" id="vehChips"></div>
      </div>
    </div>
    <div style="display:flex; gap:10px; justify-content:flex-end;">
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
      <button id="btnConfirmYes" class="ga-btn danger">Sí, continuar</button>
    </div>
  </div>
</div>

<script>
/* ===== Vehículos desde PHP ===== */
const VEHICULOS = <?php echo json_encode($vehiculos, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); ?>;

/* ===== Estado ===== */
let rows = [];
const $ = (q, ctx=document)=>ctx.querySelector(q);
const $$ = (q, ctx=document)=>[...ctx.querySelectorAll(q)];

/* ===== API helpers ===== */
async function api(action, payload=null){
  const opt = payload ? { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload) } : {};
  const r = await fetch('inventario_api.php?action='+action, opt);
  let j;
  try {
    j = await r.json();
  } catch(e) {
    const txt = await r.text().catch(()=> '');
    throw new Error((txt && txt.length<400? txt : '') || 'Respuesta no válida del servidor');
  }
  if(!j.ok) throw new Error(j.error||('Error '+action));
  return j.data;
}
async function loadData(){ rows = await api('list'); render(); }

/* ===== Modal helpers ===== */
function show(el, on=true){ el.classList.toggle('show', !!on); el.setAttribute('aria-hidden', on? 'false' : 'true'); }
function openForm(title, data){
  $('#modalTitle').textContent = title || 'Nuevo producto';
  $('#f_nombre').value   = data?.nombre || '';
  $('#f_marca').value    = data?.marca || '';
  $('#f_modelo').value   = data?.modelo || '';
  $('#f_cantidad').value = data?.cantidad ?? 0;
  $('#f_min').value      = data?.min ?? 0;
  $('#f_max').value      = data?.max ?? 0;
  $('#f_sku').value      = data?.sku || '';
  $('#f_costo').value    = (data?.costo ?? 0);
  $('#f_cont').value     = (data?.contenido ?? 1);
  $('#f_uni').value      = (data?.unidad || '');
  $('#btnGuardar').dataset.editId = data?.id ?? '';
  selectedVeh = new Set(data?.vehiculos || []);
  $('#vehSearch').value = '';
  renderVehiculosPicker(); renderVehChips();
  show($('#modalForm'), true); $('#f_nombre').focus();
}
function confirmDialog(msg, onYes){
  $('#confirmMsg').textContent = msg || '¿Seguro?';
  $('#btnConfirmYes').onclick = () => { show($('#modalConfirm'), false); onYes?.(); };
  show($('#modalConfirm'), true);
}

/* ===== Tabla ===== */
function estadoRow(r){ const low = Number(r.cantidad) <= Number(r.min); return { cls: low ? 'low-row' : '', badge: low ? '<span class="badge low">Stock bajo</span>' : '<span class="badge ok">OK</span>' }; }
function vehiculosResumidos(ids){
  if(!ids || !ids.length) return '—';
  const nombres = ids.map(id => { const v = VEHICULOS.find(x=>x.id===id); return v ? (v.placa || ('ID '+id)) : ('ID '+id); });
  return `${nombres.slice(0,3).join(', ')}${nombres.length>3? '… ('+nombres.length+' total)': ''}`;
}
function render(){
  const q = ($('#search').value||'').trim().toLowerCase();
  const tbody = $('#tbody'); tbody.innerHTML = '';
  rows
    .filter(r => [r.nombre, r.marca, r.modelo, String(r.id)].some(x => (x||'').toLowerCase().includes(q)))
    .forEach(r => {
      const st = estadoRow(r);
      const tr = document.createElement('tr'); tr.className = st.cls;
      tr.innerHTML = `
        <td>${r.id}</td>
        <td>${r.nombre}</td>
        <td>${r.marca||'—'}</td>
        <td>${r.modelo||'—'}</td>
        <td>${r.cantidad}</td>
        <td>${r.min}</td>
        <td>${r.max}</td>
        <td>${r.sku || '—'}</td>
        <td>$${Number(r.costo||0).toFixed(2)}</td>
        <td>${(r.contenido||1)} ${(r.unidad||'').trim()}</td>
        <td title="${(r.vehiculos||[]).map(id=>((VEHICULOS.find(v=>v.id===id)||{}).label||('ID '+id))).join('\\n')}">
          ${vehiculosResumidos(r.vehiculos||[])}
        </td>
        <td>
          <div class="row-actions">
            <button class="ga-btn secondary" data-edit="${r.id}">✏️ Editar</button>
            <button class="ga-btn warn" data-ajustar="${r.id}">➕➖ Ajustar</button>
            <button class="ga-btn danger" data-del="${r.id}">🗑️ Eliminar</button>
          </div>
        </td>`;
      tbody.appendChild(tr);
    });
}

/* ===== Picker de vehículos ===== */
let selectedVeh = new Set();
function renderVehiculosPicker(){
  const box = $('#vehiculosBox'); box.innerHTML = '';
  const q = ($('#vehSearch').value||'').toLowerCase();
  VEHICULOS
    .filter(v => [v.placa, v.tipo, v.suc, v.label].some(s => (s||'').toLowerCase().includes(q)))
    .forEach(v => {
      const row = document.createElement('div'); row.className = 'vehiculo-item';
      row.innerHTML = `
        <input type="checkbox" ${selectedVeh.has(v.id)?'checked':''} data-veh="${v.id}">
        <div class="vehiculo-label"><div>${v.placa || 'SN-PLACA'} · ${v.tipo}</div><small>${v.suc}</small></div>`;
      box.appendChild(row);
    });
}
function renderVehChips(){
  const chips = $('#vehChips'); chips.innerHTML = '';
  [...selectedVeh].slice(0,12).forEach(id => {
    const v = VEHICULOS.find(x=>x.id===id);
    const c = document.createElement('span'); c.className = 'chip';
    c.textContent = (v?.placa || ('ID '+id)) + ' · ' + (v?.suc||''); chips.appendChild(c);
  });
  if(selectedVeh.size>12){ const more = document.createElement('span'); more.className='chip'; more.textContent = `+${selectedVeh.size-12} más`; chips.appendChild(more); }
}

/* ===== Eventos ===== */
$('#btnNuevo').addEventListener('click', ()=> openForm('Nuevo producto'));
$('#search').addEventListener('input', render);
$$('[data-close]').forEach(b => b.addEventListener('click', ()=>{ show($('#modalForm'), false); show($('#modalConfirm'), false); }));

document.addEventListener('click', async (e)=>{
  const t = e.target;
  if(t.matches('[data-edit]')){
    const id = Number(t.dataset.edit);
    const item = rows.find(r=>r.id===id);
    if(item) openForm('Editar producto', item);
  }
  if(t.matches('[data-ajustar]')){
    const id = Number(t.dataset.ajustar);
    const item = rows.find(r=>r.id===id);
    if(item){ openForm('Ajustar stock - ' + item.nombre, item); }
  }
  if(t.matches('[data-del]')){
    const id = Number(t.dataset.del);
    const item = rows.find(r=>r.id===id);
    if(!item) return;
    confirmDialog(`¿Eliminar "${item.nombre}" (ID ${item.id}) del inventario?`, async ()=>{
      try{ await api('delete', {id}); await loadData(); } catch(e){ alert(e.message); }
    });
  }
});

$('#vehSearch').addEventListener('input', renderVehiculosPicker);
document.addEventListener('change', (e)=>{
  if(e.target.matches('[data-veh]')){
    const id = Number(e.target.dataset.veh);
    if(e.target.checked) selectedVeh.add(id); else selectedVeh.delete(id);
    renderVehChips();
  }
});
$('#btnSelTodos').addEventListener('click', ()=>{ VEHICULOS.forEach(v => selectedVeh.add(v.id)); renderVehiculosPicker(); renderVehChips(); });
$('#btnSelNinguno').addEventListener('click', ()=>{ selectedVeh.clear(); renderVehiculosPicker(); renderVehChips(); });

/* ===== Guardar (create/update) ===== */
$('#btnGuardar').addEventListener('click', async ()=>{
  const data = {
    id:       $('#btnGuardar').dataset.editId || undefined,
    nombre:   $('#f_nombre').value.trim(),
    marca:    $('#f_marca').value.trim(),
    modelo:   $('#f_modelo').value.trim(),
    cantidad: parseInt($('#f_cantidad').value||0),
    min:      parseInt($('#f_min').value||0),
    max:      parseInt($('#f_max').value||0),
    sku:      $('#f_sku').value.trim(),
    costo:    parseFloat($('#f_costo').value||0),
    contenido:parseFloat($('#f_cont').value||1),
    unidad:   $('#f_uni').value.trim(),
    vehiculos:[...selectedVeh]
  };
  if(!data.nombre){ alert('El nombre es obligatorio'); return; }
  if(data.min>0 && data.max>0 && data.min>data.max){ alert('El stock mínimo no puede ser mayor al máximo'); return; }
  if(data.costo<0){ alert('El costo no puede ser negativo'); return; }
  if(data.contenido<=0){ alert('El contenido debe ser mayor a 0'); return; }

  try{
    if(data.id){ await api('update', data); }
    else{ const resp = await api('create', data); data.id = resp.id; }
    show($('#modalForm'), false);
    await loadData();
  }catch(e){ alert(e.message); }
});

/* ===== Exportar CSV ===== */
$('#btnExport').addEventListener('click', ()=>{
  const header = ['id','nombre','marca','modelo','cantidad','stock_minimo','stock_maximo','sku','costo','presentacion_unidad','presentacion_cantidad','vehiculos_ids'];
  const lines = [header.join(',')].concat(
    rows.map(r => [
      r.id, `"${(r.nombre||'').replace(/"/g,'""')}"`, `"${(r.marca||'').replace(/"/g,'""')}"`,
      `"${(r.modelo||'').replace(/"/g,'""')}"`, r.cantidad, r.min, r.max,
      `"${(r.sku||'').replace(/"/g,'""')}"`, Number(r.costo||0).toFixed(2), `"${(r.unidad||'').replace(/"/g,'""')}"`, Number(r.contenido||1).toFixed(3),
      '"' + (r.vehiculos||[]).join('|') + '"'
    ].join(','))
  );
  const blob = new Blob([lines.join('\n')], {type:'text/csv;charset=utf-8;'});
  const a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download = 'inventario.csv'; a.click();
});

/* ===== Inicial ===== */
loadData();
</script>
</body>
</html>
