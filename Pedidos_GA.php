<?php
// Iniciar la sesión de forma segura
ini_set('session.cookie_httponly', true); // Sólo permitir cookies de sesión vía HTTP
ini_set('session.cookie_secure', true); // Solo enviar cookies de sesión a través de conexiones HTTPS
session_name("GA");
session_start();

// Verificar si el usuario no está logeado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: /Pedidos_GA/Sesion/login.html");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pedidos GA</title>
  <link rel="stylesheet" type="text/css" href="styles.css">
  <link rel="icon" type="image/png" href="/Pedidos_GA/Img/Botones%20entregas/ICONOSPAG/ICONOPEDIDOS.png">
  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <!-- Mapbox GL JS para modal de destinatario y vista general -->
  <link href='https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css' rel='stylesheet' />
  <script src='https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js'></script>
  <script src='https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v5.0.0/mapbox-gl-geocoder.min.js'></script>
  <link rel='stylesheet' href='https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v5.0.0/mapbox-gl-geocoder.css' type='text/css' />
  <script src="https://cdn.jsdelivr.net/npm/@turf/turf@6/turf.min.js"></script>

  <!-- Estilos para modal de destinatario -->
  <style>
    /* ========== ESTILOS PARA BOTONES ========== */
    .btn {
      display: inline-block;
      padding: 6px 12px;
      font-size: 14px;
      font-weight: 400;
      line-height: 1.42857143;
      text-align: center;
      white-space: nowrap;
      vertical-align: middle;
      cursor: pointer;
      border: 1px solid transparent;
      border-radius: 4px;
      transition: all 0.15s ease-in-out;
    }

    .btn-sm {
      padding: 5px 10px;
      font-size: 12px;
      line-height: 1.5;
      border-radius: 3px;
    }

    .btn-primary {
      color: #fff;
      background-color: #007bff;
      border-color: #007bff;
    }

    .btn-primary:hover {
      background-color: #0056b3;
      border-color: #004085;
    }

    .btn-success {
      color: #fff;
      background-color: #28a745;
      border-color: #28a745;
    }

    .btn-success:hover {
      background-color: #218838;
      border-color: #1e7e34;
    }

    .btn-secondary {
      color: #fff;
      background-color: #6c757d;
      border-color: #6c757d;
    }

    .btn-secondary:hover {
      background-color: #5a6268;
      border-color: #545b62;
    }

    .btn:focus,
    .btn:active {
      outline: 0;
      box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }

    .mapboxgl-map {
      border-radius: 8px;
      margin-top: 10px;
    }

    .form-destinatario {
      max-height: 70vh;
      overflow-y: auto;
      padding: 10px;
    }

    .form-destinatario .form-section {
      margin-bottom: 20px;
      border-bottom: 1px solid #e5e7eb;
      padding-bottom: 15px;
    }

    .form-destinatario .form-section:last-child {
      border-bottom: none;
    }

    .form-destinatario h4 {
      margin: 0 0 15px 0;
      color: #1f2937;
      font-size: 16px;
      font-weight: 600;
    }

    .form-destinatario .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px;
      margin-bottom: 10px;
    }

    .form-destinatario .form-row.full {
      grid-template-columns: 1fr;
    }

    .form-destinatario label {
      display: block;
      font-size: 13px;
      font-weight: 500;
      margin-bottom: 4px;
      color: #374151;
    }

    .form-destinatario input,
    .form-destinatario textarea {
      width: 100%;
      padding: 8px 10px;
      border: 1px solid #d1d5db;
      border-radius: 6px;
      font-size: 13px;
      box-sizing: border-box;
    }

    .form-destinatario input:focus,
    .form-destinatario textarea:focus {
      outline: none;
      border-color: #2563eb;
      box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }

    .mapbox-search-wrapper {
      position: relative;
      margin-bottom: 10px;
    }

    #map-destinatario {
      width: 100%;
      height: 250px;
    }

    .coordenadas-info {
      font-size: 11px;
      color: #6b7280;
      margin-top: 5px;
      text-align: center;
    }

    /* Estilos para el toggle de vista */
    .view-toggle-container {
      display: flex;
      justify-content: center;
      margin-bottom: 20px;
      gap: 0;
    }

    .view-toggle-btn {
      padding: 10px 25px;
      font-size: 14px;
      font-weight: 600;
      border: 1px solid #005aa3;
      cursor: pointer;
      transition: all 0.3s;
      background: #fff;
      color: #005aa3;
    }

    .view-toggle-btn.active {
      background: #005aa3;
      color: #fff;
    }

    .view-toggle-btn:first-child {
      border-radius: 20px 0 0 20px;
    }

    .view-toggle-btn:last-child {
      border-radius: 0 20px 20px 0;
    }

    #map-and-table-wrapper {
      display: none;
      align-items: stretch;
      height: 75vh;
      min-height: 500px;
      margin-bottom: 20px;
      border-radius: 10px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      background: white;
      overflow: hidden;
    }

    #map-view-container {
      flex: 1;
      position: relative;
    }

    #map-stats-badge {
      position: absolute;
      top: 10px;
      right: 10px;
      z-index: 10;
      background: white;
      padding: 10px 15px;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.2);
      font-size: 13px;
      color: #333;
      border-left: 4px solid #005aa3;
      display: flex;
      flex-direction: column;
      gap: 5px;
    }

    .clickable-stat {
      cursor: pointer;
      transition: color 0.2s;
    }

    .clickable-stat:hover {
      color: #ed6b1f;
      text-decoration: underline;
    }

    /* Estilos para marcadores personalizados */
    .custom-marker {
      display: flex;
      justify-content: center;
      align-items: center;
      width: 32px;
      height: 32px;
      border-radius: 50% 50% 50% 0;
      transform: rotate(-45deg);
      border: 2px solid white;
      box-shadow: 0 4px 8px rgba(0,0,0,0.3);
      cursor: pointer;
      transition: transform 0.2s;
    }

    .custom-marker:hover {
      transform: rotate(-45deg) scale(1.1);
    }

    .marker-inner {
      transform: rotate(45deg);
      font-size: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
    }
  </style>
</head>
    
