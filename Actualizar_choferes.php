<?php
// Iniciar la sesión
session_name("GA");
session_start();

// Verificar si el usuario no está logeado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    // Si no está logeado, redirigir al formulario de inicio de sesión
     header("location: /RH%20VACACIONES/Sesion/login.html");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="/Pedidos_GA/Img/Botones%20entregas/ICONOSPAG/ICONOPEDIDOS.png">
    <link rel="stylesheet" href="styles7.css">
    <title>Formulario</title>
</head>


<script>
    document.addEventListener("DOMContentLoaded", function() {
     
     var iconoVolver = document.querySelector(".icono-Volver");
     var iconoFAP = document.querySelector(".icono-FAP-img");
 
     
 
     var imgNormalVolver = "/Pedidos_GA/Img/Botones%20entregas/RegistrarChofer/VOLVAZ.png";
     var imgHoverVolver = "/Pedidos_GA/Img/Botones%20entregas/RegistrarChofer/VOLVNA.png";
 
     // Cambiar la imagen al pasar el mouse para Volver
     if (iconoVolver) {
         iconoVolver.addEventListener("mouseover", function() {
             iconoVolver.src = imgHoverVolver;
         });
 
         iconoVolver.addEventListener("mouseout", function() {
             iconoVolver.src = imgNormalVolver;
         });
     }


     var imgNormalFAP = "/Pedidos_GA/Img/Botones%20entregas/RegistrarChofer/ACTNA.png";
    var imgHoverFAP = "/Pedidos_GA/Img/Botones%20entregas/RegistrarChofer/ACTNAF.png";

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
         
         <img src="\Pedidos_GA\Img\Botones entregas\RegistrarChofer\CHOFBL.png" alt="Estaditicas " class = "icono-AddChofer"style="max-width: 20%; height: auto;">
         
         </div>
        <nav class="navbar">
            <ul>
                <li class="nav-item"><a href='Choferes.php' class="nav-link">
                    
                <img src="\Pedidos_GA\Img\Botones entregas\RegistrarChofer\VOLVAZ.png" alt="Choferes"class = "icono-Volver"style="max-width: 5%; height: auto; position:absolute; top: 50px; left: 25px;">
                

                </a></li>
             
            </ul>
        </nav>
    </header>
<p></p>
    
    <div class ="container">
        
        <h2>Actualizar Chofer</h2>

<?php
// Conexión a la base de datos
require_once __DIR__ . "/Conexiones/Conexion.php";

// Obtener el ID del pedido a actualizar
$ID = $_GET['id'];

// Consulta SQL para obtener los datos del pedido
$sql = "SELECT * FROM choferes WHERE ID = '$ID'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Mostrar el formulario con los datos del pedido para su actualización
    $row = $result->fetch_assoc();
    ?>
    <form action="Funcion_ActualizarChofer.php" method="POST">
        <input type="hidden" name="ID" value="<?php echo $ID; ?>">
        
         <label for="username">Nombre:</label><br>
        <input type="text" id="username" name="username" value="<?php echo $row['username']; ?>" required><br><br>
        
        
          <label for="Numero">Numero:</label><br>
        <input type="text" id="Numero" name="Numero" value="<?php echo $row['Numero']; ?>" required><br><br>
        
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


<label for="Estado">Estado:</label><br>
<select id="Estado" name="Estado" required>
    <option value="">Selecciona Estado</option>
    <option value="ACTIVO" <?php if ($row['Estado'] === 'ACTIVO') echo 'selected'; ?>>ACTIVO</option>
    <option value="INACTIVO" <?php if ($row['Estado'] === 'INACTIVO') echo 'selected'; ?>>INACTIVO</option>
   
</select><br><br>
 
       <!-- <input src="/Pedidos_GA/Img/Botones%2520entregas/RegistrarChofer/ACTNA.png" type="submit" value="Actualizar">-->
        <button type="submit" class="icono-FAP" style="background: none; border: none; padding: 0;">
                <img src="/Pedidos_GA/Img/Botones%20entregas/RegistrarChofer/ACTNA.png" class="icono-FAP-img" alt="Actualizar Pedido" style="max-width: 50%; height: auto; display: flex;">
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