<?php
// Iniciar la sesión
session_name("GA");
session_start();

// Verificar si el usuario está logeado y tiene permisos
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Solo Admin y JC pueden usar esta funcionalidad
if (!in_array($_SESSION["Rol"], ["Admin", "JC"])) {
    echo json_encode(['success' => false, 'message' => 'No tiene permisos para realizar esta acción']);
    exit;
}

// Conexión a la base de datos
require_once __DIR__ . "/Conexiones/Conexion.php";

// Obtener datos JSON del request
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!isset($data['pedidos']) || !is_array($data['pedidos']) || count($data['pedidos']) === 0) {
    echo json_encode(['success' => false, 'message' => 'No se recibieron pedidos para actualizar']);
    exit;
}

$pedidos = $data['pedidos'];
$exitosos = 0;
$errores = 0;
$detallesErrores = [];

$usuario_validacion = $_SESSION["Nombre"];
$fecha_validacion = date('Y-m-d H:i:s');

// Procesar cada pedido
foreach ($pedidos as $pedido) {
    $id = intval($pedido['id']);
    $precioReal = floatval($pedido['precioReal']);
    $validarPrecio = isset($pedido['validarPrecio']) && $pedido['validarPrecio'] ? 1 : 0;
    $sucursalChofer = $pedido['sucursalChofer'] ?? '';
    $chofer = $pedido['chofer'] ?? '';

    // Validaciones
    if ($id <= 0) {
        $errores++;
        $detallesErrores[] = "ID de pedido inválido: $id";
        continue;
    }

    if ($precioReal <= 0) {
        $errores++;
        $detallesErrores[] = "Precio inválido para pedido #$id";
        continue;
    }

    // Iniciar transacción
    $conn->begin_transaction();

    try {
        // Obtener datos actuales del pedido
        $sqlSelect = "SELECT CHOFER_ASIGNADO, precio_factura_real, precio_validado_jc FROM pedidos WHERE ID = ?";
        $stmtSelect = $conn->prepare($sqlSelect);
        $stmtSelect->bind_param("i", $id);
        $stmtSelect->execute();
        $resultSelect = $stmtSelect->get_result();

        if ($resultSelect->num_rows === 0) {
            throw new Exception("Pedido #$id no encontrado");
        }

        $pedidoActual = $resultSelect->fetch_assoc();
        $choferAnterior = $pedidoActual['CHOFER_ASIGNADO'];
        $precioAnterior = $pedidoActual['precio_factura_real'];
        $validadoAnterior = $pedidoActual['precio_validado_jc'];
        $stmtSelect->close();

        // Construir query de actualización
        $campos = [];
        $tipos = "";
        $valores = [];

        // Siempre actualizar precio real
        $campos[] = "precio_factura_real = ?";
        $tipos .= "d";
        $valores[] = $precioReal;

        // Actualizar validación de precio
        if ($validarPrecio == 1) {
            $campos[] = "precio_validado_jc = ?";
            $campos[] = "fecha_validacion_precio = ?";
            $campos[] = "usuario_validacion_precio = ?";
            $tipos .= "iss";
            $valores[] = 1;
            $valores[] = $fecha_validacion;
            $valores[] = $usuario_validacion;
        }

        // Actualizar chofer si se proporcionó
        if (!empty($chofer)) {
            $campos[] = "CHOFER_ASIGNADO = ?";
            $tipos .= "s";
            $valores[] = $chofer;
        }

        // Agregar ID al final
        $tipos .= "i";
        $valores[] = $id;

        // Ejecutar actualización
        $sqlUpdate = "UPDATE pedidos SET " . implode(", ", $campos) . " WHERE ID = ?";
        $stmtUpdate = $conn->prepare($sqlUpdate);
        $stmtUpdate->bind_param($tipos, ...$valores);

        if (!$stmtUpdate->execute()) {
            throw new Exception("Error al actualizar pedido #$id: " . $stmtUpdate->error);
        }
        $stmtUpdate->close();

        // Registrar en historial de cambios
        $cambios = [];

        if ($precioReal != $precioAnterior) {
            $cambios[] = "Precio real cambiado de $" . number_format($precioAnterior, 2) . " a $" . number_format($precioReal, 2);
        }

        if ($validarPrecio == 1 && $validadoAnterior != 1) {
            $cambios[] = "Precio validado por JC";
        }

        if (!empty($chofer) && $chofer != $choferAnterior) {
            $cambios[] = "Chofer asignado: $chofer" . (!empty($choferAnterior) ? " (anterior: $choferAnterior)" : "");
        }

        if (count($cambios) > 0) {
            $descripcionCambio = implode(" | ", $cambios);
            $sqlHistorial = "INSERT INTO historial_cambios (Pedido_ID, Usuario_ID, Cambio, Fecha_Hora) VALUES (?, ?, ?, NOW())";
            $stmtHistorial = $conn->prepare($sqlHistorial);
            $stmtHistorial->bind_param("iss", $id, $usuario_validacion, $descripcionCambio);
            $stmtHistorial->execute();
            $stmtHistorial->close();
        }

        // Enviar notificación de WhatsApp si se asignó chofer
        if (!empty($chofer) && $chofer != $choferAnterior) {
            // Obtener datos completos del pedido para la notificación
            $sqlPedido = "SELECT * FROM pedidos WHERE ID = ?";
            $stmtPedido = $conn->prepare($sqlPedido);
            $stmtPedido->bind_param("i", $id);
            $stmtPedido->execute();
            $resultPedido = $stmtPedido->get_result();
            $pedidoCompleto = $resultPedido->fetch_assoc();
            $stmtPedido->close();

            // Obtener teléfono del chofer
            $sqlChofer = "SELECT Numero FROM choferes WHERE username = ?";
            $stmtChofer = $conn->prepare($sqlChofer);
            $stmtChofer->bind_param("s", $chofer);
            $stmtChofer->execute();
            $resultChofer = $stmtChofer->get_result();

            if ($resultChofer->num_rows > 0) {
                $choferData = $resultChofer->fetch_assoc();
                $numero_chofer = $choferData['Numero'];
                $telefono = '52' . $numero_chofer;

                // TOKEN Y URL de WhatsApp (API de Facebook)
                $token = 'EAAGacaATjwEBOZBgqhohcVk1ZBGEAbiTl7i86qESvSPjdllaomwzIG7LmOOvyTFpzyIlXX6dtTYTVTLLuw6SjaLoh2rec07I8qu1nGNYSVZAmQTGNa3QCQjujTqfd7QuLLwFNQllnX2z1V7JvToDhEi5KVqUWXHSqgSETvGyU7S2SN2fpXW0NpQaRI48pwZAgGS7A1BQMjLl5ZBjy';
                $url = 'https://graph.facebook.com/v19.0/335894526282507/messages';

                // Configuración del mensaje usando template
                $mensaje = json_encode([
                    "messaging_product" => "whatsapp",
                    "to" => $telefono,
                    "type" => "template",
                    "template" => [
                        "name" => "ga_notificarchofer",
                        "language" => ["code" => "en_US"],
                    ]
                ]);

                // Enviar mensaje via cURL
                $header = ["Authorization: Bearer " . $token, "Content-Type: application/json"];
                $curl = curl_init();
                curl_setopt($curl, CURLOPT_URL, $url);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $mensaje);
                curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                $response = json_decode(curl_exec($curl), true);
                $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                curl_close($curl);

                // Registrar en historial
                $cambioWP = "Notificación WhatsApp enviada al chofer $chofer";
                if ($httpCode == 200 && isset($response['messages'])) {
                    $cambioWP .= " (Enviado exitosamente)";
                } else {
                    $cambioWP .= " (Error al enviar)";
                }

                $sqlHistorialWP = "INSERT INTO historial_cambios (Pedido_ID, Usuario_ID, Cambio, Fecha_Hora) VALUES (?, ?, ?, NOW())";
                $stmtHistorialWP = $conn->prepare($sqlHistorialWP);
                $stmtHistorialWP->bind_param("iss", $id, $usuario_validacion, $cambioWP);
                $stmtHistorialWP->execute();
                $stmtHistorialWP->close();
            }
            $stmtChofer->close();
        }

        // Confirmar transacción
        $conn->commit();
        $exitosos++;

    } catch (Exception $e) {
        // Revertir transacción en caso de error
        $conn->rollback();
        $errores++;
        $detallesErrores[] = $e->getMessage();
    }
}

$conn->close();

// Responder con resultado
$response = [
    'success' => ($exitosos > 0),
    'exitosos' => $exitosos,
    'errores' => $errores,
    'message' => "Se procesaron $exitosos pedido(s) correctamente" . ($errores > 0 ? " con $errores error(es)" : ""),
    'detalles_errores' => $detallesErrores,
    'debug' => true // Activar modo debug
];

header('Content-Type: application/json');
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