<body>
  <div class="sidebar">
    <ul>
      <!-- Grupo 1: Operaciones principales (siempre visible) -->
      <li>
        <a href="NuevoRegistroInicio.php" title="Agregar Registro">
          <img src="\Pedidos_GA\Img\Botones entregas\Pedidos_GA\AGRENA.png" alt="AddRegistro" class="icono-registro" style="max-width: 70%; height: auto;">
        </a>
      </li>
      <li>
        <a href="Estadisticas_Home.php" title="Estadísticas">
          <img src="\Pedidos_GA\Img\Botones entregas\Pedidos_GA\ESTNA2.png" alt="Estaditicas" class="icono-estadisticas" style="max-width: 70%; height: auto;">
        </a>
      </li>
     

      <!-- Menú desplegable: Administración -->
      <?php if ($_SESSION["Rol"] === "Admin" || $_SESSION["Rol"] === "JC" || $_SESSION["Rol"] === "MEC"): ?>
      <li class="sidebar-divider"></li>
      <li class="sidebar-dropdown">
        <a href="javascript:void(0)" class="dropdown-toggle" data-menu="admin">
          <span style="color: white; font-size: 11px; font-weight: bold;">ADMIN</span>
        </a>
        <ul class="dropdown-menu" id="menu-admin">
          <?php if ($_SESSION["Rol"] === "Admin"): ?>
            <li>
            <a href="mapa_calor.php" title="Mapa de Calor" style="display:flex; align-items:center; justify-content:center;">
             <img src="\Pedidos_GA\Img\Botones entregas\Pedidos_GA\Mapa_calor.png" alt="calor" class="icono-calor" style="max-width: 60%; height: auto;">
            </a>
            </li>

             <li>
        <a href="reporte_zonas.php" title="Reporte de Zonas" style="display:flex; align-items:center; justify-content:center;">
          <img src="\Pedidos_GA\Img\zonascolor.png" alt="Reporte Zonas" class="icono-reporte-zonas" style="max-width: 60%; height: auto;">
        </a>
      </li>
          <?php endif; ?>
          <?php if ($_SESSION["Rol"] === "Admin" || $_SESSION["Rol"] === "JC"): ?>
          <li>
            <a href="reporte_tiempos_estados.php" title="Reporte de Tiempos por Estado" style="display:flex; flex-direction:column; align-items:center; justify-content:center; text-decoration:none; color:#ed6b1f; transition: color 0.2s;" onmouseover="this.style.color='#ffffff'; this.querySelector('svg').style.transform='scale(1.15)'" onmouseout="this.style.color='#ed6b1f'; this.querySelector('svg').style.transform='scale(1)'">
              <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="icono-tiempos" style="max-width: 60%; height: auto; transition: transform 0.2s;">
                <circle cx="12" cy="12" r="10" />
                <polyline points="12 6 12 12 16 14" />
              </svg>
              <span style="font-size: 11px; font-weight: bold; margin-top: 4px;">Tiempos</span>
            </a>
          </li>
          <li>
            <a href="reporte_incidencias.php" title="Reporte de Incidencias" style="display:flex; flex-direction:column; align-items:center; justify-content:center; text-decoration:none; color:#ed6b1f; transition: color 0.2s;" onmouseover="this.style.color='#ffffff'; this.querySelector('svg').style.transform='scale(1.15)'" onmouseout="this.style.color='#ed6b1f'; this.querySelector('svg').style.transform='scale(1)'">
              <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="icono-incidencias" style="max-width: 60%; height: auto; transition: transform 0.2s;">
                <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
                <line x1="12" y1="9" x2="12" y2="13"/>
                <line x1="12" y1="17" x2="12.01" y2="17"/>
              </svg>
              <span style="font-size: 11px; font-weight: bold; margin-top: 4px;">Incidencias</span>
            </a>
          </li>
          <?php endif; ?>
          <?php if ($_SESSION["Rol"] === "Admin" || $_SESSION["Rol"] === "JC" || $_SESSION["Rol"] === "MEC"): ?>
          <li>
            <a href="vehiculos.php" title="Vehículos" style="display:flex; align-items:center; justify-content:center;">
              <img src="\Pedidos_GA\Img\Botones entregas\Pedidos_GA\SERVMECNA.png" alt="vehiculos" class="icono-vehiculos" style="max-width: 60%; height: auto;">
            </a>
          </li>
          <?php endif; ?>
          <li>
            <a href="Choferes.php" title="Choferes" style="display:flex; align-items:center; justify-content:center;">
              <img src="\Pedidos_GA\Img\Botones entregas\Pedidos_GA\CHOFNA2.png" alt="Choferes" class="icono-choferes" style="max-width: 60%; height: auto;">
            </a>
          </li>
        </ul>
      </li>
      <?php else: ?>
      <!-- Si no es admin, mostrar Choferes directamente -->
      <li class="sidebar-divider"></li>
      <li>
        <a href="Choferes.php" title="Choferes" style="display:flex; align-items:center; justify-content:center;">
          <img src="\Pedidos_GA\Img\Botones entregas\Pedidos_GA\CHOFNA2.png" alt="Choferes" class="icono-choferes" style="max-width: 70%; height: auto;">
        </a>
      </li>
      <?php endif; ?>

      <!-- Menú desplegable: Configuración (solo Admin/JC) -->
      <?php if ($_SESSION["Rol"] === "Admin" || $_SESSION["Rol"] === "JC"): ?>
      <li class="sidebar-divider"></li>
      <li class="sidebar-dropdown">
        <a href="javascript:void(0)" class="dropdown-toggle" data-menu="config">
          <span style="color: white; font-size: 11px; font-weight: bold;">CONFIG</span>
        </a>
        <ul class="dropdown-menu" id="menu-config">
          <?php if ($_SESSION["Rol"] === "Admin"): ?>
          <li>
            <a href="Usuarios.php" title="Usuarios" style="display:flex; align-items:center; justify-content:center;">
              <img src="\Pedidos_GA\Img\Botones entregas\Pedidos_GA\USUNA.png" alt="Usuarios" class="icono-U" style="max-width: 60%; height: auto;">
            </a>
          </li>
          <li>
            <a href="historial.php" title="Historial" style="display:flex; align-items:center; justify-content:center;">
              <img src="\Pedidos_GA\Img\Botones entregas\Pedidos_GA\H2.png" alt="Historial" class="icono-H" style="max-width: 60%; height: auto;">
            </a>
          </li>
          <?php endif; ?>
          <li>
            <a href="reporte_precios_facturas.php" title="Reporte de Precios" style="display:flex; align-items:center; justify-content:center;">
              <img src="\Pedidos_GA\Img\Botones entregas\Pedidos_GA\ICONO_CHIDO.png" alt="Reporte Precios" class="icono-reporte-precios" style="max-width: 60%; height: auto;">
            </a>
          </li>
        </ul>
      </li>
      <?php endif; ?>

      <!-- Cerrar sesión (siempre al final) -->
      <li class="corner-left-bottom">
        <a href="logout.php" title="Cerrar Sesión">
          <img src="\Pedidos_GA\Img\Botones entregas\Pedidos_GA\CERRSESBL.png" alt="Cerrar Sesión" class="icono-CS" style="max-width: 40%; height: auto;">
        </a>
      </li>
    </ul>
  </div>
    
  <div class="content">
    <!-- Contenido principal de tu página -->
  </div>

  <script>
    // Manejo de menús desplegables en sidebar
    document.addEventListener("DOMContentLoaded", function() {
      // Toggle dropdowns
      const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
      dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
          e.preventDefault();
          const menuId = this.dataset.menu;
          const menu = document.getElementById('menu-' + menuId);
          if (menu) {
            menu.classList.toggle('open');
          }
        });
      });

      // Manejo de imágenes e iconos
      var iconoVehiculos = document.querySelector(".icono-vehiculos");
       var iconoCalor = document.querySelector(".icono-calor");
      var iconoChoferes = document.querySelector(".icono-choferes");
      var iconoEstadisticas = document.querySelector(".icono-estadisticas");
      var iconoAddRegistro = document.querySelector(".icono-registro");
      var iconoCS = document.querySelector(".icono-CS");
      var iconoU = document.querySelector(".icono-U");
      var iconoH = document.querySelector(".icono-H");
      var iconoReportePrecios = document.querySelector(".icono-reporte-precios");
      var iconoReporteZonas = document.querySelector(".icono-reporte-zonas");
      var iconoMapaCalor = document.querySelector(".icono-mapa-calor");
      var iconoMaps = document.querySelector(".icono-Maps");
      var iconoHome = document.querySelector(".icono-Home");
      var iconoWP = document.querySelector(".icono-WP");
      
      if (iconoChoferes) {
        var imgNormalChoferes = "/Pedidos_GA/Img/Botones%20entregas/Pedidos_GA/CHOFNA2.png";
        var imgHoverChoferes = "/Pedidos_GA/Img/Botones%20entregas/Pedidos_GA/CHOFBL2.png";
        iconoChoferes.addEventListener("mouseover", function() {
            iconoChoferes.src = imgHoverChoferes;
        });
        iconoChoferes.addEventListener("mouseout", function() {
            iconoChoferes.src = imgNormalChoferes;
        });
      }

      if (iconoVehiculos) {
        var imgNormalVehiculos = "/Pedidos_GA/Img/Botones%20entregas/Pedidos_GA/SERVMECNA.png";
        var imgHoverVehiculos = "/Pedidos_GA/Img/Botones%20entregas/Pedidos_GA/SERVMECBLANC.png";
        iconoVehiculos.addEventListener("mouseover", function() {
            iconoVehiculos.src = imgHoverVehiculos;
        });
        iconoVehiculos.addEventListener("mouseout", function() {
            iconoVehiculos.src = imgNormalVehiculos;
        });
      }

      if (iconoCalor) {
        var imgNormalCalor = "/Pedidos_GA/Img/Botones%20entregas/Pedidos_GA/Mapa_calor.png";
        var imgHoverCalor = "/Pedidos_GA/Img/Botones%20entregas/Pedidos_GA/Mapa_calor_bc.png";
        iconoCalor.addEventListener("mouseover", function() {
            iconoCalor.src = imgHoverCalor;
        });
        iconoCalor.addEventListener("mouseout", function() {
            iconoCalor.src = imgNormalCalor;
        });
      }
      
      if (iconoEstadisticas) {
        var imgNormalEstadisticas = "/Pedidos_GA/Img/Botones%20entregas/Pedidos_GA/ESTNA2.png";
        var imgHoverEstadisticas = "/Pedidos_GA/Img/Botones%20entregas/Pedidos_GA/ESTBL2.png";
        iconoEstadisticas.addEventListener("mouseover", function() {
            iconoEstadisticas.src = imgHoverEstadisticas;
        });
        iconoEstadisticas.addEventListener("mouseout", function() {
            iconoEstadisticas.src = imgNormalEstadisticas;
        });
      }
      
      if (iconoAddRegistro) {
        var imgNormalAddRegistro = "/Pedidos_GA/Img/Botones%20entregas/Pedidos_GA/AGRENA.png";
        var imgHoverAddRegistro = "/Pedidos_GA/Img/Botones%20entregas/Pedidos_GA/AGREBL.png";
        iconoAddRegistro.addEventListener("mouseover", function() {
            iconoAddRegistro.src = imgHoverAddRegistro;
        });
        iconoAddRegistro.addEventListener("mouseout", function() {
            iconoAddRegistro.src = imgNormalAddRegistro;
        });
      }
      
      if (iconoU) {
        var imgNormalU = "/Pedidos_GA/Img/Botones%20entregas/Pedidos_GA/USUNA.png";
        var imgHoverU = "/Pedidos_GA/Img/Botones%20entregas/Pedidos_GA/USUBL.png";
        iconoU.addEventListener("mouseover", function() {
            iconoU.src = imgHoverU;
        });
        iconoU.addEventListener("mouseout", function() {
            iconoU.src = imgNormalU;
        });
      }

      var imgNormalH = "/Pedidos_GA/Img/Botones%20entregas/Pedidos_GA/H2.png";
      var imgHoverH = "/Pedidos_GA/Img/Botones%20entregas/Pedidos_GA/H1.png";
      if (iconoH) {
          iconoH.addEventListener("mouseover", function() {
              iconoH.src = imgHoverH;
          });
          iconoH.addEventListener("mouseout", function() {
              iconoH.src = imgNormalH;
          });
      }

    
      if (iconoReportePrecios) {
          var imgNormalReportePrecios = "/Pedidos_GA/Img/Botones%20entregas/Pedidos_GA/ICONO_CHIDO.png";
      var imgHoverReportePrecios = "/Pedidos_GA/Img/Botones%20entregas/Pedidos_GA/ICONO_CHIDO2_BLANCO.png";
      
          iconoReportePrecios.addEventListener("mouseover", function() {
              iconoReportePrecios.src = imgHoverReportePrecios;
          });
          iconoReportePrecios.addEventListener("mouseout", function() {
              iconoReportePrecios.src = imgNormalReportePrecios;
          });
      }

      if (iconoReporteZonas) {
          var imgNormalReporteZonas = "/Pedidos_GA/Img/zonascolor.png";
          var imgHoverReporteZonas = "/Pedidos_GA/Img/ZG_BC.png";
          
          iconoReporteZonas.addEventListener("mouseover", function() {
              iconoReporteZonas.src = imgHoverReporteZonas;
          });
          iconoReporteZonas.addEventListener("mouseout", function() {
              iconoReporteZonas.src = imgNormalReporteZonas;
          });
      }

      var imgNormalMapaCalor = "/Pedidos_GA/Img/Botones%20entregas/Pedidos_GA/MAPA_NA.png";
      var imgHoverMapaCalor = "/Pedidos_GA/Img/Botones%20entregas/Pedidos_GA/MAPA_BL.png";
      if (iconoMapaCalor) {
          iconoMapaCalor.addEventListener("mouseover", function() {
              iconoMapaCalor.src = imgHoverMapaCalor;
          });
          iconoMapaCalor.addEventListener("mouseout", function() {
              iconoMapaCalor.src = imgNormalMapaCalor;
          });
      }

      if (iconoCS) {
        var imgNormalSC = "/Pedidos_GA/Img/Botones%20entregas/Pedidos_GA/CERRSESBL.png";
        var imgHoverSC = "/Pedidos_GA/Img/Botones%20entregas/Pedidos_GA/CERRSESNA.png";
        iconoCS.addEventListener("mouseover", function() {
            iconoCS.src = imgHoverSC;
        });
        iconoCS.addEventListener("mouseout", function() {
            iconoCS.src = imgNormalSC;
        });
      }
      
      var imgNormalMaps = "/Pedidos_GA/Img/Botones%20entregas/Inicio/DETPED/MAPSNA.png";
      var imgHoverMaps = "/Pedidos_GA/Img/Botones%20entregas/Inicio/DETPED/ABRGMAZ.png";
      if (iconoMaps) {
          iconoMaps.addEventListener("mouseover", function() {
              iconoMaps.src = imgHoverMaps;
          });
          iconoMaps.addEventListener("mouseout", function() {
              iconoMaps.src = imgNormalMaps;
          });
      }
      
      if (iconoHome) {
        var imgNormalHome = "/Pedidos_GA/Img/Botones%20entregas/Pedidos_GA/CERRSESBL.png";
        var imgHoverHome = "/Pedidos_GA/Img/Botones%20entregas/Pedidos_GA/CERRSESNA.png";
        iconoHome.addEventListener("mouseover", function() {
            iconoHome.src = imgHoverHome;
        });
        iconoHome.addEventListener("mouseout", function() {
            iconoHome.src = imgNormalHome;
        });
      }
      
      if (iconoWP) {
        var imgNormalWP = "/Pedidos_GA/Img/Botones%20entregas/Whatsapp/WWSPAZ.png";
        var imgHoverWP = "/Pedidos_GA/Img/Botones%20entregas/Whatsapp/WSPNA.png";
        iconoWP.addEventListener("mouseover", function() {
            iconoWP.src = imgHoverWP;
        });
        iconoWP.addEventListener("mouseout", function() {
            iconoWP.src = imgNormalWP;
        });
      }
    });
  </script>
    
  <!-- Botón para Mensajería WhatsApp (sin cambios) -->
  <?php if ($_SESSION["Rol"] === "JC") : ?>
  <div class="WP-button">
    <form id="wpForm" action="/Mensajes_WP/Mensaje_WP_NotificarChoferes.php" method="post" onsubmit="return false;">
      <button type="button" onclick="confirmSubmit()" value="WP" style="background: none; border: none; padding: 0;">
        <img src="/Pedidos_GA/Img/Botones entregas/Whatsapp/WWSPAZ.png" alt="icono-WP" class="icono-WP" style="max-width: 45%; height: auto;">
      </button>
    </form>
  </div>
  <?php endif; ?>

  <!-- Botón para Descargar App Móvil (Admin y JC) -->
  <?php if ($_SESSION["Rol"] === "Admin" || $_SESSION["Rol"] === "JC") : ?>
  <div class="download-app-button">
    <a href="http://107.21.163.64/Pedidos_GA/Pedidos_GA/App/Apks/Pedidos_GA_V5_WEB.apk" download="Pedidos_GA_V5_WEB.apk" title="Descargar App Móvil">
      <div class="download-icon">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white" width="35px" height="35px">
          <path d="M0 0h24v24H0z" fill="none"/>
          <path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/>
        </svg>
      </div>
    </a>
  </div>
  <?php endif; ?>
    
  <script>
    function confirmSubmit() {
      if (confirm("¿Está seguro que desea realizar esta operación?")) {
        var xhr = new XMLHttpRequest();
        var formData = new FormData(document.getElementById('wpForm'));
        xhr.open('POST', '/Pedidos_GA/Mensajes_WP/Mensaje_WP_NotificarChoferes.php', true);
        xhr.onload = function () {
          if (xhr.status === 200) {
            try {
              var response = JSON.parse(xhr.responseText);
              if (response.status === 'success') {
                alert('Operación realizada con éxito: ' + response.message);
              } else {
                alert('Hubo un error al realizar la operación: ' + response.message);
                if (response.errors) {
                  console.error(response.errors);
                }
              }
            } catch (e) {
              alert('Hubo un error al procesar la respuesta del servidor.');
              console.error(e, xhr.responseText);
            }
          } else {
            alert('Hubo un error al realizar la operación. Código de estado: ' + xhr.status);
          }
        };
        xhr.onerror = function () {
          alert('Hubo un error en la petición AJAX.');
        };
        xhr.send(formData);
      }
    }
  </script>
    
  <div class="container">
    <h2 class="titulo">
      <img src="\Pedidos_GA\Img\Botones entregas\Pedidos_GA\PEDPRNAZ.png" alt="Pedidos" class="icono-registro" style="max-width: 15%; height: auto;">
    </h2>
    <p>Bienvenido, <?php echo $_SESSION["Nombre"]; ?>!</p>
    
    <!-- Formulario para selección de sucursal -->
    <?php if ($_SESSION["Rol"] === "Admin"): ?>
      <form id="consultaForm" class="formulario" style="margin-bottom: 10px;">
        <label for="sucursal" class="label">Sucursal:</label>
        <select id="sucursal" name="sucursal">
          <option value="TODAS">TODAS</option>
          <option value="GABSA">GABSA</option>
          <option value="ILUMINACION">ILUMINACION</option>
          <option value="DIMEGSA">DIMEGSA</option>
          <option value="DEASA">DEASA</option>
          <option value="AIESA">AIESA</option>
          <option value="SEGSA">SEGSA</option>
          <option value="FESA">FESA</option>
          <option value="TAPATIA">TAPATIA</option>
          <option value="VALLARTA">VALLARTA</option>
          <option value="TALLER">TALLER</option>
          <option value="CODI">CODI</option>
          <option value="QUERETARO">QUERETARO</option>
          <option value="CONSTITUYENTES">CONSTITUYENTES</option>
        </select>
      </form>
    <?php endif; ?>
    <?php if (($_SESSION["Rol"] === "JC") OR ($_SESSION["Rol"] === "VR")): ?>
      <form id="consultaForm" class="formulario" style="margin-bottom: 10px;">
        <label for="sucursal" class="label">Sucursal:</label>
        <select id="sucursal" name="sucursal" disabled>
          <option value="TODAS">TODAS</option>
          <option value="GABSA">GABSA</option>
          <option value="ILUMINACION">ILUMINACION</option>
          <option value="DIMEGSA">DIMEGSA</option>
          <option value="DEASA">DEASA</option>
          <option value="AIESA">AIESA</option>
          <option value="SEGSA">SEGSA</option>
          <option value="FESA">FESA</option>
          <option value="TAPATIA">TAPATIA</option>
          <option value="VALLARTA">VALLARTA</option>
          <option value="TALLER">TALLER</option>
          <option value="CODI">CODI</option>
          <option value="QUERETARO">QUERETARO</option>
          <option value="CONSTITUYENTES">CONSTITUYENTES</option>
        </select>
      </form>
    <?php endif; ?>
    
    <!-- Formulario de filtrado: Checkboxes y búsqueda -->
    <form id="filtroEstadoForm" style="background: #e8ebed; padding: 15px; border-radius: 8px; margin-bottom: 20px;">

      

      <!-- 1. Filtrar por Estado -->
      <div style="padding-bottom: 6px;">
        <label class="label" style="font-weight: bold; font-size: 13px; margin-bottom: 8px; display: block; color: #495057;">Filtrar por Estado:</label>
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px;">
          <div style="display: flex; align-items: center;">
            <input type="checkbox" id="estadoCancelado" name="estado" value="CANCELADO" style="margin: 0;">
            <label for="estadoCancelado" style="font-size: 12px; margin-left: 4px; cursor: pointer;">CANCELADO</label>
          </div>
          <div style="display: flex; align-items: center;">
            <input type="checkbox" id="estadoEnTienda" name="estado" value="EN TIENDA" checked style="margin: 0;">
            <label for="estadoEnTienda" style="font-size: 12px; margin-left: 4px; cursor: pointer;">EN TIENDA</label>
          </div>
          <div style="display: flex; align-items: center;">
            <input type="checkbox" id="estadoReprogramado" name="estado" value="REPROGRAMADO" checked style="margin: 0;">
            <label for="estadoReprogramado" style="font-size: 12px; margin-left: 4px; cursor: pointer;">REPROGRAMADO</label>
          </div>
          <div style="display: flex; align-items: center;">
            <input type="checkbox" id="estadoActivo" name="estado" value="ACTIVO" checked style="margin: 0;">
            <label for="estadoActivo" style="font-size: 12px; margin-left: 4px; cursor: pointer;">ACTIVO</label>
          </div>
          <div style="display: flex; align-items: center;">
            <input type="checkbox" id="estadoEnRuta" name="estado" value="EN RUTA" checked style="margin: 0;">
            <label for="estadoEnRuta" style="font-size: 12px; margin-left: 4px; cursor: pointer;">EN RUTA</label>
          </div>
          <div style="display: flex; align-items: center;">
            <input type="checkbox" id="estadoEntregado" name="estado" value="ENTREGADO" style="margin: 0;">
            <label for="estadoEntregado" style="font-size: 12px; margin-left: 4px; cursor: pointer;">ENTREGADO</label>
          </div>
        </div>
      </div>

      <!-- 1b. Filtrar por Zona (LOCAL / FORÁNEO) -->
      <div style="padding-bottom: 6px;">
        <label class="label" style="font-weight: bold; font-size: 13px; margin-bottom: 8px; display: block; color: #495057;">Filtrar por Zona:</label>
        <div style="display: flex; gap: 8px;">
          <button type="button" id="zonaBtn-TODAS" class="zona-btn zona-btn-active" data-zona="" onclick="seleccionarZona('')"
            style="flex:1; padding:7px 10px; border:2px solid #6c757d; border-radius:20px; background:#6c757d; color:#fff; font-size:12px; font-weight:700; cursor:pointer; transition:all .2s;">
            TODOS
          </button>
          <button type="button" id="zonaBtn-LOCAL" class="zona-btn" data-zona="LOCAL" onclick="seleccionarZona('LOCAL')"
            style="flex:1; padding:7px 10px; border:2px solid #28a745; border-radius:20px; background:#fff; color:#28a745; font-size:12px; font-weight:700; cursor:pointer; transition:all .2s;">
            🏠 LOCAL
          </button>
          <button type="button" id="zonaBtn-FORANEO" class="zona-btn" data-zona="FORANEO" onclick="seleccionarZona('FORANEO')"
            style="flex:1; padding:7px 10px; border:2px solid #ff9800; border-radius:20px; background:#fff; color:#ff9800; font-size:12px; font-weight:700; cursor:pointer; transition:all .2s;">
            🌍 FORÁNEO
          </button>
        </div>
        <input type="hidden" id="filtroZona" name="filtroZona" value="">
      </div>

      <!-- 2. Filtrar por Grupo -->
      <div style="padding-bottom: 6px;">
        <label class="label" style="font-weight: bold; font-size: 13px; margin-bottom: 8px; display: block; color: #495057;">Filtrar por Grupo:</label>
        <select id="filtroGrupo" name="filtroGrupo" style="width: 100%; padding: 8px; border: 1px solid #ced4da; border-radius: 4px; font-size: 13px; box-sizing: border-box; margin-bottom: 8px;">
          <option value="">Todos los pedidos</option>
          <option value="CON_GRUPO">Con grupo asignado</option>
          <option value="SIN_GRUPO">Sin grupo asignado</option>
        </select>
        <input type="text" id="buscarGrupo" name="buscarGrupo" placeholder="Buscar por nombre de grupo..." style="width: 100%; padding: 8px; border: 1px solid #ced4da; border-radius: 4px; font-size: 13px; box-sizing: border-box;">
      </div>

      <!-- 3. Rango de Fechas -->
      <div style="padding-bottom: 6px;">
        <label class="label" style="font-weight: bold; font-size: 13px; margin-bottom: 8px; display: block; color: #495057;">Rango de Fechas:</label>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 8px;">
          <div>
            <label for="fechaInicio" style="font-size: 11px; display: block; margin-bottom: 4px; color: #666;">Desde:</label>
            <input type="date" id="fechaInicio" name="fechaInicio" style="width: 100%; padding: 6px; border: 1px solid #ced4da; border-radius: 4px; font-size: 12px; box-sizing: border-box;">
          </div>
          <div>
            <label for="fechaFin" style="font-size: 11px; display: block; margin-bottom: 4px; color: #666;">Hasta:</label>
            <input type="date" id="fechaFin" name="fechaFin" style="width: 100%; padding: 6px; border: 1px solid #ced4da; border-radius: 4px; font-size: 12px; box-sizing: border-box;">
          </div>
        </div>
        <button type="button" id="limpiarFechas" class="boton-consultar" style="background-color: #6c757d; width: 100%; padding: 8px; font-size: 12px; box-sizing: border-box;">Limpiar fechas</button>
      </div>

      <!-- 4. Búsqueda General -->
      <div style="padding-bottom: 6px;">
        <label class="label" style="font-weight: bold; font-size: 13px; margin-bottom: 8px; display: block; color: #495057;">Búsqueda General:</label>
        <div style="display: flex; gap: 8px; align-items: stretch;">
          <input type="text" id="busqueda" name="busqueda" placeholder="Buscar en todo (nombre, factura, chofer, dirección...)" style="flex: 1; padding: 8px; border: 1px solid #ced4da; border-radius: 4px; font-size: 13px; box-sizing: border-box;">
          <button type="button" id="boton-buscar" class="boton-consultar" style="padding: 8px 16px; font-size: 13px; white-space: nowrap; box-sizing: border-box;">Buscar</button>
        </div>
        <div id="busqueda-hint" style="font-size: 11px; color: #888; margin-top: 4px;">Busca en: N°, vendedor, estado, chofer, factura, dirección, cliente, contacto, sucursal, tipo envío</div>
      </div>

    </form>
    
    <!-- Toggle de Vista (Solo Admin y JC) -->
    <?php if (in_array($_SESSION["Rol"], ["Admin", "JC"])): ?>
    <div class="view-toggle-container">
      <button type="button" id="btn-view-table" class="view-toggle-btn active" onclick="switchView('table')">
        📋 Vista Tabla
      </button>
      <button type="button" id="btn-view-map" class="view-toggle-btn" onclick="switchView('map')">
        🗺️ Vista Mapa
      </button>
    </div>

    <div id="map-and-table-wrapper">
      <div id="map-table-container" style="flex: 0 0 25%; overflow-y: auto; border-right: 2px solid #ddd;">
        <table class="mi-tabla" id="map-orders-table" style="width: 100%; margin: 0; font-size: 11px; border-collapse: collapse; text-align: left;">
          <thead style="position: sticky; top: 0; background: #005aa3; color: white; z-index: 1;">
            <tr>
              <th style="padding: 4px;">Pedido</th>
              <th style="padding: 4px;">Factura</th>
              <th style="padding: 4px;">Cliente</th>
              <th style="padding: 4px;">Sucursal</th>
              <th style="padding: 4px;">Estado</th>
              <th style="padding: 4px; text-align: center;">Acción</th>
            </tr>
          </thead>
          <tbody>
          </tbody>
        </table>
      </div>

      <div id="map-view-container">
        <div id="map-stats-badge">
          <div id="stat-total">Total: <strong>0</strong></div>
          <div id="stat-missing" class="clickable-stat" onclick="showMissingCoordsList()">Sin coordenadas: <strong style="color: #dc3545;">0</strong></div>
        </div>
        <div id="map-main" style="width: 100%; height: 100%;"></div>
      </div>
    </div>
    <?php endif; ?>
    
    <p></p>
    <!-- Contenedor para mostrar resultados -->
    <div id="resultado">
      <!-- Aquí se cargarán los resultados dinámicamente -->
    </div>
    
    <!-- Controles de paginación -->
    <div id="pagination" style="margin-top: 20px; text-align: center;">
      <button type="button" id="prevPage">Anterior</button>
      <span id="currentPage">Página 1</span>
      <button type="button" id="nextPage">Siguiente</button>
    </div>
  </div>
    
  <script>
    document.addEventListener("DOMContentLoaded", function() {
      // Variable global para el offset (en registros) y página actual
      var offset = 0;
      var registrosPorPagina = 100;
      var currentPage = 1;
      
      // Variables para el Mapa Principal
      mapboxgl.accessToken = 'pk.eyJ1IjoiYWNhc2lsbGFzNzY2IiwiYSI6ImNsdW12cTZyMjB4NnMya213MDdseXp6ZGgifQ.t7-l1lQfd8mgHILM5YrdNw';
      var mainMap = null;
      var mainMarkers = [];
      var currentView = 'table';
      var missingCoordsOrders = []; // Pedidos que no tienen coordenadas válidas

      // Colores por sucursal
      const branchColors = {
        'GABSA': '#005aa3',
        'ILUMINACION': '#ed6b1f',
        'DIMEGSA': '#28a745',
        'DEASA': '#6f42c1',
        'AIESA': '#e83e8c',
        'SEGSA': '#20c997',
        'FESA': '#fd7e14',
        'TAPATIA': '#004d6f',
        'VALLARTA': '#17a2b8',
        'CODI': '#d63384',
        'QUERETARO': '#ffc107',
        'CONSTITUYENTES': '#495057',
        'OTRA': '#6c757d'
      };

      function getSucursalColor(sucursal) {
        return branchColors[sucursal] || branchColors['OTRA'];
      }

      // Mostrar modal con lista de pedidos sin coordenadas
      window.showMissingCoordsList = function() {
        if (missingCoordsOrders.length === 0) {
          Swal.fire({
            icon: 'info',
            title: 'Todo bien',
            text: 'Todos los pedidos de esta página tienen coordenadas válidas.'
          });
          return;
        }

        let html = '<div style="text-align: left; max-height: 400px; overflow-y: auto;">';
        html += '<p style="margin-bottom: 15px; color: #666; font-size: 14px;">Haz clic en cualquier fila para ver los detalles del pedido.</p>';
        html += '<table class="mi-tabla" style="width: 100%;">';
        html += '<tr><th>ID</th><th>Factura</th><th>Cliente</th><th>Sucursal</th><th>Dirección</th></tr>';
        missingCoordsOrders.forEach(p => {
          html += `<tr onclick="window.open('Inicio.php?id=${p.id}', '_blank')" style="cursor: pointer;">
            <td>${p.id}</td>
            <td>${p.factura}</td>
            <td>${p.cliente}</td>
            <td>${p.sucursal}</td>
            <td style="font-size: 11px;">${p.direccion}</td>
          </tr>`;
        });
        html += '</table></div>';

        Swal.fire({
          title: 'Pedidos sin Coordenadas',
          html: html,
          width: '850px',
          confirmButtonColor: '#005aa3'
        });
      };

      // Función para cambiar de vista (Tabla/Mapa)
      window.switchView = function(view) {
        currentView = view;
        const resDiv = document.getElementById("resultado");
        const pagDiv = document.getElementById("pagination");
        const mapWrapperDiv = document.getElementById("map-and-table-wrapper");
        const btnTable = document.getElementById("btn-view-table");
        const btnMap = document.getElementById("btn-view-map");

        if (view === 'map') {
          resDiv.style.display = 'none';
          pagDiv.style.display = 'none';
          if(mapWrapperDiv) mapWrapperDiv.style.display = 'flex';
          btnMap.classList.add('active');
          btnTable.classList.remove('active');
          
          if (!mainMap) {
            initMainMap();
          } else {
            setTimeout(() => mainMap.resize(), 100);
          }
          updateMapMarkers();
        } else {
          resDiv.style.display = 'block';
          pagDiv.style.display = 'block';
          if(mapWrapperDiv) mapWrapperDiv.style.display = 'none';
          btnTable.classList.add('active');
          btnMap.classList.remove('active');
        }
      };

      function initMainMap() {
        mainMap = new mapboxgl.Map({
          container: 'map-main',
          style: 'mapbox://styles/mapbox/streets-v12',
          center: [-103.35, 20.67], // Guadalajara
          zoom: 11
        });
        mainMap.addControl(new mapboxgl.NavigationControl());
      }

      function updateMapMarkers() {
        if (!mainMap) return;
        
        // Limpiar marcadores anteriores
        mainMarkers.forEach(m => m.remove());
        mainMarkers = [];
        missingCoordsOrders = [];
        
        const checkboxes = document.querySelectorAll('.pedido-checkbox');
        const bounds = new mapboxgl.LngLatBounds();
        let plottedCount = 0;

        const tbody = document.querySelector('#map-orders-table tbody');
        if(tbody) tbody.innerHTML = '';
        const fragment = document.createDocumentFragment();

        checkboxes.forEach(cb => {
          const coordsRaw = cb.dataset.coordenadas;
          const sucursal = cb.dataset.sucursal;
          const color = getSucursalColor(sucursal);
          const hasDriver = cb.dataset.chofer && cb.dataset.chofer !== '';
          const hasGroup = cb.dataset.grupoId && cb.dataset.grupoId !== '';

          if (coordsRaw && coordsRaw.includes(',')) {
            const [lat, lng] = coordsRaw.split(',').map(Number);
            if (!isNaN(lat) && !isNaN(lng)) {
              // Crear elemento de marcador personalizado
              const el = document.createElement('div');
              el.className = 'custom-marker';
              el.style.backgroundColor = color;
              
              const inner = document.createElement('div');
              inner.className = 'marker-inner';
              // Si tiene chofer o grupo, mostrar icono de camión, si no, un punto
              inner.innerHTML = (hasDriver || hasGroup) ? '🚚' : '📍';
              el.appendChild(inner);

              const popup = new mapboxgl.Popup({ offset: 25 }).setHTML(`
                  <div style="font-family: 'Segoe UI', sans-serif; min-width: 180px;">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 5px;">
                        <h4 style="margin: 0; color: ${color};">Pedido #${cb.dataset.id}</h4>
                        <span style="font-size: 10px; background: #eee; padding: 2px 5px; border-radius: 4px;">${sucursal}</span>
                    </div>
                    <p style="margin: 2px 0;"><strong>Factura:</strong> ${cb.dataset.factura}</p>
                    <p style="margin: 2px 0;"><strong>Cliente:</strong> ${cb.dataset.cliente}</p>
                    <p style="margin: 2px 0;"><strong>Estado:</strong> ${cb.dataset.estado}</p>
                    ${hasDriver ? `<p style="margin: 2px 0; color: #155724; font-weight: bold;">🚚 Chofer: ${cb.dataset.chofer}</p>` : ''}
                    <p style="margin: 2px 0; font-size: 11px; color: #666;">${cb.dataset.direccion}</p>
                    <hr style="margin: 8px 0; border: 0; border-top: 1px solid #eee;">
                    <a href="Inicio.php?id=${cb.dataset.id}" target="_blank" style="color: #ed6b1f; font-weight: bold; text-decoration: none; font-size: 12px;">Ver Detalles →</a>
                  </div>
                `);

              popup.on('open', () => {
                document.querySelectorAll('.map-table-row').forEach(r => r.style.backgroundColor = '');
                const row = document.querySelector(`.map-table-row[data-id="${cb.dataset.id}"]`);
                if(row) {
                  row.style.backgroundColor = '#e7f3ff';
                  row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
              });

              popup.on('close', () => {
                const row = document.querySelector(`.map-table-row[data-id="${cb.dataset.id}"]`);
                if(row) row.style.backgroundColor = '';
              });

              const marker = new mapboxgl.Marker(el)
                .setLngLat([lng, lat])
                .setPopup(popup)
                .addTo(mainMap);
              
              marker.pedidoId = cb.dataset.id;
              mainMarkers.push(marker);
              bounds.extend([lng, lat]);
              plottedCount++;
              
              if(tbody) {
                const tr = document.createElement('tr');
                tr.className = 'map-table-row';
                tr.dataset.id = cb.dataset.id;
                tr.style.cursor = 'pointer';
                tr.innerHTML = `
                  <td style="padding: 3px 4px; border-bottom: 1px solid #eee;">
                    <a href="Inicio.php?id=${cb.dataset.id}" target="_blank" style="color: #005aa3; font-weight: bold; text-decoration: none;" onclick="event.stopPropagation();">#${cb.dataset.id}</a>
                  </td>
                  <td style="padding: 3px 4px; border-bottom: 1px solid #eee;">${cb.dataset.factura}</td>
                  <td style="padding: 3px 4px; border-bottom: 1px solid #eee; max-width: 100px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="${cb.dataset.cliente}">${cb.dataset.cliente}</td>
                  <td style="padding: 3px 4px; border-bottom: 1px solid #eee;">
                    <span style="background: ${color}; color: white; padding: 2px 4px; border-radius: 4px; font-size: 9px;">${sucursal}</span>
                  </td>
                  <td style="padding: 3px 4px; border-bottom: 1px solid #eee;">${cb.dataset.estado}</td>
                  <td style="padding: 3px 4px; border-bottom: 1px solid #eee; text-align: center;">
                    <a href="Inicio.php?id=${cb.dataset.id}" target="_blank" onclick="event.stopPropagation();" style="display: inline-block; background-color: #ed6b1f; color: white; padding: 3px 6px; border-radius: 4px; font-size: 9px; font-weight: bold; text-decoration: none; transition: background-color 0.2s;" onmouseover="this.style.backgroundColor='#d35400'" onmouseout="this.style.backgroundColor='#ed6b1f'">Detalles</a>
                  </td>
                `;
                
                tr.addEventListener('click', function() {
                  document.querySelectorAll('.map-table-row').forEach(r => r.style.backgroundColor = '');
                  this.style.backgroundColor = '#e7f3ff';
                  const m = mainMarkers.find(mark => mark.pedidoId === cb.dataset.id);
                  if(m) {
                    mainMap.flyTo({ center: m.getLngLat(), zoom: 15 });
                    if (!m.getPopup().isOpen()) {
                      m.togglePopup();
                    }
                  }
                });
                
                tr.addEventListener('dblclick', function() {
                  window.open(`Inicio.php?id=${cb.dataset.id}`, '_blank');
                });
                
                fragment.appendChild(tr);
              }
            } else {
              missingCoordsOrders.push({ id: cb.dataset.id, factura: cb.dataset.factura, cliente: cb.dataset.cliente, sucursal: sucursal, direccion: cb.dataset.direccion });
            }
          } else {
            missingCoordsOrders.push({ id: cb.dataset.id, factura: cb.dataset.factura, cliente: cb.dataset.cliente, sucursal: sucursal, direccion: cb.dataset.direccion });
          }
        });

        if(tbody) tbody.appendChild(fragment);

        // Actualizar estadísticas del mapa (si existe el contenedor)
        const totalStat = document.querySelector('#stat-total strong');
        const missingStat = document.querySelector('#stat-missing strong');
        if (totalStat) totalStat.textContent = checkboxes.length;
        if (missingStat) missingStat.textContent = missingCoordsOrders.length;

        if (plottedCount > 0 && currentView === 'map') {
            mainMap.fitBounds(bounds, { padding: 50, maxZoom: 15 });
        }
      }
      
      // Función para enviar los filtros a filtrar.php mediante AJAX, incluyendo el offset
      function filterData() {
        var sucursal = document.getElementById("sucursal").value;
        var estadosSeleccionados = [];
        var checkboxes = document.querySelectorAll('input[name="estado"]:checked');
        checkboxes.forEach(function(checkbox) {
          estadosSeleccionados.push(checkbox.value);
        });

        // Nuevos filtros
        var filtroGrupo = document.getElementById("filtroGrupo").value;
        var buscarGrupo = document.getElementById("buscarGrupo").value;
        var fechaInicio = document.getElementById("fechaInicio").value;
        var fechaFin = document.getElementById("fechaFin").value;
        var filtroZona = document.getElementById("filtroZona").value;

        var xhr = new XMLHttpRequest();
        xhr.open("POST", "filtrar.php", true);
        xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function() {
          if (xhr.readyState == 4 && xhr.status == 200) {
            document.getElementById("resultado").innerHTML = xhr.responseText;
            // Si no hay resultados, se puede deshabilitar el botón "Siguiente"
            var tableRows = document.querySelectorAll("#resultado table tr");
            // Consideramos que la primera fila es el header
            if (tableRows.length <= 1) {
              document.getElementById("nextPage").disabled = true;
            } else {
              document.getElementById("nextPage").disabled = false;
            }
            // Actualizar el indicador de página
            document.getElementById("currentPage").textContent = "Página " + currentPage;
            
            // Si la vista mapa está activa, actualizar marcadores
            if (currentView === 'map') {
                updateMapMarkers();
            }
          }
        };
        // Enviamos todos los parámetros
        var params = "sucursal=" + encodeURIComponent(sucursal) +
                     "&estados=" + encodeURIComponent(JSON.stringify(estadosSeleccionados)) +
                     "&offset=" + offset +
                     "&filtro_grupo=" + encodeURIComponent(filtroGrupo) +
                     "&buscar_grupo=" + encodeURIComponent(buscarGrupo) +
                     "&fecha_inicio=" + encodeURIComponent(fechaInicio) +
                     "&fecha_fin=" + encodeURIComponent(fechaFin) +
                     "&filtro_zona=" + encodeURIComponent(filtroZona);
        xhr.send(params);
      }
      
      // Eventos para el filtrado dinámico
      var filtroCheckboxes = document.querySelectorAll('input[name="estado"]');
      filtroCheckboxes.forEach(function(checkbox) {
        checkbox.addEventListener("change", function() {
          offset = 0;
          currentPage = 1;
          filterData();
        });
      });
      document.getElementById("sucursal").addEventListener("change", function() {
        offset = 0;
        currentPage = 1;
        filterData();
      });

      // Eventos para los nuevos filtros
      document.getElementById("filtroGrupo").addEventListener("change", function() {
        offset = 0;
        currentPage = 1;
        filterData();
      });
      document.getElementById("buscarGrupo").addEventListener("input", function() {
        offset = 0;
        currentPage = 1;
        filterData();
      });
      document.getElementById("fechaInicio").addEventListener("change", function() {
        offset = 0;
        currentPage = 1;
        filterData();
      });
      document.getElementById("fechaFin").addEventListener("change", function() {
        offset = 0;
        currentPage = 1;
        filterData();
      });
      document.getElementById("limpiarFechas").addEventListener("click", function() {
        document.getElementById("fechaInicio").value = "";
        document.getElementById("fechaFin").value = "";
        offset = 0;
        currentPage = 1;
        filterData();
      });

      // Dispara el filtrado al cargar la página
      filterData();

      // ---- Función de búsqueda general ----
      function ejecutarBusqueda() {
        var busqueda = document.getElementById("busqueda").value.trim();
        if (busqueda === '') {
          // Si borra todo, vuelve al filtrado normal
          filterData();
          return;
        }
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "buscar.php", true);
        xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function() {
          if (xhr.readyState == 4 && xhr.status == 200) {
            document.getElementById("resultado").innerHTML = xhr.responseText;
            // Si la vista mapa está activa, actualizar marcadores
            if (currentView === 'map') {
                updateMapMarkers();
            }
          }
        };
        xhr.send("busqueda=" + encodeURIComponent(busqueda));
      }

      // Búsqueda al hacer clic en botón
      document.getElementById("boton-buscar").addEventListener("click", function(event) {
        event.preventDefault();
        ejecutarBusqueda();
      });

      // Búsqueda en tiempo real al escribir (debounce 400ms)
      var busquedaTimer = null;
      document.getElementById("busqueda").addEventListener("input", function() {
        clearTimeout(busquedaTimer);
        busquedaTimer = setTimeout(function() {
          ejecutarBusqueda();
        }, 400);
      });

      // Enter en el campo de búsqueda
      document.getElementById("busqueda").addEventListener("keydown", function(e) {
        if (e.key === 'Enter') {
          e.preventDefault();
          clearTimeout(busquedaTimer);
          ejecutarBusqueda();
        }
      });

      // ---- Función para seleccionar zona LOCAL/FORÁNEO ----
      window.seleccionarZona = function(zona) {
        document.getElementById('filtroZona').value = zona;
        // Actualizar estilos de botones
        var btns = document.querySelectorAll('.zona-btn');
        btns.forEach(function(btn) {
          var bzona = btn.getAttribute('data-zona');
          var isSelected = (bzona === zona);
          if (bzona === '' && zona === '') isSelected = true;
          if (isSelected) {
            btn.classList.add('zona-btn-active');
            if (bzona === '') { btn.style.background = '#6c757d'; btn.style.color = '#fff'; }
            else if (bzona === 'LOCAL') { btn.style.background = '#28a745'; btn.style.color = '#fff'; }
            else if (bzona === 'FORANEO') { btn.style.background = '#ff9800'; btn.style.color = '#fff'; }
          } else {
            btn.classList.remove('zona-btn-active');
            if (bzona === '') { btn.style.background = '#fff'; btn.style.color = '#6c757d'; }
            else if (bzona === 'LOCAL') { btn.style.background = '#fff'; btn.style.color = '#28a745'; }
            else if (bzona === 'FORANEO') { btn.style.background = '#fff'; btn.style.color = '#ff9800'; }
          }
        });
        offset = 0;
        currentPage = 1;
        filterData();
      };
      
      // Evitar que el submit del formulario de filtrado recargue la página
      document.getElementById("filtroEstadoForm").addEventListener("submit", function(event) {
        event.preventDefault();
        offset = 0;
        currentPage = 1;
        filterData();
      });
      
      // Envío del formulario de consulta (si se pulsa el botón)
      document.getElementById("consultaForm").addEventListener("submit", function(event) {
        event.preventDefault();
        var sucursal = document.getElementById("sucursal").value;
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "consulta.php", true);
        xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function() {
          if (xhr.readyState == 4 && xhr.status == 200) {
            document.getElementById("resultado").innerHTML = xhr.responseText;
          }
        };
        xhr.send("sucursal=" + encodeURIComponent(sucursal));
      });
      
      // Controles de paginación
      document.getElementById("prevPage").addEventListener("click", function() {
        if (offset >= registrosPorPagina) {
          offset -= registrosPorPagina;
          currentPage--;
          filterData();
        }
      });
      document.getElementById("nextPage").addEventListener("click", function() {
        offset += registrosPorPagina;
        currentPage++;
        filterData();
      });
    });
  </script>

  <script>
