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
</head>
    
<body>
  <div class="sidebar">
    <ul>
      <li>
        <a href="NuevoRegistroInicio.php">
          <img src="\Pedidos_GA\Img\Botones entregas\Pedidos_GA\AGRENA.png" alt="AddRegistro" class="icono-registro" style="max-width: 80%; height: auto;">
        </a>
      </li>
      <li>
        <a href="Estadisticas_Home.php">
          <img src="\Pedidos_GA\Img\Botones entregas\Pedidos_GA\ESTNA2.png" alt="Estaditicas" class="icono-estadisticas" style="max-width: 80%; height: auto;">
        </a>
      </li>
      
      <li>
        <a href="Choferes.php">
          <img src="\Pedidos_GA\Img\Botones entregas\Pedidos_GA\CHOFNA2.png" alt="Choferes" class="icono-choferes" style="max-width: 80%; height: auto;">
        </a>
      </li>
      <?php if ($_SESSION["Rol"] === "Admin"): ?>
      <li>
        <a href="Usuarios.php">
          <img src="\Pedidos_GA\Img\Botones entregas\Pedidos_GA\USUNA.png" alt="Usuarios" class="icono-U" style="max-width: 80%; height: auto;">
        </a>

      <?php endif; ?>

      <?php if ($_SESSION["Rol"] === "Admin"): ?>
      <li>
        <a href="historial.php">
          <img src="\Pedidos_GA\Img\Botones entregas\Pedidos_GA\H2.png" alt="Usuarios" class="icono-H" style="max-width: 80%; height: auto;">
        </a>

      <?php endif; ?>

      <?php if ($_SESSION["Rol"] === "Admin" || $_SESSION["Rol"] === "JC"): ?>
      <li>
        <a href="reporte_precios_facturas.php">
          <img src="\Pedidos_GA\Img\Botones entregas\Pedidos_GA\ICONO_CHIDO.png" alt="Reporte Precios" class="icono-reporte-precios" style="max-width: 80%; height: auto;">
        
        </a>
      </li>
      <?php endif; ?>

      <?php if ($_SESSION["Rol"] === "Admin" ||$_SESSION["Rol"] === "JC"||$_SESSION["Rol"] === "MEC"): ?>
      <li>
               <a href="vehiculos.php">
          <img src="\Pedidos_GA\Img\Botones entregas\Pedidos_GA\SERVMECNA.png" alt="vehiculos" class="icono-vehiculos" style="max-width: 80%; height: auto;">
        </a>
      </li>
      <?php endif; ?>
      <li class="corner-left-bottom">
        <a href="logout.php">
          <img src="\Pedidos_GA\Img\Botones entregas\Pedidos_GA\CERRSESBL.png" alt="Cerrar Sesión" class="icono-CS" style="max-width: 40%; height: auto;">
        </a>
      </li>
    </ul>
  </div>
    
  <div class="content">
    <!-- Contenido principal de tu página -->
  </div>

  <script>
    // Manejo de imágenes e iconos
    document.addEventListener("DOMContentLoaded", function() {
      var iconoVehiculos = document.querySelector(".icono-vehiculos");
      var iconoChoferes = document.querySelector(".icono-choferes");
      var iconoEstadisticas = document.querySelector(".icono-estadisticas");
      var iconoAddRegistro = document.querySelector(".icono-registro");
      var iconoCS = document.querySelector(".icono-CS");
      var iconoU = document.querySelector(".icono-U");
      var iconoH = document.querySelector(".icono-H");
      var iconoReportePrecios = document.querySelector(".icono-reporte-precios");
      var iconoMaps = document.querySelector(".icono-Maps");
      var iconoHome = document.querySelector(".icono-Home");
      var iconoWP = document.querySelector(".icono-WP");
      
      var imgNormalChoferes = "/Pedidos_GA/Img/Botones%20entregas/Pedidos_GA/CHOFNA2.png";
      var imgHoverChoferes = "/Pedidos_GA/Img/Botones%20entregas/Pedidos_GA/CHOFBL2.png";
      iconoChoferes.addEventListener("mouseover", function() {
          iconoChoferes.src = imgHoverChoferes;
      });
      iconoChoferes.addEventListener("mouseout", function() {
          iconoChoferes.src = imgNormalChoferes;
      });

      var imgNormalVehiculos = "/Pedidos_GA/Img/Botones%20entregas/Pedidos_GA/SERVMECNA.png";
      var imgHoverVehiculos = "/Pedidos_GA/Img/Botones%20entregas/Pedidos_GA/SERVMECBLANC.png";
      iconoVehiculos.addEventListener("mouseover", function() {
          iconoVehiculos.src = imgHoverVehiculos;
      });
      iconoVehiculos.addEventListener("mouseout", function() {
          iconoVehiculos.src = imgNormalVehiculos;
      });
      
      var imgNormalEstadisticas = "/Pedidos_GA/Img/Botones%20entregas/Pedidos_GA/ESTNA2.png";
      var imgHoverEstadisticas = "/Pedidos_GA/Img/Botones%20entregas/Pedidos_GA/ESTBL2.png";
      iconoEstadisticas.addEventListener("mouseover", function() {
          iconoEstadisticas.src = imgHoverEstadisticas;
      });
      iconoEstadisticas.addEventListener("mouseout", function() {
          iconoEstadisticas.src = imgNormalEstadisticas;
      });
      
      var imgNormalAddRegistro = "/Pedidos_GA/Img/Botones%20entregas/Pedidos_GA/AGRENA.png";
      var imgHoverAddRegistro = "/Pedidos_GA/Img/Botones%20entregas/Pedidos_GA/AGREBL.png";
      iconoAddRegistro.addEventListener("mouseover", function() {
          iconoAddRegistro.src = imgHoverAddRegistro;
      });
      iconoAddRegistro.addEventListener("mouseout", function() {
          iconoAddRegistro.src = imgNormalAddRegistro;
      });
      
      var imgNormalU = "/Pedidos_GA/Img/Botones%20entregas/Pedidos_GA/USUNA.png";
      var imgHoverU = "/Pedidos_GA/Img/Botones%20entregas/Pedidos_GA/USUBL.png";
      iconoU.addEventListener("mouseover", function() {
          iconoU.src = imgHoverU;
      });
      iconoU.addEventListener("mouseout", function() {
          iconoU.src = imgNormalU;
      });

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
      var imgHoverReportePrecios = "/Pedidos_GA/Img/Botones%20entregas/Pedidos_GA/ESTBL2.png";
      if (iconoReportePrecios) {
          iconoReportePrecios.addEventListener("mouseover", function() {
              iconoReportePrecios.src = imgHoverReportePrecios;
          });
          iconoReportePrecios.addEventListener("mouseout", function() {
              iconoReportePrecios.src = imgNormalReportePrecios;
          });
      }

      var imgNormalSC = "/Pedidos_GA/Img/Botones%20entregas/Pedidos_GA/CERRSESBL.png";
      var imgHoverSC = "/Pedidos_GA/Img/Botones%20entregas/Pedidos_GA/CERRSESNA.png";
      iconoCS.addEventListener("mouseover", function() {
          iconoCS.src = imgHoverSC;
      });
      iconoCS.addEventListener("mouseout", function() {
          iconoCS.src = imgNormalSC;
      });
      
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
      
      var imgNormalHome = "/Pedidos_GA/Img/Botones%20entregas/Pedidos_GA/CERRSESBL.png";
      var imgHoverHome = "/Pedidos_GA/Img/Botones%20entregas/Pedidos_GA/CERRSESNA.png";
      iconoHome.addEventListener("mouseover", function() {
          iconoHome.src = imgHoverHome;
      });
      iconoHome.addEventListener("mouseout", function() {
          iconoHome.src = imgNormalHome;
      });
      
      var iconoWP = document.querySelector(".icono-WP");
      var imgNormalWP = "/Pedidos_GA/Img/Botones%20entregas/Whatsapp/WWSPAZ.png";
      var imgHoverWP = "/Pedidos_GA/Img/Botones%20entregas/Whatsapp/WSPNA.png";
      iconoWP.addEventListener("mouseover", function() {
          iconoWP.src = imgHoverWP;
      });
      iconoWP.addEventListener("mouseout", function() {
          iconoWP.src = imgNormalWP;
      });
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