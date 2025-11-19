<?php
session_name("GA");
session_start();

// Verificar si el usuario está logeado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    echo json_encode(["success" => false, "message" => "No autorizado"]);
    exit;
}

// Verificar que sea Admin o JC
$rolSesion = $_SESSION["Rol"] ?? "";
if ($rolSesion !== "Admin" && $rolSesion !== "JC") {
    echo json_encode(["success" => false, "message" => "No tiene permisos para crear grupos"]);
    exit;
}

// Establecer la conexión a la base de datos
require_once __DIR__ . "/Conexiones/Conexion.php";

header('Content-Type: application/json');

// Verificar que sea una petición POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["success" => false, "message" => "Método no permitido"]);
    exit;
}

// Obtener datos del POST
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(["success" => false, "message" => "Datos inválidos"]);
    exit;
}

$pedidos = $input['pedidos'] ?? [];
$sucursal = $input['sucursal'] ?? '';
$chofer = $input['chofer'] ?? '';
$nombreGrupo = $input['nombre_grupo'] ?? '';
$notas = $input['notas'] ?? '';
$moverDesdeOtrosGrupos = $input['mover_desde_otros_grupos'] ?? false;

// Validaciones
if (empty($pedidos) || !is_array($pedidos)) {
    echo json_encode(["success" => false, "message" => "Debe seleccionar al menos un pedido"]);
    exit;
}

if (empty($sucursal)) {
    echo json_encode(["success" => false, "message" => "Debe seleccionar una sucursal"]);
    exit;
}

if (empty($chofer)) {
    echo json_encode(["success" => false, "message" => "Debe seleccionar un chofer"]);
    exit;
}

// VALIDACIÓN: Verificar que el chofer tenga un vehículo asignado
$sqlVerificarVehiculo = "SELECT COUNT(v.id_vehiculo) AS tiene_vehiculo
                         FROM choferes c
                         LEFT JOIN vehiculos v ON v.id_chofer_asignado = c.ID AND v.Sucursal = c.Sucursal
                         WHERE c.username = ? AND c.Estado = 'ACTIVO'
                         GROUP BY c.ID";
$stmtVerificar = $conn->prepare($sqlVerificarVehiculo);
$stmtVerificar->bind_param("s", $chofer);
$stmtVerificar->execute();
$resultVerificar = $stmtVerificar->get_result();

if ($rowVerificar = $resultVerificar->fetch_assoc()) {
    $tieneVehiculo = (int)$rowVerificar['tiene_vehiculo'];
    if ($tieneVehiculo == 0) {
        $stmtVerificar->close();
        echo json_encode([
            "success" => false,
            "message" => "El chofer '$chofer' no tiene un vehículo asignado. Debe asignarle un vehículo antes de crear un grupo."
        ]);
        exit;
    }
} else {
    $stmtVerificar->close();
    echo json_encode([
        "success" => false,
        "message" => "El chofer '$chofer' no existe o no está activo."
    ]);
    exit;
}
$stmtVerificar->close();

// VALIDACIÓN: Verificar que todos los pedidos sean de la misma sucursal
// Excepción: ILUMINACION y TAPATIA se pueden mezclar
$pedidosIds = array_column($pedidos, 'id');
$placeholders = implode(',', array_fill(0, count($pedidosIds), '?'));
$sqlValidar = "SELECT DISTINCT SUCURSAL FROM pedidos WHERE ID IN ($placeholders)";
$stmtValidar = $conn->prepare($sqlValidar);
$types = str_repeat('i', count($pedidosIds));
$stmtValidar->bind_param($types, ...$pedidosIds);
$stmtValidar->execute();
$resultValidar = $stmtValidar->get_result();

$sucursalesEncontradas = [];
while ($row = $resultValidar->fetch_assoc()) {
    $sucursalesEncontradas[] = $row['SUCURSAL'];
}
$stmtValidar->close();

// Si hay más de una sucursal, verificar si son solo ILUMINACION y TAPATIA
if (count($sucursalesEncontradas) > 1) {
    $todasPermitidas = true;
    foreach ($sucursalesEncontradas as $suc) {
        if ($suc !== 'ILUMINACION' && $suc !== 'TAPATIA') {
            $todasPermitidas = false;
            break;
        }
    }

    if (!$todasPermitidas) {
        echo json_encode([
            "success" => false,
            "message" => "No se pueden mezclar pedidos de diferentes sucursales. Solo ILUMINACION y TAPATIA pueden ir juntos."
        ]);
        exit;
    }
}

// Si no se proporciona nombre de grupo, generar uno automático
if (empty($nombreGrupo)) {
    $nombreGrupo = "Ruta " . date('Y-m-d H:i');
}

$usuarioSesion = $_SESSION["username"] ?? "Sistema";