document.addEventListener('click', async (e) => {
  const btn = e.target.closest('.accion-factura');
  if (!btn) return;

  // Previene submit/recargas si está dentro de un <form>
  e.preventDefault();
  e.stopPropagation();

  const id = btn.dataset.id;
  const accion = btn.dataset.accion;

  const mensajes = {
    'entregar_jefe':     '¿Marcar factura como "Entregada a Jefe de choferes"?',
    'pendiente_surtido': '¿Marcar como "Pendiente de Surtido"?',
    'surtido':           '¿Marcar como "Surtido"?',
    'cargado_camioneta': '¿Marcar como "Cargado en Camioneta"?',
  };
  const mensajeConfirmacion = mensajes[accion] || `¿Ejecutar acción "${accion}"?`;

  if (!confirm(mensajeConfirmacion)) return;

  btn.disabled = true;

  try {
    const res = await fetch('./update_factura_caja.php', {
      method: 'POST',
      headers: {'Content-Type':'application/x-www-form-urlencoded'},
      body: new URLSearchParams({ id_pedido: id, accion })
    });

    const raw = await res.text();
    let data;
    try { data = JSON.parse(raw); } catch { data = null; }

    if (!res.ok || !data || data.ok !== true) {
      const msg = data && data.msg ? data.msg : raw || 'Error desconocido';
      alert(`Error: ${msg}`);
      btn.disabled = false;
      return;
    }

    // Actualizar la celda sin recargar
    const td = btn.closest('td');
    td.innerHTML = data.html;

  } catch (err) {
    console.error(err);
    alert('Error de red. Revisa la consola.');
    btn.disabled = false;
  }
});
</script>

