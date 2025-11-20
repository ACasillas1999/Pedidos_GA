<?php
ini_set('session.cookie_httponly', true);
ini_set('session.cookie_secure', true);
session_name('GA');
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
  header('location: /Pedidos_GA/Sesion/login.html');
  exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pedidos – Kanban</title>
  <link rel="icon" type="image/png" href="/Pedidos_GA/Img/Botones%20entregas/ICONOSPAG/ICONOPEDIDOS.png">
  <style>
    *{box-sizing:border-box}
    body{margin:0;font-family:system-ui,Segoe UI,Roboto,Arial,sans-serif;background:#f3f4f6;color:#111827}
    header{display:flex;gap:12px;align-items:center;justify-content:space-between;padding:12px 16px;background:#8b5cf6;color:#fff;position:sticky;top:0;z-index:2}
    a.btn{appearance:none;border:none;background:#1f2937;color:#fff;padding:8px 12px;border-radius:8px;cursor:pointer;text-decoration:none}
    .toolbar{display:flex;gap:8px;align-items:center;padding:10px 16px}
    .toolbar select,.toolbar input{padding:8px 10px;border:1px solid #d1d5db;border-radius:8px}
    .board{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:12px;padding:12px}
    .col{background:#f9fafb;border:1px solid #e5e7eb;border-radius:12px;display:flex;flex-direction:column;min-height:60vh}
    .col-header{padding:10px 12px;border-bottom:1px solid #e5e7eb;display:flex;justify-content:space-between;align-items:center}
    .col-body{padding:10px;display:flex;flex-direction:column;gap:10px}
    .card{background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:10px;cursor:grab}
    .card:active{cursor:grabbing}
    .meta{color:#6b7280;font-size:12px}
    .dropping{outline:2px dashed #8b5cf6}
    .count{background:#eef2ff;border:1px solid #c7d2fe;color:#3730a3;border-radius:999px;padding:2px 6px;font-size:12px}
  </style>
  <script>
    // Evita que el navegador use un cache viejo mientras probamos
    if ('serviceWorker' in navigator) { navigator.serviceWorker.getRegistrations().then(rs=>rs.forEach(r=>r.unregister())); }
  </script>
</head>
<body>
  <header>
    <strong>Pedidos – Kanban</strong>
    <div>
      <a class="btn" href="Pedidos_GA.php">Tabla</a>
      <a class="btn" href="pedidos_cards.php">Cards</a>
    </div>
  </header>

  <div class="toolbar">
    <input id="q" placeholder="Buscar cliente/dirección" style="min-width:260px" />
    <select id="fSucursal"><option value="">Todas las sucursales</option></select>
    <select id="fChofer"><option value="">Todos los choferes</option></select>
  </div>

  <section class="board" id="board"></section>

  <script>
    let data = [];
    let estados = [];
    const board = document.getElementById('board');
    const q = document.getElementById('q');
    const fSucursal = document.getElementById('fSucursal');
    const fChofer = document.getElementById('fChofer');

    function unique(arr,key){ return Array.from(new Set(arr.map(x=>x[key]).filter(Boolean))).sort(); }
    const norm = s => (s||'').toString().toLowerCase();
    const pass = p => {
      const qq = norm(q.value);
      if(qq && !norm(`${p.NOMBRE_CLIENTE} ${p.DIRECCION} ${p.FACTURA}`).includes(qq)) return false;
      if(fSucursal.value && p.SUCURSAL !== fSucursal.value) return false;
      if(fChofer.value && p.CHOFER_ASIGNADO !== fChofer.value) return false;
      return true;
    }

    function renderFilters(){
      unique(data,'SUCURSAL').forEach(v=>{ const o=document.createElement('option');o.value=v;o.textContent=v;fSucursal.appendChild(o); });
      unique(data,'CHOFER_ASIGNADO').forEach(v=>{ const o=document.createElement('option');o.value=v;o.textContent=v;fChofer.appendChild(o); });
    }

    function buildBoard(){
      estados = unique(data,'ESTADO');
      if(estados.length===0) estados=['SIN ESTADO'];
      board.innerHTML = '';
      const frag = document.createDocumentFragment();
      estados.forEach(est => {
        const col = document.createElement('section');
        col.className = 'col';
        col.dataset.estado = est;
        col.innerHTML = `
          <div class="col-header">
            <div><strong>${est}</strong></div>
            <span class="count" id="count-${CSS.escape(est)}">0</span>
          </div>
          <div class="col-body" data-drop="${est}"></div>`;
        frag.appendChild(col);
      });
      board.appendChild(frag);
      enableDnD();
      renderCards();
    }

    function cardEl(p){
      const el = document.createElement('article');
      el.className='card';
      el.draggable=true;
      el.dataset.id = p.ID;
      el.dataset.estado = p.ESTADO || '';
      el.innerHTML = `<div style="display:flex;justify-content:space-between;gap:8px;align-items:center;">
          <div style="font-weight:600;">#${p.ID} · ${p.NOMBRE_CLIENTE||''}</div>
          <a href="detalle_pedido.php?id=${encodeURIComponent(p.ID)}" style="text-decoration:none;color:#2563eb;font-size:12px">Detalles</a>
        </div>
        <div class="meta">${p.SUCURSAL||''} · Chofer: ${p.CHOFER_ASIGNADO||'—'}</div>
        <div class="meta">Factura: ${p.FACTURA||'-'} · Recepción: ${p.FECHA_RECEPCION_FACTURA||'-'}</div>`;
      return el;
    }

    function renderCards(){
      document.querySelectorAll('.col-body').forEach(c=>c.innerHTML='');
      const byEstado = new Map();
      estados.forEach(e=>byEstado.set(e,[]));
      data.filter(pass).forEach(p=>{
        const e = p.ESTADO || 'SIN ESTADO';
        if(!byEstado.has(e)) byEstado.set(e,[]);
        byEstado.get(e).push(p);
      });
      byEstado.forEach((list,est)=>{
        const body = document.querySelector(`.col-body[data-drop="${CSS.escape(est)}"]`);
        const count = document.getElementById(`count-${CSS.escape(est)}`);
        if(!body) return;
        const frag = document.createDocumentFragment();
        list.forEach(p=>frag.appendChild(cardEl(p)));
        body.appendChild(frag);
        if(count) count.textContent = String(list.length);
      });
    }

    function enableDnD(){
      let dragging = null;
      document.addEventListener('dragstart', e=>{
        const card = e.target.closest('.card');
        if(!card) return; dragging = card; e.dataTransfer.setData('text/plain', card.dataset.id);
      });
      document.addEventListener('dragover', e=>{
        const col = e.target.closest('.col-body');
        if(!col) return; e.preventDefault(); col.classList.add('dropping');
      });
      document.addEventListener('dragleave', e=>{
        const col = e.target.closest('.col-body');
        if(!col) return; col.classList.remove('dropping');
      });
      document.addEventListener('drop', async e=>{
        const col = e.target.closest('.col-body');
        if(!col) return; e.preventDefault(); col.classList.remove('dropping');
        if(!dragging) return;
        const id = dragging.dataset.id;
        const nuevo = col.getAttribute('data-drop');
        const prev = dragging.dataset.estado;
        col.appendChild(dragging);
        dragging.dataset.estado = nuevo;
        try{
          const resp = await fetch('actualizar_estado.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({id,estado:nuevo})});
          if(!resp.ok){ throw new Error('HTTP '+resp.status); }
        }catch(err){
          alert('No se pudo actualizar el estado en el servidor');
          dragging.dataset.estado = prev;
          renderCards();
        }finally{
          dragging = null; renderCards();
        }
      });
    }

    async function load(){
      try{
        const res = await fetch('obtener_pedidos.php',{credentials:'same-origin'});
        if(!res.ok) throw new Error('HTTP '+res.status);
        data = await res.json();
      }catch(e){
        board.innerHTML = '<div style="padding:24px;">No se pudo cargar la información o el formato no es válido.</div>';
        return;
      }
      renderFilters();
      buildBoard();
    }

    [q,fSucursal,fChofer].forEach(el=> el.addEventListener('input', ()=>renderCards()));
    load();
  </script>
</body>
</html>

