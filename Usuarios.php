<?php
// Iniciar la sesión de forma segura
ini_set('session.cookie_httponly', true); // Sólo permitir cookies de sesión vía HTTP
ini_set('session.cookie_secure', true); // Solo enviar cookies de sesión a través de conexiones HTTPS
session_name("GA");
session_start();

// Verificar si el usuario no está logeado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    // Si no está logeado, redirigir al formulario de inicio de sesión
    header("location: /Pedidos_GA/Sesion/login.html");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="icon" type="image/png" href="//Img/Botones%20entregas/ICONOSPAG/ICONOPEDIDOS.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultar Usuarios</title>
    <link rel="stylesheet" type="text/css" href="styles6.css">
    <link rel="icon" href="/Img/Paquete.ico" type="image/x-icon">
</head>
    
    
   <script>
    document.addEventListener("DOMContentLoaded", function() {  
       
        var iconoAddChofer = document.querySelector(".icono-AddChofer");
        var iconoVolver = document.querySelector(".icono-Volver");
    
            var imgNormalAddChoferes ="//Img/Botones%20entregas/Pedidos_GA/AGUSNA.png";
            var imgHoverCAddhoferes = "//Img/Botones%20entregas/Pedidos_GA/AGUSBL.png";

            // Cambiar la imagen al pasar el mouse para Pendientes
            iconoAddChofer.addEventListener("mouseover", function() {
                iconoAddChofer.src = imgHoverCAddhoferes;
            });

            // Cambiar de vuelta al mover el mouse fuera para Pendientes
            iconoAddChofer.addEventListener("mouseout", function() {
                iconoAddChofer.src = imgNormalAddChoferes;
            });
        
        
          var imgNormalVolver="//Img/Botones%20entregas/Usuario/VOLVAZ.png";
            var imgHoverVolver = "//Img/Botones%20entregas/Usuario/VOLVNA.png";

            // Cambiar la imagen al pasar el mouse para Pendientes
            iconoVolver.addEventListener("mouseover", function() {
                iconoVolver.src = imgHoverVolver;
            });

            // Cambiar de vuelta al mover el mouse fuera para Pendientes
            iconoVolver.addEventListener("mouseout", function() {
                iconoVolver.src = imgNormalVolver;
            });
        
        
        
     });
    
    </script> 
    
    
<body>
    
 <div class="sidebar">
     
     
    <ul>
        <li><a href="/Pedidos_GA/Registrar">
            <img src="//Img/Botones%20entregas/Pedidos_GA/AGUSNA.png" alt="Estaditicas " class = "icono-AddChofer"style="max-width: 80%; height: auto;">
            
           </a></li>
        
        
         <li class="corner-left-bottom"><a href="Pedidos_GA.php">
            <img src="//Img/Botones%20entregas/Usuario/VOLVAZ.png" alt="Estaditicas " class = "icono-Volver"style="max-width: 35%; height: auto;">
            
           </a></li>
        
              <!-- <li class="logout-button">
    <form action="Pedidos_GA.php" method="post" src="//Img/Botones%20entregas/Pedidos_GA/AGUSNA.png" >
        <input type="submit" value="Volver">
       
    </form>
                   
     
            
                   
</li>-->

    </ul>
   
</div>
    
    <div class="content">
        <!-- Contenido principal de tu página -->
    </div>

   
<div class="container">
          <h2 class="titulo">
            <div class="chart-title">
               
               <img src="//Img/Botones%20entregas/Usuario/USUAZ.png" alt="iconoVPedidos "class= "icono-registro" style="max-width: 20%; height: auto; ">
                
                

</div>

    
    </h2>
       <form id="filtroEstadoForm">
            <p></p>
        <input type="text" id="busqueda" name="busqueda" placeholder="Buscar...">
        <button type="button" id="boton-buscar" class="boton-consultar">Buscar</button>
    </form>

    <p></p>

    
    <div id="resultado">
        <!-- Aquí se mostrarán los resultados de la consulta -->
    </div>

</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
  

    // Función para manejar el clic en el botón de búsqueda
    document.getElementById("boton-buscar").addEventListener("click", function(event) {
        event.preventDefault(); // Evita el envío del formulario por defecto
        
        // Obtener el valor de búsqueda
        var busqueda = document.getElementById("busqueda").value.trim();
        
        // Realizar una solicitud AJAX para buscar los datos
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "Buscar_Usuarios.php", true); // Reemplaza "buscar.php" con tu script de búsqueda
        xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function() {
            if (xhr.readyState == 4 && xhr.status == 200) {
                document.getElementById("resultado").innerHTML = xhr.responseText; // Actualizar el contenido de la tabla con los resultados de la búsqueda
            }
        };
        xhr.send("busqueda=" + encodeURIComponent(busqueda)); // Enviar el término de búsqueda al servidor
    });
});
</script>


<!-- SweetAlert2 (una sola vez en la página) -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Delegación de eventos: funciona con contenido insertado vía innerHTML
document.getElementById('resultado').addEventListener('click', function (e) {
  const btn = e.target.closest('.btn-regenerar');
  if (!btn) return;

  const id   = btn.dataset.id;
  const user = btn.dataset.user || '';

  Swal.fire({
    title: '¿Regenerar contraseña?',
    html: 'Se generará una nueva contraseña para <b>' + user + '</b>.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Sí, regenerar',
    cancelButtonText: 'Cancelar'
  }).then((res) => {
    if (!res.isConfirmed) return;

    // Ajusta la ruta si tu archivo NO está en la misma carpeta
    fetch('regenerar_password.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: 'id_usuario=' + encodeURIComponent(id)
    })
    .then(r => r.json())
    .then(data => {
      if (data && data.ok) {
        Swal.fire({
          title: 'Nueva contraseña',
          html: '<code style="font-size:1.1rem">' + data.nueva + '</code>',
          icon: 'success'
        });
      } else {
        Swal.fire('Error', (data && data.msg) ? data.msg : 'No se pudo regenerar.', 'error');
      }
    })
    .catch(() => Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error'));
  });
});
</script>





</body>
</html>