<!-- Botón flotante para gestión masiva de pedidos (solo Admin/JC) -->
<?php if (in_array($_SESSION["Rol"], ["Admin", "JC"])): ?>
<div id="floating-action-btn" style="display:none;">
  <div class="fab-content">
    <span id="fab-counter">0 pedidos seleccionados</span>
    <button id="fab-btn-process" type="button">
      <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M10 5V15M5 10H15" stroke="white" stroke-width="2" stroke-linecap="round"/>
      </svg>
      Validar y Asignar Choferes
    </button>
  </div>
</div>

<style>
#floating-action-btn {
  position: fixed;
  bottom: 30px;
  right: 30px;
  z-index: 1000;
  animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
  from {
    transform: translateY(100px);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

.fab-content {
  background: linear-gradient(135deg, #006996 0%, #004d6f 100%);
  padding: 15px 20px;
  border-radius: 50px;
  box-shadow: 0 8px 25px rgba(0, 105, 150, 0.4);
  display: flex;
  align-items: center;
  gap: 15px;
}

#fab-counter {
  color: white;
  font-weight: bold;
  font-size: 14px;
  padding-right: 15px;
  border-right: 2px solid rgba(255, 255, 255, 0.3);
}

#fab-btn-process {
  background: rgba(255, 255, 255, 0.2);
  border: 2px solid rgba(255, 255, 255, 0.5);
  color: white;
  padding: 10px 20px;
  border-radius: 25px;
  font-weight: bold;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
}

#fab-btn-process:hover {
  background: rgba(255, 255, 255, 0.3);
  transform: scale(1.05);
}

#fab-btn-process:active {
  transform: scale(0.95);
}
</style>

