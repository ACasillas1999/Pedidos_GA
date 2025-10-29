/*<?php
// Iniciar la sesión
session_name("GA");
session_start();

// Verificar si el usuario no está logeado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    // Si no está logeado, redirigir al formulario de inicio de sesión
    header("location: /Pedidos_GA/Sesion/login.html");
    exit;
}
?>*/

<?php
// Establecer la conexión a la base de datos
require_once __DIR__ . "/Conexiones/Conexion.php";

// Verificar si se ha enviado información de actualización
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['nombre']) && isset($_POST['status'])) {
    $nombre = $_POST['nombre'];
    $status = $_POST['status'];
    
    // Actualizar el estado en la base de datos
    $sql = "UPDATE Pedidos SET ESTADO='$status' WHERE NOMBRE_CLIENTE='$nombre'";
    $result = $conn->query($sql);
    
    if ($result === true) {
        echo "Estado actualizado correctamente";
        echo "<script>
                setTimeout(function(){
                    document.getElementById('mensaje').style.display = 'none';
                }, 3000); // 3000 milisegundos = 3 segundos
              </script>";
    } else {
        echo "Error al actualizar el estado: " . $conn->error;
    }
} else {
    // Consulta a la base de datos
    $sql = "SELECT * FROM Pedidos";
    $result = $conn->query($sql);

    // Verificar si la consulta fue exitosa
    if ($result === false) {
        echo "Error en la consulta: " . $conn->error;
    } else {
       if ($result->num_rows > 0) {
            echo "<style>
                .envio-programado { background-color: #D4EFDF; font-weight: bold; color: #145A32; }
                .envio-paqueteria { background-color: #D6EAF8; font-weight: bold; color: #1B4F72; }
                    .envio-domicilio { background-color: #FFF9C4; font-weight: bold; color: #7D6608; }

            </style>";

            echo "<table class='mi-tabla' border='1'>";
            echo "<tr>
                    <th>Tipo de Envío</th>
                    <th>Estado</th>
                    <th>Fecha Recepción Factura</th>
                    <th>Fecha Entrega Cliente</th>
                    <th>Chofer Asignado</th>
                    <th>Vendedor</th>
                    <th>Factura</th>
                    <th>Dirección</th>
                    <th>Fecha Mínima de Entrega</th>
                    <th>Fecha Máxima de Entrega</th>
                    <th>Min Ventana Horaria</th>
                    <th>Max Ventana Horaria</th>
                    <th>Nombre Cliente</th>
                    <th>Teléfono</th>
                    <th>Contacto</th>
                    <th>Comentarios</th>
                  </tr>";

            while ($row = $result->fetch_assoc()) {
                $tipo_envio = $row["tipo_envio"] ?? '';
                $clase_tipo = '';

                switch (strtolower($tipo_envio)) {
    case "programado":
        $clase_tipo = 'envio-programado';
        break;
    case "paquetería":
    case "paqueteria":
        $clase_tipo = 'envio-paqueteria';
        break;
    case "domicilio":
        $clase_tipo = 'envio-domicilio';
        break;
}

                echo "<tr>";
                echo "<td class='$clase_tipo'>" . htmlspecialchars($tipo_envio) . "</td>";
                echo "<td>" . $row["ESTADO"] . "</td>";
                echo "<td>" . $row["FECHA_RECEPCION_FACTURA"] . "</td>";
                echo "<td>" . $row["FECHA_ENTREGA_CLIENTE"] . "</td>";
                echo "<td>" . $row["CHOFER_ASIGNADO"] . "</td>";
                echo "<td>" . $row["VENDEDOR"] . "</td>";
                echo "<td>" . $row["FACTURA"] . "</td>";
                echo "<td>" . $row["DIRECCION"] . "</td>";
                echo "<td>" . $row["FECHA_MIN_ENTREGA"] . "</td>";
                echo "<td>" . $row["FECHA_MAX_ENTREGA"] . "</td>";
                echo "<td>" . $row["MIN_VENTANA_HORARIA_1"] . "</td>";
                echo "<td>" . $row["MAX_VENTANA_HORARIA_1"] . "</td>";
                echo "<td>" . $row["NOMBRE_CLIENTE"] . "</td>";
                echo "<td>" . $row["TELEFONO"] . "</td>";
                echo "<td>" . $row["CONTACTO"] . "</td>";
                echo "<td>" . $row["COMENTARIOS"] . "</td>";
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