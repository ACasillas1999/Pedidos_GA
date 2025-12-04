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

  <!-- Mapbox GL JS para modal de destinatario -->
  <link href='https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css' rel='stylesheet' />
  <script src='https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js'></script>
  <script src='https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v5.0.0/mapbox-gl-geocoder.min.js'></script>
  <link rel='stylesheet' href='https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v5.0.0/mapbox-gl-geocoder.css' type='text/css' />

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
    <a href="/var/www/html/App/Apks/App_Pedidos_GA_v2.apk
" download="App_Pedidos_GA_v2.apk" title="Descargar App Móvil">
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
        </select>
      </form>
    <?php endif; ?>
    
    <!-- Formulario de filtrado: Checkboxes y búsqueda -->
    <form id="filtroEstadoForm" style="background: #e8ebed; padding: 15px; border-radius: 8px; margin-bottom: 20px;">

      

      <!-- 1. Filtrar por Estado -->
      <div style="margin-bottom: 15px; padding-bottom: 12px; border-bottom: 1px solid #dee2e6;">
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

      <!-- 2. Filtrar por Grupo -->
      <div style="margin-bottom: 15px; padding-bottom: 12px; border-bottom: 1px solid #dee2e6;    margin-left: 37px;">
        <label class="label" style="font-weight: bold; font-size: 13px; margin-bottom: 8px; display: block; color: #495057;">Filtrar por Grupo:</label>
        <select id="filtroGrupo" name="filtroGrupo" style="width: 100%; padding: 8px; border: 1px solid #ced4da; border-radius: 4px; font-size: 13px; box-sizing: border-box; margin-bottom: 8px;">
          <option value="">Todos los pedidos</option>
          <option value="CON_GRUPO">Con grupo asignado</option>
          <option value="SIN_GRUPO">Sin grupo asignado</option>
        </select>
        <input type="text" id="buscarGrupo" name="buscarGrupo" placeholder="Buscar por nombre de grupo..." style="width: 100%; padding: 8px; border: 1px solid #ced4da; border-radius: 4px; font-size: 13px; box-sizing: border-box;">
      </div>

      <!-- 3. Rango de Fechas -->
      <div style="margin-bottom: 15px; padding-bottom: 12px; border-bottom: 1px solid #dee2e6;     padding-right: 79px;margin-left: 74px;">
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
      <div>
        <label class="label" style="font-weight: bold; font-size: 13px; margin-bottom: 8px; display: block; color: #495057;">Búsqueda General:</label>
        <div style="display: flex; gap: 8px; align-items: stretch;">
          <input type="text" id="busqueda" name="busqueda" placeholder="Buscar pedido..." style="flex: 1; padding: 8px; border: 1px solid #ced4da; border-radius: 4px; font-size: 13px; box-sizing: border-box;">
          <button type="button" id="boton-buscar" class="boton-consultar" style="padding: 8px 16px; font-size: 13px; white-space: nowrap; box-sizing: border-box;">Buscar</button>
        </div>
      </div>

    </form>
    
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
          }
        };
        // Enviamos todos los parámetros
        var params = "sucursal=" + encodeURIComponent(sucursal) +
                     "&estados=" + encodeURIComponent(JSON.stringify(estadosSeleccionados)) +
                     "&offset=" + offset +
                     "&filtro_grupo=" + encodeURIComponent(filtroGrupo) +
                     "&buscar_grupo=" + encodeURIComponent(buscarGrupo) +
                     "&fecha_inicio=" + encodeURIComponent(fechaInicio) +
                     "&fecha_fin=" + encodeURIComponent(fechaFin);
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
      
      // Manejo de la búsqueda (sin cambios)
      document.getElementById("boton-buscar").addEventListener("click", function(event) {
        event.preventDefault();
        var busqueda = document.getElementById("busqueda").value.trim();
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "buscar.php", true);
        xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function() {
          if (xhr.readyState == 4 && xhr.status == 200) {
            document.getElementById("resultado").innerHTML = xhr.responseText;
          }
        };
        xhr.send("busqueda=" + encodeURIComponent(busqueda));
      });
      
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

  let mensajeConfirmacion = (accion === 'entregar_jefe')
    ? '¿Seguro que quieres marcar esta factura como "Entregada a Jefe de choferes"?'
    : '¿Seguro que quieres marcar esta factura como "Devuelta a Caja"?';

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