try {
    // Iniciar transacción
    $conn->begin_transaction();

    // 1. Crear el grupo de ruta
    $sqlGrupo = "INSERT INTO grupos_rutas (nombre_grupo, sucursal, chofer_asignado, usuario_creo, notas)
                 VALUES (?, ?, ?, ?, ?)";
    $stmtGrupo = $conn->prepare($sqlGrupo);
    $stmtGrupo->bind_param("sssss", $nombreGrupo, $sucursal, $chofer, $usuarioSesion, $notas);

    if (!$stmtGrupo->execute()) {
        throw new Exception("Error al crear el grupo: " . $stmtGrupo->error);
    }

    $grupoId = $conn->insert_id;
    $stmtGrupo->close();

    // 2. Procesar cada pedido
    $pedidosActualizados = 0;
    $errores = [];

    foreach ($pedidos as $index => $pedido) {
        $pedidoId = intval($pedido['id']);
        $precioReal = floatval($pedido['precio_real']);
        $precioValidado = isset($pedido['validado']) && $pedido['validado'] ? 1 : 0;

        // Validar que el precio esté validado
        if (!$precioValidado) {
            $errores[] = "Pedido #$pedidoId: Debe validar el precio";
            continue;
        }

        // 2a. Actualizar el pedido con el chofer y validación de precio
        $sqlUpdate = "UPDATE pedidos SET
                      CHOFER_ASIGNADO = ?,
                      precio_factura_real = ?,
                      precio_validado_jc = 1,
                      fecha_validacion_precio = NOW(),
                      usuario_validacion_precio = ?
                      WHERE ID = ?";

        $stmtUpdate = $conn->prepare($sqlUpdate);
        $stmtUpdate->bind_param("sdsi", $chofer, $precioReal, $usuarioSesion, $pedidoId);

        if (!$stmtUpdate->execute()) {
            $errores[] = "Pedido #$pedidoId: " . $stmtUpdate->error;
            $stmtUpdate->close();
            continue;
        }
        $stmtUpdate->close();

        // 2b. Si se debe mover de otros grupos, remover asociaciones previas
        if ($moverDesdeOtrosGrupos) {
            $sqlRemover = "DELETE FROM pedidos_grupos WHERE pedido_id = ?";
            $stmtRemover = $conn->prepare($sqlRemover);
            $stmtRemover->bind_param("i", $pedidoId);
            $stmtRemover->execute();
            $stmtRemover->close();
        }

        // 2c. Asociar el pedido al grupo
        $ordenEntrega = $index + 1;
        $sqlAsociar = "INSERT INTO pedidos_grupos (pedido_id, grupo_id, orden_entrega)
                       VALUES (?, ?, ?)";

        $stmtAsociar = $conn->prepare($sqlAsociar);
        $stmtAsociar->bind_param("iii", $pedidoId, $grupoId, $ordenEntrega);

        if (!$stmtAsociar->execute()) {
            $errores[] = "Pedido #$pedidoId: " . $stmtAsociar->error;
            $stmtAsociar->close();
            continue;
        }
        $stmtAsociar->close();

        // 2d. Registrar en historial de cambios
        $sqlHistorial = "INSERT INTO historial_cambios (Pedido_ID, Usuario_ID, Cambio, Fecha_Hora) VALUES (?, ?, ?, NOW())";
        $descripcionCambio = "Asignado a chofer '$chofer' en grupo '$nombreGrupo'";
        $stmtHistorial = $conn->prepare($sqlHistorial);
        $stmtHistorial->bind_param("iss", $pedidoId, $usuarioSesion, $descripcionCambio);
        $stmtHistorial->execute();
        $stmtHistorial->close();

        $pedidosActualizados++;
    }

    // 3. Enviar notificación de WhatsApp al chofer (una sola vez después de procesar todos los pedidos)
    if ($pedidosActualizados > 0) {
        // Obtener teléfono del chofer
        $sqlChofer = "SELECT Numero FROM choferes WHERE username = ?";
        $stmtChofer = $conn->prepare($sqlChofer);
        $stmtChofer->bind_param("s", $chofer);
        $stmtChofer->execute();
        $resultChofer = $stmtChofer->get_result();

        if ($rowChofer = $resultChofer->fetch_assoc()) {
            $telefonoChofer = $rowChofer['Numero'];
            $telefono = "52{$telefonoChofer}";

            // TOKEN Y URL de WhatsApp (mismo que en FuncionActualizarPedido.php)
            $token = 'EAAGacaATjwEBOZBgqhohcVk1ZBGEAbiTl7i86qESvSPjdllaomwzIG7LmOOvyTFpzyIlXX6dtTYTVTLLuw6SjaLoh2rec07I8qu1nGNYSVZAmQTGNa3QCQjujTqfd7QuLLwFNQllnX2z1V7JvToDhEi5KVqUWXHSqgSETvGyU7S2SN2fpXW0NpQaRI48pwZAgGS7A1BQMjLl5ZBjy';
            $url = 'https://graph.facebook.com/v19.0/335894526282507/messages';

            // Configuración del mensaje (mismo template que cuando se asigna chofer individual)
            $mensaje = json_encode([
                "messaging_product" => "whatsapp",
                "to" => $telefono,
                "type" => "template",
                "template" => [
                    "name" => "ga_notificarchofer",
                    "language" => ["code" => "en_US"],
                ]
            ]);

            // Enviar mensaje
            $header = ["Authorization: Bearer {$token}", "Content-Type: application/json"];
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $mensaje);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            $response = json_decode(curl_exec($curl), true);
            curl_close($curl);
        }
        $stmtChofer->close();
    }

    // Commit de la transacción
    $conn->commit();

    $response = [
        "success" => true,
        "message" => "Grupo creado exitosamente",
        "grupo_id" => $grupoId,
        "nombre_grupo" => $nombreGrupo,
        "pedidos_actualizados" => $pedidosActualizados,
        "total_pedidos" => count($pedidos)
    ];

    if (!empty($errores)) {
        $response['errores'] = $errores;
        $response['message'] = "Grupo creado con algunos errores";
    }

    echo json_encode($response);

} catch (Exception $e) {
    // Rollback en caso de error
    $conn->rollback();
    echo json_encode([
        "success" => false,
        "message" => "Error al crear el grupo: " . $e->getMessage()
    ]);
}

$conn->close();
?>
