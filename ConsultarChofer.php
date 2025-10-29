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

// Verificar si se ha enviado información de actualización
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['nombre'])) {
    $nombre = $_POST['nombre'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    
    // Consulta a la base de datos filtrando por el chofer asignado y el rango de fechas
    $sql = "SELECT * FROM pedidos WHERE CHOFER_ASIGNADO = (
                SELECT username FROM choferes WHERE username = '$nombre'
            ) AND FECHA_RECEPCION_FACTURA BETWEEN '$start_date' AND '$end_date'
            ORDER BY FECHA_RECEPCION_FACTURA";
    
    $result = $conn->query($sql);

    // Verificar si la consulta fue exitosa
    if ($result === false) {
        echo "Error en la consulta: " . $conn->error;
    } else {
        if ($result->num_rows > 0) {
            // Mostrar los datos encontrados en forma de tabla
            echo "<table class='mi-tabla' border='1'>";
            echo "<tr><th>N°</th><th>Estado</th><th>Sucursal</th><th>Fecha Recepción Factura</th><th>Chofer Asignado</th><th>Vendedor</th><th>Factura</th><th>Dirección</th><th>Nombre Cliente</th><th>Contacto</th><th>Acción</th></tr>";
            
            while ($row = $result->fetch_assoc()) {
                // Definir el color de fondo según el estado
                $estado = $row["ESTADO"];
                $color = "#FFFFFF"; // Color por defecto (blanco)

                switch (strtoupper($estado)) {
                    case "CANCELADO":
                        $color = "#FFCCCC"; // ROJO pastel
                        break;
                    case "EN TIENDA":
                        $color = "#FFFFCC"; // AMARILLO pastel
                        break;
                    case "REPROGRAMADO":
                        $color = "#E6CCFF"; // MORADO pastel
                        break;
                    case "ACTIVO":
                        $color = "#CCE5FF"; // AZUL pastel
                        break;
                    case "EN RUTA":
                        $color = "#FFD699"; // NARANJA pastel
                        break;
                    case "ENTREGADO":
                        $color = "#CCFFCC"; // VERDE pastel
                        break;
                }

                echo "<tr>";
                echo "<td>" . $row["ID"] . "</td>";
                echo "<td style='background-color: $color;'>" . $estado . "</td>";
                echo "<td>" . $row["SUCURSAL"] . "</td>";
                echo "<td>" . $row["FECHA_RECEPCION_FACTURA"] . "</td>";
                echo "<td>" . $row["CHOFER_ASIGNADO"] . "</td>";
                echo "<td>" . $row["VENDEDOR"] . "</td>";
                echo "<td>" . $row["FACTURA"] . "</td>";
                echo "<td>" . $row["DIRECCION"] . "</td>";
                echo "<td>" . $row["NOMBRE_CLIENTE"] . "</td>";
                echo "<td>" . $row["CONTACTO"] . "</td>";
                echo "<td><a href='Inicio.php?id=" . $row["ID"] . "'>Ver Detalles</a></td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "No se encontraron resultados.";
        }
    }
}

$conn->close();
?>