// Abrir modal de gestión masiva con SweetAlert2
async function abrirModalGestionMasiva(pedidos) {
  // Generar HTML para cada pedido
  let pedidosHTML = '';

  pedidos.forEach((pedido, index) => {
    const alertaPrecio = pedido.precioReal > 0 && pedido.precioReal < 1000
      ? '<span style="color: #856404; font-weight: bold;">⚠️ Precio menor a $1000</span>'
      : '';

    const estadoValidacion = pedido.validado === 1
      ? '<span style="color: #28a745;">✓ Validado</span>'
      : '<span style="color: #ffc107;">⏳ Pendiente</span>';

    pedidosHTML += `
      <div class="pedido-item" data-index="${index}" style="border: 2px solid #006996; border-radius: 10px; padding: 15px; margin-bottom: 15px; background: #f8f9fa;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
          <h4 style="margin: 0; color: #006996;">Pedido #${pedido.id} - Factura: ${pedido.factura}</h4>
          ${estadoValidacion}
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
          <div><strong>Cliente:</strong> ${pedido.cliente}</div>
          <div><strong>Dirección:</strong> ${pedido.direccion}</div>
          <div><strong>Sucursal:</strong> ${pedido.sucursal}</div>
          <div><strong>Tipo Envío:</strong> ${pedido.tipoEnvio}</div>
        </div>

        <div style="background: white; padding: 10px; border-radius: 5px; margin-bottom: 10px;">
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
            <div>
              <label style="font-weight: bold; display: block; margin-bottom: 5px;">Precio Vendedor:</label>
              <input type="number" class="precio-vendedor-input" value="${pedido.precioVendedor}" readonly
                style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; background: #e9ecef;">
            </div>
            <div>
              <label style="font-weight: bold; display: block; margin-bottom: 5px;">Precio Real de Factura:</label>
              <input type="number" class="precio-real-input" data-index="${index}" value="${pedido.precioReal}" step="0.01" min="0.01"
                style="width: 100%; padding: 8px; border: 1px solid #006996; border-radius: 4px;">
              ${alertaPrecio}
            </div>
          </div>

          <div style="margin-top: 10px;">
            <label style="display: flex; align-items: center; cursor: pointer;">
              <input type="checkbox" class="validar-precio-checkbox" data-index="${index}" ${pedido.validado === 1 ? 'checked' : ''}
                style="margin-right: 8px; width: 18px; height: 18px;">
              <span style="font-weight: bold;">Validar Precio</span>
            </label>
          </div>
        </div>

        <div style="background: white; padding: 10px; border-radius: 5px;">
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
            <div>
              <label style="font-weight: bold; display: block; margin-bottom: 5px;">Sucursal para Chofer:</label>
              <select class="sucursal-chofer-select" data-index="${index}"
                style="width: 100%; padding: 8px; border: 1px solid #006996; border-radius: 4px;">
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
              </select>
            </div>
            <div>
              <label style="font-weight: bold; display: block; margin-bottom: 5px;">Chofer:</label>
              <select class="chofer-select" data-index="${index}" disabled
                style="width: 100%; padding: 8px; border: 1px solid #006996; border-radius: 4px;">
                <option value="">Primero seleccione sucursal</option>
              </select>
            </div>
          </div>
        </div>
      </div>
    `;
  });

  const resultado = await Swal.fire({
    title: 'Gestión Masiva de Pedidos',
    html: `
      <div style="max-height: 60vh; overflow-y: auto; text-align: left;">
        <div style="margin-bottom: 15px; padding: 10px; background: #e7f3ff; border-radius: 8px; border-left: 4px solid #006996;">
          <strong>💡 Tip:</strong> Si deseas agrupar estos pedidos en una misma ruta, usa el botón "Crear Grupo/Ruta" para asignarles el mismo chofer a todos.
        </div>
        ${pedidosHTML}
      </div>
    `,
    width: '90%',
    showCancelButton: true,
    showDenyButton: true,
    confirmButtonText: 'Asignar Individualmente',
    denyButtonText: '🚚 Crear Grupo/Ruta',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#006996',
    denyButtonColor: '#28a745',
    cancelButtonColor: '#6c757d',
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
          const alerta = parent.querySelector('span');

          if (!isNaN(precio) && precio > 0 && precio < 1000) {
            if (!alerta) {
              const span = document.createElement('span');
              span.style.cssText = 'color: #856404; font-weight: bold; display: block; margin-top: 5px;';
              span.textContent = '⚠️ Precio menor a $1000 - Flete no conveniente';
              parent.appendChild(span);
            }
            this.style.backgroundColor = '#fff3cd';
          } else {
            if (alerta) alerta.remove();
            this.style.backgroundColor = '';
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
    let mensajeConflictos = '<div style="text-align: left; margin: 15px 0;">';
    mensajeConflictos += '<p style="color: #856404; margin-bottom: 10px;">⚠️ <strong>Los siguientes pedidos ya están asignados a otros grupos:</strong></p>';
    mensajeConflictos += '<div style="background: #fff3cd; padding: 15px; border-radius: 5px; border-left: 4px solid #ffc107; max-height: 200px; overflow-y: auto;">';

    data.pedidos_en_grupos.forEach(pedido => {
      mensajeConflictos += `
        <div style="padding: 8px 0; border-bottom: 1px solid #ffe69c;">
          <strong>Pedido #${pedido.pedido_id}</strong> - ${pedido.FACTURA}<br>
          <small style="color: #666;">
            Cliente: ${pedido.NOMBRE_CLIENTE}<br>
            Grupo actual: "${pedido.nombre_grupo}" (Chofer: ${pedido.chofer_asignado})
          </small>
        </div>
      `;
    });

    mensajeConflictos += '</div></div>';

    const resultado = await Swal.fire({
      icon: 'warning',
      title: 'Pedidos ya asignados a grupos',
      html: `
        ${mensajeConflictos}
        <p style="margin-top: 15px; color: #333;">
          <strong>¿Qué deseas hacer?</strong>
        </p>
      `,
      width: '650px',
      showDenyButton: true,
      showCancelButton: true,
      confirmButtonText: 'Mover al nuevo grupo',
      denyButtonText: 'Continuar sin cambios',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#28a745',
      denyButtonColor: '#6c757d',
      cancelButtonColor: '#dc3545'
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
  // Generar lista de pedidos para mostrar
  let listaPedidos = '<div style="margin: 15px 0; padding: 10px; background: #f8f9fa; border-radius: 5px; max-height: 200px; overflow-y: auto;">';
  listaPedidos += '<strong>Pedidos seleccionados:</strong><ul style="margin: 10px 0; text-align: left;">';

  pedidos.forEach(pedido => {
    listaPedidos += `<li>Pedido #${pedido.id} - ${pedido.factura} (${pedido.cliente})</li>`;
  });

  listaPedidos += '</ul></div>';

  // Generar HTML para validación de precios
  let preciosHTML = '<div style="margin: 15px 0;">';
  preciosHTML += '<div style="background: white; padding: 15px; border-radius: 8px; border: 2px solid #006996;">';
  preciosHTML += '<h4 style="margin-top: 0; color: #006996;">Validación de Precios</h4>';

  pedidos.forEach((pedido, index) => {
    const alertaPrecio = pedido.precioReal > 0 && pedido.precioReal < 1000
      ? '<span style="color: #856404; font-size: 12px;">⚠️ Precio menor a $1000</span>'
      : '';

    preciosHTML += `
      <div style="padding: 10px; margin-bottom: 10px; background: #f8f9fa; border-radius: 5px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
          <strong style="color: #006996;">Pedido #${pedido.id}</strong>
          <span style="color: #666; font-size: 13px;">Precio vendedor: $${pedido.precioVendedor}</span>
        </div>
        <div style="display: flex; gap: 10px; align-items: center;">
          <input type="number" class="grupo-precio-real" data-index="${index}"
                 value="${pedido.precioReal}" step="0.01" min="0.01"
                 placeholder="Precio real"
                 style="flex: 1; padding: 8px; border: 1px solid #006996; border-radius: 4px;">
          <label style="display: flex; align-items: center; gap: 5px; white-space: nowrap;">
            <input type="checkbox" class="grupo-validar-precio" data-index="${index}"
                   ${pedido.validado === 1 ? 'checked' : ''}
                   style="width: 18px; height: 18px;">
            <span>Validar</span>
          </label>
        </div>
        ${alertaPrecio}
      </div>
    `;
  });

  preciosHTML += '</div></div>';

  const result = await Swal.fire({
    title: '🚚 Crear Grupo/Ruta',
    html: `
      <div style="text-align: left;">
        <p style="color: #666; margin-bottom: 15px;">
          Agrupa estos pedidos y asígnalos todos al mismo chofer para optimizar la ruta de entrega.
        </p>

        ${listaPedidos}

        ${preciosHTML}

        <div style="margin: 15px 0;">
          <label style="display: block; font-weight: bold; margin-bottom: 5px;">Nombre del Grupo (opcional):</label>
          <input type="text" id="grupo-nombre" placeholder="Ej: Ruta Norte Mañana"
                 style="width: 100%; padding: 10px; border: 1px solid #006996; border-radius: 4px;">
          <small style="color: #666;">Si lo dejas vacío, se generará automáticamente</small>
        </div>

        <div style="margin: 15px 0;">
          <label style="display: block; font-weight: bold; margin-bottom: 5px;">Sucursal:</label>
          <select id="grupo-sucursal" style="width: 100%; padding: 10px; border: 1px solid #006996; border-radius: 4px;">
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
          </select>
        </div>

        <div style="margin: 15px 0;">
          <label style="display: block; font-weight: bold; margin-bottom: 5px;">Chofer:</label>
          <select id="grupo-chofer" disabled style="width: 100%; padding: 10px; border: 1px solid #006996; border-radius: 4px;">
            <option value="">Primero seleccione sucursal</option>
          </select>
        </div>

        <div style="margin: 15px 0;">
          <label style="display: block; font-weight: bold; margin-bottom: 5px;">Notas (opcional):</label>
          <textarea id="grupo-notas" rows="3" placeholder="Notas o comentarios sobre esta ruta..."
                    style="width: 100%; padding: 10px; border: 1px solid #006996; border-radius: 4px; resize: vertical;"></textarea>
        </div>

        <!-- Mapa de vista previa -->
        <div style="margin: 15px 0;">
          <label style="display: block; font-weight: bold; margin-bottom: 5px;">
            🗺️ Vista Previa de la Ruta:
          </label>
          <div id="preview-map" style="width: 100%; height: 350px; border-radius: 8px; border: 2px solid #006996;"></div>
          <small style="color: #666; display: block; margin-top: 5px;">
            Esta es la ruta sugerida basada en las direcciones de los pedidos seleccionados
          </small>
        </div>
      </div>
    `,
    width: '900px',
    showCancelButton: true,
    confirmButtonText: 'Crear Grupo y Asignar',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#28a745',
    cancelButtonColor: '#6c757d',
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
          const parent = this.parentElement.parentElement;
          let alerta = parent.querySelector('span[style*="color: #856404"]');

          if (!isNaN(precio) && precio > 0 && precio < 1000) {
            if (!alerta) {
              alerta = document.createElement('span');
              alerta.style.cssText = 'color: #856404; font-size: 12px; display: block; margin-top: 5px;';
              alerta.textContent = '⚠️ Precio menor a $1000';
              parent.appendChild(alerta);
            }
            this.style.backgroundColor = '#fff3cd';
          } else {
            if (alerta) alerta.remove();
            this.style.backgroundColor = '';
          }
        });
      });

      // Inicializar mapa de vista previa
      mapboxgl.accessToken = 'pk.eyJ1IjoiYWNhc2lsbGFzNzY2IiwiYSI6ImNsdW12cTZyMjB4NnMya213MDdseXp6ZGgifQ.t7-l1lQfd8mgHILM5YrdNw';

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
        const infoDiv = document.getElementById('preview-map').parentElement.querySelector('div[style*="background: #d4edda"]');
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
                  background: #ff6b6b;
                  color: white;
                  width: 40px;
                  height: 40px;
                  border-radius: 50%;
                  display: flex;
                  align-items: center;
                  justify-content: center;
                  font-size: 24px;
                  border: 3px solid white;
                  box-shadow: 0 2px 8px rgba(0,0,0,0.4);
                  cursor: pointer;
                `;
                elOrigen.textContent = '🏢';

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
            infoDiv.style.cssText = 'margin-top: 10px; padding: 10px; background: #d4edda; border-radius: 5px; color: #155724; text-align: center;';
            infoDiv.innerHTML = '<strong>📊 Información de la Ruta:</strong><br>' +
              'Distancia: ' + distanciaKm + ' km | Tiempo estimado: ' + tiempoMin + ' minutos';
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
<script src="js/modal_destinatario.js"></script>