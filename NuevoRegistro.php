<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Iniciar la sesión
session_name("GA");
session_start();

// Verificar si el usuario no está logeado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    // Si no está logeado, redirigir al formulario de inicio de sesión
    header("location: /Pedidos_GA/Sesion/login.html");
    exit;
}
?>

<?php

  function validarFecha($fecha) {
    return (!empty($fecha) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) ? "'$fecha'" : "NULL";
}
// Conexión a la base de datos
require_once __DIR__ . "/Conexiones/Conexion.php";


// Obtener los datos del formulario
$sucursal = $_POST['sucursal'];
$estado = $_POST['estado'];
$fecha_recepcion_factura = $_POST['fecha_recepcion_factura'];
$fecha_entrega_cliente = $_POST['fecha_entrega_cliente'];
$chofer_asignado = $_POST['chofer_asignado'];
$vendedor = $_POST['vendedor'];
$factura = $_POST['factura'];
$direccion = $_POST['direccion'];
$fecha_min_entrega = $_POST['fecha_min_entrega'];
$fecha_max_entrega = $_POST['fecha_max_entrega'];
$min_ventana_horaria_1 = $_POST['min_ventana_horaria_1'];
$max_ventana_horaria_1 = $_POST['max_ventana_horaria_1'];
$nombre_cliente = $_POST['nombre_cliente'];
$telefono = $_POST['telefono'];
$contacto = $_POST['contacto'];
$comentarios = $_POST['comentarios'];
$coord_origen = $_POST['coord_origen'];
$coord_destino = $_POST['coord_destino'];
$tipo_envio = $_POST['tipo_envio'];
$precio_factura_vendedor = isset($_POST['precio_factura_vendedor']) ? floatval($_POST['precio_factura_vendedor']) : NULL;

// Consulta SQL para insertar los datos
$ruta = $_POST['ruta'] ?? ''; // Por si el campo no se envía

$sql = "INSERT INTO pedidos (
    SUCURSAL, ESTADO, FECHA_RECEPCION_FACTURA, FECHA_ENTREGA_CLIENTE,
    CHOFER_ASIGNADO, VENDEDOR, FACTURA, DIRECCION,
    FECHA_MIN_ENTREGA, FECHA_MAX_ENTREGA,
    MIN_VENTANA_HORARIA_1, MAX_VENTANA_HORARIA_1,
    NOMBRE_CLIENTE, TELEFONO, CONTACTO, COMENTARIOS,
    Coord_Origen, Coord_Destino, tipo_envio, Ruta,
    precio_factura_vendedor, precio_factura_real, precio_validado_jc
) VALUES (
    '$sucursal', '$estado', " . validarFecha($fecha_recepcion_factura) . ", " . validarFecha($fecha_entrega_cliente) . ",
    '$chofer_asignado', '$vendedor', '$factura', '$direccion',
    " . validarFecha($fecha_min_entrega) . ", " . validarFecha($fecha_max_entrega) . ",
    '$min_ventana_horaria_1', '$max_ventana_horaria_1',
    '$nombre_cliente', '$telefono', '$contacto', '$comentarios',
    '$coord_origen', '$coord_destino', '$tipo_envio', '$ruta',
    " . ($precio_factura_vendedor !== NULL ? $precio_factura_vendedor : "NULL") . ",
    " . ($precio_factura_vendedor !== NULL ? $precio_factura_vendedor : "NULL") . ",
    0
)";

if ($conn->query($sql) === TRUE) {
    $last_id = $conn->insert_id; // Obtener el ID del último pedido insertado
   // include "/Pedidos_GA/Mensajes_WP/Mensaje_WP_Notificacion.php"; // O require "Mensaje_WP.php";
    echo '<script>alert("Agregado correctamente.");';
    
    echo 'window.location.href = "/Pedidos_GA/Mensajes_WP/Mensaje_WP_Notificacion.php?pedido_id=' . $last_id . '";</script>';
    //echo 'window.location.href = "Inicio.php?id=' . $last_id . '";</script>';
} else {
    echo "Error al agregar el pedido: " . $conn->error;
}

$conn->close();
?>

