<?php
session_name("GA");
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: /Pedidos_GA/Sesion/login.html");
    exit;
}

require_once __DIR__ . "/Conexiones/Conexion.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $id_pedido = $_POST['id_pedido'];

    $sql_pedido_actual = "SELECT * FROM pedidos WHERE ID = $id_pedido";
    $resultado_actual = $conn->query($sql_pedido_actual);

    if ($resultado_actual->num_rows == 0) {
        echo "Pedido no encontrado.";
        exit;
    }

    $pedido_actual = $resultado_actual->fetch_assoc();

    // Lista de campos exactos como están en tu BD
    $campos = [
    'SUCURSAL', 'ESTADO', 'FECHA_RECEPCION_FACTURA', 'FECHA_ENTREGA_CLIENTE', 
    'CHOFER_ASIGNADO', 'VENDEDOR', 'FACTURA', 'DIRECCION', 'FECHA_MIN_ENTREGA', 
    'FECHA_MAX_ENTREGA', 'MIN_VENTANA_HORARIA_1', 'MAX_VENTANA_HORARIA_1', 
    'NOMBRE_CLIENTE', 'TELEFONO', 'CONTACTO', 'COMENTARIOS', 'Coord_Origen', 'Coord_Destino', 
    'TIPO_ENVIO'
];


    $cambios_realizados = [];
    $valores_update = [];

    foreach($campos as $campo){
        $campo_post = strtolower($campo);  // Para capturarlo desde $_POST
        $nuevo_valor = isset($_POST[$campo_post]) ? trim($_POST[$campo_post]) : '';
        $valor_actual = isset($pedido_actual[$campo]) ? trim($pedido_actual[$campo]) : '';

        if($valor_actual !== $nuevo_valor){
            $cambios_realizados[] = "$campo: '$valor_actual' → '$nuevo_valor'";
        }

        $valores_update[] = "$campo = '$nuevo_valor'";
    }

    $sql_update = "UPDATE pedidos SET " . implode(", ", $valores_update) . " WHERE ID = $id_pedido";

    if ($conn->query($sql_update) === TRUE) {

        if(!empty($cambios_realizados)){
            $Usuario_ID = $_SESSION['username'];
            $Fecha_Hora = date("Y-m-d H:i:s");
            $Cambio = implode(" | ", $cambios_realizados);

            $sql_historial = "
                INSERT INTO historial_cambios (Usuario_ID, Pedido_ID, Cambio, Fecha_Hora)
                VALUES (?, ?, ?, ?)";

            if ($stmt = $conn->prepare($sql_historial)) {
                $stmt->bind_param("siss", $Usuario_ID, $id_pedido, $Cambio, $Fecha_Hora);
                $stmt->execute();
                $stmt->close();
            }
        }

        echo '<script>window.location.href = "Inicio.php?id=' . $id_pedido . '";</script>';

    } else {
        echo "Error al actualizar el pedido: " . $conn->error;
    }

} else {
    echo "No se ha recibido ningún dato para actualizar.";
}

$conn->close();
?>
