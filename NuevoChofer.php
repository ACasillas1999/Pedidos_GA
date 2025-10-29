<?php


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
<html>
<head>
    <title>Estadísticas del Chofer</title>
    <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <link rel="stylesheet" href="styles4.css">
    </head>
<body>
    <header class="header">
        <div class="logo">Agregar Chofer</div>
        <nav class="navbar">
            <ul>
                <li class="nav-item"><a href='Choferes.php' class="nav-link">Volver</a></li>
            </ul>
        </nav>
    </header>
    
   <div class="container">
    <div id="chofer-username"></div>
       <div class="container">
    <div id="barchart" class="chart-container"></div>
    <div id="resumen"></div>
           </div>
    <p></p>
    <div id="pedidos-chofer"></div>
       </div>
</body>
</html>
