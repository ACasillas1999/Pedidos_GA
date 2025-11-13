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
    document.addEventListener("DOMContentLoaded", function() {
      var iconoInventario = document.querySelector(".icono-inventario");
      if (iconoInventario) {
        var imgNormalInventario = "/Pedidos_GA/Img/SVG/InventarioN.svg";
        var imgHoverInventario = "/Pedidos_GA/Img/SVG/InventarioB.svg";
        iconoInventario.addEventListener("mouseover", function() {
          this.src = imgHoverInventario;
        });
        iconoInventario.addEventListener("mouseout", function() {
          this.src = imgNormalInventario;
        });
      }

      var iconoVolver = document.querySelector(".icono-Volver");
      if (iconoVolver) {
        var imgNormalVolver = "/Pedidos_GA/Img/Botones%20entregas/Usuario/VOLVAZ.png";
        var imgHoverVolver = "/Pedidos_GA/Img/Botones%20entregas/Usuario/VOLVNA.png";
        iconoVolver.addEventListener("mouseover", function() {
          this.src = imgHoverVolver;
        });
        iconoVolver.addEventListener("mouseout", function() {
          this.src = imgNormalVolver;
        });
      }

      var iconoAgregar = document.querySelector(".icono-agregar_servicio");
      if (iconoAgregar) {
        var imgNormalAgregar = "/Pedidos_GA/Img/SVG/CrearSerN.svg";
        var imgHoverAgregar = "/Pedidos_GA/Img/SVG/CrearSerB.svg";
        iconoAgregar.addEventListener("mouseover", function() {
          this.src = imgHoverAgregar;
        });
        iconoAgregar.addEventListener("mouseout", function() {
          this.src = imgNormalAgregar;
        });
      }
    });
  </script>

  <div class="sidebar">
    <ul>
      <li>
        <a href="inventario.php">
          <img src="/Pedidos_GA/Img/SVG/InventarioN.svg" class="icono-inventario sidebar-icon" alt="Inventario">
        </a>
      </li>
      <li>
        <a href="agregar_servicio.php">
          <img src="/Pedidos_GA/Img/SVG/CrearSerN.svg" class="icono-agregar_servicio sidebar-icon" alt="Agregar">
        </a>
        </a>
      </li>
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
      :root {
        --bg: #f6f8fb;
        --panel: #ffffff;
        --muted: #475569;
        --text: #0f172a;
        --accent: #005996;
        --accent-2: #ED6C24;
        --ok: #16a34a;
        --warn: #d97706;
        --danger: #dc2626;
        --border: #e5e7eb;
        --shadow: 0 8px 20px rgba(15, 23, 42, .08);
        --radius: 16px;
        --tint-pend: #FEF2F2;
        --tint-prog: #FFFBEB;
        --tint-taller: #FFF7ED;
        --tint-comp: #F0FDF4;
        --tint-pend-b: #FECACA;
        --tint-prog-b: #FDE68A;
        --tint-taller-b: #FED7AA;
        --tint-comp-b: #BBF7D0;
      }

      body {
        background: var(--bg);
        color: var(--text);
        font-family: system-ui, -apple-system, Segoe UI, Roboto, Inter, Arial, sans-serif
      }

      .mantto-shell {
        background: transparent
      }

      .mantto-tabs {
        display: flex;
        gap: .5rem;
        align-items: center;
        margin: 0 0 1rem;
        flex-wrap: wrap
      }

      .mantto-tabs .btn {
        border: 1px solid var(--border);
        background: #fff;
        color: var(--text);
        padding: .55rem .9rem;
        border-radius: 12px;
        cursor: pointer;
        transition: .2s;
        font-weight: 600;
        box-shadow: var(--shadow)
      }

      .mantto-tabs .btn[aria-pressed="true"] {
        background: linear-gradient(135deg, #e6f0ff, #ffffff);
        border-color: #c7d2fe
      }

      .mantto-tabs .meta {
        margin-left: auto;
        color: var(--muted);
        font-size: .9rem
      }

      .mantto-wrap {
        display: block
      }

      .kanban {
        display: grid;
        gap: 1rem;
        grid-template-columns: repeat(4, minmax(260px, 1fr))
      }

      .col {
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: .75rem;
        min-height: 360px;
        box-shadow: var(--shadow);
        background: var(--panel)
      }

      .col h3 {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .5rem;
        font-size: 1rem;
        margin: 0 0 .5rem
      }

      .badge {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        font-size: .8rem;
        color: #fff;
        padding: .2rem .5rem;
        border-radius: 999px
      }

      .b-pendiente {
        background: var(--danger)
      }

      .b-programado {
        background: var(--warn)
      }

      .b-taller {
        background: var(--accent-2)
      }

      .b-completado {
        background: var(--ok)
      }

      .col-Pendiente {
        background: linear-gradient(180deg, var(--tint-pend), #fff)
      }

      .col-Pendiente .drop {
        background: #fff;
        border-color: var(--tint-pend-b)
      }

      .col-Programado {
        background: linear-gradient(180deg, var(--tint-prog), #fff)
      }

      .col-Programado .drop {
        background: #fff;
        border-color: var(--tint-prog-b)
      }

      .col-EnTaller {
        background: linear-gradient(180deg, var(--tint-taller), #fff)
      }

      .col-EnTaller .drop {
        background: #fff;
        border-color: var(--tint-taller-b)
      }

      .col-Completado {
        background: linear-gradient(180deg, var(--tint-comp), #fff)
      }

      .col-Completado .drop {
        background: #fff;
        border-color: var(--tint-comp-b)
      }

      .drop {
        display: flex;
        flex-direction: column;
        gap: .6rem;
        min-height: 290px;
        padding: .5rem;
        border: 1px dashed var(--border);
        border-radius: 12px;
        transition: .2s
      }

      .drop.over {
        box-shadow: inset 0 0 0 3px rgba(59, 130, 246, .15)
      }

      .card {
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 14px;
        padding: .75rem .75rem;
        box-shadow: var(--shadow);
        cursor: grab;
        user-select: none;
        transition: transform .15s ease, box-shadow .2s
      }

      .card:active {
        cursor: grabbing;
        transform: scale(.99)
      }

      .card .top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: .25rem
      }

      .card .title {
        font-weight: 700;
        letter-spacing: .2px
      }

      .card .meta {
        color: var(--muted);
        font-size: .9rem
      }

      .tags {
        display: flex;
        gap: .4rem;
        margin-top: .45rem;
        flex-wrap: wrap
      }

      .tag {
        font-size: .75rem;
        border: 1px solid var(--border);
        color: var(--muted);
        padding: .15rem .45rem;
        border-radius: 999px;
        background: #fff
      }

      .prio-alta {
        border-color: var(--danger);
        color: #b91c1c
      }

      .prio-media {
        border-color: var(--warn);
        color: #b45309
      }

      .prio-baja {
        border-color: var(--ok);
        color: #166534
      }

      .goto {
        margin-left: .5rem;
        font-size: .8rem;
        color: #1d4ed8;
        text-decoration: underline;
        cursor: pointer
      }

      .list-wrap {
        display: none
      }

      .list-tools {
        display: flex;
        gap: .5rem;
        margin: .25rem 0 .75rem
      }

      .list-tools input {
        background: #fff;
        border: 1px solid var(--border);
        color: var(--text);
        border-radius: 10px;
        padding: .5rem .7rem;
        width: 260px;
        box-shadow: var(--shadow)
      }

      table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0 10px
      }

      thead th {
        font-size: .85rem;
        color: var(--muted);
        font-weight: 700;
        text-align: left;
        padding: .4rem .6rem
      }

      tbody td {
        background: #fff;
        border: 1px solid var(--border);
        padding: .6rem .7rem;
        color: var(--text)
      }

      tbody tr {
        box-shadow: var(--shadow)
      }

      tbody td:first-child {
        border-top-left-radius: 12px;
        border-bottom-left-radius: 12px
      }

      tbody td:last-child {
        border-top-right-radius: 12px;
        border-bottom-right-radius: 12px
      }

      .status-pill {
        display: inline-block;
        padding: .15rem .5rem;
        border-radius: 999px;
        font-size: .75rem;
        font-weight: 700
      }

      .status-pill.s-pend {
        background: #fff6d6;
        color: #8a6d00;
        border: 1px solid #fde9a8
      }

      .status-pill.s-prog {
        background: #eef2ff;
        color: #1e3a8a;
        border: 1px solid #e5e7eb
      }

      .status-pill.s-taller {
        background: #fff1e6;
        color: #c2410c;
        border: 1px solid #fed7aa
      }

      .status-pill.s-comp {
        background: #e7f9e7;
        color: #217a21;
        border: 1px solid #cfeacf
      }

      /* Estilos para vista de Observaciones */
      .observaciones-wrap {
        background: var(--panel);
        border-radius: var(--radius);
        padding: 1.5rem;
      }

      .obs-search-box {
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 1rem;
        margin-bottom: 1.5rem;
        box-shadow: var(--shadow);
      }

      .obs-search-input {
        width: 100%;
        padding: .6rem 1rem;
        border: 1px solid var(--border);
        border-radius: 8px;
        font-size: .95rem;
        transition: .2s;
      }

      .obs-search-input:focus {
        outline: none;
        border-color: var(--accent);
        box-shadow: 0 0 0 3px rgba(0, 89, 150, 0.1);
      }

      .obs-search-meta {
        margin-top: .5rem;
        font-size: .85rem;
        color: var(--muted);
      }

      .obs-sections-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
        align-items: start;
      }

      @media (max-width: 1200px) {
        .obs-sections-grid {
          grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        }
      }

      @media (max-width: 768px) {
        .obs-sections-grid {
          grid-template-columns: 1fr;
        }
      }

      .obs-section {
        background: #fff;
        border: 2px solid #e5e7eb;
        border-radius: 18px;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
        overflow: hidden;
        transition: all .35s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
      }

      .obs-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--danger) 0%, #f97316 50%, var(--danger) 100%);
        opacity: 0;
        transition: opacity .3s;
      }

      .obs-section:hover::before {
        opacity: 1;
      }

      .obs-section:hover {
        transform: translateY(-6px);
        box-shadow: 0 12px 32px rgba(0, 0, 0, 0.12);
        border-color: #fca5a5;
      }

      .obs-section.collapsed {
        background: #fff;
      }

      .obs-section.collapsed:hover {
        background: linear-gradient(135deg, #fefefe 0%, #fafafa 100%);
      }

      .obs-section.expanded {
        background: #fff;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
      }

      .obs-section.expanded::before {
        opacity: 1;
        height: 5px;
      }

      .obs-section-header {
        padding: 1.5rem 1.75rem;
        cursor: pointer;
        transition: all .3s ease;
        user-select: none;
        background: linear-gradient(135deg, #fef8f8 0%, #fff 100%);
        border-bottom: 2px solid transparent;
        position: relative;
      }

      .obs-section-header::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 1.75rem;
        right: 1.75rem;
        height: 2px;
        background: linear-gradient(90deg, transparent 0%, #fee2e2 50%, transparent 100%);
        opacity: 0;
        transition: opacity .3s;
      }

      .obs-section.collapsed .obs-section-header {
        border-bottom: none;
        background: linear-gradient(135deg, #fafafa 0%, #fff 100%);
      }

      .obs-section.collapsed .obs-section-header:hover {
        background: linear-gradient(135deg, #fef2f2 0%, #fff7f7 100%);
      }

      .obs-section.expanded .obs-section-header::after {
        opacity: 1;
      }

      .obs-section-header:hover {
        background: linear-gradient(135deg, #fef2f2 0%, #fff 100%);
      }

      .obs-section-header:active {
        transform: scale(0.99);
      }

      .obs-section-header-content {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
      }

      .obs-section-header-left {
        display: flex;
        align-items: center;
        gap: .75rem;
        flex: 1;
      }

      .obs-section-icon {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, var(--danger) 0%, #dc2626 100%);
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 1.75rem;
        box-shadow: 0 6px 20px rgba(220, 38, 38, 0.35);
        transition: all .35s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
      }

      .obs-section-icon::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, rgba(255,255,255,0.3) 0%, transparent 100%);
        opacity: 0;
        transition: opacity .3s;
      }

      .obs-section:hover .obs-section-icon::before {
        opacity: 1;
      }

      .obs-section:hover .obs-section-icon {
        transform: scale(1.15) rotate(8deg);
        box-shadow: 0 8px 24px rgba(220, 38, 38, 0.45);
      }

      .obs-section.expanded .obs-section-icon {
        transform: scale(1.05);
        box-shadow: 0 4px 16px rgba(220, 38, 38, 0.3);
      }

      .obs-section-info {
        flex: 1;
      }

      .obs-section-title {
        font-size: 1.1rem;
        font-weight: 800;
        color: var(--text);
        margin-bottom: .35rem;
        line-height: 1.3;
        letter-spacing: .3px;
        text-transform: uppercase;
        background: linear-gradient(135deg, var(--text) 0%, #475569 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
      }

      .obs-section-subtitle {
        font-size: .85rem;
        color: var(--muted);
        display: flex;
        align-items: center;
        gap: .5rem;
        font-weight: 600;
      }

      .obs-section-subtitle span {
        display: inline-flex;
        align-items: center;
        gap: .25rem;
      }

      .obs-section-subtitle span::before {
        content: '';
        width: 4px;
        height: 4px;
        background: var(--muted);
        border-radius: 50%;
        display: inline-block;
      }

      .obs-section-subtitle span:first-child::before {
        display: none;
      }

      .obs-section-count {
        background: linear-gradient(135deg, var(--danger) 0%, #dc2626 100%);
        color: #fff;
        padding: .4rem .85rem;
        border-radius: 999px;
        font-size: .9rem;
        font-weight: 800;
        box-shadow: 0 3px 12px rgba(220, 38, 38, 0.4);
        min-width: 40px;
        text-align: center;
        border: 2px solid rgba(255, 255, 255, 0.9);
        transition: all .3s;
      }

      .obs-section:hover .obs-section-count {
        transform: scale(1.1);
        box-shadow: 0 4px 16px rgba(220, 38, 38, 0.5);
      }

      .obs-section-toggle {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #f8fafc 0%, #fff 100%);
        border-radius: 12px;
        transition: all .3s;
        border: 2px solid #e5e7eb;
        position: relative;
        overflow: hidden;
      }

      .obs-section-toggle::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, var(--accent) 0%, #0284c7 100%);
        opacity: 0;
        transition: opacity .3s;
      }

      .obs-section-toggle svg {
        transition: transform .35s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        z-index: 1;
      }

      .obs-section:hover .obs-section-toggle {
        border-color: var(--accent);
        transform: scale(1.05);
      }

      .obs-section:hover .obs-section-toggle::before {
        opacity: 0.1;
      }

      .obs-section.collapsed .obs-section-toggle {
        background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
        border-color: #cbd5e1;
      }

      .obs-section.collapsed .obs-section-toggle svg {
        transform: rotate(-90deg);
      }

      .obs-section.expanded .obs-section-toggle {
        background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%);
        border-color: var(--accent);
      }

      .obs-section.expanded .obs-section-toggle svg {
        stroke: var(--accent);
      }

      .obs-section-body {
        max-height: 0;
        overflow: hidden;
        transition: max-height .5s cubic-bezier(0.4, 0, 0.2, 1), padding .5s, opacity .3s;
        padding: 0 1.75rem;
        opacity: 0;
      }

      .obs-section.expanded .obs-section-body {
        max-height: 15000px;
        padding: 1.5rem 1.75rem 1.75rem;
        opacity: 1;
      }

      .obs-vehiculos {
        display: grid;
        gap: 1.25rem;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
      }

      @media (max-width: 768px) {
        .obs-vehiculos {
          grid-template-columns: 1fr;
        }
      }

      .obs-vehiculos .obs-card.expanded {
        box-shadow: 0 10px 30px rgba(220, 38, 38, .25);
        z-index: 10;
      }

      .obs-card {
        background: #fff;
        border: 2px solid #fecaca;
        border-radius: 12px;
        padding: 0;
        transition: all .3s ease;
        cursor: pointer;
        position: relative;
        overflow: hidden;
      }

      .obs-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 5px;
        height: 100%;
        background: linear-gradient(180deg, var(--danger) 0%, #fca5a5 100%);
        transition: width .3s ease;
      }

      .obs-card:hover {
        box-shadow: 0 6px 16px rgba(220, 38, 38, .15);
        transform: translateY(-3px);
        border-color: var(--danger);
      }

      .obs-card.expanded {
        border-color: var(--danger);
        box-shadow: 0 8px 24px rgba(220, 38, 38, .2);
      }

      .obs-card.expanded::before {
        width: 8px;
      }

      .obs-card.expanded:hover {
        transform: translateY(-1px);
      }

      .obs-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1rem 1rem 0 1.25rem;
        background: linear-gradient(180deg, #fef2f2 0%, #fff 100%);
        border-bottom: 1px solid #fee2e2;
        gap: .5rem;
        flex-wrap: wrap;
      }

      .obs-card.expanded .obs-card-header {
        padding-bottom: .75rem;
        background: linear-gradient(180deg, #fef2f2 0%, #fff7f7 100%);
      }

      .obs-placa {
        font-size: 1.15rem;
        font-weight: 800;
        color: var(--text);
        letter-spacing: .5px;
        display: flex;
        align-items: center;
        gap: .5rem;
      }

      .obs-placa::before {
        content: '🚗';
        font-size: 1.3rem;
      }

      .obs-tipo {
        font-size: .85rem;
        color: var(--muted);
        padding: 0 1.25rem .75rem;
        display: flex;
        align-items: center;
        gap: .5rem;
        background: linear-gradient(180deg, #fff 0%, #fafafa 100%);
      }

      .obs-tipo::before {
        content: '📍';
        font-size: .9rem;
      }

      .obs-card-compact-info {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: .75rem 1.25rem;
        background: #fafafa;
        border-top: 1px solid #f3f4f6;
      }

      .obs-items-count {
        background: linear-gradient(135deg, var(--danger) 0%, #dc2626 100%);
        color: #fff;
        padding: .35rem .7rem;
        border-radius: 8px;
        font-size: .8rem;
        font-weight: 700;
        box-shadow: 0 2px 4px rgba(220, 38, 38, .2);
        display: inline-flex;
        align-items: center;
        gap: .35rem;
      }

      .obs-items-count::before {
        content: '⚠️';
        font-size: .85rem;
      }

      .obs-card-details {
        max-height: 0;
        overflow: hidden;
        transition: max-height .4s cubic-bezier(0.4, 0, 0.2, 1), padding .4s;
        padding: 0 1.25rem;
      }

      .obs-card.expanded .obs-card-details {
        max-height: 2500px;
        padding: 1rem 1.25rem 1.25rem;
      }

      .obs-expand-indicator {
        font-size: .8rem;
        color: var(--accent);
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        padding: .35rem .6rem;
        background: rgba(0, 89, 150, 0.08);
        border-radius: 8px;
        transition: all .3s;
        border: 1px solid rgba(0, 89, 150, 0.15);
      }

      .obs-expand-indicator:hover {
        background: rgba(0, 89, 150, 0.12);
        transform: scale(1.05);
      }

      .obs-expand-indicator svg {
        width: 16px;
        height: 16px;
        transition: transform .3s;
      }

      .obs-card.expanded .obs-expand-indicator {
        color: var(--ok);
        background: rgba(22, 163, 74, 0.08);
        border-color: rgba(22, 163, 74, 0.15);
      }

      .obs-card.expanded .obs-expand-indicator:hover {
        background: rgba(22, 163, 74, 0.12);
      }

      .obs-card.expanded .obs-expand-indicator svg {
        transform: rotate(180deg);
      }

      .obs-expand-indicator::before {
        content: '👁️ Ver detalles';
        font-size: .8rem;
      }

      .obs-card.expanded .obs-expand-indicator::before {
        content: '📦 Ocultar';
      }

      .obs-item {
        background: linear-gradient(135deg, #fff 0%, #fef9f9 100%);
        padding: .75rem 1rem;
        border-radius: 10px;
        margin-bottom: .65rem;
        border: 1px solid #fee2e2;
        border-left: 3px solid var(--danger);
        transition: all .2s;
        box-shadow: 0 1px 3px rgba(0, 0, 0, .05);
      }

      .obs-item:hover {
        transform: translateX(4px);
        box-shadow: 0 2px 6px rgba(220, 38, 38, .1);
        border-left-width: 4px;
      }

      .obs-item:last-child {
        margin-bottom: 0;
      }

      .obs-item-name {
        font-weight: 700;
        color: var(--danger);
        font-size: .95rem;
        margin-bottom: .35rem;
        display: flex;
        align-items: center;
        gap: .5rem;
      }

      .obs-item-name::before {
        content: '🔴';
        font-size: .8rem;
        animation: pulse 2s ease-in-out infinite;
      }

      @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.6; }
      }

      .obs-item-obs {
        font-size: .85rem;
        color: var(--muted);
        line-height: 1.5;
        padding-left: 1.3rem;
        font-style: italic;
      }

      .obs-meta {
        display: flex;
        gap: 1.25rem;
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 2px solid #fee2e2;
        font-size: .8rem;
        color: var(--muted);
        flex-wrap: wrap;
      }

      .obs-meta > div {
        display: flex;
        align-items: center;
        gap: .35rem;
        font-weight: 600;
        padding: .25rem .5rem;
        background: #fafafa;
        border-radius: 6px;
      }

      .obs-empty {
        text-align: center;
        padding: 3rem 1rem;
        color: var(--muted);
        font-size: 1.1rem;
      }

      .obs-empty svg {
        width: 64px;
        height: 64px;
        margin: 0 auto 1rem;
        opacity: .3;
      }

      .btn-crear-orden {
        position: relative;
        overflow: hidden;
      }

      .btn-crear-orden::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        border-radius: 50%;
        background: rgba(255, 255, 255, .3);
        transform: translate(-50%, -50%);
        transition: width .4s, height .4s;
      }

      .btn-crear-orden:hover::before {
        width: 300px;
        height: 300px;
      }

      .btn-crear-orden:hover {
        background: linear-gradient(135deg, var(--accent) 0%, #004477 100%) !important;
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0, 89, 150, .35);
      }

      .btn-ver-orden {
        position: relative;
        overflow: hidden;
      }

      .btn-ver-orden:hover {
        background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%) !important;
        border-color: #94a3b8 !important;
        color: #1e293b !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(71, 85, 105, .25);
      }

      .obs-card-con-orden {
        opacity: 0.92;
      }

      .obs-card-con-orden::before {
        background: linear-gradient(180deg, #64748b 0%, #94a3b8 100%);
      }

      .obs-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .5rem;
        flex-wrap: wrap;
      }

      .obs-badge {
        white-space: nowrap;
        animation: pulse-badge 2s ease-in-out infinite;
      }

      @keyframes pulse-badge {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.8; }
      }

      @media (max-width:1200px) {
        .kanban {
          grid-template-columns: repeat(3, minmax(260px, 1fr))
        }
      }

      @media (max-width:900px) {
        .kanban {
          grid-template-columns: repeat(2, minmax(260px, 1fr))
        }
      }

      @media (max-width:640px) {
        .kanban {
          grid-template-columns: 1fr
        }

        .list-tools input {
          width: 100%
        }
      }

      /* estilos mínimos del modal si tu styles.css no los tiene */
      .modal-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, .35);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 50
      }

      .modal {
        background: #fff;
        border-radius: 16px;
        width: min(720px, 92vw);
        box-shadow: var(--shadow);
        padding: 12px 16px
      }

      .modal header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-bottom: 1px solid var(--border);
        margin: -12px -16px 12px;
        padding: 12px 16px
      }

      .modal .close {
        background: #f1f5f9;
        border: 1px solid var(--border);
        border-radius: 10px;
        padding: .25rem .55rem;
        cursor: pointer
      }

      .grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px
      }

      .grid-1 {
        display: block
      }

      .field label {
        display: block;
        font-size: .85rem;
        color: #475569;
        margin-bottom: 4px
      }

      .field input,
      .field select,
      .field textarea {
        width: 100%;
        border: 1px solid var(--border);
        border-radius: 10px;
        padding: .5rem .6rem;
        background: #fff
      }

      .actions {
        display: flex;
        gap: .5rem;
        justify-content: flex-end;
        margin-top: 10px
      }

      .btn-primary {
        background: #0a66c2;
        color: #fff;
        border: none;
        border-radius: 10px;
        padding: .55rem .9rem;
        cursor: pointer
      }

      .btn-ghost {
        background: #fff;
        color: #0f172a;
        border: 1px solid var(--border);
        border-radius: 10px;
        padding: .55rem .9rem;
        cursor: pointer
      }

      .toast {
        position: fixed;
        right: 16px;
        bottom: 16px;
        background: #0f172a;
        color: #fff;
        padding: .6rem .9rem;
        border-radius: 10px;
        box-shadow: var(--shadow);
        z-index: 60
      }

      /* Mejora de estilos para la vista de lista */
      .table-wrap {
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 12px;
        box-shadow: var(--shadow);
        overflow: auto
      }

      .list-wrap thead th {
        position: sticky;
        top: 0;
        background: #f8fafc;
        z-index: 1;
        border-bottom: 1px solid var(--border)
      }

      .list-wrap tbody tr:hover td {
        background: #f1f5f9
      }

      .list-wrap td button {
        border: 1px solid var(--border);
        background: #fff;
        border-radius: 8px;
        padding: .25rem .5rem;
        cursor: pointer
      }

      .list-wrap td button:hover {
        background: #0a66c2;
        color: #fff;
        border-color: #0a66c2
      }

      .sidebar-icon {
        width: 62px;
        height: auto;
        display: block;
        transition: transform .15s ease;
      }
    </style>

    <!-- ===== Script del kanban + modal, envuelto en DOMContentLoaded ===== -->
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        const API_URL = 'api_mantto.php';
        let COSTO_MINUTO = 0; // costo por minuto para MO auto

        const root = document.getElementById('mantto-root');
        if (!root) {
          console.error('No se encontro #mantto-root');
          return;
        }
        root.classList.add('mantto-shell');

        const ESTATUS = [{
            key: 'Pendiente',
            label: 'Pendiente',
            badge: 'b-pendiente'
          },
          {
            key: 'Programado',
            label: 'Programado',
            badge: 'b-programado'
          },
          {
            key: 'EnTaller',
            label: 'En Taller',
            badge: 'b-taller'
          },
          {
            key: 'Completado',
            label: 'Completado',
            badge: 'b-completado'
          },
        ];
        const state = {
          tab: 'board',
          items: [],
          allItems: [], // Todos los items sin filtrar
          observaciones: [], // Observaciones de vehículos con calificación "Mal"
          draggingId: null,
          fechaDesde: '',
          fechaHasta: '',
          options: {
            vehiculos: [],
            servicios: [],
            inventario: []
          }
        };

        // Función para obtener el primer y último día del mes actual
        function getCurrentMonthRange() {
          const now = new Date();
          const year = now.getFullYear();
          const month = now.getMonth();
          const firstDay = new Date(year, month, 1);
          const lastDay = new Date(year, month + 1, 0);
          return {
            desde: firstDay.toISOString().slice(0, 10),
            hasta: lastDay.toISOString().slice(0, 10)
          };
        }

        // Función para filtrar items por rango de fechas
        function filterByDateRange(items, desde, hasta) {
          if (!desde && !hasta) return items;
          return items.filter(it => {
            const fecha = it.fecha || it.fecha_programada || '';
            if (!fecha) return false;
            const fechaItem = fecha.slice(0, 10);
            if (desde && fechaItem < desde) return false;
            if (hasta && fechaItem > hasta) return false;
            return true;
          });
        }

        // Función para actualizar el info de fechas
        function updateFechaInfo() {
          if (!el.fechaInfo) return;
          const total = state.allItems.length;
          const filtered = state.items.length;
          if (state.fechaDesde || state.fechaHasta) {
            el.fechaInfo.textContent = `Mostrando ${filtered} de ${total} órdenes`;
          } else {
            el.fechaInfo.textContent = '';
          }
        }

        const fmt = n => new Intl.NumberFormat('es-MX').format(n ?? 0);
        const prioClass = p => p === 'Alta' ? 'prio-alta' : (p === 'Media' ? 'prio-media' : 'prio-baja');
        const humanStatus = k => (ESTATUS.find(e => e.key === k)?.label || k);
        const toast = (msg, ok = true) => {
          const t = document.getElementById('toast');
          if (!t) {
            alert(msg);
            return;
          } // <- guarda por si no existe el toast
          t.textContent = msg;
          t.style.background = ok ? '#0f172a' : '#9f1239';
          t.style.display = 'block';
          setTimeout(() => t.style.display = 'none', 2500);
        };

        async function apiGet(action) {
          const r = await fetch(`${API_URL}?action=${encodeURIComponent(action)}`, {
            credentials: 'same-origin'
          });
          return r.json();
        }
        // Reemplaza tu apiPost actual por esto:
        async function apiPost(action, data) {
          const r = await fetch(`${API_URL}?action=${encodeURIComponent(action)}`, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            credentials: 'same-origin',
            body: JSON.stringify(data) //  ya NO mandamos "action" dentro
          });
          return r.json();
        }


        root.innerHTML = `
        <div class="mantto-tabs">
          <button class="btn" data-tab="board" aria-pressed="true">Tablero (Drag & Drop)</button>
          <button class="btn" data-tab="list"  aria-pressed="false">Lista</button>
          <button class="btn" data-tab="observaciones"  aria-pressed="false">Observaciones</button>
          <span class="meta" id="mantto-count"></span>
          <button class="btn" id="btn-add">Agregar servicio</button>
        </div>

        <div class="list-tools" style="margin-bottom:1rem;display:flex;gap:.5rem;flex-wrap:wrap;align-items:center;background:#fff;padding:1rem;border-radius:12px;border:1px solid var(--border);box-shadow:var(--shadow)">
          <label style="font-weight:600;color:var(--text);font-size:.95rem">📅 Filtrar por fecha:</label>
          <input type="date" id="fecha-desde" style="background:#fff;border:1px solid var(--border);border-radius:10px;padding:.5rem .7rem;font-size:.9rem;font-weight:500">
          <span style="color:var(--muted);font-weight:500">hasta</span>
          <input type="date" id="fecha-hasta" style="background:#fff;border:1px solid var(--border);border-radius:10px;padding:.5rem .7rem;font-size:.9rem;font-weight:500">
          <button class="btn" id="btn-reset-fecha" style="background:#f8fafc;border:1px solid var(--border);color:var(--text);padding:.5rem .9rem;font-weight:600;display:inline-flex;align-items:center;gap:.35rem">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/><path d="M8 16H3v5"/></svg>
            Restablecer
          </button>
          <span class="meta" id="fecha-info" style="margin-left:.5rem;font-weight:600;color:var(--accent)"></span>
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

          <section class="observaciones-wrap" id="mantto-observaciones" style="display:none">
            <div id="observaciones-content"></div>
          </section>
        </div>
      `;

        const el = {
          count: root.querySelector('#mantto-count'),
          board: root.querySelector('#mantto-board'),
          listWrap: root.querySelector('#mantto-list'),
          q: root.querySelector('#mantto-q'),
          tabs: Array.from(root.querySelectorAll('.mantto-tabs .btn[data-tab]')),
          tbody: root.querySelector('#mantto-tbody'),
          btnAdd: root.querySelector('#btn-add'),
          fechaDesde: root.querySelector('#fecha-desde'),
          fechaHasta: root.querySelector('#fecha-hasta'),
          btnResetFecha: root.querySelector('#btn-reset-fecha'),
          fechaInfo: root.querySelector('#fecha-info'),
        };

        // Modal de confirmación de cambio de estatus
        const confirmEl = (function() {
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
                <div class="field grid-1" style="grid-column:1/-1;display:none" id="confirm-service-wrap">
                  <label for="confirm-service">Servicio</label>
                  <select id="confirm-service"></select>
                  <div style="color:#64748b; font-size:.9rem; margin-top:4px">Selecciona el servicio para esta orden.</div>
                </div>
                <div class="field grid-1" style="grid-column:1/-1;display:none" id="confirm-reset-wrap">
                  <label><input type="checkbox" id="confirm-reset"> Reiniciar Km_actual a 0</label>
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
            resetWrap: wrap.querySelector('#confirm-reset-wrap'),
            reset: wrap.querySelector('#confirm-reset'),
            serviceWrap: wrap.querySelector('#confirm-service-wrap'),
            service: wrap.querySelector('#confirm-service'),
            btnOk: wrap.querySelector('[data-ok]'),
            btnCancel: wrap.querySelector('[data-cancel]'),
            btnX: wrap.querySelector('[data-x]')
          };
        })();

        function openConfirm({
          id,
          to,
          onConfirm
        }) {
          const human = humanStatus(to);
          confirmEl.msg.textContent = `Confirmas mover la orden ${id} a ${human}? Esta operación no se podrá revertir.`;
          const needDate = (to === 'Programado');
          confirmEl.dateWrap.style.display = needDate ? 'block' : 'none';
          const needReset = (to === 'Completado');
          confirmEl.resetWrap.style.display = needReset ? 'block' : 'none';
          if (!needReset) confirmEl.reset.checked = false;
          if (needDate) {
            const hoy = new Date().toISOString().slice(0, 10);
            confirmEl.date.value = hoy;
          }
          // Si la orden no tiene servicio y vamos a Programado, pedirlo
          const cur = state.items.find(x => x.id === id);
          const needService = (to === 'Programado' && (!cur || !Number(cur.id_servicio || 0)));
          confirmEl.serviceWrap.style.display = needService ? 'block' : 'none';
          if (needService) {
            // Cargar catálogos si faltan
            const ensureOptions = async () => {
              if (!state.options.servicios.length || !state.options.vehiculos.length) {
                const res = await apiGet('options');
                if (res && res.ok) {
                  state.options = {
                    vehiculos: res.vehiculos || [],
                    servicios: res.servicios || [],
                    inventario: res.inventario || []
                  };
                }
              }
            };
            ensureOptions().then(() => {
              const vid = Number(cur?.id_vehiculo || 0);
              const servicios = (state.options.servicios || []).filter(s => !Array.isArray(s.vehiculos) || s.vehiculos.length === 0 || s.vehiculos.includes(vid));
              confirmEl.service.innerHTML = '<option value="" disabled selected>Selecciona...</option>' + servicios.map(s => `<option value="${s.id}">${s.nombre}</option>`).join('');
            });
          }

          function close() {
            confirmEl.wrap.style.display = 'none';
            cleanup();
          }

          function cleanup() {
            confirmEl.btnOk.removeEventListener('click', onOk);
            confirmEl.btnCancel.removeEventListener('click', close);
            confirmEl.btnX.removeEventListener('click', close);
            confirmEl.wrap.removeEventListener('click', onBackdrop);
          }

          function onBackdrop(e) {
            if (e.target === confirmEl.wrap) close();
          }
          async function onOk() {
            const payload = {
              id,
              estatus: to
            };
            if (to === 'Programado') {
              const val = (confirmEl.date.value || '').slice(0, 10);
              const hoy = new Date().toISOString().slice(0, 10);
              if (!val || val < hoy) {
                toast('La fecha no puede ser anterior a hoy', false);
                return;
              }
              payload.fecha_programada = val;
              if (confirmEl.serviceWrap.style.display !== 'none') {
                const srv = Number(confirmEl.service.value || 0);
                if (!srv) {
                  toast('Selecciona un servicio', false);
                  return;
                }
                // Asignar servicio antes de programar
                const res = await apiPost('set_service', {
                  id,
                  id_servicio: srv
                });
                if (!res || !res.ok) {
                  toast(res?.msg || 'No se pudo asignar el servicio', false);
                  return;
                }
              }
            }
            if (to === 'Completado' && confirmEl.reset && confirmEl.reset.checked) {
              payload.reset_km = true;
            }
            close();
            onConfirm(payload);
          }
          confirmEl.btnOk.addEventListener('click', onOk);
          confirmEl.btnCancel.addEventListener('click', close);
          confirmEl.btnX.addEventListener('click', close);
          confirmEl.wrap.addEventListener('click', onBackdrop);
          confirmEl.wrap.style.display = 'flex';
        }

        function card(it) {
          const div = document.createElement('div');
          div.className = 'card';
          div.draggable = true;
          div.dataset.id = it.id;

          div.addEventListener('dragstart', e => {
            state.draggingId = it.id;
            div.classList.add('dragging');
            e.dataTransfer.setData('text/plain', String(it.id));
          });
          div.addEventListener('dragend', () => {
            state.draggingId = null;
            div.classList.remove('dragging');
          });

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
          // Si no tiene servicio asignado, agrega un acceso para asignarlo
          try {
            if (!(Number(it.id_servicio || 0) > 0)) {
              const tags = div.querySelector('.tags');
              if (tags) {
                const a = document.createElement('span');
                a.className = 'goto';
                a.setAttribute('data-assign', String(it.id));
                a.title = 'Asignar servicio';
                a.textContent = 'asignar servicio';
                tags.appendChild(a);
              }
            }
          } catch (_) {}
          div.querySelector('.goto').addEventListener('click', () => {
            window.location.href = `/Pedidos_GA/Servicios/detalles_orden.php?id=${it.id}`;
          });
          const assignEl = div.querySelector('[data-assign]');
          if (assignEl) assignEl.addEventListener('click', () => openServicePicker(it.id));
          return div;
        }

        function onDrop(e) {
          e.preventDefault();
          this.classList.remove('over');
          const id = Number(e.dataTransfer.getData('text/plain')) || state.draggingId;
          const dest = this.dataset.status;
          if (!id || !dest) return;
          const cur = state.items.find(x => x.id === id);
          if (cur && cur.status === dest) return;
          const order = {
            'Pendiente': 0,
            'Programado': 1,
            'EnTaller': 2,
            'Completado': 3
          };
          const curIdx = order[cur?.status || 'Pendiente'] ?? 0;
          const destIdx = order[dest] ?? 0;
          if (destIdx < curIdx) {
            toast('No puedes retroceder estatus', false);
            return;
          }
          if (destIdx > curIdx + 1) {
            toast('Sigue la secuencia: Pendiente ? Programado ? En Taller ? Completado', false);
            return;
          }
          if (dest === 'Programado' && cur && cur.status !== 'Pendiente') {
            toast('Solo se puede reprogramar desde Programado (usa el detalle).', false);
            return;
          }

          openConfirm({
            id,
            to: dest,
            onConfirm: (payload) => {
              apiPost('update_status', payload).then(res => {
                if (!res.ok) {
                  toast(res.msg || 'No se pudo actualizar estatus', false);
                  loadList();
                  return;
                }
                toast(res.msg || 'Estatus actualizado');
                loadList();
              }).catch(() => toast('Error de red', false));
            }
          });
        }

        function renderBoard() {
          el.board.innerHTML = '';
          el.count.textContent = `${state.items.length} Ordenes`;
          updateFechaInfo();
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
            drop.addEventListener('dragover', e => {
              e.preventDefault();
              drop.classList.add('over');
            });
            drop.addEventListener('dragleave', () => drop.classList.remove('over'));
            drop.addEventListener('drop', onDrop);
            items.forEach(it => drop.appendChild(card(it)));
            el.board.appendChild(col);
          });
        }

        function renderList() {
          const q = (el.q?.value || '').trim().toLowerCase();
          const rows = state.items
            .filter(it => !q || [it.placa, it.tipo, it.servicio, it.status, it.prio, String(it.id)].join(' ').toLowerCase().includes(q))
            .sort((a, b) => a.id - b.id);

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

          el.tbody.querySelectorAll('button[data-move]').forEach(btn => {
            btn.addEventListener('click', () => {
              const id = Number(btn.getAttribute('data-move'));
              const to = btn.getAttribute('data-to');
              const cur = state.items.find(x => x.id === id);
              if (to === 'Programado' && cur && cur.status !== 'Pendiente') {
                toast('No puedes volver a Programado aqui. Usa reprogramacion en el detalle.', false);
                return;
              }
              openConfirm({
                id,
                to,
                onConfirm: (payload) => {
                  apiPost('update_status', payload).then(res => {
                    if (!res.ok) {
                      toast(res.msg || 'No se pudo actualizar', false);
                      loadList();
                      return;
                    }
                    toast(res.msg || 'Estatus actualizado');
                    loadList();
                  }).catch(() => toast('Error de red', false));
                }
              });
            });
          });
          // Agregar boton Asignar servicio dinámicamente a filas sin servicio
          try {
            const trs = Array.from(el.tbody.querySelectorAll('tr'));
            trs.forEach((tr, idx) => {
              const it = rows[idx];
              if (it && !(Number(it.id_servicio || 0) > 0)) {
                const cell = tr.querySelector('td:last-child');
                if (cell) {
                  const b = document.createElement('button');
                  b.setAttribute('data-assign', String(it.id));
                  b.textContent = 'Asignar servicio';
                  cell.insertBefore(b, cell.querySelector('button[data-goto]'));
                }
              }
            });
          } catch (_) {}
          el.tbody.querySelectorAll('button[data-assign]').forEach(btn => {
            btn.addEventListener('click', () => {
              const id = Number(btn.getAttribute('data-assign'));
              openServicePicker(id);
            });
          });
          el.tbody.querySelectorAll('button[data-goto]').forEach(btn => {
            btn.addEventListener('click', () => {
              const id = Number(btn.getAttribute('data-goto'));
              window.location.href = `/Pedidos_GA/Servicios/detalles_orden.php?id=${id}`;
            });
          });
        }

        // Función para renderizar observaciones agrupadas por sección
        function renderObservaciones() {
          const obsContent = root.querySelector('#observaciones-content');
          if (!obsContent) return;

          const obs = state.observaciones || [];

          if (obs.length === 0) {
            obsContent.innerHTML = `
              <div class="obs-empty">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div>No hay vehículos con calificación "Mal" en sus checklists</div>
              </div>
            `;
            return;
          }

          // Agrupar por sección
          const groupedBySection = {};
          obs.forEach(item => {
            const seccion = item.seccion || 'Sin sección';
            if (!groupedBySection[seccion]) {
              groupedBySection[seccion] = {};
            }
            const vehiculoKey = item.id_vehiculo;
            if (!groupedBySection[seccion][vehiculoKey]) {
              groupedBySection[seccion][vehiculoKey] = {
                id_vehiculo: item.id_vehiculo,
                placa: item.placa,
                tipo: item.tipo,
                Sucursal: item.Sucursal,
                Km_Actual: item.Km_Actual,
                orden_id: item.orden_id,
                orden_estatus: item.orden_estatus,
                items: []
              };
            }
            groupedBySection[seccion][vehiculoKey].items.push({
              item: item.item,
              observaciones: item.observaciones_rotulado,
              fecha_inspeccion: item.fecha_inspeccion,
              km_inspeccion: item.kilometraje
            });
          });

          // Generar HTML con buscador
          let html = `
            <div class="obs-search-box">
              <input type="text" id="obs-search" class="obs-search-input" placeholder="🔍 Buscar por placa...">
              <div class="obs-search-meta" id="obs-search-meta"></div>
            </div>
            <div class="obs-sections-grid" id="obs-sections-container">
          `;

          // Iconos para cada tipo de sección
          const sectionIcons = {
            'SISTEMA DE LUCES': '💡',
            'PARTE EXTERNA': '🚗',
            'PARTE INTERNA': '🪑',
            'ESTADO DE LLANTAS': '⚫',
            'default': '⚠️'
          };

          // Generar HTML por sección con cards tipo carpeta
          let sectionIndex = 0;
          Object.keys(groupedBySection).sort().forEach(seccion => {
            const vehiculos = Object.values(groupedBySection[seccion]);
            const totalVehiculos = vehiculos.length;
            const icon = sectionIcons[seccion] || sectionIcons['default'];
            const totalItems = vehiculos.reduce((sum, v) => sum + v.items.length, 0);
            const currentSectionIndex = sectionIndex; // Guardar el índice actual

            html += `
              <div class="obs-section collapsed" data-seccion="${seccion}" data-section-index="${currentSectionIndex}" id="obs-section-${currentSectionIndex}">
                <div class="obs-section-header" data-section-toggle="${currentSectionIndex}">
                  <div class="obs-section-header-content">
                    <div class="obs-section-header-left">
                      <div class="obs-section-icon">${icon}</div>
                      <div class="obs-section-info">
                        <div class="obs-section-title">${seccion}</div>
                        <div class="obs-section-subtitle">
                          <span>${totalVehiculos} vehículo${totalVehiculos !== 1 ? 's' : ''}</span>
                          <span>•</span>
                          <span>${totalItems} problema${totalItems !== 1 ? 's' : ''}</span>
                        </div>
                      </div>
                    </div>
                    <div style="display:flex;align-items:center;gap:.5rem">
                      <span class="obs-section-count">${totalVehiculos}</span>
                      <div class="obs-section-toggle">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                          <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="obs-section-body">
                  <div class="obs-vehiculos">
            `;

            vehiculos.forEach(veh => {
              const kmActual = veh.Km_Actual ? Number(veh.Km_Actual).toLocaleString() : 'N/A';
              const tieneOrden = veh.orden_id && veh.orden_id !== null;
              const estatusOrden = veh.orden_estatus || 'Pendiente';
              const totalItems = veh.items.length;

              // Colores y etiquetas por estatus
              const estatusInfo = {
                'Pendiente': { color: '#dc2626', bg: '#fef2f2', label: '⏳ Orden Pendiente' },
                'Programado': { color: '#d97706', bg: '#fffbeb', label: '📅 Orden Programada' },
                'EnTaller': { color: '#ea580c', bg: '#fff7ed', label: '🔧 En Taller' }
              };
              const info = estatusInfo[estatusOrden] || estatusInfo['Pendiente'];

              const cardId = `obs-card-${sectionIndex}-${veh.id_vehiculo}`;
              html += `
                <div class="obs-card ${tieneOrden ? 'obs-card-con-orden' : ''}" data-placa="${veh.placa || ''}" id="${cardId}" onclick="if(!event.target.closest('button')){document.getElementById('${cardId}').classList.toggle('expanded')}">
                  <div class="obs-card-header">
                    <div class="obs-placa">${veh.placa || 'Sin placa'}</div>
                    ${tieneOrden ? `<span class="obs-badge" style="background:${info.bg};color:${info.color};padding:.25rem .6rem;border-radius:6px;font-size:.75rem;font-weight:700;border:1px solid ${info.color}33;">${info.label}</span>` : ''}
                  </div>
                  <div class="obs-tipo">${veh.tipo || 'Sin tipo'} | ${veh.Sucursal || 'Sin sucursal'}</div>
                  <div class="obs-card-compact-info">
                    <span class="obs-items-count">${totalItems} ítem${totalItems !== 1 ? 's' : ''} con problemas</span>
                    <span class="obs-expand-indicator">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 12 15 18 9"></polyline>
                      </svg>
                    </span>
                  </div>

                  <div class="obs-card-details">
              `;

              veh.items.forEach(it => {
                const obs = it.observaciones ? it.observaciones : '';
                html += `
                  <div class="obs-item">
                    <div class="obs-item-name">${it.item || 'Sin descripción'}</div>
                    ${obs ? `<div class="obs-item-obs">${obs}</div>` : ''}
                  </div>
                `;
              });

              const fechaInspeccion = veh.items[0]?.fecha_inspeccion ? new Date(veh.items[0].fecha_inspeccion).toLocaleDateString('es-MX') : 'N/A';
              const kmInspeccion = veh.items[0]?.km_inspeccion ? Number(veh.items[0].km_inspeccion).toLocaleString() : 'N/A';

              html += `
                    <div class="obs-meta">
                      <div>📅 ${fechaInspeccion}</div>
                      <div>🚗 ${kmInspeccion} km</div>
                      <div>📊 Actual: ${kmActual} km</div>
                    </div>
              `;

              if (tieneOrden) {
                html += `
                    <button class="btn-ver-orden" data-orden="${veh.orden_id}" style="width:100%;margin-top:.75rem;background:#f1f5f9;color:#475569;border:1px solid #cbd5e1;padding:.6rem 1rem;border-radius:8px;font-weight:600;cursor:pointer;transition:.2s;" onclick="event.stopPropagation()">
                      👁️ Ver Orden #${veh.orden_id}
                    </button>
                `;
              } else {
                html += `
                    <button class="btn-crear-orden" data-vehiculo="${veh.id_vehiculo}" data-seccion="${seccion}" style="width:100%;margin-top:.75rem;background:var(--accent);color:#fff;border:none;padding:.6rem 1rem;border-radius:8px;font-weight:600;cursor:pointer;transition:.2s;" onclick="event.stopPropagation()">
                      ➕ Crear Orden de Servicio
                    </button>
                `;
              }

              html += `
                  </div>
                </div>
              `;
            });

            html += `
                </div>
              </div>
            `;

            sectionIndex++; // Incrementar para la siguiente sección
          });

          html += `</div>`; // Cierra obs-sections-container

          obsContent.innerHTML = html;

          // Event listeners para búsqueda
          const searchInput = document.getElementById('obs-search');
          const searchMeta = document.getElementById('obs-search-meta');

          if (searchInput) {
            searchInput.addEventListener('input', (e) => {
              const query = e.target.value.toLowerCase().trim();
              const allCards = obsContent.querySelectorAll('.obs-card');
              const allSections = obsContent.querySelectorAll('.obs-section');

              if (query === '') {
                // Mostrar todo
                allCards.forEach(card => card.style.display = '');
                allSections.forEach(section => {
                  section.style.display = '';
                  const visibleCards = section.querySelectorAll('.obs-card');
                  const count = visibleCards.length;
                  section.querySelector('.obs-section-count').textContent = `${count} vehículo${count !== 1 ? 's' : ''}`;
                });
                searchMeta.textContent = '';
                return;
              }

              let visibleCount = 0;
              allSections.forEach(section => {
                const cards = section.querySelectorAll('.obs-card');
                let sectionVisibleCount = 0;

                cards.forEach(card => {
                  const placa = card.getAttribute('data-placa').toLowerCase();
                  if (placa.includes(query)) {
                    card.style.display = '';
                    sectionVisibleCount++;
                    visibleCount++;
                  } else {
                    card.style.display = 'none';
                  }
                });

                // Ocultar sección si no tiene vehículos visibles
                if (sectionVisibleCount === 0) {
                  section.style.display = 'none';
                } else {
                  section.style.display = '';
                  section.classList.remove('collapsed'); // Expandir sección con resultados
                  section.querySelector('.obs-section-count').textContent = `${sectionVisibleCount} vehículo${sectionVisibleCount !== 1 ? 's' : ''}`;
                }
              });

              searchMeta.textContent = visibleCount > 0
                ? `Se encontraron ${visibleCount} vehículo${visibleCount !== 1 ? 's' : ''} con la placa "${query}"`
                : `No se encontraron vehículos con la placa "${query}"`;
            });
          }

          // Agregar event listeners para el acordeón de secciones
          obsContent.querySelectorAll('[data-section-toggle]').forEach(header => {
            header.addEventListener('click', () => {
              const sectionIndex = header.getAttribute('data-section-toggle');
              const clickedSection = document.getElementById(`obs-section-${sectionIndex}`);
              if (!clickedSection) return;

              const isCurrentlyExpanded = clickedSection.classList.contains('expanded');

              // Colapsar todas las secciones
              obsContent.querySelectorAll('.obs-section').forEach(section => {
                section.classList.remove('expanded');
                section.classList.add('collapsed');
              });

              // Si la sección clickeada estaba colapsada, expandirla
              if (!isCurrentlyExpanded) {
                clickedSection.classList.remove('collapsed');
                clickedSection.classList.add('expanded');
              }
            });
          });

          // Agregar event listeners a los botones de crear orden
          obsContent.querySelectorAll('.btn-crear-orden').forEach(btn => {
            btn.addEventListener('click', () => {
              const idVehiculo = parseInt(btn.getAttribute('data-vehiculo'));
              const seccion = btn.getAttribute('data-seccion');
              crearOrdenDesdeObservacion(idVehiculo, seccion);
            });
          });

          // Agregar event listeners a los botones de ver orden
          obsContent.querySelectorAll('.btn-ver-orden').forEach(btn => {
            btn.addEventListener('click', () => {
              const ordenId = parseInt(btn.getAttribute('data-orden'));
              window.location.href = `/Pedidos_GA/Servicios/detalles_orden.php?id=${ordenId}`;
            });
          });
        }

        // Función para crear orden de servicio desde observaciones
        async function crearOrdenDesdeObservacion(idVehiculo, seccion) {
          // Encontrar el vehículo en las observaciones
          const obsVehiculo = state.observaciones.find(o => o.id_vehiculo === idVehiculo && o.seccion === seccion);
          if (!obsVehiculo) {
            toast('No se encontró información del vehículo', false);
            return;
          }

          // Obtener todos los ítems con "Mal" de este vehículo en esta sección
          const itemsMal = state.observaciones
            .filter(o => o.id_vehiculo === idVehiculo && o.seccion === seccion)
            .map(o => o.item)
            .join(', ');

          // Crear nota indicando que viene de observaciones
          const nota = `[OBSERVACIONES - ${seccion}] Ítems detectados: ${itemsMal}`;

          // Crear la orden de servicio sin servicio asignado (pendiente)
          const payload = {
            id_vehiculo: idVehiculo,
            id_servicio: 0, // Sin servicio por ahora, se asignará después
            duracion_minutos: 60, // Duración por defecto
            notas: nota,
            materiales: []
          };

          try {
            const res = await apiPost('create', payload);
            if (!res || !res.ok) {
              toast(res?.msg || 'No se pudo crear la orden', false);
              return;
            }

            toast(`✅ Orden creada para ${obsVehiculo.placa} desde observaciones`);

            // Recargar la lista y cambiar a vista de tablero
            await loadList();
            setTab('board');
          } catch (error) {
            toast('Error al crear la orden', false);
            console.error(error);
          }
        }

        // Modal para asignar servicio a una OS pendiente sin servicio
        function openServicePicker(id) {
          const cur = state.items.find(x => x.id === id);
          confirmEl.msg.textContent = `Asignar servicio a la orden ${id}`;
          confirmEl.dateWrap.style.display = 'none';
          confirmEl.serviceWrap.style.display = 'block';
          const ensureOptions = async () => {
            if (!state.options.servicios.length || !state.options.vehiculos.length) {
              const res = await apiGet('options');
              if (res && res.ok) {
                state.options = {
                  vehiculos: res.vehiculos || [],
                  servicios: res.servicios || [],
                  inventario: res.inventario || []
                };
              }
            }
          };
          ensureOptions().then(() => {
            const vid = Number(cur?.id_vehiculo || 0);
            const servicios = (state.options.servicios || []).filter(s => !Array.isArray(s.vehiculos) || s.vehiculos.length === 0 || s.vehiculos.includes(vid));
            confirmEl.service.innerHTML = '<option value="" disabled selected>Selecciona...</option>' + servicios.map(s => `<option value="${s.id}">${s.nombre}</option>`).join('');
          });

          function close() {
            confirmEl.wrap.style.display = 'none';
            cleanup();
          }

          function cleanup() {
            confirmEl.btnOk.removeEventListener('click', onOk);
            confirmEl.btnCancel.removeEventListener('click', close);
            confirmEl.btnX.removeEventListener('click', close);
            confirmEl.wrap.removeEventListener('click', onBack);
          }

          function onBack(e) {
            if (e.target === confirmEl.wrap) close();
          }
          async function onOk() {
            const srv = Number(confirmEl.service.value || 0);
            if (!srv) {
              toast('Selecciona un servicio', false);
              return;
            }
            const res = await apiPost('set_service', {
              id,
              id_servicio: srv
            });
            if (!res || !res.ok) {
              toast(res?.msg || 'No se pudo asignar', false);
              return;
            }
            close();
            toast('Servicio asignado');
            loadList();
          }
          confirmEl.btnOk.addEventListener('click', onOk);
          confirmEl.btnCancel.addEventListener('click', close);
          confirmEl.btnX.addEventListener('click', close);
          confirmEl.wrap.addEventListener('click', onBack);
          confirmEl.wrap.style.display = 'flex';
        }

        function setTab(tab) {
          state.tab = tab;
          el.tabs.forEach(b => b.setAttribute('aria-pressed', String(b.dataset.tab === tab)));

          const kanban = root.querySelector('.kanban');
          const listWrap = root.querySelector('.list-wrap');
          const obsWrap = root.querySelector('.observaciones-wrap');

          if (tab === 'board') {
            kanban.style.display = 'grid';
            listWrap.style.display = 'none';
            obsWrap.style.display = 'none';
          } else if (tab === 'list') {
            kanban.style.display = 'none';
            listWrap.style.display = 'block';
            obsWrap.style.display = 'none';
          } else if (tab === 'observaciones') {
            kanban.style.display = 'none';
            listWrap.style.display = 'none';
            obsWrap.style.display = 'block';
            renderObservaciones();
          }
        }
        el.tabs.forEach(b => b.addEventListener('click', () => setTab(b.dataset.tab)));
        root.querySelector('#mantto-q').addEventListener('input', renderList);

        // ===== Modal (ahora sí, después de que TODO el DOM existe) =====
        const mb = document.getElementById('modal-os');
        const f = document.getElementById('form-os');
        const selVeh = document.getElementById('vehiculo');
        const selSrv = document.getElementById('servicio');
        const containerMats = document.getElementById('mat-rows');

        const btnAddMat = document.getElementById('btn-add-mat');

        if (!mb || !f) {
          console.error('Modal o formulario no encontrados');
        }

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
          row.querySelector('.mat-del').addEventListener('click', () => row.remove());
          containerMats.appendChild(row);
        }

        function openModal() {
          if (!mb) return;
          mb.style.display = 'flex';
          if (!state.options.vehiculos.length || !state.options.servicios.length) {
            Promise.all([
              apiGet('options'),
              fetch('servicios_api.php?action=config', {
                credentials: 'same-origin'
              }).then(r => r.json()).catch(() => ({
                ok: true,
                data: {
                  costo_minuto_mo: 0
                }
              }))
            ]).then(([res, cfg]) => {
              if (!res.ok) {
                toast('No se pudieron cargar catalogos', false);
                return;
              }
              state.options = {
                vehiculos: res.vehiculos || [],
                servicios: res.servicios || [],
                inventario: res.inventario || []
              };
              if (cfg && cfg.ok && cfg.data && typeof cfg.data.costo_minuto_mo !== 'undefined') {
                COSTO_MINUTO = Number(cfg.data.costo_minuto_mo) || 0;
              }
              if (selVeh) selVeh.innerHTML = '<option value="" disabled selected>Seleccionado</option>' +
                state.options.vehiculos.map(v => `<option value="${v.id}">${v.placa} - ${v.tipo} - ${fmt(v.km)}</option>`).join('');
              // servicios se filtran al elegir vehículo
              if (selSrv) selSrv.innerHTML = '<option value="" disabled selected>Seleccionado</option>';
              renderMoAuto2();
            }).catch(() => toast('Error de red (catalogos)', false));
          } else {
            // ya tenemos catálogos; al abrir solo recalcula el MO con lo actual
            renderMoAuto2();
          }
        }

        function closeModal() {
          if (!mb || !f) return;
          mb.style.display = 'none';
          f.reset();
          if (containerMats) containerMats.innerHTML = '';
        }

        document.querySelectorAll('[data-close]').forEach(x => x.addEventListener('click', closeModal));
        el.btnAdd.addEventListener('click', openModal);
        mb?.addEventListener('click', (e) => {
          if (e.target === mb) closeModal();
        });

        selVeh?.addEventListener('change', () => {
          const vid = Number(selVeh.value || 0);
          const servicios = (state.options.servicios || []).filter(s => !Array.isArray(s.vehiculos) || s.vehiculos.length === 0 || s.vehiculos.includes(vid));
          selSrv.innerHTML = '<option value="" disabled selected>Seleccionado</option>' + servicios.map(s => `<option value="${s.id}" data-d="${s.duracion_minutos}">${s.nombre}</option>`).join('');
          // limpiar materiales y preparar inventario filtrado
          if (containerMats) containerMats.innerHTML = '';
        });

        selSrv?.addEventListener('change', () => {
          // Al seleccionar servicio, cargar materiales del catalogo en la lista editable
          const vid = Number(selVeh.value || 0);
          const inv = (state.options.inventario || []).filter(i => !Array.isArray(i.vehiculos) || i.vehiculos.length === 0 || i.vehiculos.includes(vid));
          // No adjuntar listener aqui para evitar duplicados al cambiar servicio
          const srvId = Number(selSrv.value || 0);
          if (srvId) {
            fetch('servicios_api.php?action=get&id=' + srvId, {
                credentials: 'same-origin'
              })
              .then(r => r.json())
              .then(j => {
                if (!j || !j.ok) return;
                const mats = Array.isArray(j.data?.materiales) ? j.data.materiales : [];
                if (containerMats) containerMats.innerHTML = '';
                const notCompat = [];
                mats.forEach(m => {
                  const exists = inv.find(x => x.id === m.id_inventario);
                  if (!exists) {
                    notCompat.push(m.id_inventario);
                    return;
                  }
                  addMatRow(inv);
                  const last = containerMats.lastElementChild;
                  if (!last) return;
                  const sel = last.querySelector('.mat-inv');
                  const qty = last.querySelector('.mat-cant');
                  if (sel) sel.value = String(m.id_inventario);
                  if (qty) qty.value = String(m.cantidad);
                });
                if (notCompat.length) {
                  const labels = notCompat.map(id => {
                    const it = (state.options.inventario || []).find(x => x.id === id);
                    return it ? it.nombre : ('ID ' + id);
                  }).join(', ');
                  toast('Algunos insumos del servicio no aplican al vehiculo: ' + labels, false);
                }
              }).catch(() => {});
          }
        });

        if (btnAddMat) {
          // Garantiza un solo handler: evita acumulaciones por reabrir el modal
          btnAddMat.onclick = () => {
            const vid = Number(selVeh.value || 0);
            const inv = (state.options.inventario || []).filter(i => !Array.isArray(i.vehiculos) || i.vehiculos.length === 0 || i.vehiculos.includes(vid));
            addMatRow(inv);
          };
        }

        // Cálculo automático de MO en este modal
        function renderMoAuto2() {
          const dur = document.getElementById('duracion_minutos');
          const span = document.getElementById('moAuto2');
          if (!dur || !span) return;
          const d = Number(dur.value || 0);
          const mo = (d > 0 ? d * (COSTO_MINUTO || 0) : 0);
          span.textContent = mo.toFixed(2);
        }
        document.getElementById('duracion_minutos')?.addEventListener('input', renderMoAuto2);

        f?.addEventListener('submit', (e) => {
          e.preventDefault();
          const mats = Array.from(containerMats?.querySelectorAll('.grid') || []).map(row => {
            const id_inv = Number(row.querySelector('.mat-inv')?.value || 0);
            const cant = Number(row.querySelector('.mat-cant')?.value || 0);
            return (id_inv > 0 && cant > 0) ? {
              id_inventario: id_inv,
              cantidad: cant
            } : null;
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
          apiPost('create', data).then(res => {
            if (!res.ok) {
              toast(res.msg || 'No se pudo guardar', false);
              return;
            }
            closeModal();
            toast('Orden creada correctamente');
            loadList();
          }).catch(() => toast('Error de red al guardar', false));
        });

        // Función para aplicar los filtros de fecha
        function applyDateFilters() {
          state.fechaDesde = el.fechaDesde.value || '';
          state.fechaHasta = el.fechaHasta.value || '';
          state.items = filterByDateRange(state.allItems, state.fechaDesde, state.fechaHasta);
          renderBoard();
          renderList();
        }

        async function loadList() {
          const res = await apiGet('list');
          if (!res.ok) {
            toast('No se pudo cargar la lista', false);
            return;
          }
          state.allItems = res.items || [];
          applyDateFilters();

          // Cargar observaciones
          const obsRes = await apiGet('observaciones');
          if (obsRes && obsRes.ok) {
            state.observaciones = obsRes.items || [];
          }

          setTab('board');
        }

        // Inicializar fechas con el mes actual
        const currentMonth = getCurrentMonthRange();
        if (el.fechaDesde) el.fechaDesde.value = currentMonth.desde;
        if (el.fechaHasta) el.fechaHasta.value = currentMonth.hasta;
        state.fechaDesde = currentMonth.desde;
        state.fechaHasta = currentMonth.hasta;

        // Event listeners para los filtros de fecha
        if (el.fechaDesde) {
          el.fechaDesde.addEventListener('change', applyDateFilters);
        }
        if (el.fechaHasta) {
          el.fechaHasta.addEventListener('change', applyDateFilters);
        }
        if (el.btnResetFecha) {
          el.btnResetFecha.addEventListener('click', () => {
            const currentMonth = getCurrentMonthRange();
            if (el.fechaDesde) el.fechaDesde.value = currentMonth.desde;
            if (el.fechaHasta) el.fechaHasta.value = currentMonth.hasta;
            applyDateFilters();
          });
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