<script>
// ========== GESTIÓN MASIVA DE PEDIDOS ==========
document.addEventListener('DOMContentLoaded', function() {
  const floatingBtn = document.getElementById('floating-action-btn');
  const fabCounter = document.getElementById('fab-counter');
  const fabBtnProcess = document.getElementById('fab-btn-process');
  let pedidosSeleccionados = [];

  // Actualizar contador y visibilidad del botón flotante
  function actualizarBotonFlotante() {
    const checkboxes = document.querySelectorAll('.pedido-checkbox:checked:not(:disabled)');
    pedidosSeleccionados = Array.from(checkboxes).map(cb => ({
      id: cb.dataset.id,
      estado: cb.dataset.estado,
      tipoEnvio: cb.dataset.tipoEnvio,
      sucursal: cb.dataset.sucursal,
      factura: cb.dataset.factura,
      cliente: cb.dataset.cliente,
      direccion: cb.dataset.direccion,
      precioVendedor: parseFloat(cb.dataset.precioVendedor) || 0,
      precioReal: parseFloat(cb.dataset.precioReal) || 0,
      validado: parseInt(cb.dataset.validado) || 0,
      coordenadas: cb.dataset.coordenadas || ''
    }));

    const count = pedidosSeleccionados.length;

    if (count > 0) {
      fabCounter.textContent = `${count} pedido${count > 1 ? 's' : ''} seleccionado${count > 1 ? 's' : ''}`;
      floatingBtn.style.display = 'block';
    } else {
      floatingBtn.style.display = 'none';
    }
  }

  // Delegación de eventos para checkboxes (funciona con contenido dinámico)
  document.addEventListener('change', function(e) {
    if (e.target.classList.contains('pedido-checkbox') || e.target.id === 'selectAll') {
      if (e.target.id === 'selectAll') {
        const checkboxes = document.querySelectorAll('.pedido-checkbox:not(:disabled)');
        checkboxes.forEach(cb => cb.checked = e.target.checked);
      }
      actualizarBotonFlotante();
    }
  });

  // Abrir modal de gestión masiva
  fabBtnProcess.addEventListener('click', function() {
    if (pedidosSeleccionados.length === 0) {
      Swal.fire({
        icon: 'warning',
        title: 'Sin selección',
        text: 'No hay pedidos seleccionados',
        confirmButtonColor: '#006996'
      });
      return;
    }

    abrirModalGestionMasiva(pedidosSeleccionados);
  });
});

// Función de iconos SVG compartida
const svgIcon = (name, size = 15) => {
  const paths = {
    'layers': '<polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/>',
    'info': '<circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>',
    'check': '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>',
    'clock': '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
    'alert': '<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
    'user': '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
    'pin': '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>',
    'building': '<rect x="4" y="2" width="16" height="20" rx="2" ry="2"/><line x1="9" y1="6" x2="9.01" y2="6"/><line x1="15" y1="6" x2="15.01" y2="6"/><line x1="9" y1="10" x2="9.01" y2="10"/><line x1="15" y1="10" x2="15.01" y2="10"/><line x1="9" y1="14" x2="9.01" y2="14"/><line x1="15" y1="14" x2="15.01" y2="14"/><path d="M9 22v-4h6v4"/>',
    'truck': '<rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>',
    'dollar': '<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>',
    'map': '<polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"/><line x1="8" y1="2" x2="8" y2="18"/><line x1="16" y1="6" x2="16" y2="22"/>'
  };
  return `<svg width="${size}" height="${size}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-0.15em;flex-shrink:0;display:inline-block;">${paths[name] || paths['info']}</svg>`;
};

// Abrir modal de gestión masiva con SweetAlert2
async function abrirModalGestionMasiva(pedidos) {

  // Generar HTML para cada pedido
  let pedidosHTML = '';

  pedidos.forEach((pedido, index) => {
    const alertaPrecio = pedido.precioReal > 0 && pedido.precioReal < 1000
      ? `<span class="alerta-precio-bajo" style="color: #b45309; background: #fef3c7; border: 1px solid #fde68a; padding: 4px 8px; border-radius: 6px; font-size: 11.5px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px; margin-top: 6px;">${svgIcon('alert', 13)} Precio menor a $1000 - Flete no conveniente</span>`
      : '';

    const estadoValidacion = pedido.validado === 1
      ? `<span class="gm-badge gm-badge-ok">${svgIcon('check', 13)} Validado</span>`
      : `<span class="gm-badge gm-badge-pending">${svgIcon('clock', 13)} Pendiente</span>`;

    pedidosHTML += `
      <div class="pedido-item gm-card" data-index="${index}">
        <div class="gm-card-header">
          <div class="gm-card-title-group">
            <span class="gm-id-pill">#${pedido.id}</span>
            <span class="gm-factura-title">Factura: <strong>${pedido.factura || 'S/N'}</strong></span>
            <span class="gm-meta-chip">${svgIcon('building', 12)} ${pedido.sucursal || 'N/A'}</span>
            <span class="gm-meta-chip gm-meta-chip-envio">${svgIcon('truck', 12)} ${pedido.tipoEnvio || 'Domicilio'}</span>
          </div>
          ${estadoValidacion}
        </div>

        <div class="gm-info-strip">
          <div class="gm-info-cell">
            <span class="gm-info-label">${svgIcon('user', 13)} Cliente</span>
            <span class="gm-info-val">${pedido.cliente || 'Sin especificar'}</span>
          </div>
          <div class="gm-info-cell">
            <span class="gm-info-label">${svgIcon('pin', 13)} Dirección de Entrega</span>
            <span class="gm-info-val">${pedido.direccion || 'Sin dirección'}</span>
          </div>
        </div>

        <div class="gm-panels-grid">
          <!-- Panel 1: Precios y Validación -->
          <div class="gm-subpanel">
            <div class="gm-subpanel-title">${svgIcon('dollar', 14)} Facturación y Validación</div>
            <div class="gm-fields-row">
              <div>
                <label class="gm-label">Precio Vendedor</label>
                <input type="number" class="precio-vendedor-input gm-input gm-input-readonly" value="${pedido.precioVendedor}" readonly>
              </div>
              <div>
                <label class="gm-label">Precio Real Factura *</label>
                <input type="number" class="precio-real-input gm-input" data-index="${index}" value="${pedido.precioReal}" step="0.01" min="0.01"
                  style="${pedido.precioReal > 0 && pedido.precioReal < 1000 ? 'background-color: #fffbeb; border-color: #f59e0b;' : ''}">
                ${alertaPrecio}
              </div>
            </div>
            <label class="gm-check-box">
              <input type="checkbox" class="validar-precio-checkbox" data-index="${index}" ${pedido.validado === 1 ? 'checked' : ''}>
              <span>Confirmar y validar importe de factura</span>
            </label>
          </div>

          <!-- Panel 2: Asignación de Chofer -->
          <div class="gm-subpanel">
            <div class="gm-subpanel-title">${svgIcon('truck', 14)} Asignación de Chofer</div>
            <div class="gm-fields-row">
              <div>
                <label class="gm-label">Sucursal para Chofer</label>
                <select class="sucursal-chofer-select gm-input" data-index="${index}">
                  <option value="">Seleccionar sucursal...</option>
                  <option value="GABSA">GABSA</option>
                  <option value="ILUMINACION">ILUMINACION</option>
                  <option value="DIMEGSA">DIMEGSA</option>
                  <option value="DEASA">DEASA</option>
                  <option value="AIESA">AIESA</option>
                  <option value="SEGSA">SEGSA</option>
                  <option value="FESA">FESA</option>
                  <option value="TAPATIA">TAPATIA</option>
                  <option value="VALLARTA">VALLARTA</option>
                  <option value="CODI">CODI</option>
                  <option value="QUERETARO">QUERETARO</option>
                  <option value="CONSTITUYENTES">CONSTITUYENTES</option>
                </select>
              </div>
              <div>
                <label class="gm-label">Chofer Asignado</label>
                <select class="chofer-select gm-input" data-index="${index}" disabled>
                  <option value="">Primero seleccione sucursal</option>
                </select>
              </div>
            </div>
            <div class="gm-helper-note">
              Selecciona la sucursal operativa para listar los choferes con unidad activa.
            </div>
          </div>
        </div>
      </div>
    `;
  });

  const resultado = await Swal.fire({
    title: '',
    padding: '0',
    customClass: {
      popup: 'gm-swal-popup',
      htmlContainer: 'gm-swal-html',
      actions: 'gm-swal-actions'
    },
    html: `
      <style>
        .gm-swal-popup {
          border-radius: 16px !important;
          overflow: hidden !important;
          padding: 0 !important;
          box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.35) !important;
        }
        .gm-swal-html {
          margin: 0 !important;
          padding: 0 !important;
        }
        .gm-swal-actions {
          margin: 0 !important;
          padding: 14px 24px !important;
          width: 100% !important;
          box-sizing: border-box !important;
          background: #f8fafc !important;
          border-top: 1px solid #e2e8f0 !important;
          justify-content: flex-end !important;
          gap: 8px !important;
        }
        .gm-swal-actions .swal2-styled {
          margin: 0 !important;
          border-radius: 9px !important;
          font-size: 13.5px !important;
          font-weight: 700 !important;
          padding: 9px 18px !important;
          display: inline-flex !important;
          align-items: center !important;
          gap: 6px !important;
        }
        .gm-modal-wrap {
          font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
          text-align: left;
          color: #1e293b;
        }
        .gm-modal-header {
          background: linear-gradient(135deg, #0f4c81 0%, #005aa3 100%);
          color: #ffffff;
          padding: 18px 24px;
          display: flex;
          justify-content: space-between;
          align-items: center;
        }
        .gm-modal-header-left {
          display: flex;
          align-items: center;
          gap: 12px;
        }
        .gm-modal-icon {
          width: 38px;
          height: 38px;
          border-radius: 10px;
          background: rgba(255, 255, 255, 0.14);
          border: 1px solid rgba(255, 255, 255, 0.24);
          display: flex;
          align-items: center;
          justify-content: center;
          color: #ffffff;
        }
        .gm-modal-header h3 {
          margin: 0;
          font-size: 18px;
          font-weight: 800;
          color: #ffffff;
          letter-spacing: -0.01em;
        }
        .gm-modal-header p {
          margin: 2px 0 0 0;
          font-size: 12.5px;
          color: rgba(255, 255, 255, 0.82);
        }
        .gm-count-badge {
          background: rgba(255, 255, 255, 0.18);
          border: 1px solid rgba(255, 255, 255, 0.3);
          color: #ffffff;
          padding: 5px 12px;
          border-radius: 999px;
          font-size: 12px;
          font-weight: 700;
          white-space: nowrap;
        }
        .gm-modal-body {
          max-height: 66vh;
          overflow-y: auto;
          padding: 20px 24px;
          background: #ffffff;
        }
        .gm-tip-banner {
          display: flex;
          align-items: center;
          gap: 10px;
          margin-bottom: 16px;
          padding: 10px 14px;
          background: #f0f9ff;
          border: 1px solid #bae6fd;
          border-radius: 10px;
          font-size: 12.5px;
          color: #0c4a6e;
        }
        .gm-card {
          background: #ffffff;
          border: 1px solid #e2e8f0;
          border-radius: 12px;
          margin-bottom: 14px;
          overflow: hidden;
        }
        .gm-card:last-child {
          margin-bottom: 0;
        }
        .gm-card-header {
          background: #f8fafc;
          border-bottom: 1px solid #e2e8f0;
          padding: 11px 18px;
          display: flex;
          justify-content: space-between;
          align-items: center;
          flex-wrap: wrap;
          gap: 10px;
        }
        .gm-card-title-group {
          display: flex;
          align-items: center;
          flex-wrap: wrap;
          gap: 8px;
        }
        .gm-id-pill {
          background: #0f4c81;
          color: #ffffff;
          font-weight: 800;
          font-size: 12.5px;
          padding: 3px 9px;
          border-radius: 6px;
        }
        .gm-factura-title {
          font-size: 14px;
          color: #0f172a;
          margin-right: 2px;
        }
        .gm-meta-chip {
          display: inline-flex;
          align-items: center;
          gap: 4px;
          background: #e0f2fe;
          color: #0369a1;
          border: 1px solid #bae6fd;
          padding: 2px 8px;
          border-radius: 999px;
          font-size: 11px;
          font-weight: 700;
        }
        .gm-meta-chip-envio {
          background: #dcfce7;
          color: #166534;
          border-color: #bbf7d0;
        }
        .gm-badge {
          display: inline-flex;
          align-items: center;
          gap: 5px;
          padding: 3px 10px;
          border-radius: 999px;
          font-size: 11.5px;
          font-weight: 700;
        }
        .gm-badge-ok {
          background: #dcfce7;
          color: #166534;
          border: 1px solid #86efac;
        }
        .gm-badge-pending {
          background: #fef3c7;
          color: #92400e;
          border: 1px solid #fde68a;
        }
        .gm-info-strip {
          display: grid;
          grid-template-columns: 1fr 1.3fr;
          gap: 16px;
          padding: 12px 18px;
          background: #ffffff;
          border-bottom: 1px solid #f1f5f9;
        }
        .gm-info-cell {
          display: flex;
          flex-direction: column;
          gap: 2px;
        }
        .gm-info-label {
          font-size: 11px;
          font-weight: 700;
          text-transform: uppercase;
          letter-spacing: 0.04em;
          color: #64748b;
          display: inline-flex;
          align-items: center;
          gap: 4px;
        }
        .gm-info-val {
          font-size: 13.5px;
          font-weight: 600;
          color: #1e293b;
        }
        .gm-panels-grid {
          display: grid;
          grid-template-columns: 1fr 1fr;
          gap: 0;
          background: #ffffff;
        }
        @media (max-width: 820px) {
          .gm-info-strip, .gm-panels-grid {
            grid-template-columns: 1fr;
          }
        }
        .gm-subpanel {
          padding: 14px 18px 16px 18px;
          display: flex;
          flex-direction: column;
          justify-content: space-between;
        }
        .gm-subpanel:first-child {
          border-right: 1px solid #f1f5f9;
        }
        @media (max-width: 820px) {
          .gm-subpanel:first-child {
            border-right: none;
            border-bottom: 1px solid #f1f5f9;
          }
        }
        .gm-subpanel-title {
          font-size: 12px;
          font-weight: 800;
          color: #0f4c81;
          text-transform: uppercase;
          letter-spacing: 0.03em;
          margin-bottom: 10px;
          display: flex;
          align-items: center;
          gap: 6px;
        }
        .gm-fields-row {
          display: grid;
          grid-template-columns: 1fr 1fr;
          gap: 10px;
        }
        .gm-label {
          display: block;
          font-size: 11.5px;
          font-weight: 700;
          color: #475569;
          margin-bottom: 5px;
        }
        .gm-input {
          width: 100%;
          padding: 8px 10px;
          border: 1.5px solid #cbd5e1;
          border-radius: 8px;
          font-size: 13.5px;
          font-weight: 600;
          color: #0f172a;
          background: #ffffff;
          box-sizing: border-box;
          transition: all 0.15s ease;
        }
        .gm-input:focus {
          outline: none;
          border-color: #005aa3;
          box-shadow: 0 0 0 3px rgba(0, 90, 163, 0.14);
        }
        .gm-input-readonly, .gm-input:disabled {
          background: #f8fafc;
          color: #64748b;
          border-color: #e2e8f0;
          cursor: not-allowed;
        }
        .gm-check-box {
          margin-top: 12px;
          display: inline-flex;
          align-items: center;
          gap: 8px;
          padding: 7px 11px;
          background: #f8fafc;
          border: 1px solid #cbd5e1;
          border-radius: 8px;
          cursor: pointer;
          font-size: 12.5px;
          font-weight: 700;
          color: #0f4c81;
          user-select: none;
        }
        .gm-check-box input[type="checkbox"] {
          width: 16px;
          height: 16px;
          accent-color: #0f4c81;
          cursor: pointer;
          margin: 0;
        }
        .gm-helper-note {
          margin-top: 12px;
          font-size: 11.5px;
          color: #64748b;
          padding: 4px 0 0 0;
        }
      </style>
      <div class="gm-modal-wrap">
        <div class="gm-modal-header">
          <div class="gm-modal-header-left">
            <div class="gm-modal-icon">${svgIcon('layers', 20)}</div>
            <div>
              <h3>Gestión Masiva de Pedidos</h3>
              <p>Validación de importes y asignación operativa de choferes</p>
            </div>
          </div>
          <span class="gm-count-badge">${pedidos.length} ${pedidos.length === 1 ? 'pedido seleccionado' : 'pedidos seleccionados'}</span>
        </div>
        <div class="gm-modal-body">
          <div class="gm-tip-banner">
            <span style="color:#0284c7;display:flex;">${svgIcon('info', 18)}</span>
            <div><strong>Tip operativo:</strong> Si deseas agrupar estos pedidos en una misma ruta, usa el botón <strong>"Crear Grupo/Ruta"</strong> para asignarles el mismo chofer a todos.</div>
          </div>
          ${pedidosHTML}
        </div>
      </div>
    `,
    width: '980px',
    showCancelButton: true,
    showDenyButton: true,
    confirmButtonText: `${svgIcon('check', 15)} Asignar Individualmente`,
    denyButtonText: `${svgIcon('truck', 15)} Crear Grupo/Ruta`,
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#0f4c81',
    denyButtonColor: '#16a34a',
    cancelButtonColor: '#64748b',
    didOpen: () => {
      // Event listener para cambio de sucursal
      document.querySelectorAll('.sucursal-chofer-select').forEach(select => {
        select.addEventListener('change', async function() {
          const index = this.dataset.index;
          const sucursal = this.value;
          const choferSelect = document.querySelector(`.chofer-select[data-index="${index}"]`);

          if (!sucursal) {
            choferSelect.disabled = true;
            choferSelect.innerHTML = '<option value="">Primero seleccione sucursal</option>';
            return;
          }

          // Cargar choferes
          try {
            const response = await fetch(`obtener_choferes.php?sucursal=${encodeURIComponent(sucursal)}`);
            const choferes = await response.json();

            choferSelect.disabled = false;
            choferSelect.innerHTML = '<option value="">Seleccionar chofer...</option>';

            // Separar choferes con y sin vehículo
            const conVehiculo = [];
            const sinVehiculo = [];

            choferes.forEach(chofer => {
              if (chofer.tiene_vehiculo) {
                conVehiculo.push(chofer);
              } else {
                sinVehiculo.push(chofer);
              }
            });

            // Agregar grupos
            if (conVehiculo.length > 0) {
              const optgroupCon = document.createElement('optgroup');
              optgroupCon.label = 'Con vehículo';
              conVehiculo.forEach(chofer => {
                const option = document.createElement('option');
                option.value = chofer.username;
                option.textContent = `${chofer.username} – ${chofer.placa || 'sin placa'}`;
                optgroupCon.appendChild(option);
              });
              choferSelect.appendChild(optgroupCon);
            }

            if (sinVehiculo.length > 0) {
              const optgroupSin = document.createElement('optgroup');
              optgroupSin.label = 'Sin vehículo';
              sinVehiculo.forEach(chofer => {
                const option = document.createElement('option');
                option.value = chofer.username;
                option.textContent = `${chofer.username} – sin vehículo`;
                option.disabled = true;
                optgroupSin.appendChild(option);
              });
              choferSelect.appendChild(optgroupSin);
            }
          } catch (error) {
            console.error('Error al cargar choferes:', error);
            Swal.showValidationMessage('Error al cargar choferes');
          }
        });
      });

      // Event listener para validación de precio en tiempo real
      document.querySelectorAll('.precio-real-input').forEach(input => {
        input.addEventListener('input', function() {
          const precio = parseFloat(this.value);
          const parent = this.parentElement;
          const alerta = parent.querySelector('.alerta-precio-bajo');

          if (!isNaN(precio) && precio > 0 && precio < 1000) {
            if (!alerta) {
              const span = document.createElement('span');
              span.className = 'alerta-precio-bajo';
              span.style.cssText = 'color: #b45309; background: #fef3c7; border: 1px solid #fde68a; padding: 4px 8px; border-radius: 6px; font-size: 11.5px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px; margin-top: 6px;';
              span.innerHTML = `${svgIcon('alert', 13)} Precio menor a $1000 - Flete no conveniente`;
              parent.appendChild(span);
            }
            this.style.backgroundColor = '#fffbeb';
            this.style.borderColor = '#f59e0b';
          } else {
            if (alerta) alerta.remove();
            this.style.backgroundColor = '';
            this.style.borderColor = '';
          }
        });
      });
    },
    preConfirm: () => {
      const pedidosActualizados = [];

      pedidos.forEach((pedido, index) => {
        const precioReal = parseFloat(document.querySelector(`.precio-real-input[data-index="${index}"]`).value);
        const validarPrecio = document.querySelector(`.validar-precio-checkbox[data-index="${index}"]`).checked;
        const sucursalChofer = document.querySelector(`.sucursal-chofer-select[data-index="${index}"]`).value;
        const chofer = document.querySelector(`.chofer-select[data-index="${index}"]`).value;

        // Validaciones
        if (isNaN(precioReal) || precioReal <= 0) {
          Swal.showValidationMessage(`El precio del pedido #${pedido.id} debe ser mayor a 0`);
          return false;
        }

        // VALIDACIÓN OBLIGATORIA: Debe validar el precio
        if (!validarPrecio) {
          Swal.showValidationMessage(`Debe validar el precio del pedido #${pedido.id} marcando el checkbox "Validar Precio"`);
          return false;
        }

        // Si valida el precio, DEBE asignar un chofer
        if (validarPrecio && !chofer) {
          Swal.showValidationMessage(`Debe asignar un chofer al pedido #${pedido.id} si desea validar el precio`);
          return false;
        }

        pedidosActualizados.push({
          id: pedido.id,
          precioReal: precioReal,
          validarPrecio: validarPrecio,
          sucursalChofer: sucursalChofer,
          chofer: chofer
        });
      });

      return pedidosActualizados;
    }
  });

  // Manejar la respuesta del modal
  if (resultado.isConfirmed && resultado.value) {
    // Asignación individual (comportamiento original)
    await procesarPedidosMasivamente(resultado.value);
  } else if (resultado.isDenied) {
    // Crear grupo/ruta - primero verificar conflictos
    await verificarYCrearGrupo(pedidos);
  }
}

