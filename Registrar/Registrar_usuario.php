<?php
// Iniciar la sesión
session_name("GA");
session_start();

// Verificar si el usuario no está logeado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    // Si no está logeado, redirigir al formulario de inicio de sesión
    header("location: /Pedidos_GA/Sesion/login.html");
    exit;
}

// Verificar si se han enviado los datos de registro
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Conectar a la base de datos (reemplaza los valores con los tuyos)
    require_once __DIR__ . "/../Conexiones/Conexion.php";

    // Obtener los datos del formulario
    $username = $_POST["username"];
     $Nombre = $_POST["Nombre"];
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];
     $Rol = $_POST["Rol"];
     $Numero = $_POST["Numero"];
    $Sucursal = $_POST["Sucursal"];
   

    // Verificar si las contraseñas coinciden
    if ($password != $confirm_password) {
        $error_message = "Las contraseñas no coinciden.";
    } else {
        // Cifrar la contraseña
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Obtener la fecha actual
        $fecha_registro = date("Y-m-d H:i:s");

        // Insertar el usuario en la base de datos
        $sql = "INSERT INTO usuarios (username, Nombre, password, Rol, Numero,Sucursal,fecha_registro) VALUES (?, ?, ? , ?, ? ,?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssss", $username,$Nombre, $hashed_password,$Rol, $Numero,$Sucursal, $fecha_registro);

        if ($stmt->execute()) {
            // Usuario registrado correctamente
            header("location: Registro_exitoso.html"); // Redirigir a la página de registro exitoso
            exit;
        } else {
            $error_message = "Error al registrar el usuario.";
        }

        $stmt->close();
    }

    $conn->close();
}
?>
