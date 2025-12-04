<?php


session_name("GA");
session_start();

// Verificar si el usuario no está logeado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    // Si no está logeado, redirigir al formulario de inicio de sesión
    header("location: /Pedidos_GA/Sesion/login.html");
    exit;
}
// Establecer la conexión a la base de datos
require_once __DIR__ . "/Conexiones/Conexion.php";

// Verificar si se ha enviado información de búsqueda
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['busqueda'])) {
    $busqueda = $_POST['busqueda']; $sucursal = $_SESSION["Sucursal"];

    // Consulta SQL para buscar registros que contengan el término de búsqueda en varios campos
    if($sucursal === "TODAS"){
        
         $sql = "SELECT * FROM choferes 
            WHERE ID LIKE '%$busqueda%' OR 
                  SUCURSAL LIKE '%$busqueda%' OR 
                  username LIKE '%$busqueda%'";
        
    }else{
        
         $sql = "SELECT * FROM choferes 
            WHERE Sucursal = '$sucursal' AND (ID LIKE '%$busqueda%' OR 
                  SUCURSAL LIKE '%$busqueda%' OR 
                  username LIKE '%$busqueda%')";
        
    }
    
   
                  
    $result = $conn->query($sql);

    // Verificar si la consulta fue exitosa
    if ($result === false) {
        echo "Error en la consulta: " . $conn->error;
    } else {
        if ($result->num_rows > 0) {
            // Mostrar los datos encontrados en forma de tabla
            echo "<table class='mi-tabla' border='1'>";
            
            if($_SESSION["Rol"]==="Admin" || $_SESSION["Rol"] === "JC"){
                 echo "<tr><th>N°</th><th>Nombre</th><th>Numero</th><th>Sucursal</th><th>Estado</th><th>Funcion</th><th>Editar</th><th>Restablecer</th></tr>";

            }else {
                echo "<tr><th>N°</th><th>Nombre</th><th>Numero</th><th>Sucursal</th><th>Estado</th><th>Funcion</th></tr>";

            }
            
            
            
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row["ID"] . "</td>";
                echo "<td>" . $row["username"] . "</td>";
                echo "<td>" . $row["Numero"] . "</td>";
                echo "<td>" . $row["Sucursal"] . "</td>";
                echo "<td>" . $row["Estado"] . "</td>";
                echo "<td><a href='EstadisticasChofer.php?id=" . $row["username"] . "'>Ver Detalles</a></td>";

                if($_SESSION["Rol"]=== "Admin"||$_SESSION["Rol"] === "JC"){
                   echo "<td><a href='Actualizar_choferes.php?id=" . $row["ID"] . "'>

                   '<img src='/Pedidos_GA/Img/Botones%20entregas/RegistrarChofer/ACTCHOFAZ.png' alt='Estaditicas ' class = 'icono-AddChofer'style='max-width: 50%; height: auto;'>

                   </a></td>";

                   // Botón para restablecer contraseña
                   echo "<td>
                       <form method='POST' action='restablecer_password_chofer.php' style='display:inline;' onsubmit='return confirm(\"¿Estás seguro de restablecer la contraseña del chofer " . htmlspecialchars($row["username"]) . "?\");'>
                           <input type='hidden' name='chofer_id' value='" . $row["ID"] . "'>
                           <button type='submit' style='background-color: #ff9800; color: white; border: none; padding: 5px 10px; cursor: pointer; border-radius: 3px;'>
                               🔑 Restablecer
                           </button>
                       </form>
                   </td>";
                }

                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "No se encontraron resultados para la búsqueda: '$busqueda'.";
        }
    }
} else {
    echo "Por favor, ingrese un término de búsqueda.";
}

$conn->close();
?>