// Procesar pedidos masivamente
async function procesarPedidosMasivamente(pedidos) {
  Swal.fire({
    title: 'Procesando...',
    html: 'Actualizando pedidos, por favor espere...',
    allowOutsideClick: false,
    didOpen: () => {
      Swal.showLoading();
    }
  });

  try {
    const response = await fetch('ActualizarPedidosMasivo.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ pedidos: pedidos })
    });

    const resultado = await response.json();

    if (resultado.success) {
      await Swal.fire({
        icon: 'success',
        title: 'Pedidos Actualizados',
        html: `
          <p><strong>${resultado.exitosos}</strong> pedido(s) actualizado(s) correctamente</p>
          ${resultado.errores > 0 ? `<p style="color: #dc3545;"><strong>${resultado.errores}</strong> error(es)</p>` : ''}
        `,
        confirmButtonColor: '#006996'
      });

      // Recargar la página para mostrar los cambios
      window.location.reload();
    } else {
      // Mostrar detalles de errores para debugging
      let errorHtml = resultado.message || 'Ocurrió un error al procesar los pedidos';

      if (resultado.detalles_errores && resultado.detalles_errores.length > 0) {
        errorHtml += '<br><br><strong>Detalles de errores:</strong><br>';
        errorHtml += '<div style="text-align: left; max-height: 300px; overflow-y: auto; padding: 10px; background: #f8f9fa; border-radius: 5px;">';
        resultado.detalles_errores.forEach(error => {
          errorHtml += `<div style="margin-bottom: 5px;">• ${error}</div>`;
        });
        errorHtml += '</div>';
      }

      Swal.fire({
        icon: 'error',
        title: 'Error',
        html: errorHtml,
        width: '600px',
        confirmButtonColor: '#006996'
      });
    }
  } catch (error) {
    console.error('Error:', error);
    Swal.fire({
      icon: 'error',
      title: 'Error de Conexión',
      text: 'No se pudo conectar con el servidor',
      confirmButtonColor: '#006996'
    });
  }
}

// ========== CREAR GRUPO/RUTA ==========

// Verificar conflictos antes de crear grupo
async function verificarYCrearGrupo(pedidos) {
  // Mostrar indicador de carga
  Swal.fire({
    title: 'Verificando...',
    text: 'Comprobando si los pedidos ya están en otros grupos',
    allowOutsideClick: false,
    didOpen: () => {
      Swal.showLoading();
    }
  });

  try {
    // Obtener solo los IDs de los pedidos
    const pedidosIds = pedidos.map(p => p.id);

    // Llamar al endpoint de verificación
    const response = await fetch('verificar_pedidos_en_grupos.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ pedidos_ids: pedidosIds })
    });

    const data = await response.json();

    if (!data.success) {
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: data.message || 'Error al verificar pedidos'
      });
      return;
    }

    // Cerrar el indicador de carga
    Swal.close();

    // Si no hay conflictos, proceder directamente
    if (!data.tiene_conflictos) {
      await abrirModalCrearGrupo(pedidos, false);
      return;
    }

    // Si hay conflictos, mostrar advertencia
    let mensajeConflictos = '<div style="text-align: left; margin: 10px 0;">';
    mensajeConflictos += '<div style="display: flex; align-items: center; gap: 8px; color: #b45309; font-weight: 700; font-size: 13px; margin-bottom: 10px;">' + svgIcon('alert', 16) + ' Los siguientes pedidos ya están asignados a otros grupos:</div>';
    mensajeConflictos += '<div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 10px; max-height: 220px; overflow-y: auto; padding: 4px 10px;">';

    data.pedidos_en_grupos.forEach(pedido => {
      mensajeConflictos += `
        <div style="padding: 9px 4px; border-bottom: 1px solid #fef3c7;">
          <div style="display: flex; justify-content: space-between; align-items: center;">
            <strong style="color: #0f4c81; font-size: 13px;">Pedido #${pedido.pedido_id}</strong>
            <span style="font-weight: 700; color: #0f172a; font-size: 12px;">Fact: ${pedido.FACTURA || 'S/F'}</span>
          </div>
          <div style="color: #475569; font-size: 12px; margin-top: 2px;">
            Cliente: <strong>${pedido.NOMBRE_CLIENTE}</strong><br>
            <span style="color: #64748b;">Grupo actual: "<strong>${pedido.nombre_grupo}</strong>" (Chofer: ${pedido.chofer_asignado || 'Sin chofer'})</span>
          </div>
        </div>
      `;
    });

    mensajeConflictos += '</div></div>';

    const resultado = await Swal.fire({
      icon: 'warning',
      title: 'Pedidos ya asignados a grupos',
      html: `
        ${mensajeConflictos}
        <p style="margin-top: 14px; color: #1e293b; font-size: 13px;">
          <strong>¿Qué acción deseas realizar con estos pedidos?</strong>
        </p>
      `,
      width: '650px',
      showDenyButton: true,
      showCancelButton: true,
      confirmButtonText: `${svgIcon('check', 14)} Mover al nuevo grupo`,
      denyButtonText: 'Continuar sin moverlos',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#16a34a',
      denyButtonColor: '#0f4c81',
      cancelButtonColor: '#64748b'
    });

    if (resultado.isConfirmed) {
      // Usuario quiere mover los pedidos al nuevo grupo
      await abrirModalCrearGrupo(pedidos, true);
    } else if (resultado.isDenied) {
      // Usuario quiere continuar pero sin mover los que ya están en grupos
      // Filtrar solo los pedidos que NO están en grupos
      const pedidosEnGruposIds = data.pedidos_en_grupos.map(p => p.pedido_id);
      const pedidosSinGrupo = pedidos.filter(p => !pedidosEnGruposIds.includes(p.id));

      if (pedidosSinGrupo.length === 0) {
        Swal.fire({
          icon: 'info',
          title: 'Sin pedidos disponibles',
          text: 'Todos los pedidos seleccionados ya están en grupos activos.'
        });
        return;
      }

      await abrirModalCrearGrupo(pedidosSinGrupo, false);
    }
    // Si es cancelar, no hacer nada

  } catch (error) {
    console.error('Error verificando conflictos:', error);
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Error al verificar conflictos. ' + error.message
    });
  }
}

