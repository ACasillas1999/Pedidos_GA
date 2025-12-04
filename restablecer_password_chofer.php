<?php
session_name("GA");
session_start();

// Verificar si el usuario está logeado y es Admin o JC
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: /Pedidos_GA/Sesion/login.html");
    exit;
}

if ($_SESSION["Rol"] !== "Admin" && $_SESSION["Rol"] !== "JC") {
    echo '<script>alert("No tienes permisos para restablecer contraseñas."); window.history.back();</script>';
    exit;
}

require_once __DIR__ . "/Conexiones/Conexion.php";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['chofer_id'])) {
    $chofer_id = (int)$_POST['chofer_id'];

    // Obtener información del chofer
    $sql = "SELECT username, Numero FROM choferes WHERE ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $chofer_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $chofer = $result->fetch_assoc();
        $username = $chofer['username'];
        $numero = $chofer['Numero'];

        // Generar nueva contraseña: primeras 4 letras del nombre + últimos 4 dígitos del teléfono
        $primeras_letras = substr($username, 0, 4);
        $ultimos_digitos = substr($numero, -4);
        $nueva_password = $primeras_letras . $ultimos_digitos;

        // Actualizar la contraseña (sin hash, como lo manejas actualmente)
        $sql_update = "UPDATE choferes SET password = ? WHERE ID = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("si", $nueva_password, $chofer_id);

        if ($stmt_update->execute()) {
            // Registrar en historial (opcional)
            $usuario_admin = $_SESSION['username'];
            $cambio = "Contraseña restablecida para chofer: $username. Nueva contraseña: $nueva_password";

            echo '<script>
                alert("Contraseña restablecida exitosamente.\\n\\nChofer: ' . $username . '\\nNueva contraseña: ' . $nueva_password . '\\n\\nPor favor, anota esta contraseña y entrégala al chofer.");
                window.location.href = "Choferes.php";
            </script>';
        } else {
            echo '<script>alert("Error al restablecer la contraseña: ' . $conn->error . '"); window.history.back();</script>';
        }

        $stmt_update->close();
    } else {
        echo '<script>alert("Chofer no encontrado."); window.history.back();</script>';
    }

    $stmt->close();
} else {
    echo '<script>alert("Solicitud inválida."); window.history.back();</script>';
}

$conn->close();
?>
