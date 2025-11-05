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
            <a href="mapa_calor.php" title="Mapa de Calor">
              <img src="\Pedidos_GA\Img\Botones entregas\Pedidos_GA\MAPA_NA.png" alt="Mapa Calor" class="icono-mapa-calor" style="max-width: 60%; height: auto;">
            </a>
          </li>
          <?php endif; ?>
          <?php if ($_SESSION["Rol"] === "Admin" || $_SESSION["Rol"] === "JC" || $_SESSION["Rol"] === "MEC"): ?>
          <li>
            <a href="vehiculos.php" title="Vehículos">
              <img src="\Pedidos_GA\Img\Botones entregas\Pedidos_GA\SERVMECNA.png" alt="vehiculos" class="icono-vehiculos" style="max-width: 60%; height: auto;">
            </a>
          </li>
          <?php endif; ?>
          <li>
            <a href="Choferes.php" title="Choferes">
              <img src="\Pedidos_GA\Img\Botones entregas\Pedidos_GA\CHOFNA2.png" alt="Choferes" class="icono-choferes" style="max-width: 60%; height: auto;">
            </a>
          </li>
        </ul>
      </li>
      <?php else: ?>
      <!-- Si no es admin, mostrar Choferes directamente -->
      <li class="sidebar-divider"></li>
      <li>
        <a href="Choferes.php" title="Choferes">
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
            <a href="Usuarios.php" title="Usuarios">
              <img src="\Pedidos_GA\Img\Botones entregas\Pedidos_GA\USUNA.png" alt="Usuarios" class="icono-U" style="max-width: 60%; height: auto;">
            </a>
          </li>
          <li>
            <a href="historial.php" title="Historial">
              <img src="\Pedidos_GA\Img\Botones entregas\Pedidos_GA\H2.png" alt="Historial" class="icono-H" style="max-width: 60%; height: auto;">
            </a>
          </li>
          <?php endif; ?>
          <li>
            <a href="reporte_precios_facturas.php" title="Reporte de Precios">
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

      var imgNormalReportePrecios = "/Pedidos_GA/Img/Botones%20entregas/Pedidos_GA/ICONO_CHIDO.png";
      var imgHoverReportePrecios = "/Pedidos_GA/Img/Botones%20entregas/Pedidos_GA/Precios_chido_BLANCO.png";
      if (iconoReportePrecios) {
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
    <form id="wpForm" action="/Pedidos_GA/Mensajes_WP/Mensaje_WP_NotificarChoferes.php" method="post" onsubmit="return false;">
      <button type="button" onclick="confirmSubmit()" value="WP" style="background: none; border: none; padding: 0;">
        <img src="/Pedidos_GA/Img/Botones entregas/Whatsapp/WWSPAZ.png" alt="icono-WP" class="icono-WP" style="max-width: 45%; height: auto;">
      </button>
    </form>
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
      <form id="consultaForm" class="formulario">
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
      <form id="consultaForm" class="formulario">
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
    <form id="filtroEstadoForm">
      <label class="label">Filtrar por Estado:</label><br>
      <input type="checkbox" id="estadoCancelado" name="estado" value="CANCELADO">
      <label for="estadoCancelado">CANCELADO</label><br>
      <input type="checkbox" id="estadoEnTienda" name="estado" value="EN TIENDA" checked>
      <label for="estadoEnTienda">EN TIENDA</label><br>
      <input type="checkbox" id="estadoReprogramado" name="estado" value="REPROGRAMADO" checked>
      <label for="estadoReprogramado">REPROGRAMADO</label><br>
      <input type="checkbox" id="estadoActivo" name="estado" value="ACTIVO" checked>
      <label for="estadoActivo">ACTIVO</label><br>
      <input type="checkbox" id="estadoEnRuta" name="estado" value="EN RUTA" checked>
      <label for="estadoEnRuta">EN RUTA</label><br>
      <input type="checkbox" id="estadoEntregado" name="estado" value="ENTREGADO">
      <label for="estadoEntregado">ENTREGADO</label><br>
      <!-- Botón "Filtrar" se conserva como respaldo -->
      <button type="submit" class="boton-consultar">Filtrar</button>
      <!-- Campo de búsqueda -->
      <p></p>
      <input type="text" id="busqueda" name="busqueda" placeholder="Buscar...">
      <button type="button" id="boton-buscar" class="boton-consultar">Buscar</button>
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
        // Enviamos sucursal, estados y offset
        xhr.send("sucursal=" + encodeURIComponent(sucursal) + "&estados=" + encodeURIComponent(JSON.stringify(estadosSeleccionados)) + "&offset=" + offset);
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
      validado: parseInt(cb.dataset.validado) || 0
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

  const { value: confirmado } = await Swal.fire({
    title: 'Gestión Masiva de Pedidos',
    html: `
      <div style="max-height: 60vh; overflow-y: auto; text-align: left;">
        ${pedidosHTML}
      </div>
    `,
    width: '90%',
    showCancelButton: true,
    confirmButtonText: 'Guardar Todo',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#006996',
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

  if (confirmado) {
    await procesarPedidosMasivamente(confirmado);
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

</style>