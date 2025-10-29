<?php


session_name("GA");
session_start();

// Verificar si el usuario no está logeado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    // Si no está logeado, redirigir al formulario de inicio de sesión
    header("location: /Pedidos_GA/Sesion/login.html");
    exit;
}



// Conexión a la base de datos
require_once __DIR__ . "/Conexiones/Conexion.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtener los datos del formulario
   
    $username = $_POST['username'];
     $Nombre = $_POST['Nombre'];
    $Sucursal = $_POST['Sucursal'];
    $Rol = $_POST['Rol'];
    $Numero = $_POST['Numero'];
      $ID = $_POST['ID'];
    
    

    // Consulta SQL para actualizar los datos del pedido
    $sql = "UPDATE usuarios SET 
            username = '$username', 
             Nombre = '$Nombre', 
            Sucursal = '$Sucursal', 
            Numero = '$Numero', 
            Rol = '$Rol' 
           
            WHERE ID = '$ID'";

    if ($conn->query($sql) === TRUE) {
      // echo '<script>alert("Jefe actualizado correctamente.");';
       echo '<script>window.location.href = "Usuarios.php";</script>';




    } else {
        echo "Error al actualizar jefe: " . $conn->error;
    }
} else {
    echo "No se ha recibido ningún dato para actualizar.";
}

$conn->close();
?>
