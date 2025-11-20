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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="/Img/Botones%20entregas/ICONOSPAG/ICONOPEDIDOS.png">
    <link rel="stylesheet" href="styles7.css">
    <title>Formulario</title>
</head>

<script>
    document.addEventListener("DOMContentLoaded", function() {
     
     var iconoVolver = document.querySelector(".icono-Volver");
     var iconoFAP = document.querySelector(".icono-FAP-img");
 
     
 
     var imgNormalVolver = "/Img/Botones%20entregas/RegistrarChofer/VOLVAZ.png";
     var imgHoverVolver = "/Img/Botones%20entregas/RegistrarChofer/VOLVNA.png";
 
     // Cambiar la imagen al pasar el mouse para Volver
     if (iconoVolver) {
         iconoVolver.addEventListener("mouseover", function() {
             iconoVolver.src = imgHoverVolver;
         });
 
         iconoVolver.addEventListener("mouseout", function() {
             iconoVolver.src = imgNormalVolver;
         });
     }


     var imgNormalFAP = "/Img/Botones%20entregas/RegistrarChofer/ACTNA.png";
    var imgHoverFAP = "/Img/Botones%20entregas/RegistrarChofer/ACTNAF.png";

    // Cambiar la imagen al pasar el mouse para FAP
    if (iconoFAP) {
        iconoFAP.addEventListener("mouseover", function() {
            iconoFAP.src = imgHoverFAP;
        });

        iconoFAP.addEventListener("mouseout", function() {
            iconoFAP.src = imgNormalFAP;
        });
    }

 });
 
 
     </script>

<body>
    
    
     <header class="header">
        <div class="logo">
         
          <img src="/Img/Botones%20entregas/Usuario/USUABL.png" alt="Estaditicas " class = "icono-AddChofer"style="max-width: 200%; height: auto;">
         
         </div>
        <nav class="navbar">
            <ul>
                <li class="nav-item"><a href='Usuarios.php' class="nav-link">
                    
                <img src="/Img/Botones entregas\RegistrarChofer\VOLVAZ.png" alt="Choferes"class = "icono-Volver"style="max-width: 5%; height: auto; position:absolute; top: 50px; left: 25px;">
                
                </a></li>
             
            </ul>
        </nav>
    </header>
<p></p>
    
    <div class ="container">
        
        <h2>Actualizar Usuario</h2>

<?php
// Conexión a la base de datos
require_once __DIR__ . "/Conexiones/Conexion.php";

// Obtener el ID del pedido a actualizar
$ID = $_GET['id'];

// Consulta SQL para obtener los datos del pedido
$sql = "SELECT * FROM usuarios WHERE ID = '$ID'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Mostrar el formulario con los datos del pedido para su actualización
    $row = $result->fetch_assoc();
    ?>
    <form action="Funcion_ActualizarUsuarios.php" method="POST">
        <input type="hidden" name="ID" value="<?php echo $ID; ?>">
        
         <label for="username">UserName:</label><br>
        <input type="text" id="username" name="username" value="<?php echo $row['username']; ?>" required><br><br>
        
        <label for="username">Nombre:</label><br>
        <input type="text" id="Nombre" name="Nombre" value="<?php echo $row['Nombre']; ?>" required><br><br>
        
     <div>
        <label for="Sucursal">Sucursal:</label><br>
<select id="Sucursal" name="Sucursal" required>
    <option value="">Selecciona una sucursal</option>
    <option value="TODAS" <?php if ($row['Sucursal'] === 'TODAS') echo 'selected'; ?>>TODAS</option>
    <option value="DIMEGSA" <?php if ($row['Sucursal'] === 'DIMEGSA') echo 'selected'; ?>>DIMEGSA</option>
    <option value="DEASA" <?php if ($row['Sucursal'] === 'DEASA') echo 'selected'; ?>>DEASA</option>
    <option value="AIESA" <?php if ($row['Sucursal'] === 'AIESA') echo 'selected'; ?>>AIESA</option>
    <option value="SEGSA" <?php if ($row['Sucursal'] === 'SEGSA') echo 'selected'; ?>>SEGSA</option>
    <option value="FESA" <?php if ($row['Sucursal'] === 'FESA') echo 'selected'; ?>>FESA</option>
    <option value="TAPATIA" <?php if ($row['Sucursal'] === 'TAPATIA') echo 'selected'; ?>>TAPATIA</option>
    <option value="GABSA" <?php if ($row['Sucursal'] === 'GABSA') echo 'selected'; ?>>GABSA</option>
    <option value="ILUMINACION" <?php if ($row['Sucursal'] === 'ILUMINACION') echo 'selected'; ?>>ILUMINACION</option>
    <option value="VALLARTA" <?php if ($row['Sucursal'] === 'VALLARTA') echo 'selected'; ?>>VALLARTA</option>
      <option value="CODI" <?php if ($row['Sucursal'] === 'CODI') echo 'selected'; ?>>CODI</option>
    <option value="QUERETARO" <?php if ($row['Sucursal'] === 'QUERETARO') echo 'selected'; ?>>QUERETARO</option>
</select><br><br>

    
       
        
        <label for="Rol">Rol:</label><br>
        <select id="Rol" name="Rol" required>
        <option value="">Selecciona ROL</option>
        <option value="Admin" <?php if ($row['Rol'] === 'Admin') echo 'selected'; ?>>Administrador</option>
         <option value="JC" <?php if ($row['Rol'] === 'JC') echo 'selected'; ?>>Jefe Choferes</option>    
          <option value="VR" <?php if ($row['Rol'] === 'VR') echo 'selected'; ?>>Vendedor</option>   

       
          
        </select><br><br>
        
        <label for="Numero">Numero:</label><br>
        <input type="text" id="Numero" name="Numero" value="<?php echo $row['Numero']; ?>" required><br><br>
        
        
       <!-- <input type="submit" value="Actualizar">-->
        

        <button type="submit" class="icono-FAP" style="background: none; border: none; padding: 0;">
            <img src="/Img/Botones%20entregas/RegistrarChofer/ACTNA.png" class="icono-FAP-img" alt="Actualizar Pedido" style="max-width: 50%; height: auto; display: flex;">
        </button>
       </div>
      <!--<?php print_r($row); ?> -->
    </form>
    <?php
} else {
    echo "No se encontró el pedido.";
}

$conn->close();
?>
        
        
        </div>
</body>
</html>