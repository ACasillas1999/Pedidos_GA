<?php
require_once __DIR__ . "/Conexiones/Conexion.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_vehiculo = $_POST['id_vehiculo'];
    $id_chofer = $_POST['id_chofer'];
    $fecha_servicio = $_POST['fecha_servicio'];
    $kilometraje_inicial = $_POST['kilometraje_inicial'];
    $kilometraje_final = $_POST['kilometraje_final'];
    $tipo_servicio = $_POST['tipo_servicio'];
    $detalles = $_POST['detalles'] ?? '';
    $observaciones = $_POST['observaciones'] ?? '';
    $reiniciar_km = isset($_POST['reiniciar_km']) ? 1 : 0;

    $conn->begin_transaction(); // Iniciar transacción

    try {
        // Registrar el servicio en `registro_kilometraje`
        $query = "INSERT INTO registro_kilometraje (id_vehiculo, id_chofer, Tipo_Registro, fecha_registro, kilometraje_inicial, kilometraje_final) 
                  VALUES (?, ?, 'Servicio', ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iissi", $id_vehiculo, $id_chofer, $fecha_servicio, $kilometraje_inicial, $kilometraje_final);
        $stmt->execute();
        $id_servicio = $stmt->insert_id; // Obtener el ID del servicio registrado

        // Registrar los detalles en `servicios_detalle`
        $query_detalle = "INSERT INTO servicios_detalle (id_servicio, tipo_servicio, detalles, reiniciar_km, observaciones) 
                          VALUES (?, ?, ?, ?, ?)";
        $stmt_detalle = $conn->prepare($query_detalle);
        $stmt_detalle->bind_param("issis", $id_servicio, $tipo_servicio, $detalles, $reiniciar_km, $observaciones);
        $stmt_detalle->execute();

        // Si el usuario seleccionó reiniciar el kilometraje, actualizar `Km_Actual`
        if ($reiniciar_km) {
            $query_update = "UPDATE vehiculos SET Km_Actual = 0, Fecha_Ultimo_Servicio = ? WHERE id_vehiculo = ?";
            $stmt_update = $conn->prepare($query_update);
            $stmt_update->bind_param("si", $fecha_servicio, $id_vehiculo);
            $stmt_update->execute();
        }

        $conn->commit(); // Confirmar transacción
        echo "<script>alert('Servicio registrado exitosamente'); window.location.href='detalles_vehiculo.php?id=$id_vehiculo';</script>";
    } catch (Exception $e) {
        $conn->rollback(); // Revertir cambios en caso de error
        die("Error al registrar el servicio: " . $e->getMessage());
    }
}
?>