async function abrirModalCrearGrupo(pedidos, moverDesdeOtrosGrupos = false) {
  // Chips de pedidos seleccionados
  let chipsPedidosHTML = '';
  pedidos.forEach(p => {
    chipsPedidosHTML += `
      <div class="cg-pedido-chip">
        <span class="cg-chip-id">#${p.id}</span>
        <span class="cg-chip-fact">Fact: <strong>${p.factura || 'S/F'}</strong></span>
        <span class="cg-chip-cli" title="${p.cliente || ''}">${p.cliente || 'Sin cliente'}</span>
      </div>
    `;
  });

  // Generar filas para validación de precios
  let preciosHTML = '';
  pedidos.forEach((pedido, index) => {
    const alertaPrecio = pedido.precioReal > 0 && pedido.precioReal < 1000
      ? `<span class="alerta-precio-bajo" style="color: #b45309; background: #fef3c7; border: 1px solid #fde68a; padding: 4px 8px; border-radius: 6px; font-size: 11.5px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px; margin-top: 4px;">${svgIcon('alert', 13)} Precio menor a $1000 - Flete no conveniente</span>`
      : '';

    preciosHTML += `
      <div class="cg-precio-row" data-index="${index}">
        <div class="cg-precio-top">
          <div style="display: flex; align-items: center; gap: 8px;">
            <span class="cg-chip-id">#${pedido.id}</span>
            <strong style="color: #0f172a; font-size: 13px;">${pedido.factura ? 'Factura: ' + pedido.factura : 'Sin factura'}</strong>
            <span style="color: #64748b; font-size: 12px;">(${pedido.cliente || 'Sin cliente'})</span>
          </div>
          <span style="color: #64748b; font-size: 12px; font-weight: 600;">Precio Vendedor: <strong style="color: #0f172a;">$${pedido.precioVendedor}</strong></span>
        </div>
        <div class="cg-precio-fields">
          <input type="number" class="grupo-precio-real cg-input-real" data-index="${index}"
                 value="${pedido.precioReal}" step="0.01" min="0.01"
                 placeholder="Precio real de factura">
          <label class="cg-check-validar">
            <input type="checkbox" class="grupo-validar-precio" data-index="${index}"
                   ${pedido.validado === 1 ? 'checked' : ''}>
            <span>${svgIcon('check', 13)} Validar</span>
          </label>
        </div>
        ${alertaPrecio}
      </div>
    `;
  });

  const result = await Swal.fire({
    html: `
      <style>
        .cg-swal-popup {
          padding: 0 !important;
          overflow: hidden !important;
          border-radius: 18px !important;
          box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.25) !important;
          border: 1px solid #cbd5e1 !important;
          font-family: inherit !important;
        }
        .cg-swal-html {
          padding: 0 !important;
          margin: 0 !important;
          max-height: 84vh;
          overflow-y: auto;
          overflow-x: hidden;
        }
        .cg-swal-actions {
          margin: 0 !important;
          padding: 16px 24px !important;
          background: #f8fafc !important;
          border-top: 1px solid #e2e8f0 !important;
          gap: 12px !important;
        }
        .cg-modal-wrap {
          text-align: left;
          color: #1e293b;
          background: #ffffff;
        }
        .cg-modal-header {
          background: linear-gradient(135deg, #0f4c81 0%, #006996 100%);
          color: #ffffff;
          padding: 20px 24px;
          display: flex;
          align-items: center;
          justify-content: space-between;
          gap: 16px;
        }
        .cg-modal-header-left {
          display: flex;
          align-items: center;
          gap: 14px;
        }
        .cg-modal-icon {
          width: 44px;
          height: 44px;
          background: rgba(255, 255, 255, 0.15);
          border: 1px solid rgba(255, 255, 255, 0.25);
          border-radius: 12px;
          display: flex;
          align-items: center;
          justify-content: center;
          color: #ffffff;
          flex-shrink: 0;
        }
        .cg-modal-header h3 {
          margin: 0;
          font-size: 19px;
          font-weight: 800;
          letter-spacing: -0.01em;
          color: #ffffff;
        }
        .cg-modal-header p {
          margin: 3px 0 0 0;
          font-size: 13px;
          color: #e0f2fe;
          opacity: 0.95;
        }
        .cg-count-badge {
          background: rgba(255, 255, 255, 0.2);
          border: 1px solid rgba(255, 255, 255, 0.35);
          color: #ffffff;
          font-size: 12px;
          font-weight: 700;
          padding: 5px 12px;
          border-radius: 999px;
          white-space: nowrap;
        }
        .cg-modal-body {
          padding: 20px 24px;
          display: flex;
          flex-direction: column;
          gap: 18px;
        }
        .cg-tip-banner {
          background: #f0f9ff;
          border: 1px solid #bae6fd;
          border-left: 4px solid #0284c7;
          border-radius: 10px;
          padding: 12px 14px;
          font-size: 13px;
          color: #0369a1;
          display: flex;
          align-items: center;
          gap: 10px;
          line-height: 1.4;
        }
        .cg-pedidos-strip {
          display: flex;
          flex-wrap: wrap;
          gap: 8px;
          max-height: 110px;
          overflow-y: auto;
          padding: 4px 2px;
        }
        .cg-pedido-chip {
          display: inline-flex;
          align-items: center;
          gap: 6px;
          background: #f8fafc;
          border: 1px solid #cbd5e1;
          padding: 5px 10px;
          border-radius: 8px;
          font-size: 12px;
        }
        .cg-chip-id {
          font-weight: 800;
          color: #0f4c81;
          background: #e0f2fe;
          padding: 2px 6px;
          border-radius: 5px;
          font-size: 11px;
        }
        .cg-chip-fact {
          font-size: 11.5px;
          color: #475569;
        }
        .cg-chip-cli {
          font-weight: 600;
          color: #0f172a;
          max-width: 140px;
          white-space: nowrap;
          overflow: hidden;
          text-overflow: ellipsis;
        }
        .cg-section-card {
          background: #ffffff;
          border: 1px solid #e2e8f0;
          border-radius: 12px;
          box-shadow: 0 1px 3px rgba(15, 23, 42, 0.05);
          overflow: hidden;
        }
        .cg-section-header {
          background: #f8fafc;
          padding: 12px 16px;
          border-bottom: 1px solid #e2e8f0;
          font-size: 12px;
          font-weight: 800;
          color: #0f4c81;
          text-transform: uppercase;
          letter-spacing: 0.04em;
          display: flex;
          align-items: center;
          gap: 8px;
        }
        .cg-section-body {
          padding: 16px;
        }
        .cg-precios-list {
          display: flex;
          flex-direction: column;
          gap: 10px;
          max-height: 250px;
          overflow-y: auto;
          padding-right: 4px;
        }
        .cg-precio-row {
          background: #f8fafc;
          border: 1px solid #e2e8f0;
          border-radius: 10px;
          padding: 10px 14px;
          display: flex;
          flex-direction: column;
          gap: 8px;
          transition: border-color 0.15s;
        }
        .cg-precio-row:hover {
          border-color: #cbd5e1;
        }
        .cg-precio-top {
          display: flex;
          justify-content: space-between;
          align-items: center;
          font-size: 12.5px;
          flex-wrap: wrap;
          gap: 6px;
        }
        .cg-precio-fields {
          display: flex;
          align-items: center;
          gap: 12px;
        }
        .cg-input-real {
          flex: 1;
          padding: 8px 12px;
          border: 1.5px solid #cbd5e1;
          border-radius: 8px;
          font-size: 13.5px;
          font-weight: 700;
          color: #0f172a;
          background: #ffffff;
          box-sizing: border-box;
          transition: all 0.15s;
        }
        .cg-input-real:focus {
          outline: none;
          border-color: #005aa3;
          box-shadow: 0 0 0 3px rgba(0, 90, 163, 0.12);
        }
        .cg-check-validar {
          display: inline-flex;
          align-items: center;
          gap: 6px;
          padding: 7px 12px;
          background: #ffffff;
          border: 1px solid #cbd5e1;
          border-radius: 8px;
          cursor: pointer;
          font-size: 12px;
          font-weight: 700;
          color: #0f4c81;
          user-select: none;
          white-space: nowrap;
          transition: all 0.15s;
        }
        .cg-check-validar:hover {
          background: #f0f9ff;
          border-color: #bae6fd;
        }
        .cg-check-validar input[type="checkbox"] {
          width: 16px;
          height: 16px;
          accent-color: #0f4c81;
          cursor: pointer;
          margin: 0;
        }
        .cg-params-grid {
          display: grid;
          grid-template-columns: 1fr 1fr 1.2fr;
          gap: 14px;
          align-items: start;
        }
        @media (max-width: 768px) {
          .cg-params-grid {
            grid-template-columns: 1fr;
          }
        }
        .cg-form-group {
          display: flex;
          flex-direction: column;
          gap: 5px;
        }
        .cg-label {
          font-size: 11.5px;
          font-weight: 700;
          color: #475569;
          text-transform: uppercase;
          letter-spacing: 0.03em;
        }
        .cg-input, .cg-select, .cg-textarea {
          width: 100%;
          padding: 8px 10px;
          border: 1.5px solid #cbd5e1;
          border-radius: 8px;
          font-size: 13px;
          font-weight: 600;
          color: #0f172a;
          background: #ffffff;
          box-sizing: border-box;
          transition: all 0.15s;
        }
        .cg-input:focus, .cg-select:focus, .cg-textarea:focus {
          outline: none;
          border-color: #005aa3;
          box-shadow: 0 0 0 3px rgba(0, 90, 163, 0.12);
        }
        .cg-select:disabled {
          background: #f8fafc;
          color: #94a3b8;
          border-color: #e2e8f0;
          cursor: not-allowed;
        }
        .cg-helper-text {
          font-size: 11px;
          color: #64748b;
        }
        #preview-map {
          width: 100%;
          height: 330px;
          border-radius: 10px;
          border: 1px solid #cbd5e1;
          overflow: hidden;
        }
        .cg-ruta-info-badge {
          margin-top: 10px;
          padding: 10px 14px;
          background: #ecfdf5;
          border: 1px solid #a7f3d0;
          border-radius: 8px;
          color: #065f46;
          display: flex;
          align-items: center;
          justify-content: center;
          gap: 16px;
          font-size: 13px;
          font-weight: 700;
        }
      </style>

      <div class="cg-modal-wrap">
        <!-- Header -->
        <div class="cg-modal-header">
          <div class="cg-modal-header-left">
            <div class="cg-modal-icon">${svgIcon('truck', 22)}</div>
            <div>
              <h3>Crear Grupo / Hoja de Ruta</h3>
              <p>Agrupación y optimización logística de pedidos para chofer</p>
            </div>
          </div>
          <span class="cg-count-badge">${pedidos.length} ${pedidos.length === 1 ? 'pedido seleccionado' : 'pedidos seleccionados'}</span>
        </div>

        <div class="cg-modal-body">
          <!-- Tip Banner -->
          <div class="cg-tip-banner">
            <span style="color:#0284c7;display:flex;">${svgIcon('info', 18)}</span>
            <div><strong>Organización de ruta:</strong> Todos los pedidos quedarán vinculados a este grupo con una secuencia sugerida y el chofer seleccionado.</div>
          </div>

          <!-- Tira de pedidos seleccionados -->
          <div class="cg-section-card">
            <div class="cg-section-header">
              ${svgIcon('layers', 14)} Pedidos Incluidos en este Grupo (${pedidos.length})
            </div>
            <div class="cg-section-body" style="padding: 10px 14px;">
              <div class="cg-pedidos-strip">
                ${chipsPedidosHTML}
              </div>
            </div>
          </div>

          <!-- Validación de precios -->
          <div class="cg-section-card">
            <div class="cg-section-header">
              ${svgIcon('dollar', 14)} Validación de Precios de Factura
            </div>
            <div class="cg-section-body">
              <div class="cg-precios-list">
                ${preciosHTML}
              </div>
            </div>
          </div>

          <!-- Parámetros del Grupo y Chofer -->
          <div class="cg-section-card">
            <div class="cg-section-header">
              ${svgIcon('user', 14)} Configuración del Grupo y Asignación
            </div>
            <div class="cg-section-body" style="display: flex; flex-direction: column; gap: 14px;">
              <div class="cg-params-grid">
                <div class="cg-form-group">
                  <label class="cg-label">Nombre del Grupo (Opcional):</label>
                  <input type="text" id="grupo-nombre" class="cg-input" placeholder="Ej: Ruta Norte Mañana">
                  <small class="cg-helper-text">Por defecto se generará automático</small>
                </div>

                <div class="cg-form-group">
                  <label class="cg-label">Sucursal para Chofer:</label>
                  <select id="grupo-sucursal" class="cg-select">
                    <option value="">Seleccionar sucursal...</option>
                    <option value="GABSA">GABSA</option>
                    <option value="ILUMINACION">ILUMINACION</option>
                    <option value="DIMEGSA">DIMEGSA</option>
                    <option value="DEASA">DEASA</option>
                    <option value="AIESA">AIESA</option>
                    <option value="SEGSA">SEGSA</option>
                    <option value="FESA">FESA</option>
                    <option value="TAPATIA">TAPATIA</option>
                    <option value="VALLARTA">VALLARTA</option>
                    <option value="CODI">CODI</option>
                    <option value="QUERETARO">QUERETARO</option>
                    <option value="CONSTITUYENTES">CONSTITUYENTES</option>
                  </select>
                  <small class="cg-helper-text">Filtra los choferes de la sucursal</small>
                </div>

                <div class="cg-form-group">
                  <label class="cg-label">Chofer Asignado:</label>
                  <select id="grupo-chofer" class="cg-select" disabled>
                    <option value="">Primero seleccione sucursal</option>
                  </select>
                  <small class="cg-helper-text">Chofer con unidad activa</small>
                </div>
              </div>

              <div class="cg-form-group">
                <label class="cg-label">Notas o Instrucciones (Opcional):</label>
                <textarea id="grupo-notas" class="cg-textarea" rows="2" placeholder="Notas operativas para el chofer o logística de entrega..."></textarea>
              </div>
            </div>
          </div>

          <!-- Vista Previa de la Ruta (Mapa) -->
          <div class="cg-section-card">
            <div class="cg-section-header">
              ${svgIcon('map', 14)} Vista Previa y Trazado de Ruta
            </div>
            <div class="cg-section-body">
              <div id="preview-map"></div>
              <small class="cg-helper-text" style="display: block; margin-top: 6px; text-align: center;">
                Ruta sugerida basada en las coordenadas de destino de los pedidos
              </small>
            </div>
          </div>
        </div>
      </div>
    `,
    width: '980px',
    showCancelButton: true,
    confirmButtonText: `${svgIcon('truck', 15)} Crear Grupo y Asignar`,
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#16a34a',
    cancelButtonColor: '#64748b',
    customClass: {
      popup: 'cg-swal-popup',
      htmlContainer: 'cg-swal-html',
      actions: 'cg-swal-actions'
    },
    didOpen: () => {
      // Cargar choferes cuando se selecciona sucursal
      const sucursalSelect = document.getElementById('grupo-sucursal');
      const choferSelect = document.getElementById('grupo-chofer');

      sucursalSelect.addEventListener('change', async function() {
        const sucursal = this.value;

        if (!sucursal) {
          choferSelect.disabled = true;
          choferSelect.innerHTML = '<option value="">Primero seleccione sucursal</option>';
          return;
        }

        try {
          const response = await fetch(`obtener_choferes.php?sucursal=${encodeURIComponent(sucursal)}`);
          const choferes = await response.json();

          choferSelect.disabled = false;
          choferSelect.innerHTML = '<option value="">Seleccionar chofer...</option>';

          // Separar choferes con y sin vehículo
          const conVehiculo = choferes.filter(c => c.tiene_vehiculo);
          const sinVehiculo = choferes.filter(c => !c.tiene_vehiculo);

          // Agregar grupos
          if (conVehiculo.length > 0) {
            const optgroupCon = document.createElement('optgroup');
            optgroupCon.label = 'Con vehículo';
            conVehiculo.forEach(chofer => {
              const option = document.createElement('option');
              option.value = chofer.username;
              option.textContent = `${chofer.username} – ${chofer.placa || 'sin placa'}`;
              optgroupCon.appendChild(option);
            });
            choferSelect.appendChild(optgroupCon);
          }

          if (sinVehiculo.length > 0) {
            const optgroupSin = document.createElement('optgroup');
            optgroupSin.label = 'Sin vehículo';
            sinVehiculo.forEach(chofer => {
              const option = document.createElement('option');
              option.value = chofer.username;
              option.textContent = `${chofer.username} – sin vehículo`;
              option.disabled = true;
              optgroupSin.appendChild(option);
            });
            choferSelect.appendChild(optgroupSin);
          }
        } catch (error) {
          console.error('Error al cargar choferes:', error);
          Swal.showValidationMessage('Error al cargar choferes');
        }
      });

      // Validación de precios en tiempo real
      document.querySelectorAll('.grupo-precio-real').forEach(input => {
        input.addEventListener('input', function() {
          const precio = parseFloat(this.value);
          const parent = this.closest('.cg-precio-row') || this.parentElement.parentElement;
          let alerta = parent.querySelector('.alerta-precio-bajo');

          if (!isNaN(precio) && precio > 0 && precio < 1000) {
            if (!alerta) {
              alerta = document.createElement('span');
              alerta.className = 'alerta-precio-bajo';
              alerta.style.cssText = 'color: #b45309; background: #fef3c7; border: 1px solid #fde68a; padding: 4px 8px; border-radius: 6px; font-size: 11.5px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px; margin-top: 4px;';
              alerta.innerHTML = `${svgIcon('alert', 13)} Precio menor a $1000 - Flete no conveniente`;
              parent.appendChild(alerta);
            }
            this.style.backgroundColor = '#fffbeb';
            this.style.borderColor = '#f59e0b';
          } else {
            if (alerta) alerta.remove();
            this.style.backgroundColor = '';
            this.style.borderColor = '';
          }
        });
      });

      // Inicializar mapa de vista previa AQUIIIIIIIIIIIIIIII
     

      const previewMap = new mapboxgl.Map({
        container: 'preview-map',
        style: 'mapbox://styles/mapbox/streets-v12',
        center: [-103.3494, 20.6737], // Guadalajara por defecto
        zoom: 11
      });

      let warehouseMarker = null;
      let warehouseCoords = null;

      // Función para actualizar el mapa de vista previa
      async function actualizarMapaPreview() {
        // Limpiar marcadores y rutas anteriores
        const markers = document.querySelectorAll('.mapboxgl-marker');
        markers.forEach(marker => marker.remove());

        if (previewMap.getSource('route')) {
          previewMap.removeLayer('route');
          previewMap.removeSource('route');
        }

        // Remover información de ruta si existe
        const infoDiv = document.getElementById('preview-map').parentElement.querySelector('.cg-ruta-info-badge, div[style*="background: #d4edda"]');
        if (infoDiv) infoDiv.remove();

        const coordenadasValidas = [];
        const bounds = new mapboxgl.LngLatBounds();

        // Obtener sucursal del primer pedido (todos deben ser de la misma sucursal)
        let sucursalPedido = null;
        if (pedidos.length > 0 && pedidos[0].sucursal) {
          sucursalPedido = pedidos[0].sucursal;
        }

        // Si hay sucursal del pedido, obtener coordenadas del almacén
        if (sucursalPedido) {
          try {
            // Determinar qué sucursal usar (TAPATIA para ILUMINACION/TAPATIA)
            let sucursalOrigen = sucursalPedido;
            if (sucursalPedido === 'ILUMINACION' || sucursalPedido === 'TAPATIA') {
              sucursalOrigen = 'TAPATIA';
            }

            const response = await fetch('obtener_ubicacion.php?sucursal=' + encodeURIComponent(sucursalOrigen));
            const data = await response.json();

            if (data.success && data.ubicacion && data.ubicacion.coordenadas) {
              let coordString = data.ubicacion.coordenadas.trim();
              let lat, lng;

              // Parsear coordenadas (formato "lat, lng")
              if (coordString.includes(',')) {
                const parts = coordString.split(',').map(p => p.trim());
                if (parts.length === 2) {
                  lat = parseFloat(parts[0]);
                  lng = parseFloat(parts[1]);
                }
              }

              if (!isNaN(lng) && !isNaN(lat) && lng !== 0 && lat !== 0) {
                warehouseCoords = [lng, lat];

                // Crear marcador de bodega
                const elOrigen = document.createElement('div');
                elOrigen.style.cssText = `
                  background: #0f4c81;
                  color: white;
                  width: 36px;
                  height: 36px;
                  border-radius: 50%;
                  display: flex;
                  align-items: center;
                  justify-content: center;
                  border: 2.5px solid white;
                  box-shadow: 0 4px 10px rgba(0,0,0,0.3);
                  cursor: pointer;
                `;
                elOrigen.innerHTML = svgIcon('building', 18);

                warehouseMarker = new mapboxgl.Marker(elOrigen)
                  .setLngLat([lng, lat])
                  .setPopup(new mapboxgl.Popup().setHTML(
                    '<strong>🏢 Bodega/Origen</strong><br>' +
                    (data.ubicacion.NombreCompleto || '') + '<br>' +
                    (data.ubicacion.Direccion || '')
                  ))
                  .addTo(previewMap);

                bounds.extend([lng, lat]);
                coordenadasValidas.push([lng, lat]);
              }
            }
          } catch (error) {
            console.error('Error obteniendo coordenadas de bodega:', error);
          }
        }

        // Procesar coordenadas de los pedidos
        pedidos.forEach((pedido, index) => {
        if (pedido.coordenadas && pedido.coordenadas.trim() !== '') {
          try {
            let coordString = pedido.coordenadas.trim();
            let lat, lng;

            // Intentar parsear como JSON primero
            try {
              const coords = JSON.parse(coordString);
              lng = parseFloat(coords.lng);
              lat = parseFloat(coords.lat);
            } catch (jsonError) {
              // Intentar formato simple: "20.71685200, -103.36460500"
              if (coordString.includes(',')) {
                const parts = coordString.split(',').map(p => p.trim());
                if (parts.length === 2) {
                  lat = parseFloat(parts[0]);
                  lng = parseFloat(parts[1]);
                }
              }
            }

            if (!isNaN(lng) && !isNaN(lat) && lng !== 0 && lat !== 0) {
              // Crear marcador numerado
              const el = document.createElement('div');
              el.style.cssText = `
                background: #28a745;
                color: white;
                width: 30px;
                height: 30px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: bold;
                font-size: 14px;
                border: 2px solid white;
                box-shadow: 0 2px 4px rgba(0,0,0,0.3);
              `;
              el.textContent = index + 1;

              new mapboxgl.Marker(el)
                .setLngLat([lng, lat])
                .setPopup(new mapboxgl.Popup().setHTML(
                  '<strong>Pedido #' + pedido.id + '</strong><br>' +
                  pedido.cliente + '<br>' +
                  pedido.direccion
                ))
                .addTo(previewMap);

              bounds.extend([lng, lat]);
              coordenadasValidas.push([lng, lat]);
            }
          } catch (e) {
            console.error('Error procesando coordenadas del pedido ' + pedido.id + ':', e);
          }
        }
      });

        // Ajustar vista al contenido y dibujar ruta
        if (coordenadasValidas.length > 0) {
          previewMap.fitBounds(bounds, { padding: 50 });

          // Dibujar ruta si hay más de un punto (necesitamos origen + al menos 1 destino)
          if (coordenadasValidas.length > 1) {
            // Esperar a que el mapa esté cargado
            if (previewMap.loaded()) {
              await dibujarRutaPreview(coordenadasValidas);
            } else {
              previewMap.on('load', async () => {
                await dibujarRutaPreview(coordenadasValidas);
              });
            }
          }
        }
      }

      // Función auxiliar para dibujar la ruta en el preview
      async function dibujarRutaPreview(coordenadasValidas) {
        try {
          // Limitar a 25 puntos (límite de Mapbox)
          const coords = coordenadasValidas.slice(0, 25);
          const coordsString = coords.map(c => c.join(',')).join(';');
          const url = 'https://api.mapbox.com/directions/v5/mapbox/driving/' + coordsString + '?geometries=geojson&access_token=' + mapboxgl.accessToken;

          const response = await fetch(url);
          const data = await response.json();

          if (data.routes && data.routes.length > 0) {
            const route = data.routes[0].geometry;
            const distanciaKm = (data.routes[0].distance / 1000).toFixed(2);
            const tiempoMin = Math.round(data.routes[0].duration / 60);

            // Agregar capa de ruta
            if (previewMap.getSource('route')) {
              previewMap.getSource('route').setData({
                type: 'Feature',
                geometry: route
              });
            } else {
              previewMap.addSource('route', {
                type: 'geojson',
                data: {
                  type: 'Feature',
                  geometry: route
                }
              });

              previewMap.addLayer({
                id: 'route',
                type: 'line',
                source: 'route',
                layout: {
                  'line-join': 'round',
                  'line-cap': 'round'
                },
                paint: {
                  'line-color': '#28a745',
                  'line-width': 4,
                  'line-opacity': 0.8
                }
              });
            }

            // Mostrar información de la ruta
            const infoDiv = document.createElement('div');
            infoDiv.className = 'cg-ruta-info-badge';
            infoDiv.innerHTML = `${svgIcon('truck', 16)} <span>Distancia: <strong>${distanciaKm} km</strong></span> <span style="opacity:0.4;">|</span> ${svgIcon('clock', 16)} <span>Tiempo estimado: <strong>${tiempoMin} minutos</strong></span>`;
            document.getElementById('preview-map').parentElement.appendChild(infoDiv);
          }
        } catch (error) {
          console.error('Error al obtener ruta:', error);
        }
      }

      // Inicializar el mapa al abrir el modal
      previewMap.on('load', () => {
        actualizarMapaPreview();
      });

      // Actualizar mapa cuando cambie la sucursal
      document.getElementById('grupo-sucursal').addEventListener('change', () => {
        actualizarMapaPreview();
      });
    },
    preConfirm: () => {
      const nombreGrupo = document.getElementById('grupo-nombre').value.trim();
      const sucursal = document.getElementById('grupo-sucursal').value;
      const chofer = document.getElementById('grupo-chofer').value;
      const notas = document.getElementById('grupo-notas').value.trim();

      // Validaciones
      if (!sucursal) {
        Swal.showValidationMessage('Debe seleccionar una sucursal');
        return false;
      }

      if (!chofer) {
        Swal.showValidationMessage('Debe seleccionar un chofer');
        return false;
      }

      // Validar precios
      const pedidosConPrecios = [];
      let error = null;

      pedidos.forEach((pedido, index) => {
        const precioReal = parseFloat(document.querySelector(`.grupo-precio-real[data-index="${index}"]`).value);
        const validado = document.querySelector(`.grupo-validar-precio[data-index="${index}"]`).checked;

        if (isNaN(precioReal) || precioReal <= 0) {
          error = `El precio del pedido #${pedido.id} debe ser mayor a 0`;
          return;
        }

        if (!validado) {
          error = `Debe validar el precio del pedido #${pedido.id}`;
          return;
        }

        pedidosConPrecios.push({
          id: pedido.id,
          precio_real: precioReal,
          validado: validado
        });
      });

      if (error) {
        Swal.showValidationMessage(error);
        return false;
      }

      return {
        nombre_grupo: nombreGrupo,
        sucursal: sucursal,
        chofer: chofer,
        notas: notas,
        pedidos: pedidosConPrecios
      };
    }
  });

  if (result.isConfirmed && result.value) {
    await crearGrupoRuta(result.value, moverDesdeOtrosGrupos);
  }
}

