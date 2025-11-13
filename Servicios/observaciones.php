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
  <title>Observaciones Vehículos</title>
  <link rel="stylesheet" href="../styles.css">
  <link rel="icon" type="image/png" href="/Pedidos_GA/Img/Botones%20entregas/ICONOSPAG/ICONOPEDIDOS.png">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
</head>

<body>
  <script>
    document.addEventListener("DOMContentLoaded", function() {
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

      var iconoServicios = document.querySelector(".icono-servicios");
      if (iconoServicios) {
        var imgNormalServicios = "/Pedidos_GA/Img/SVG/ServiciosN.svg";
        var imgHoverServicios = "/Pedidos_GA/Img/SVG/ServiciosB.svg";
        iconoServicios.addEventListener("mouseover", function() {
          this.src = imgHoverServicios;
        });
        iconoServicios.addEventListener("mouseout", function() {
          this.src = imgNormalServicios;
        });
      }
    });
  </script>

  <div class="sidebar">
    <ul>
      <li>
        <a href="Servicios.php">
          <img src="/Pedidos_GA/Img/SVG/ServiciosN.svg" class="icono-servicios sidebar-icon" alt="Servicios">
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
    <div id="observaciones-root"></div>

    <style>
      :root {
        --bg: #f6f8fb;
        --panel: #ffffff;
        --muted: #475569;
        --text: #0f172a;
        --accent: #005996;
        --accent-2: #ED6C24;
        --border: #e2e8f0;
        --shadow: 0 2px 8px rgba(0, 0, 0, .05);
        --radius: 12px;
      }

      body {
        margin: 0;
        padding: 0;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: var(--bg);
        color: var(--text);
      }

      .container {
        margin-left: 80px;
        padding: 2rem;
        min-height: 100vh;
      }

      /* Estilos para vista de Observaciones - Diseño Moderno tipo Dashboard */
      .observaciones-wrap {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 16px;
        padding: 2rem;
      }

      @media (max-width: 1200px) {
        .observaciones-wrap > #observaciones-content {
          grid-template-columns: 1fr;
        }
      }

      /* Grid de 2 columnas aplicado al contenedor interno */
      .observaciones-wrap > #observaciones-content {
        min-height: 600px;
        display: grid;
        grid-template-columns: 280px 1fr;
        gap: 2rem;
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
        top: -50%;
        right: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(0, 89, 150, 0.03) 0%, transparent 70%);
        animation: pulse 4s ease-in-out infinite;
      }

      @keyframes pulse {
        0%, 100% { transform: scale(1); opacity: 0.5; }
        50% { transform: scale(1.1); opacity: 0.8; }
      }

      .obs-search-input {
        width: 100%;
        padding: 1rem 1.25rem;
        border: 2px solid #e9ecef;
        border-radius: 12px;
        font-size: 1rem;
        font-weight: 500;
        transition: all 0.3s ease;
        background: #ffffff;
        position: relative;
        z-index: 1;
      }

      .obs-search-input:focus {
        outline: none;
        border-color: var(--accent);
        box-shadow: 0 0 0 4px rgba(0, 89, 150, 0.1);
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
        background: linear-gradient(135deg, var(--accent) 0%, #003d6b 100%);
        padding: 1.5rem 2rem;
        color: white;
        cursor: pointer; /* NUEVO: para indicar que es desplegable */
      }

      .obs-section-header-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
      }

      .obs-section-header-left {
        display: flex;
        align-items: center;
        gap: 1rem;
      }

      .obs-section-icon {
        font-size: 2rem;
        filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
      }

      .obs-section-info {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
      }

      .obs-section-title {
        font-size: 1.35rem;
        font-weight: 700;
        letter-spacing: 0.3px;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
      }

      .obs-section-subtitle {
        font-size: 0.9rem;
        opacity: 0.95;
        display: flex;
        gap: 0.5rem;
        font-weight: 500;
      }

      .obs-section-stats {
        display: flex;
        align-items: center;
        gap: 0.75rem;
      }

      /* NUEVO: icono de desplegar */
      .obs-section-toggle-icon {
        font-size: 1.2rem;
        transition: transform .25s ease;
      }

      .obs-section.expanded .obs-section-toggle-icon {
        transform: rotate(90deg);
      }

      .obs-section-count {
        background: rgba(255, 255, 255, 0.25);
        backdrop-filter: blur(10px);
        padding: 0.75rem 1.25rem;
        border-radius: 50px;
        font-size: 1.1rem;
        font-weight: 700;
        min-width: 50px;
        text-align: center;
        transition: all .3s;
        border: 2px solid rgba(255, 255, 255, 0.5);
      }

      .obs-section:hover .obs-section-count {
        transform: scale(1.08);
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
      }

      /* NUEVO: comportamiento desplegable */
      .obs-section-body {
        padding: 1.5rem;
        background: #fafbfc;
        display: none;
      }

      .obs-section.expanded .obs-section-body {
        display: block;
      }

      .obs-vehiculos {
        display: grid;
        gap: 1.25rem;
        grid-template-columns: repeat(3, 1fr);
      }

      @media (max-width: 1800px) {
        .obs-vehiculos {
          grid-template-columns: repeat(2, 1fr);
        }
      }

      @media (max-width: 1200px) {
        .obs-vehiculos {
          grid-template-columns: 1fr;
        }
      }

      .obs-card {
        background: #ffffff;
        border: 2px solid #e9ecef;
        border-radius: 12px;
        overflow: hidden;
        transition: all .3s ease;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
      }

      .obs-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 28px rgba(0, 0, 0, 0.12);
        border-color: var(--accent);
      }

      .obs-card-header {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        padding: 1.25rem 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 2px solid #dee2e6;
      }

      .obs-placa {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--accent);
        display: flex;
        align-items: center;
        gap: 0.5rem;
      }

      .obs-badge {
        padding: 0.4rem 0.9rem;
        border-radius: 50px;
        font-size: 0.8rem;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
      }

      .obs-tipo {
        padding: 1rem 1.5rem;
        background: #f8f9fa;
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.75rem;
        border-bottom: 2px solid #e9ecef;
      }

      .obs-tipo-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.9rem;
        font-weight: 600;
        color: #495057;
      }

      .obs-tipo-item span:first-child {
        font-size: 1.1rem;
      }

      .obs-card-details {
        padding: 1.5rem;
      }

      .obs-items-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        overflow: hidden;
      }

      .obs-items-table thead {
        background: linear-gradient(135deg, #495057 0%, #343a40 100%);
        color: white;
      }

      .obs-items-table th {
        padding: 0.9rem 1rem;
        text-align: left;
        font-weight: 700;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
      }

      .obs-items-table tbody tr {
        border-bottom: 1px solid #e9ecef;
        transition: background 0.2s;
      }

      .obs-items-table tbody tr:hover {
        background: #f8f9fa;
      }

      .obs-items-table tbody tr:last-child {
        border-bottom: none;
      }

      .obs-items-table td {
        padding: 1rem;
        font-size: 0.9rem;
      }

      .obs-item-name {
        font-weight: 600;
        color: var(--text);
      }

      .obs-item-obs {
        color: #dc2626;
        font-weight: 500;
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
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color: white;
        border: none;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.85rem;
        cursor: pointer;
        transition: all 0.3s;
        box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);
      }

      .btn-crear-orden-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(40, 167, 69, 0.4);
        background: linear-gradient(135deg, #20c997 0%, #28a745 100%);
      }

      .obs-card-footer {
        padding: 1.25rem 1.5rem;
        background: #f8f9fa;
        border-top: 2px solid #e9ecef;
      }

      .obs-meta-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.9rem;
        font-weight: 600;
        color: #6c757d;
      }

      .obs-meta-icon {
        font-size: 1.1rem;
      }

      .obs-action-btn {
        background: linear-gradient(135deg, var(--accent) 0%, #003d6b 100%);
        color: white;
        border: none;
        padding: 0.6rem 1.25rem;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        box-shadow: 0 2px 8px rgba(0, 89, 150, 0.3);
      }

      .obs-action-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0, 89, 150, 0.4);
      }

      /* Estilos para el sidebar de filtros */
      .obs-filter-buttons {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        background: #ffffff;
        border-radius: 16px;
        padding: 1.5rem;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        border: 2px solid #e9ecef;
        position: sticky;
        top: 0; /* para que el menú izquierda se quede fijo dentro del panel */
      }

      .obs-filter-title {
        font-size: 1rem;
        font-weight: 700;
        color: #495057;
        margin-bottom: 0.5rem;
        padding-bottom: 0.75rem;
        border-bottom: 2px solid #e9ecef;
        text-transform: uppercase;
        letter-spacing: 0.5px;
      }

      .obs-filter-btn {
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        border: 2px solid #e9ecef;
        border-radius: 12px;
        padding: 1rem 1.25rem;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: 600;
        font-size: 0.95rem;
        color: #495057;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
      }

      .obs-filter-btn:hover {
        border-color: var(--accent);
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        transform: translateX(4px);
        box-shadow: 0 4px 12px rgba(0, 89, 150, 0.15);
      }

      .obs-filter-btn.active {
        background: linear-gradient(135deg, var(--accent) 0%, #003d6b 100%);
        color: white;
        border-color: var(--accent);
        box-shadow: 0 6px 16px rgba(0, 89, 150, 0.3);
        transform: translateX(8px) scale(1.02);
      }

      .obs-filter-btn-text {
        display: flex;
        align-items: center;
        gap: 0.75rem;
      }

      .obs-filter-btn-icon {
        font-size: 1.3rem;
        filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.1));
      }

      .obs-filter-btn-label {
        font-weight: 600;
        letter-spacing: 0.3px;
      }

      .filter-count {
        background: rgba(0, 89, 150, 0.1);
        color: var(--accent);
        padding: 0.35rem 0.75rem;
        border-radius: 50px;
        font-size: 0.85rem;
        font-weight: 700;
        min-width: 30px;
        text-align: center;
        transition: all 0.3s;
      }

      .obs-filter-btn.active .filter-count {
        background: rgba(255, 255, 255, 0.25);
        color: white;
      }

      /* Estado vacío */
      .obs-empty {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 4rem 2rem;
        text-align: center;
        gap: 1.5rem;
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        border-radius: 16px;
        border: 2px dashed #dee2e6;
      }

      .obs-empty svg {
        width: 120px;
        height: 120px;
        color: #28a745;
        filter: drop-shadow(0 4px 12px rgba(40, 167, 69, 0.2));
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

        .obs-filter-buttons {
          padding: 1rem;
          position: static;
        }

        .obs-filter-btn {
          padding: 0.75rem 1rem;
          font-size: 0.85rem;
        }
      }

      /* Toast notification styles */
      .toast {
        position: fixed;
        bottom: 2rem;
        right: 2rem;
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 12px;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
        z-index: 10000;
        animation: slideIn 0.3s ease-out;
        font-weight: 600;
      }

      .toast.error {
        background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
      }

      @keyframes slideIn {
        from {
          transform: translateX(400px);
          opacity: 0;
        }
        to {
          transform: translateX(0);
          opacity: 1;
        }
      }

      @keyframes slideOut {
        from {
          transform: translateX(0);
          opacity: 1;
        }
        to {
          transform: translateX(400px);
          opacity: 0;
        }
      }

      .page-header {
        background: linear-gradient(135deg, var(--accent) 0%, #003d6b 100%);
        padding: 2rem;
        border-radius: 16px;
        margin-bottom: 2rem;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
      }

      .page-header h1 {
        color: white;
        margin: 0;
        font-size: 2rem;
        font-weight: 700;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
      }

      .page-header p {
        color: rgba(255, 255, 255, 0.9);
        margin: 0.5rem 0 0 0;
        font-size: 1rem;
      }
    </style>

    <script>
      // Configuración de la API
      const API_BASE = '/Pedidos_GA/Servicios/api_mantto.php';

      // Funciones de utilidad
      function toast(msg, isOk = true) {
        const div = document.createElement('div');
        div.className = 'toast' + (isOk ? '' : ' error');
        div.textContent = msg;
        document.body.appendChild(div);
        setTimeout(() => {
          div.style.animation = 'slideOut 0.3s ease-out';
          setTimeout(() => div.remove(), 300);
        }, 3000);
      }

      async function apiGet(action, params = {}) {
        const url = new URL(API_BASE, window.location.origin);
        url.searchParams.set('action', action);
        Object.entries(params).forEach(([k, v]) => url.searchParams.set(k, v));
        try {
          const res = await fetch(url);
          return await res.json();
        } catch (err) {
          console.error('API GET error:', err);
          return { ok: false, msg: 'Error de conexión' };
        }
      }

      async function apiPost(action, data = {}) {
        try {
          const res = await fetch(API_BASE + '?action=' + action, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
          });
          return await res.json();
        } catch (err) {
          console.error('API POST error:', err);
          return { ok: false, msg: 'Error de conexión' };
        }
      }

      // Función global para mostrar/ocultar historial
      function toggleHistorial(itemId) {
        const el = document.getElementById(itemId);
        if (!el) return;
        if (el.style.display === 'none') {
          el.style.display = 'block';
        } else {
          el.style.display = 'none';
        }
      }

      // Aplicación principal
      (function() {
        const root = document.getElementById('observaciones-root');
        if (!root) return;

        const state = {
          observaciones: [],
          resueltas: [],
          metricas: {},
          currentFilter: 'all',
          currentTab: 'pendientes'
        };

        // Renderizar página
        async function init() {
          root.innerHTML = `
            <div class="page-header">
              <h1>🔍 Observaciones de Vehículos</h1>
              <p>Vehículos con calificación "Mal" en sus checklists vehiculares</p>
            </div>
            <div class="obs-tabs" style="display:flex;gap:0.5rem;margin-bottom:1.5rem;border-bottom:2px solid #e9ecef;padding-bottom:0.5rem;">
              <button class="obs-tab-btn active" data-tab="pendientes" style="background:linear-gradient(135deg, #005996 0%, #003d6b 100%);color:white;border:none;padding:0.75rem 1.5rem;border-radius:8px 8px 0 0;font-weight:700;cursor:pointer;transition:all 0.3s;">
                ⚠️ Observaciones Pendientes
              </button>
              <button class="obs-tab-btn" data-tab="resueltas" style="background:#f8f9fa;color:#495057;border:none;padding:0.75rem 1.5rem;border-radius:8px 8px 0 0;font-weight:700;cursor:pointer;transition:all 0.3s;">
                ✅ Historial Resueltas
              </button>
            </div>
            <section class="observaciones-wrap" id="obs-pendientes-section">
              <div id="observaciones-content">Cargando...</div>
            </section>
            <section class="observaciones-wrap" id="obs-resueltas-section" style="display:none;">
              <div id="resueltas-content">Cargando...</div>
            </section>
          `;

          // Event listeners para tabs
          root.querySelectorAll('.obs-tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
              const tab = btn.getAttribute('data-tab');
              switchTab(tab);
            });
          });

          await loadObservaciones();
          renderObservaciones();
        }

        function switchTab(tab) {
          state.currentTab = tab;

          // Actualizar botones
          root.querySelectorAll('.obs-tab-btn').forEach(btn => {
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
          const pendientesSection = root.querySelector('#obs-pendientes-section');
          const resueltasSection = root.querySelector('#obs-resueltas-section');

          if (tab === 'pendientes') {
            pendientesSection.style.display = 'block';
            resueltasSection.style.display = 'none';
          } else {
            pendientesSection.style.display = 'none';
            resueltasSection.style.display = 'block';
            loadResueltas();
          }
        }

        // Cargar observaciones desde la API
        async function loadObservaciones() {
          const res = await apiGet('observaciones');
          if (res && res.ok) {
            state.observaciones = res.items || res.data || [];
          } else {
            toast('Error al cargar observaciones', false);
            state.observaciones = [];
          }
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

          // Iconos para cada tipo de sección
          const sectionIcons = {
            'SISTEMA DE LUCES': '💡',
            'PARTE EXTERNA': '🚗',
            'PARTE INTERNA': '🪑',
            'ESTADO DE LLANTAS': '⚫',
            'ACCESORIOS DE SEGURIDAD': '🛡️',
            'default': '⚠️'
          };

          // Generar HTML con buscador y filtros
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
                <div class="obs-filter-title">🎯 Secciones</div>
                <button class="obs-filter-btn active" data-filter="all">
                  <span class="obs-filter-btn-text">
                    <span class="obs-filter-btn-icon">📊</span>
                    <span class="obs-filter-btn-label">Todas (ver lista)</span>
                  </span>
                  <span class="filter-count">${totalVehiculosAll}</span>
                </button>
          `;

          // Generar botones de filtro para cada sección
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
              <div class="obs-search-box">
                <input type="text" id="obs-search" class="obs-search-input" placeholder="🔍 Buscar por placa...">
                <div class="obs-search-meta" id="obs-search-meta"></div>
              </div>
              <div class="obs-sections-grid" id="obs-sections-container">
          `;

          // Generar HTML por sección con cards
          Object.keys(groupedBySection).sort().forEach(seccion => {
            const vehiculos = Object.values(groupedBySection[seccion]);
            const totalVehiculos = vehiculos.length;
            const icon = sectionIcons[seccion] || sectionIcons['default'];
            const totalItems = vehiculos.reduce((sum, v) => sum + v.items.length, 0);

            html += `
              <div class="obs-section" data-seccion="${seccion}">
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
                      <span class="obs-section-toggle-icon">▶</span>
                      <span class="obs-section-count">${totalVehiculos}</span>
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
                'Pendiente': { color: '#dc2626', bg: '#fef2f2', label: '⏳ Pendiente' },
                'Programado': { color: '#f59e0b', bg: '#fffbeb', label: '📅 Programada' },
                'EnTaller': { color: '#f97316', bg: '#fff7ed', label: '🔧 En Taller' }
              };
              const info = estatusInfo[estatusOrden] || estatusInfo['Pendiente'];

              const fechaInspeccion = veh.items[0]?.ultima_inspeccion ? new Date(veh.items[0].ultima_inspeccion).toLocaleDateString('es-MX') : 'N/A';
              const kmInspeccion = veh.items[0]?.ultimo_km ? Number(veh.items[0].ultimo_km).toLocaleString() : 'N/A';

              html += `
                <div class="obs-card" data-placa="${veh.placa || ''}">
                  <div class="obs-card-header">
                    <div class="obs-placa">🚘 ${veh.placa || 'Sin placa'}</div>
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

              veh.items.forEach((it, idx) => {
                const totalReportes = it.total_reportes || 1;
                const historial = it.historial || [];
                const ultimaObs = historial.length > 0 ? historial[0].observacion : 'Sin observaciones';
                const itemId = `item-${veh.id_vehiculo}-${idx}`;

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
                          <td><div class="obs-item-name">🔧 ${it.item || 'Sin descripción'}</div></td>
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
                        <button class="obs-action-btn btn-ver-orden" data-orden="${veh.orden_id}">
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
          });

          html += `</div></div></div>`;

          obsContent.innerHTML = html;

          const searchInput = document.getElementById('obs-search');
          const searchMeta = document.getElementById('obs-search-meta');
          const allSections = obsContent.querySelectorAll('.obs-section');

          // Por defecto: mostrar TODAS las secciones visibles pero solo la primera desplegada
          if (allSections.length > 0) {
            allSections.forEach(s => s.classList.remove('expanded'));
            allSections[0].classList.add('expanded');
          }

          // Click en header de sección -> desplegar/colapsar
          obsContent.querySelectorAll('.obs-section-header').forEach(header => {
            header.addEventListener('click', () => {
              const section = header.closest('.obs-section');
              const alreadyExpanded = section.classList.contains('expanded');

              // Solo 1 sección abierta a la vez (efecto acordeón)
              allSections.forEach(s => s.classList.remove('expanded'));
              if (!alreadyExpanded) {
                section.classList.add('expanded');
              }
            });
          });

          // Event listeners para búsqueda
          if (searchInput) {
            searchInput.addEventListener('input', (e) => {
              const query = e.target.value.toLowerCase().trim();
              const allCards = obsContent.querySelectorAll('.obs-card');

              if (query === '') {
                // Restaurar al filtro activo
                const filter = state.currentFilter;
                allSections.forEach(section => {
                  const seccionName = section.getAttribute('data-seccion');
                  const shouldShow = filter === 'all' || seccionName === filter;

                  if (shouldShow) {
                    section.classList.remove('hidden');
                  } else {
                    section.classList.add('hidden');
                  }
                });

                // Dejar solo una sección desplegada para que no crezca infinito
                allSections.forEach(s => s.classList.remove('expanded'));
                const visibleFirst = Array.from(allSections).find(s => !s.classList.contains('hidden'));
                if (visibleFirst) visibleFirst.classList.add('expanded');

                searchMeta.textContent = '';
                return;
              }

              let visibleCount = 0;

              allSections.forEach(section => {
                const seccionName = section.getAttribute('data-seccion');
                const shouldSearchInSection = state.currentFilter === 'all' || seccionName === state.currentFilter;

                if (!shouldSearchInSection) {
                  section.classList.add('hidden');
                  section.classList.remove('expanded');
                  return;
                }

                const cards = section.querySelectorAll('.obs-card');
                let sectionVisibleCount = 0;

                cards.forEach(card => {
                  const placa = (card.getAttribute('data-placa') || '').toLowerCase();
                  if (placa.includes(query)) {
                    card.classList.remove('hidden');
                    sectionVisibleCount++;
                    visibleCount++;
                  } else {
                    card.classList.add('hidden');
                  }
                });

                if (sectionVisibleCount === 0) {
                  section.classList.add('hidden');
                  section.classList.remove('expanded');
                } else {
                  section.classList.remove('hidden');
                  section.classList.add('expanded'); // expandir las secciones con resultados
                  const countEl = section.querySelector('.obs-section-count');
                  if (countEl) countEl.textContent = `${sectionVisibleCount}`;
                }
              });

              searchMeta.textContent = visibleCount > 0
                ? `Se encontraron ${visibleCount} vehículo${visibleCount !== 1 ? 's' : ''} con la placa "${query}"`
                : `No se encontraron vehículos con la placa "${query}"`;
            });
          }

          // Event listeners para los botones de crear orden
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

          // Event listeners para los botones de ver orden
          obsContent.querySelectorAll('.btn-ver-orden').forEach(btn => {
            btn.addEventListener('click', () => {
              const ordenId = parseInt(btn.getAttribute('data-orden'));
              window.location.href = `/Pedidos_GA/Servicios/detalles_orden.php?id=${ordenId}`;
            });
          });

          // Event listeners para los botones del menú lateral
          const filterButtons = obsContent.querySelectorAll('.obs-filter-btn');

          filterButtons.forEach(btn => {
            btn.addEventListener('click', () => {
              const filter = btn.getAttribute('data-filter');
              state.currentFilter = filter;

              // Actualizar botones activos
              filterButtons.forEach(b => b.classList.remove('active'));
              btn.classList.add('active');

              // Limpiar búsqueda
              if (searchInput) searchInput.value = '';
              if (searchMeta) searchMeta.textContent = '';

              // Aplicar "modo tabs": si es "all" mostramos todas las secciones (solo primera expandida).
              // Si es una sección específica, solo esa visible y expandida.
              if (filter === 'all') {
                allSections.forEach(s => {
                  s.classList.remove('hidden', 'expanded');
                });
                // Solo la primera desplegada
                allSections.forEach(s => s.classList.remove('expanded'));
                const first = allSections[0];
                if (first) first.classList.add('expanded');
              } else {
                allSections.forEach(section => {
                  const seccionName = section.getAttribute('data-seccion');
                  if (seccionName === filter) {
                    section.classList.remove('hidden');
                    section.classList.add('expanded');
                  } else {
                    section.classList.add('hidden');
                    section.classList.remove('expanded');
                  }
                });
              }

              // Scroll a la sección activa (si no es "all")
              if (filter !== 'all') {
                const visibleSection = Array.from(allSections).find(s =>
                  !s.classList.contains('hidden')
                );
                if (visibleSection) {
                  setTimeout(() => {
                    visibleSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                  }, 100);
                }
              }
            });
          });
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
            const width = ((item.count / topProblematicos[0].count) * 100) + '%';
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

        // Función para crear orden desde un ítem específico
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

            // Recargar observaciones
            await loadObservaciones();
            renderObservaciones();
          } catch (error) {
            toast('Error al crear la orden', false);
            console.error(error);
          }
        }

        // Inicializar
        init();
      })();
    </script>
  </div>
</body>

</html>
