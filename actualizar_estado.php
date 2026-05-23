<?php


session_name("GA");
session_start();

// Verificar si el usuario no está logeado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    // Si no está logeado, redirigir al formulario de inicio de sesión
    header("location: /Pedidos_GA/Sesion/login.html");
    exit;
}
// Verificar si se recibieron los parámetros necesarios
if(isset($_POST['id']) && isset($_POST['estado'])) {
    // Obtener los valores del POST
    $pedidoId = $_POST['id'];
    $nuevoEstado = $_POST['estado'];

    // Establecer la conexión a la base de datos
require_once __DIR__ . "/Conexiones/Conexion.php";

    // Validación de permisos para VR (Vendedores)
    if ($_SESSION["Rol"] === "VR") {
        // Obtener el nombre del usuario actual desde la tabla usuarios
        $username = $_SESSION["username"];
        $sqlUser = "SELECT Nombre FROM usuarios WHERE username = ?";
        $stmtUser = $conn->prepare($sqlUser);
        $stmtUser->bind_param("s", $username);
        $stmtUser->execute();
        $resultUser = $stmtUser->get_result();

        if ($resultUser->num_rows > 0) {
            $userData = $resultUser->fetch_assoc();
            $nombreVendedor = $userData['Nombre'];

            // Verificar que el pedido pertenezca a este vendedor
            $sqlCheck = "SELECT VENDEDOR FROM pedidos WHERE ID = ? AND VENDEDOR = ?";
            $stmtCheck = $conn->prepare($sqlCheck);
            $stmtCheck->bind_param("is", $pedidoId, $nombreVendedor);
            $stmtCheck->execute();
            $resultCheck = $stmtCheck->get_result();

            if ($resultCheck->num_rows === 0) {
                echo "Error: No tienes permisos para modificar este pedido.";
                $conn->close();
                exit;
            }
        }
    }

    // Preparar la consulta SQL para actualizar el estado
    $sql = "UPDATE pedidos SET ESTADO = ? WHERE ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $nuevoEstado, $pedidoId);

    // Ejecutar la consulta y verificar el resultado
    if ($stmt->execute()) {
        // Sincronizar estado_factura_caja si el estado es EN RUTA o ENTREGADO
        $facturaCajaMap = ['EN RUTA' => 5, 'ENTREGADO' => 6];
        $estadoUpper = strtoupper($nuevoEstado);
        if (isset($facturaCajaMap[$estadoUpper])) {
            $nuevoFactura = $facturaCajaMap[$estadoUpper];
            // Solo avanza, nunca retrocede
            $stmtF = $conn->prepare("UPDATE pedidos SET estado_factura_caja = ? WHERE ID = ? AND estado_factura_caja < ?");
            $stmtF->bind_param("iii", $nuevoFactura, $pedidoId, $nuevoFactura);
            $stmtF->execute();
            $affected = $stmtF->affected_rows;
            $stmtF->close();

            if ($affected > 0) {
                $usrLog = isset($_SESSION["username"]) ? trim($_SESSION["username"]) : 'Web';
                $msgLog = ($nuevoFactura === 5) 
                    ? 'Factura: Cargado en Camioneta → En Ruta [Oficina]' 
                    : 'Factura: En Ruta → Entregado [Oficina]';
                $stmtH = $conn->prepare("INSERT INTO historial_cambios (Usuario_ID, Pedido_ID, Cambio, Fecha_Hora) VALUES (?, ?, ?, NOW())");
                $stmtH->bind_param("sis", $usrLog, $pedidoId, $msgLog);
                $stmtH->execute();
                $stmtH->close();
            }
        }
        echo "Estado actualizado correctamente a: " . $nuevoEstado;
    } else {
        echo "Error al actualizar el estado en la base de datos.";
    }

    // Cerrar la conexión a la base de datos
    $conn->close();
} else {
    // No se recibieron los parámetros necesarios
    echo "Faltan parámetros para actualizar el estado en la base de datos.";
}
?>