// Crear grupo/ruta en el backend
async function crearGrupoRuta(datos, moverDesdeOtrosGrupos = false) {
  Swal.fire({
    title: 'Creando Grupo...',
    html: 'Creando grupo y asignando pedidos, por favor espere...',
    allowOutsideClick: false,
    didOpen: () => {
      Swal.showLoading();
    }
  });

  try {
    // Agregar flag al objeto de datos
    datos.mover_desde_otros_grupos = moverDesdeOtrosGrupos;

    const response = await fetch('crear_grupo_ruta.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(datos)
    });

    const resultado = await response.json();

    if (resultado.success) {
      await Swal.fire({
        icon: 'success',
        title: 'Grupo Creado',
        html: `
          <p><strong>${resultado.nombre_grupo}</strong></p>
          <p>${resultado.pedidos_actualizados} de ${resultado.total_pedidos} pedidos asignados</p>
          ${resultado.errores ? `<p style="color: #dc3545;">Con algunos errores</p>` : ''}
        `,
        confirmButtonColor: '#28a745'
      });

      // Recargar la página para mostrar los cambios
      window.location.reload();
    } else {
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: resultado.message || 'No se pudo crear el grupo',
        confirmButtonColor: '#006996'
      });
    }
  } catch (error) {
    console.error('Error:', error);
    Swal.fire({
      icon: 'error',
      title: 'Error de Conexión',
      text: 'No se pudo conectar con el servidor',
      confirmButtonColor: '#006996'
    });
  }
}
</script>
<?php endif; ?>

</body>
</html>


<style>

/* Contenedor de paginación */
#pagination {
  margin-top: 20px;
  text-align: center;
}

/* Botones de paginación */
#pagination button {
  background-color: #006996; /* Verde base */
  color: #fff;
  padding: 10px 15px;
  margin: 0 5px;
  border: none;
  border-radius: 5px;
  font-size: 14px;
  cursor: pointer;
  transition: background-color 0.3s ease;
}

/* Hover en botones */
#pagination button:hover {
  background-color:rgb(0, 75, 107);
}

/* Botones deshabilitados */
#pagination button:disabled {
  background-color: #ccc;
  cursor: not-allowed;
}

/* Indicador de página */
#pagination span {
  font-size: 16px;
  margin: 0 10px;
  color: #333;
}

/* ========== ESTILOS PARA BADGE DE GRUPO/RUTA ========== */
.badge-grupo-link {
  display: inline-block;
  max-width: 100%;
  text-decoration: none;
}

.badge-grupo {
  display: inherit;
  align-items: center;
  gap: 5px;
  color: white;
  padding: 4px 10px;
  border-radius: 15px;
  font-size: 11px;
  font-weight: bold;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
  animation: fadeInBadge 0.3s ease-in;
  cursor: pointer;
  max-width: 100%;
  overflow: hidden;
}

.badge-grupo:hover {
  transform: scale(1.05);
  box-shadow: 0 3px 6px rgba(0, 0, 0, 0.3);
  transition: all 0.2s ease;
  filter: brightness(1.1);
}

.grupo-icono {
  font-size: 12px;
  flex-shrink: 0;
}

.grupo-nombre {
  max-width: 100px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  flex: 1;
  min-width: 0;
}

.grupo-orden {
  background: rgba(255, 255, 255, 0.3);
  padding: 2px 6px;
  border-radius: 10px;
  font-size: 10px;
  flex-shrink: 0;
  white-space: nowrap;
}

@keyframes fadeInBadge {
  from {
    opacity: 0;
    transform: translateY(-5px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

</style>

<!-- Script para modal de destinatario -->
<script>
  
</script>
<script src="js/modal_destinatario.js"></script>
<script src="js/modal_incidencias_desktop.js?v=6"></script>
