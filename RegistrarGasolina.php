<?php
require_once __DIR__ . "/Conexiones/Conexion.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_vehiculo = $_POST['id_vehiculo'];
    $id_chofer = $_POST["id_chofer"]; // Se asume que el ID del usuario está en la sesión
    $fecha_registro = $_POST['fecha_registro'];
    $litros = $_POST['litros'];
    $costo = $_POST['costo'];

    $conn->query("INSERT INTO registro_gasolina (id_vehiculo, id_chofer, fecha_registro, litros, costo) 
                  VALUES ($id_vehiculo, $id_chofer, '$fecha_registro', $litros, $costo)");

    echo "<script>alert('Carga de gasolina registrada exitosamente'); window.location.href='detalles_vehiculo.php?id=$id_vehiculo';</script>";
}
?>
