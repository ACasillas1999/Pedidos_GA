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
<<<<<<< HEAD
    <link rel="icon" type="image/png" href="/Img/Botones%20entregas/ICONOSPAG/ICONOPEDIDOS.png">
=======
    <link rel="icon" type="image/png" href="/Pedidos_GA/Img/Botones%20entregas/ICONOSPAG/ICONOPEDIDOS.png">
>>>>>>> parent of 5e8b02c (parra amazon Update image paths and SQL table names)
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedidos GA</title>
    <link rel="stylesheet" type="text/css" href="styles.css">
    <link rel="icon" href="Pedidos_GA/Img/Paquete.ico" type="image/x-icon">
</head>
    
    
   <script>
    document.addEventListener("DOMContentLoaded", function() {  
       
        var iconoAddChofer = document.querySelector(".icono-AddChofer");
        var iconoVolver = document.querySelector(".icono-Volver");
    
<<<<<<< HEAD
            var imgNormalAddChoferes ="/Img/Botones%20entregas/Choferes/AGCHOF2.png";
            var imgHoverCAddhoferes = "/Img/Botones%20entregas/Choferes/AGCHOFBL2.png";
=======
            var imgNormalAddChoferes ="/Pedidos_GA/Img/Botones%20entregas/Choferes/AGCHOF2.png";
            var imgHoverCAddhoferes = "/Pedidos_GA/Img/Botones%20entregas/Choferes/AGCHOFBL2.png";
>>>>>>> parent of 5e8b02c (parra amazon Update image paths and SQL table names)

            // Cambiar la imagen al pasar el mouse para Pendientes
            iconoAddChofer.addEventListener("mouseover", function() {
                iconoAddChofer.src = imgHoverCAddhoferes;
            });

            // Cambiar de vuelta al mover el mouse fuera para Pendientes
            iconoAddChofer.addEventListener("mouseout", function() {
                iconoAddChofer.src = imgNormalAddChoferes;
            });
        
<<<<<<< HEAD
        var imgNormalVolver="/Img/Botones%20entregas/Usuario/VOLVAZ.png";
            var imgHoverVolver = "/Img/Botones%20entregas/Usuario/VOLVNA.png";
=======
        var imgNormalVolver="/Pedidos_GA/Img/Botones%20entregas/Usuario/VOLVAZ.png";
            var imgHoverVolver = "/Pedidos_GA/Img/Botones%20entregas/Usuario/VOLVNA.png";
>>>>>>> parent of 5e8b02c (parra amazon Update image paths and SQL table names)

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
         <?php if ($_SESSION["Rol"] === "Admin"||$_SESSION["Rol"] === "JC" ): ?>
<<<<<<< HEAD
        <li><a href="/RegistrarChofer">
            <img src="/Img/Botones entregas\Choferes\AGCHOF2.png" alt="Estaditicas " class = "icono-AddChofer"style="max-width: 80%; height: auto;">
=======
        <li><a href="/Pedidos_GA/RegistrarChofer">
            <img src="\Pedidos_GA\Img\Botones entregas\Choferes\AGCHOF2.png" alt="Estaditicas " class = "icono-AddChofer"style="max-width: 80%; height: auto;">
>>>>>>> parent of 5e8b02c (parra amazon Update image paths and SQL table names)
            
           </a></li>
         <?php endif; ?>
        
        
        
               <li class="corner-left-bottom"><a href="Pedidos_GA.php">
<<<<<<< HEAD
            <img src="/Img/Botones%20entregas/Usuario/VOLVAZ.png" alt="Estaditicas " class = "icono-Volver"style="max-width: 35%; height: auto;">
=======
            <img src="/Pedidos_GA/Img/Botones%20entregas/Usuario/VOLVAZ.png" alt="Estaditicas " class = "icono-Volver"style="max-width: 35%; height: auto;">
>>>>>>> parent of 5e8b02c (parra amazon Update image paths and SQL table names)
            
           </a></li>
    </ul>
   
</div>
    
    <div class="content">
        <!-- Contenido principal de tu página -->
    </div>

   
<div class="container">
          <h2 class="titulo">
            <div class="chart-title">
<<<<<<< HEAD
                <img src="/Img/Botones%20entregas/Choferes/CONCHOF3.png" alt="iconoVPedidos "class= "icono-registro" style="max-width: 20%; height: auto; ">
=======
                <img src="/Pedidos_GA/Img/Botones%20entregas/Choferes/CONCHOF3.png" alt="iconoVPedidos "class= "icono-registro" style="max-width: 20%; height: auto; ">
>>>>>>> parent of 5e8b02c (parra amazon Update image paths and SQL table names)
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
        xhr.open("POST", "BuscarChofer.php", true); // Reemplaza "buscar.php" con tu script de búsqueda
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

</body>
</html>
