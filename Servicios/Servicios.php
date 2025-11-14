<?php
// Iniciar la sesión de forma segura
ini_set('session.cookie_httponly', true);
ini_set('session.cookie_secure', true);
session_name("GA");
session_start();

// SEGURIDAD: Verificar que el usuario tenga sesión activa
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: /Pedidos_GA/Sesion/login.html");
    exit;
}

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

      var iconoObservaciones = document.querySelector(".icono-observaciones");
      if (iconoObservaciones) {
        iconoObservaciones.addEventListener("mouseover", function() {
          this.style.transform = "scale(1.1)";
        });
        iconoObservaciones.addEventListener("mouseout", function() {
          this.style.transform = "scale(1)";
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
      </li>
      <li>
        <a href="observaciones.php" title="Observaciones de Vehículos">
          <span class="icono-observaciones sidebar-icon" style="font-size: 2rem; transition: transform 0.2s;">🔍</span>
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

      /* Estilos para vista de Observaciones - Diseño Moderno tipo Dashboard + menú lateral */
      .observaciones-wrap {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 16px;
        padding: 2rem;
      }

      .observaciones-wrap > #observaciones-content {
        min-height: 600px;
        display: grid;
        grid-template-columns: 260px 1fr;
        gap: 2rem;
      }

      @media (max-width: 1200px) {
        .observaciones-wrap > #observaciones-content {
          grid-template-columns: 1fr;
        }
      }

      .obs-sidebar {
        display: flex;
        flex-direction: column;
        gap: 1rem;
      }

      .obs-main-content {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
        min-width: 0;
      }

      .obs-search-box {
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        border: 2px solid #dee2e6;
        border-radius: 16px;
        padding: 1.5rem;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        position: relative;
        overflow: hidden;
      }

      .obs-search-box::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #6366f1 0%, #8b5cf6 50%, #d946ef 100%);
      }

      .obs-filter-buttons {
        display: flex;
        flex-direction: column;
        gap: .75rem;
        background: #ffffff;
        border: 2px solid #dee2e6;
        border-radius: 16px;
        padding: 1.25rem;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        position: sticky;
        top: 1rem;
      }

      .obs-filter-title {
        font-size: 1rem;
        font-weight: 800;
        color: #212529;
        margin-bottom: .5rem;
        padding-bottom: .75rem;
        border-bottom: 2px solid #e9ecef;
        text-transform: uppercase;
        letter-spacing: .5px;
      }

      .obs-filter-btn {
        padding: .85rem 1rem;
        border: 2px solid transparent;
        border-radius: 10px;
        font-weight: 700;
        font-size: .8rem;
        cursor: pointer;
        transition: all .3s;
        text-transform: uppercase;
        letter-spacing: .3px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .5rem;
        background: #f8f9fa;
        color: #495057;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        width: 100%;
        text-align: left;
      }

      .obs-filter-btn:hover {
        transform: translateX(4px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
      }

      .obs-filter-btn.active {
        transform: translateX(4px);
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
      }

      .obs-filter-btn-text {
        display: flex;
        align-items: center;
        gap: .65rem;
        flex: 1;
      }

      .obs-filter-btn-icon {
        font-size: 1.1rem;
      }

      .obs-filter-btn-label {
        font-size: .75rem;
        line-height: 1.3;
      }

      .obs-filter-btn .filter-count {
        background: rgba(0, 0, 0, 0.1);
        padding: .2rem .5rem;
        border-radius: 6px;
        font-size: .75rem;
        font-weight: 800;
      }

      .obs-filter-btn:hover .filter-count,
      .obs-filter-btn.active .filter-count {
        background: rgba(255, 255, 255, 0.25);
        color: #ffffff;
      }

      /* Colores de los filtros por sección */
      .obs-filter-btn[data-filter="SISTEMA DE LUCES"] {
        border-color: #fbbf24;
        color: #b45309;
      }

      .obs-filter-btn[data-filter="SISTEMA DE LUCES"]:hover,
      .obs-filter-btn[data-filter="SISTEMA DE LUCES"].active {
        background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
        color: #ffffff;
        border-color: #f59e0b;
      }

      .obs-filter-btn[data-filter="PARTE EXTERNA"] {
        border-color: #3b82f6;
        color: #1e40af;
      }

      .obs-filter-btn[data-filter="PARTE EXTERNA"]:hover,
      .obs-filter-btn[data-filter="PARTE EXTERNA"].active {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: #ffffff;
        border-color: #2563eb;
      }

      .obs-filter-btn[data-filter="PARTE INTERNA"] {
        border-color: #8b5cf6;
        color: #6b21a8;
      }

      .obs-filter-btn[data-filter="PARTE INTERNA"]:hover,
      .obs-filter-btn[data-filter="PARTE INTERNA"].active {
        background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
        color: #ffffff;
        border-color: #7c3aed;
      }

      .obs-filter-btn[data-filter="ESTADO DE LLANTAS"] {
        border-color: #1f2937;
        color: #111827;
      }

      .obs-filter-btn[data-filter="ESTADO DE LLANTAS"]:hover,
      .obs-filter-btn[data-filter="ESTADO DE LLANTAS"].active {
        background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
        color: #ffffff;
        border-color: #111827;
      }

      .obs-filter-btn[data-filter="ACCESORIOS DE SEGURIDAD"] {
        border-color: #ef4444;
        color: #991b1b;
      }

      .obs-filter-btn[data-filter="ACCESORIOS DE SEGURIDAD"]:hover,
      .obs-filter-btn[data-filter="ACCESORIOS DE SEGURIDAD"].active {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: #ffffff;
        border-color: #dc2626;
      }

      .obs-filter-btn[data-filter="all"] {
        border-color: #6b7280;
        color: #374151;
      }

      .obs-filter-btn[data-filter="all"]:hover,
      .obs-filter-btn[data-filter="all"].active {
        background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
        color: #ffffff;
        border-color: #4b5563;
      }

      .obs-search-input {
        width: 100%;
        padding: 1rem 1.5rem;
        padding-left: 3rem;
        border: 2px solid #e9ecef;
        border-radius: 12px;
        font-size: 1rem;
        transition: all .3s;
        background: #fff;
        font-weight: 500;
      }

      .obs-search-input:focus {
        outline: none;
        border-color: #6366f1;
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        transform: translateY(-2px);
      }

      .obs-search-meta {
        margin-top: .75rem;
        font-size: .9rem;
        color: #6c757d;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: .5rem;
      }

      .obs-sections-grid {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
        margin-bottom: 2rem;
      }

      .obs-section {
        background: #ffffff;
        border: none;
        border-radius: 12px;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
        overflow: hidden;
        transition: all .3s ease;
      }

      .obs-section.hidden {
        display: none !important;
      }

      .obs-card.hidden {
        display: none !important;
      }

      .obs-section:hover {
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
      }

      .obs-section-header {
        padding: 0;
        user-select: none;
        border: none;
        position: relative;
      }

      /* Colores específicos por sección */
      .obs-section[data-seccion="SISTEMA DE LUCES"] .obs-section-header {
        background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
      }

      .obs-section[data-seccion="PARTE EXTERNA"] .obs-section-header {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
      }

      .obs-section[data-seccion="PARTE INTERNA"] .obs-section-header {
        background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
      }

      .obs-section[data-seccion="ESTADO DE LLANTAS"] .obs-section-header {
        background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
      }

      .obs-section[data-seccion="ACCESORIOS DE SEGURIDAD"] .obs-section-header {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
      }

      /* Color por defecto para otras secciones */
      .obs-section-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      }

      .obs-section-header-content {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1.25rem 1.75rem;
        gap: 1rem;
      }

      .obs-section-header-left {
        display: flex;
        align-items: center;
        gap: 1.25rem;
        flex: 1;
      }

      .obs-section-icon {
        width: 48px;
        height: 48px;
        background: rgba(255, 255, 255, 0.25);
        backdrop-filter: blur(10px);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        transition: all .3s ease;
        flex-shrink: 0;
        border: 2px solid rgba(255, 255, 255, 0.3);
      }

      .obs-section:hover .obs-section-icon {
        transform: scale(1.1);
        background: rgba(255, 255, 255, 0.35);
      }

      .obs-section-info {
        flex: 1;
      }

      .obs-section-title {
        font-size: 1rem;
        font-weight: 700;
        color: #ffffff;
        margin-bottom: .25rem;
        line-height: 1.3;
        letter-spacing: .3px;
        text-transform: uppercase;
      }

      .obs-section-subtitle {
        font-size: .85rem;
        color: rgba(255, 255, 255, 0.85);
        display: flex;
        align-items: center;
        gap: .75rem;
        font-weight: 500;
      }

      .obs-section-subtitle span {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
      }

      .obs-section-stats {
        display: flex;
        align-items: center;
        gap: .75rem;
      }

      .obs-section-count {
        background: rgba(255, 255, 255, 0.95);
        color: #667eea;
        padding: .5rem .9rem;
        border-radius: 10px;
        font-size: .9rem;
        font-weight: 800;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        min-width: 40px;
        text-align: center;
        transition: all .3s;
        border: 2px solid rgba(255, 255, 255, 0.5);
      }

      .obs-section:hover .obs-section-count {
        transform: scale(1.08);
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
      }

      /* Botón para plegar/desplegar sección (accordion) */
      .obs-section-toggle {
        background: rgba(255, 255, 255, 0.18);
        border: 1px solid rgba(255, 255, 255, 0.6);
        border-radius: 999px;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        color: #ffffff;
        transition: background .2s, transform .2s;
      }

      .obs-section-toggle:hover {
        background: rgba(255, 255, 255, 0.25);
        transform: scale(1.05);
      }

      .obs-section-toggle-icon {
        transition: transform .2s;
      }

      .obs-section.collapsed .obs-section-toggle-icon {
        transform: rotate(-90deg);
      }

      .obs-section-body {
        padding: 1.5rem;
        background: #fafbfc;
        display: block;
      }

      .obs-section.collapsed .obs-section-body {
        display: none;
      }

      .obs-vehiculos {
        display: grid;
        gap: 1.25rem;
        grid-template-columns: repeat(2, 1fr);
      }

      @media (max-width: 1200px) {
        .obs-vehiculos {
          grid-template-columns: 1fr;
        }
      }

      .obs-card {
  background: #ffffff;
  border: 2px solid #e38e28;

  border-radius: 12px;
  /* overflow: hidden;  <- QUITADO para que no corte la tabla ni el botón */
  transition: all .3s ease;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

      .obs-card:hover {
        box-shadow: 0 8px 24px rgba(0, 89, 150, 0.15);
        transform: translateY(-4px);
        border-color: var(--accent);
      }

      .obs-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1.25rem 1.5rem;
        background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
        border-bottom: 2px solid #e9ecef;
        gap: 1rem;
      }

      .obs-placa {
        font-size: 1.25rem;
        font-weight: 800;
        color: #212529;
        letter-spacing: .5px;
        display: flex;
        align-items: center;
        gap: .75rem;
        text-transform: uppercase;
      }

      .obs-placa::before {
        content: '🚙';
        font-size: 1.5rem;
      }

      .obs-tipo {
        font-size: .9rem;
        color: #6c757d;
        padding: 1rem 1.5rem;
        background: #ffffff;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        border-bottom: 1px solid #e9ecef;
      }

      .obs-tipo-item {
        display: flex;
        align-items: center;
        gap: .5rem;
        padding: .5rem .75rem;
        background: #f8f9fa;
        border-radius: 8px;
        font-weight: 600;
      }

      /* === Tabla de Ítems Inspeccionados / Observaciones / Acción === */
     .obs-card-details {
  padding: 1.25rem 1.25rem 1.5rem;
  background: #ffffff;
  overflow-x: auto;        /* scroll horizontal si no cabe */
  box-sizing: border-box;  /* que respete el ancho del card */
}


      .obs-items-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  margin-top: 1rem;
  /* Dejamos que el contenido decida el ancho de columnas */
  table-layout: auto;
  min-width: 600px; /* fuerza scroll horizontal en pantallas chicas en vez de cortar */
}


      .obs-items-table thead {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      }

      .obs-items-table thead th {
        padding: 0.75rem 0.9rem;
        text-align: left;
        font-weight: 700;
        font-size: .8rem;
        color: #ffffff;
        text-transform: uppercase;
        letter-spacing: .4px;
        white-space: nowrap;
      }

      .obs-items-table thead th:first-child {
        border-radius: 8px 0 0 0;
      }

      .obs-items-table thead th:last-child {
        border-radius: 0 8px 0 0;
      }

      .obs-items-table tbody tr:hover {
        background: #f8f9fa;
      }

      .obs-items-table tbody td {
        padding: 0.75rem 0.9rem;
        font-size: .85rem;
        color: #495057;
        vertical-align: top;
        word-wrap: break-word;
        word-break: break-word;
      }

      /* Distribución de columnas */
      .obs-items-table th:nth-child(1),
      .obs-items-table td:nth-child(1) {
        width: 28%;
      }

      .obs-items-table th:nth-child(2),
      .obs-items-table td:nth-child(2) {
        width: 52%;
      }

      .obs-items-table th:nth-child(3),
      .obs-items-table td:nth-child(3) {
        width: 20%;
        text-align: center;
        white-space: nowrap;
      }

      .obs-item-name {
        font-weight: 700;
        color: #212529;
        display: flex;
        align-items: flex-start;
        gap: .5rem;
        word-break: break-word;
      }

      .obs-item-obs {
        color: #6c757d;
        font-weight: 500;
        line-height: 1.4;
        word-break: break-word;
      }

      .obs-reportes-badge {
        display: inline-block;
        padding: 0.5rem 1rem;
        background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
        color: white;
        border-radius: 50px;
        font-weight: 700;
        font-size: 0.95rem;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(220, 38, 38, 0.3);
      }

      .obs-reportes-badge:hover {
        transform: scale(1.1);
        box-shadow: 0 4px 12px rgba(220, 38, 38, 0.5);
      }

      .obs-historial {
        animation: slideDown 0.3s ease-out;
      }

      @keyframes slideDown {
        from {
          opacity: 0;
          transform: translateY(-10px);
        }
        to {
          opacity: 1;
          transform: translateY(0);
        }
      }

      .btn-crear-orden-item {
        padding: .4rem .7rem;
        font-size: .75rem;
        white-space: nowrap;
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: #ffffff;
        border: none;
        border-radius: 6px;
        font-weight: 700;
        cursor: pointer;
        transition: all .3s;
        text-transform: uppercase;
        letter-spacing: .3px;
        display: inline-flex;
        align-items: center;
        gap: .4rem;
      }

      .btn-crear-orden-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
        background: linear-gradient(135deg, #059669 0%, #10b981 100%);
      }

      .btn-crear-orden-item:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        transform: none;
      }

      /* Ajustes responsive para la tabla */
      @media (max-width: 900px) {
        .obs-items-table thead th {
          font-size: .75rem;
          padding: 0.6rem 0.7rem;
        }

        .obs-items-table tbody td {
          font-size: .8rem;
          padding: 0.6rem 0.7rem;
        }

        .obs-items-table th:nth-child(1),
        .obs-items-table td:nth-child(1) {
          width: 32%;
        }

        .obs-items-table th:nth-child(2),
        .obs-items-table td:nth-child(2) {
          width: 48%;
        }

        .obs-items-table th:nth-child(3),
        .obs-items-table td:nth-child(3) {
          width: 20%;
        }
      }

      @media (max-width: 600px) {
        .btn-crear-orden-item {
          width: 100%;
          justify-content: center;
        }
      }

      .obs-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .4rem;
        padding: .5rem .9rem;
        border-radius: 8px;
        font-size: .75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .3px;
        white-space: nowrap;
        min-width: fit-content;
      }

      .obs-card-footer {
        padding: .85rem 1.5rem;
        background: #f8f9fa;
        border-top: 2px solid #e9ecef;
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
      }

      .obs-meta-item {
        display: flex;
        align-items: center;
        gap: .5rem;
        font-size: .8rem;
        font-weight: 600;
        color: #495057;
      }

      .obs-meta-icon {
        font-size: 1.1rem;
      }

      .obs-action-btn {
        padding: .6rem 1.2rem;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: #ffffff;
        border: none;
        border-radius: 8px;
        font-weight: 700;
        font-size: .85rem;
        cursor: pointer;
        transition: all .3s;
        text-transform: uppercase;
        letter-spacing: .3px;
      }

      .obs-action-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
      }

      .obs-empty {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 4rem 2rem;
        text-align: center;
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        border-radius: 16px;
        border: 2px dashed #dee2e6;
      }

      .obs-empty svg {
        width: 80px;
        height: 80px;
        color: #6366f1;
        margin-bottom: 1.5rem;
        stroke-width: 1.5;
      }

      .obs-empty div {
        font-size: 1.1rem;
        font-weight: 600;
        color: #495057;
        max-width: 400px;
      }

      @media (max-width: 768px) {
        .obs-sections-grid {
          gap: 1rem;
        }

        .obs-card-header {
          flex-direction: column;
          align-items: flex-start;
        }

        .obs-tipo {
          grid-template-columns: 1fr;
        }

        .obs-card-footer {
          flex-direction: column;
          align-items: flex-start;
        }

        .obs-action-btn {
          width: 100%;
        }
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
        background: rgba(255, 255, 255, .2);
        transform: translate(-50%, -50%);
        transition: width .5s, height .5s;
      }

      .btn-crear-orden:hover::before {
        width: 400px;
        height: 400px;
      }

      .btn-crear-orden:hover {
        background: linear-gradient(135deg, #764ba2 0%, #667eea 100%) !important;
      }

      .btn-ver-orden {
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%) !important;
        position: relative;
        overflow: hidden;
      }

      .btn-ver-orden:hover {
        background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%) !important;
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

    <!-- ===== Script del kanban + modal + observaciones ===== -->
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
          allItems: [],
          observaciones: [],
          resueltas: [],
          metricas: {},
          draggingId: null,
          fechaDesde: '',
          fechaHasta: '',
          options: {
            vehiculos: [],
            servicios: [],
            inventario: []
          }
        };

        // Rango de fechas del mes actual
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
          }
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

        async function apiPost(action, data) {
          const r = await fetch(`${API_URL}?action=${encodeURIComponent(action)}`, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            credentials: 'same-origin',
            body: JSON.stringify(data)
          });
          return r.json();
        }

        // Función global para mostrar/ocultar historial de reportes
        window.toggleHistorial = function(itemId) {
          const el = document.getElementById(itemId);
          if (!el) return;
          if (el.style.display === 'none') {
            el.style.display = 'block';
          } else {
            el.style.display = 'none';
          }
        };

        root.innerHTML = `
        <div class="mantto-tabs">
          <button class="btn" data-tab="board" aria-pressed="true">Tablero (Drag & Drop)</button>
          <button class="btn" data-tab="list"  aria-pressed="false">Lista</button>
          <button class="btn" data-tab="observaciones"  aria-pressed="false">Observaciones</button>
          <span class="meta" id="mantto-count"></span>
          <button class="btn" id="btn-add">Agregar servicio</button>
        </div>

        <div id="fecha-filter-toolbar" class="list-tools" style="margin-bottom:1rem;display:flex;gap:.5rem;flex-wrap:wrap;align-items:center;background:#fff;padding:1rem;border-radius:12px;border:1px solid var(--border);box-shadow:var(--shadow)">
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

        // ===== Modal confirm estatus =====
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
          const cur = state.items.find(x => x.id === id);
          const needService = (to === 'Programado' && (!cur || !Number(cur.id_servicio || 0)));
          confirmEl.serviceWrap.style.display = needService ? 'block' : 'none';
          if (needService) {
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
            toast('Sigue la secuencia: Pendiente → Programado → En Taller → Completado', false);
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
              ${it.status==='Pendiente' ? `<button data-move="${it.id}" data-to="Programado">→ Programado</button>` : ''}
              ${it.status==='Programado' ? `<button data-move="${it.id}" data-to="EnTaller">→ En Taller</button>` : ''}
              ${it.status==='EnTaller' ? `<button data-move="${it.id}" data-to="Completado">→ Completado</button>` : ''}
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

        // ===== OBSERVACIONES con menú lateral + secciones desplegables =====
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
                items: []
              };
            }
            // Cada ítem tiene su propia orden de servicio
            groupedBySection[seccion][vehiculoKey].items.push({
              item: item.item,
              total_reportes: item.total_reportes || 1,
              ultima_inspeccion: item.ultima_inspeccion || item.fecha_inspeccion,
              ultimo_km: item.ultimo_km || item.kilometraje,
              historial: item.historial || [],
              orden_id: item.orden_id,
              orden_estatus: item.orden_estatus
            });
          });

          const sectionIcons = {
            'SISTEMA DE LUCES': '💡',
            'PARTE EXTERNA': '🚗',
            'PARTE INTERNA': '🪑',
            'ESTADO DE LLANTAS': '⚫',
            'ACCESORIOS DE SEGURIDAD': '🛡️',
            'default': '⚠️'
          };

          const sectionCounts = {};
          Object.keys(groupedBySection).forEach(seccion => {
            sectionCounts[seccion] = Object.keys(groupedBySection[seccion]).length;
          });
          const allVehIds = new Set();
          Object.values(groupedBySection).forEach(map => {
            Object.keys(map).forEach(id => allVehIds.add(String(id)));
          });
          const totalVehiculosAll = allVehIds.size;

          let html = `
            <div class="obs-sidebar">
              <div class="obs-filter-buttons">
                <div class="obs-filter-title">🎯 Secciones del checklist</div>
                <button class="obs-filter-btn active" data-filter="all">
                  <span class="obs-filter-btn-text">
                    <span class="obs-filter-btn-icon">📊</span>
                    <span class="obs-filter-btn-label">Todas</span>
                  </span>
                  <span class="filter-count">${totalVehiculosAll}</span>
                </button>
          `;

          Object.keys(groupedBySection).sort().forEach(seccion => {
            const icon = sectionIcons[seccion] || sectionIcons['default'];
            const count = sectionCounts[seccion];
            html += `
                <button class="obs-filter-btn" data-filter="${seccion}">
                  <span class="obs-filter-btn-text">
                    <span class="obs-filter-btn-icon">${icon}</span>
                    <span class="obs-filter-btn-label">${seccion}</span>
                  </span>
                  <span class="filter-count">${count}</span>
                </button>
            `;
          });

          html += `
              </div>
            </div>
            <div class="obs-main-content">
              <div class="obs-tabs" style="display:flex;gap:0.5rem;margin-bottom:1.5rem;border-bottom:2px solid #e9ecef;padding-bottom:0.5rem;">
                <button class="obs-tab-btn active" data-tab="pendientes" style="background:linear-gradient(135deg, #005996 0%, #003d6b 100%);color:white;border:none;padding:0.75rem 1.5rem;border-radius:8px 8px 0 0;font-weight:700;cursor:pointer;transition:all 0.3s;">
                  ⚠️ Observaciones Pendientes
                </button>
                <button class="obs-tab-btn" data-tab="resueltas" style="background:#f8f9fa;color:#495057;border:none;padding:0.75rem 1.5rem;border-radius:8px 8px 0 0;font-weight:700;cursor:pointer;transition:all 0.3s;">
                  ✅ Historial Resueltas
                </button>
              </div>
              <div id="obs-pendientes-section">
                <div class="obs-search-box">
                  <input type="text" id="obs-search" class="obs-search-input" placeholder="🔍 Buscar por placa...">
                  <div class="obs-search-meta" id="obs-search-meta"></div>
                </div>
                <div class="obs-sections-grid" id="obs-sections-container">
          `;

          let sectionIndex = 0;
          Object.keys(groupedBySection).sort().forEach(seccion => {
            const vehiculos = Object.values(groupedBySection[seccion]);
            const totalVehiculos = vehiculos.length;
            const icon = sectionIcons[seccion] || sectionIcons['default'];
            const totalItems = vehiculos.reduce((sum, v) => sum + v.items.length, 0);

            html += `
              <div class="obs-section" data-seccion="${seccion}" id="obs-section-${sectionIndex}">
                <div class="obs-section-header">
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
                    <div class="obs-section-stats">
                      <span class="obs-section-count">${totalVehiculos}</span>
                      <button type="button" class="obs-section-toggle" aria-expanded="true">
                        <i class="fa-solid fa-chevron-down obs-section-toggle-icon"></i>
                      </button>
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
              const totalItemsVeh = veh.items.length;

              const estatusInfo = {
                'Pendiente': {
                  color: '#dc2626',
                  bg: '#fef2f2',
                  label: '⏳ Pendiente'
                },
                'Programado': {
                  color: '#f59e0b',
                  bg: '#fffbeb',
                  label: '📅 Programada'
                },
                'EnTaller': {
                  color: '#f97316',
                  bg: '#fff7ed',
                  label: '🔧 En Taller'
                }
              };
              const info = estatusInfo[estatusOrden] || estatusInfo['Pendiente'];

              const fechaInspeccion = veh.items[0]?.ultima_inspeccion ? new Date(veh.items[0].ultima_inspeccion).toLocaleDateString('es-MX') : 'N/A';
              const kmInspeccion = veh.items[0]?.ultimo_km ? Number(veh.items[0].ultimo_km).toLocaleString() : 'N/A';

              html += `
                <div class="obs-card" data-placa="${veh.placa || ''}">
                  <div class="obs-card-header">
                    <div class="obs-placa">${veh.placa || 'Sin placa'}</div>
                    ${tieneOrden ? `<span class="obs-badge" style="background:${info.bg};color:${info.color};border:2px solid ${info.color};">${info.label}</span>` : ''}
                  </div>
                  <div class="obs-tipo">
                    <div class="obs-tipo-item">
                      <span>🚙</span>
                      <span>${veh.tipo || 'Sin tipo'}</span>
                    </div>
                    <div class="obs-tipo-item">
                      <span>📍</span>
                      <span>${veh.Sucursal || 'Sin sucursal'}</span>
                    </div>
                    <div class="obs-tipo-item">
                      <span>📊</span>
                      <span>${kmActual} km</span>
                    </div>
                  </div>

                  <div class="obs-card-details">
                    <table class="obs-items-table">
                      <thead>
                        <tr>
                          <th>Ítem Inspeccionado</th>
                          <th style="width: 120px; text-align: center;">Reportes</th>
                          <th>Última Observación</th>
                          <th style="width: 180px;">Acción</th>
                        </tr>
                      </thead>
                      <tbody>
              `;

              veh.items.forEach((it, itemIndex) => {
                const totalReportes = it.total_reportes || 1;
                const historial = it.historial || [];
                const ultimaObs = historial.length > 0 ? historial[0].observacion : 'Sin observaciones';
                const itemId = `item-${veh.id_vehiculo}-${itemIndex}`;

                // Verificar si este ítem específico tiene una orden de servicio
                const tieneOrdenItem = it.orden_id && it.orden_id !== null;
                const estatusOrdenItem = it.orden_estatus || 'Pendiente';

                const estatusItemInfo = {
                  'Pendiente': { color: '#dc2626', bg: '#fef2f2', label: '⏳ Pendiente', icon: '⏳' },
                  'Programado': { color: '#f59e0b', bg: '#fffbeb', label: '📅 Programada', icon: '📅' },
                  'EnTaller': { color: '#f97316', bg: '#fff7ed', label: '🔧 En Taller', icon: '🔧' }
                };
                const infoItem = estatusItemInfo[estatusOrdenItem] || estatusItemInfo['Pendiente'];

                html += `
                        <tr>
                          <td><div class="obs-item-name">${it.item || 'Sin descripción'}</div></td>
                          <td style="text-align: center;">
                            <span class="obs-reportes-badge"
                                  ${historial.length > 1 ? `onclick="toggleHistorial('${itemId}')" style="cursor:pointer;"` : ''}>
                              ${totalReportes > 1 ? `🔴 ${totalReportes}x` : '⚠️ 1x'}
                            </span>
                          </td>
                          <td>
                            <div class="obs-item-obs">${ultimaObs}</div>
                            ${historial.length > 1 ? `
                            <div id="${itemId}" class="obs-historial" style="display:none; margin-top:0.5rem; padding:0.75rem; background:#fff3cd; border-left:4px solid #ffc107; border-radius:6px;">
                              <strong style="font-size:0.85rem; color:#856404;">📋 Historial de reportes:</strong>
                              <ul style="margin:0.5rem 0 0 0; padding-left:1.5rem; font-size:0.85rem; color:#856404;">
                                ${historial.map(h => `<li><strong>${new Date(h.fecha).toLocaleDateString('es-MX')}</strong>: ${h.observacion}</li>`).join('')}
                              </ul>
                            </div>
                            ` : ''}
                          </td>
                          <td>
                            ${tieneOrdenItem ? `
                              <div style="display:flex;flex-direction:column;gap:0.5rem;align-items:center;">
                                <span class="obs-badge" style="background:${infoItem.bg};color:${infoItem.color};border:2px solid ${infoItem.color};padding:0.5rem 1rem;border-radius:8px;font-weight:600;font-size:0.85rem;display:flex;align-items:center;gap:0.5rem;">
                                  ${infoItem.icon} ${infoItem.label}
                                </span>
                                <button class="obs-action-btn btn-ver-orden" data-orden="${it.orden_id}" style="font-size:0.75rem;padding:0.4rem 0.8rem;">
                                  👁️ Ver Orden #${it.orden_id}
                                </button>
                              </div>
                            ` : `
                              <button class="btn-crear-orden-item"
                                      data-vehiculo="${veh.id_vehiculo}"
                                      data-placa="${veh.placa || 'Sin placa'}"
                                      data-seccion="${seccion}"
                                      data-item="${it.item || 'Sin descripción'}"
                                      data-observaciones="${ultimaObs}">
                                ➕ Crear Orden
                              </button>
                            `}
                          </td>
                        </tr>
                `;
              });

              html += `
                      </tbody>
                    </table>
                  </div>

                  <div class="obs-card-footer">
                    <div style="display:flex;gap:1.5rem;flex-wrap:wrap;width:100%;">
                      <div class="obs-meta-item">
                        <span class="obs-meta-icon">📅</span>
                        <span>Inspección: ${fechaInspeccion}</span>
                      </div>
                      <div class="obs-meta-item">
                        <span class="obs-meta-icon">🛣️</span>
                        <span>Km Inspección: ${kmInspeccion}</span>
                      </div>
                      <div class="obs-meta-item">
                        <span class="obs-meta-icon">⚠️</span>
                        <span>${totalItemsVeh} problema${totalItemsVeh !== 1 ? 's' : ''}</span>
                      </div>
                      ${tieneOrden ? `
                      <div class="obs-meta-item" style="margin-left:auto;">
                        <button class="obs-action-btn btn-ver-orden" data-orden="${veh.orden_id}" style="font-size:.8rem;padding:.5rem 1rem;">
                          👁️ Ver Orden #${veh.orden_id}
                        </button>
                      </div>
                      ` : ''}
                    </div>
                  </div>
                </div>
              `;
            });

            html += `
                  </div>
                </div>
              </div>
            `;
            sectionIndex++;
          });

          html += `
                </div>
              </div>
              <div id="obs-resueltas-section" style="display:none;">
                <div id="resueltas-content">Cargando...</div>
              </div>
            </div>
          `;

          obsContent.innerHTML = html;

          // Normalizar íconos de filtros
          const iconMap = {
            'SISTEMA DE LUCES': '💡',
            'PARTE EXTERNA': '🚗',
            'PARTE INTERNA': '🧰',
            'ESTADO DE LLANTAS': '🛞',
            'ACCESORIOS DE SEGURIDAD': '🦺'
          };
          obsContent.querySelectorAll('.obs-filter-btn').forEach(btn => {
            const labelEl = btn.querySelector('.obs-filter-btn-label');
            const iconEl = btn.querySelector('.obs-filter-btn-icon');
            const label = (labelEl && labelEl.textContent || '').trim();
            if (iconEl && label !== 'Todas') iconEl.textContent = iconMap[label] || '📁';
          });
          obsContent.querySelectorAll('.obs-section').forEach(sec => {
            const name = sec.getAttribute('data-seccion') || '';
            const iconEl = sec.querySelector('.obs-section-icon');
            if (iconEl) iconEl.textContent = iconMap[name] || '📁';
          });

          let currentFilter = 'all';

          // Búsqueda por placa
          const searchInput = document.getElementById('obs-search');
          const searchMeta = document.getElementById('obs-search-meta');

          if (searchInput) {
            searchInput.addEventListener('input', (e) => {
              const query = e.target.value.toLowerCase().trim();
              const allSections = obsContent.querySelectorAll('.obs-section');

              if (query === '') {
                allSections.forEach(section => {
                  const seccionName = section.getAttribute('data-seccion');
                  const shouldShow = currentFilter === 'all' || seccionName === currentFilter;

                  if (shouldShow) {
                    section.classList.remove('hidden');
                    section.style.removeProperty('display');
                    const cards = section.querySelectorAll('.obs-card');
                    cards.forEach(card => {
                      card.classList.remove('hidden');
                      card.style.removeProperty('display');
                    });
                    const count = cards.length;
                    const countEl = section.querySelector('.obs-section-count');
                    if (countEl) countEl.textContent = `${count}`;
                  } else {
                    section.classList.add('hidden');
                  }
                });
                searchMeta.textContent = '';
                return;
              }

              let visibleCount = 0;
              allSections.forEach(section => {
                const seccionName = section.getAttribute('data-seccion');
                const shouldSearchInSection = currentFilter === 'all' || seccionName === currentFilter;

                if (!shouldSearchInSection) {
                  section.classList.add('hidden');
                  return;
                }

                const cards = section.querySelectorAll('.obs-card');
                let sectionVisibleCount = 0;

                cards.forEach(card => {
                  const placa = (card.getAttribute('data-placa') || '').toLowerCase();
                  if (placa.includes(query)) {
                    card.classList.remove('hidden');
                    card.style.removeProperty('display');
                    sectionVisibleCount++;
                    visibleCount++;
                  } else {
                    card.classList.add('hidden');
                  }
                });

                if (sectionVisibleCount === 0) {
                  section.classList.add('hidden');
                } else {
                  section.classList.remove('hidden');
                  section.style.removeProperty('display');
                  const countEl = section.querySelector('.obs-section-count');
                  if (countEl) countEl.textContent = `${sectionVisibleCount}`;
                }
              });

              searchMeta.textContent = visibleCount > 0
                ? `Se encontraron ${visibleCount} vehículo${visibleCount !== 1 ? 's' : ''} con la placa "${query}"`
                : `No se encontraron vehículos con la placa "${query}"`;
            });
          }

          // Botones "Crear orden" desde cada ítem
          obsContent.querySelectorAll('.btn-crear-orden-item').forEach(btn => {
            btn.addEventListener('click', () => {
              const idVehiculo = parseInt(btn.getAttribute('data-vehiculo'));
              const placa = btn.getAttribute('data-placa');
              const seccion = btn.getAttribute('data-seccion');
              const item = btn.getAttribute('data-item');
              const observaciones = btn.getAttribute('data-observaciones');

              crearOrdenDesdeItem(idVehiculo, placa, seccion, item, observaciones);
            });
          });

          // Botones "Ver orden"
          obsContent.querySelectorAll('.btn-ver-orden').forEach(btn => {
            btn.addEventListener('click', () => {
              const ordenId = parseInt(btn.getAttribute('data-orden'));
              window.location.href = `/Pedidos_GA/Servicios/detalles_orden.php?id=${ordenId}`;
            });
          });

          // Menú lateral: filtro por sección
          const filterButtons = obsContent.querySelectorAll('.obs-filter-btn');
          const allSections = obsContent.querySelectorAll('.obs-section');

          filterButtons.forEach(btn => {
            btn.addEventListener('click', () => {
              const filter = btn.getAttribute('data-filter');
              currentFilter = filter;

              filterButtons.forEach(b => b.classList.remove('active'));
              btn.classList.add('active');

              if (searchInput) searchInput.value = '';
              if (searchMeta) searchMeta.textContent = '';

              allSections.forEach(section => {
                const seccionName = section.getAttribute('data-seccion');
                const shouldShow = filter === 'all' || seccionName === filter;

                if (shouldShow) {
                  section.classList.remove('hidden');
                  section.style.removeProperty('display');
                  const cards = section.querySelectorAll('.obs-card');
                  cards.forEach(card => {
                    card.classList.remove('hidden');
                    card.style.removeProperty('display');
                  });
                } else {
                  section.classList.add('hidden');
                }
              });

              if (filter !== 'all') {
                const visibleSection = Array.from(allSections).find(s =>
                  s.getAttribute('data-seccion') === filter
                );
                if (visibleSection) {
                  setTimeout(() => {
                    visibleSection.scrollIntoView({
                      behavior: 'smooth',
                      block: 'start'
                    });
                  }, 100);
                }
              }
            });
          });

          // Accordion: plegar/desplegar secciones desde su header
          obsContent.querySelectorAll('.obs-section-toggle').forEach(btn => {
            btn.addEventListener('click', () => {
              const section = btn.closest('.obs-section');
              if (!section) return;
              const isCollapsed = section.classList.toggle('collapsed');
              btn.setAttribute('aria-expanded', String(!isCollapsed));
            });
          });

          // También permitir click en la cabecera para desplegar
          obsContent.querySelectorAll('.obs-section-header').forEach(header => {
            header.addEventListener('click', (e) => {
              if (e.target.closest('.obs-section-toggle')) return;
              const section = header.closest('.obs-section');
              const btn = header.querySelector('.obs-section-toggle');
              if (!section || !btn) return;
              const isCollapsed = section.classList.toggle('collapsed');
              btn.setAttribute('aria-expanded', String(!isCollapsed));
            });
          });

          // Event listeners para tabs de observaciones (Pendientes/Resueltas)
          const obsTabBtns = obsContent.querySelectorAll('.obs-tab-btn');
          obsTabBtns.forEach(btn => {
            btn.addEventListener('click', () => {
              const tab = btn.getAttribute('data-tab');
              switchObsTab(tab);
            });
          });
        }

        // Crear orden desde un ítem de checklist
        async function crearOrdenDesdeItem(idVehiculo, placa, seccion, item, observaciones) {
          const nota = `[CHECKLIST - ${seccion}]
Ítem: ${item}
Observaciones: ${observaciones}

Creada automáticamente desde observaciones del checklist vehicular.`;

          const payload = {
            id_vehiculo: idVehiculo,
            id_servicio: 0,
            duracion_minutos: 60,
            notas: nota,
            materiales: []
          };

          try {
            const res = await apiPost('create', payload);
            if (!res || !res.ok) {
              toast(res?.msg || 'No se pudo crear la orden', false);
              return;
            }

            toast(`✅ Orden creada para ${placa} - ${item}`);

            await loadList();
            renderObservaciones();
          } catch (error) {
            toast('Error al crear la orden', false);
            console.error(error);
          }
        }

        // Función para cambiar entre tabs de observaciones (Pendientes/Resueltas)
        function switchObsTab(tab) {
          const pendientesSection = root.querySelector('#obs-pendientes-section');
          const resueltasSection = root.querySelector('#obs-resueltas-section');
          const tabBtns = root.querySelectorAll('.obs-tab-btn');

          // Actualizar botones
          tabBtns.forEach(btn => {
            if (btn.getAttribute('data-tab') === tab) {
              btn.classList.add('active');
              btn.style.background = 'linear-gradient(135deg, #005996 0%, #003d6b 100%)';
              btn.style.color = 'white';
            } else {
              btn.classList.remove('active');
              btn.style.background = '#f8f9fa';
              btn.style.color = '#495057';
            }
          });

          // Mostrar/ocultar secciones
          if (tab === 'pendientes') {
            if (pendientesSection) pendientesSection.style.display = 'block';
            if (resueltasSection) resueltasSection.style.display = 'none';
          } else if (tab === 'resueltas') {
            if (pendientesSection) pendientesSection.style.display = 'none';
            if (resueltasSection) resueltasSection.style.display = 'block';
            loadResueltas();
          }
        }

        // Cargar observaciones resueltas desde la API
        async function loadResueltas() {
          const resueltasContent = root.querySelector('#resueltas-content');
          if (!resueltasContent) return;

          resueltasContent.innerHTML = '<div style="text-align:center;padding:2rem;"><i class="fa fa-spinner fa-spin" style="font-size:2rem;"></i><p>Cargando historial...</p></div>';

          const res = await apiGet('observaciones_resueltas');
          if (res && res.ok) {
            state.resueltas = res.items || [];
            state.metricas = res.metricas || {};
            renderResueltas();
          } else {
            resueltasContent.innerHTML = '<div style="text-align:center;padding:2rem;color:#dc2626;"><p>❌ Error al cargar historial de observaciones resueltas</p></div>';
          }
        }

        // Renderizar historial de observaciones resueltas
        function renderResueltas() {
          const resueltasContent = root.querySelector('#resueltas-content');
          if (!resueltasContent) return;

          const resueltas = state.resueltas || [];
          const metricas = state.metricas || {};

          if (resueltas.length === 0) {
            resueltasContent.innerHTML = `
              <div class="obs-empty">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div>No hay observaciones resueltas todavía</div>
              </div>
            `;
            return;
          }

          // Dashboard con métricas
          let html = `
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:1.5rem;margin-bottom:2rem;">
              <div style="background:linear-gradient(135deg,#28a745 0%,#20c997 100%);padding:1.5rem;border-radius:12px;color:white;box-shadow:0 4px 12px rgba(40,167,69,0.3);">
                <div style="font-size:0.9rem;opacity:0.95;margin-bottom:0.5rem;font-weight:600;">✅ Total Resueltas</div>
                <div style="font-size:2.5rem;font-weight:700;">${metricas.total_resueltas || 0}</div>
              </div>
              <div style="background:linear-gradient(135deg,#007bff 0%,#0056b3 100%);padding:1.5rem;border-radius:12px;color:white;box-shadow:0 4px 12px rgba(0,123,255,0.3);">
                <div style="font-size:0.9rem;opacity:0.95;margin-bottom:0.5rem;font-weight:600;">⏱️ Promedio Resolución</div>
                <div style="font-size:2.5rem;font-weight:700;">${metricas.dias_promedio_resolucion || 0} <span style="font-size:1.2rem;">días</span></div>
              </div>
            </div>

            <div style="background:#fff;padding:1.5rem;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.08);margin-bottom:2rem;">
              <h3 style="margin:0 0 1rem 0;font-size:1.2rem;color:#495057;">🔥 Top 5 Ítems Más Problemáticos</h3>
              <div style="display:grid;gap:0.75rem;">
          `;

          const topProblematicos = metricas.top_problematicos || [];
          topProblematicos.forEach((item, idx) => {
            const width = topProblematicos[0] ? ((item.count / topProblematicos[0].count) * 100) + '%' : '0%';
            html += `
              <div style="display:flex;align-items:center;gap:1rem;">
                <div style="min-width:30px;text-align:center;font-weight:700;color:#6c757d;">#${idx + 1}</div>
                <div style="flex:1;">
                  <div style="font-weight:600;color:#495057;margin-bottom:0.25rem;">${item.item}</div>
                  <div style="background:#e9ecef;height:24px;border-radius:12px;overflow:hidden;position:relative;">
                    <div style="background:linear-gradient(135deg,#dc2626 0%,#ef4444 100%);height:100%;width:${width};transition:width 0.5s;display:flex;align-items:center;padding:0 0.75rem;">
                      <span style="color:white;font-weight:700;font-size:0.85rem;">${item.count} veces</span>
                    </div>
                  </div>
                </div>
              </div>
            `;
          });

          html += `
              </div>
            </div>

            <div style="background:#fff;padding:1.5rem;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.08);">
              <h3 style="margin:0 0 1rem 0;font-size:1.2rem;color:#495057;">📋 Historial Completo</h3>
              <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:separate;border-spacing:0;border:1px solid #e9ecef;border-radius:8px;overflow:hidden;">
                  <thead style="background:linear-gradient(135deg,#495057 0%,#343a40 100%);color:white;">
                    <tr>
                      <th style="padding:0.9rem 1rem;text-align:left;font-weight:700;font-size:0.85rem;">Orden #</th>
                      <th style="padding:0.9rem 1rem;text-align:left;font-weight:700;font-size:0.85rem;">Vehículo</th>
                      <th style="padding:0.9rem 1rem;text-align:left;font-weight:700;font-size:0.85rem;">Sección</th>
                      <th style="padding:0.9rem 1rem;text-align:left;font-weight:700;font-size:0.85rem;">Ítem</th>
                      <th style="padding:0.9rem 1rem;text-align:left;font-weight:700;font-size:0.85rem;">Observación</th>
                      <th style="padding:0.9rem 1rem;text-align:center;font-weight:700;font-size:0.85rem;">Días Resolución</th>
                      <th style="padding:0.9rem 1rem;text-align:left;font-weight:700;font-size:0.85rem;">Fecha Creación</th>
                    </tr>
                  </thead>
                  <tbody>
          `;

          resueltas.forEach((r, idx) => {
            const bgColor = idx % 2 === 0 ? '#fff' : '#f8f9fa';
            const fechaCreacion = new Date(r.fecha_creacion).toLocaleDateString('es-MX');
            const diasBadge = r.dias_resolucion <= 3 ? '#28a745' : (r.dias_resolucion <= 7 ? '#ffc107' : '#dc2626');

            html += `
              <tr style="background:${bgColor};border-bottom:1px solid #e9ecef;transition:background 0.2s;" onmouseover="this.style.background='#f1f3f5'" onmouseout="this.style.background='${bgColor}'">
                <td style="padding:1rem;font-weight:600;color:#005996;">#${r.orden_id}</td>
                <td style="padding:1rem;">
                  <div style="font-weight:600;color:#495057;">${r.placa}</div>
                  <div style="font-size:0.85rem;color:#6c757d;">${r.tipo} - ${r.Sucursal}</div>
                </td>
                <td style="padding:1rem;font-size:0.9rem;color:#6c757d;">${r.seccion}</td>
                <td style="padding:1rem;font-weight:600;color:#495057;">${r.item}</td>
                <td style="padding:1rem;font-size:0.9rem;color:#6c757d;">${r.observaciones || 'N/A'}</td>
                <td style="padding:1rem;text-align:center;">
                  <span style="background:${diasBadge};color:white;padding:0.4rem 0.8rem;border-radius:50px;font-weight:700;font-size:0.85rem;">
                    ${r.dias_resolucion || 0} días
                  </span>
                </td>
                <td style="padding:1rem;font-size:0.9rem;color:#6c757d;">${fechaCreacion}</td>
              </tr>
            `;
          });

          html += `
                  </tbody>
                </table>
              </div>
            </div>
          `;

          resueltasContent.innerHTML = html;
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
          const fechaFilterToolbar = root.querySelector('#fecha-filter-toolbar');

          if (tab === 'board') {
            kanban.style.display = 'grid';
            listWrap.style.display = 'none';
            obsWrap.style.display = 'none';
            if (fechaFilterToolbar) fechaFilterToolbar.style.display = 'flex';
          } else if (tab === 'list') {
            kanban.style.display = 'none';
            listWrap.style.display = 'block';
            obsWrap.style.display = 'none';
            if (fechaFilterToolbar) fechaFilterToolbar.style.display = 'flex';
          } else if (tab === 'observaciones') {
            kanban.style.display = 'none';
            listWrap.style.display = 'none';
            obsWrap.style.display = 'block';
            if (fechaFilterToolbar) fechaFilterToolbar.style.display = 'none';
            renderObservaciones();
          }
        }
        el.tabs.forEach(b => b.addEventListener('click', () => setTab(b.dataset.tab)));
        root.querySelector('#mantto-q').addEventListener('input', renderList);

        // ===== Modal Agregar servicio =====
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
              if (selSrv) selSrv.innerHTML = '<option value="" disabled selected>Seleccionado</option>';
              renderMoAuto2();
            }).catch(() => toast('Error de red (catalogos)', false));
          } else {
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
          if (containerMats) containerMats.innerHTML = '';
        });

        selSrv?.addEventListener('change', () => {
          const vid = Number(selVeh.value || 0);
          const inv = (state.options.inventario || []).filter(i => !Array.isArray(i.vehiculos) || i.vehiculos.length === 0 || i.vehiculos.includes(vid));
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
          btnAddMat.onclick = () => {
            const vid = Number(selVeh.value || 0);
            const inv = (state.options.inventario || []).filter(i => !Array.isArray(i.vehiculos) || i.vehiculos.length === 0 || i.vehiculos.includes(vid));
            addMatRow(inv);
          };
        }

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

          const obsRes = await apiGet('observaciones');
          if (obsRes && obsRes.ok) {
            state.observaciones = obsRes.items || [];
          }

          setTab('board');
        }

        // Inicializar fechas con el mes actual por defecto
        const currentMonth = getCurrentMonthRange();
        if (el.fechaDesde) el.fechaDesde.value = currentMonth.desde;
        if (el.fechaHasta) el.fechaHasta.value = currentMonth.hasta;
        state.fechaDesde = currentMonth.desde;
        state.fechaHasta = currentMonth.hasta;

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
