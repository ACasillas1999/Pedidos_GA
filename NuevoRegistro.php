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


// Obtener los datos del formulario (datos comunes)
$sucursal = $_POST['sucursal'];
$estado = $_POST['estado'];
$fecha_recepcion_factura = $_POST['fecha_recepcion_factura'];
$fecha_entrega_cliente = $_POST['fecha_entrega_cliente'];
$chofer_asignado = $_POST['chofer_asignado'];
$vendedor = $_POST['vendedor'];
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
$ruta = $_POST['ruta'] ?? ''; // Por si el campo no se envía

// Obtener los arrays de facturas y precios
$facturas = isset($_POST['factura']) ? $_POST['factura'] : [];
$precios = isset($_POST['precio_factura_vendedor']) ? $_POST['precio_factura_vendedor'] : [];

// Validar que ambos arrays tengan la misma cantidad de elementos
if (count($facturas) !== count($precios)) {
    echo '<script>alert("Error: La cantidad de facturas no coincide con la cantidad de precios.");';
    echo 'window.history.back();</script>';
    exit;
}

// Validar que haya al menos una factura
if (count($facturas) === 0) {
    echo '<script>alert("Error: Debe agregar al menos una factura.");';
    echo 'window.history.back();</script>';
    exit;
}

// Variable para guardar el ID del primer pedido creado
$primer_pedido_id = null;
$pedidos_creados = 0;
$errores = [];

// Iterar sobre cada factura y crear un registro
for ($i = 0; $i < count($facturas); $i++) {
    $factura = $conn->real_escape_string(trim($facturas[$i]));
    $precio_factura_vendedor = floatval($precios[$i]);

    // Validar que la factura no esté vacía
    if (empty($factura)) {
        $errores[] = "La factura en la posición " . ($i + 1) . " está vacía";
        continue;
    }

    // Consulta SQL para insertar cada pedido
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
        " . ($precio_factura_vendedor > 0 ? $precio_factura_vendedor : "NULL") . ",
        " . ($precio_factura_vendedor > 0 ? $precio_factura_vendedor : "NULL") . ",
        0
    )";

    if ($conn->query($sql) === TRUE) {
        $pedidos_creados++;
        // Guardar el ID del primer pedido para la redirección
        if ($primer_pedido_id === null) {
            $primer_pedido_id = $conn->insert_id;
        }
    } else {
        $errores[] = "Error al agregar factura '$factura': " . $conn->error;
    }
}

$conn->close();

// Mostrar resultado
if ($pedidos_creados > 0) {
    $mensaje = "Se crearon $pedidos_creados pedido(s) correctamente.";
    if (count($errores) > 0) {
        $mensaje .= "\\n\\nErrores encontrados:\\n" . implode("\\n", $errores);
    }
    echo '<script>alert("' . $mensaje . '");';
    echo 'window.location.href = "/Pedidos_GA/Mensajes_WP/Mensaje_WP_Notificacion.php?pedido_id=' . $primer_pedido_id . '";</script>';
} else {
    echo '<script>alert("Error: No se pudo crear ningún pedido.\\n' . implode("\\n", $errores) . '");';
    echo 'window.history.back();</script>';
}
?>

