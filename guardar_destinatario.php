<?php
// guardar_destinatario.php
// API para guardar/actualizar datos del destinatario de paquetería
declare(strict_types=1);

session_name("GA");
session_start();

// Verificar autenticación
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "No autenticado"]);
    exit;
}

// Verificar que sea rol JC o Admin
$rolPermitido = in_array($_SESSION["Rol"] ?? '', ["Admin", "JC"]);
if (!$rolPermitido) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "No tienes permisos para esta acción"]);
    exit;
}

header('Content-Type: application/json');

require_once __DIR__ . "/Conexiones/Conexion.php";

// Validar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Método no permitido"]);
    exit;
}

// Obtener datos JSON
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "JSON inválido"]);
    exit;
}

// Validar pedido_id
$pedido_id = isset($data['pedido_id']) ? intval($data['pedido_id']) : 0;
if ($pedido_id <= 0) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "ID de pedido inválido"]);
    exit;
}

// Verificar que el pedido existe y es de tipo paquetería
$sqlCheck = "SELECT tipo_envio FROM pedidos WHERE ID = ?";
$stmtCheck = $conn->prepare($sqlCheck);
$stmtCheck->bind_param("i", $pedido_id);
$stmtCheck->execute();
$resultCheck = $stmtCheck->get_result();

if ($resultCheck->num_rows === 0) {
    http_response_code(404);
    echo json_encode(["success" => false, "message" => "Pedido no encontrado"]);
    exit;
}

$pedido = $resultCheck->fetch_assoc();
$tipoEnvio = mb_strtolower($pedido['tipo_envio'] ?? '', 'UTF-8');
if (!in_array($tipoEnvio, ['paquetería', 'paqueteria'], true)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Este pedido no es de tipo paquetería"]);
    exit;
}
$stmtCheck->close();

// Extraer datos del destinatario
$nombre_destinatario = trim($data['nombre_destinatario'] ?? '');
$calle = trim($data['calle'] ?? '');
$no_exterior = trim($data['no_exterior'] ?? '');
$no_interior = trim($data['no_interior'] ?? '');
$entre_calles = trim($data['entre_calles'] ?? '');
$colonia = trim($data['colonia'] ?? '');
$codigo_postal = trim($data['codigo_postal'] ?? '');
$ciudad = trim($data['ciudad'] ?? '');
$estado_destino = trim($data['estado_destino'] ?? '');
$contacto_destino = trim($data['contacto_destino'] ?? '');
$telefono_destino = trim($data['telefono_destino'] ?? '');

// Coordenadas
$lat = isset($data['lat']) && $data['lat'] !== '' ? floatval($data['lat']) : null;
$lng = isset($data['lng']) && $data['lng'] !== '' ? floatval($data['lng']) : null;

// Datos de paquetería (flexibles)
$nombre_paqueteria = trim($data['nombre_paqueteria'] ?? '');
$tipo_cobro = trim($data['tipo_cobro'] ?? '');
$atn = trim($data['atn'] ?? '');
$num_cliente = trim($data['num_cliente'] ?? '');
$clave_sat = trim($data['clave_sat'] ?? '');

$usuario_capturo = $_SESSION["username"] ?? 'Sistema';

// Verificar si ya existe un registro para este pedido
$sqlExists = "SELECT id FROM pedidos_destinatario WHERE pedido_id = ?";
$stmtExists = $conn->prepare($sqlExists);
$stmtExists->bind_param("i", $pedido_id);
$stmtExists->execute();
$resultExists = $stmtExists->get_result();
$existe = $resultExists->num_rows > 0;
$stmtExists->close();

try {
    $conn->begin_transaction();

    if ($existe) {
        // Actualizar registro existente
        $sqlUpdate = "UPDATE pedidos_destinatario SET
            nombre_destinatario = ?,
            calle = ?,
            no_exterior = ?,
            no_interior = ?,
            entre_calles = ?,
            colonia = ?,
            codigo_postal = ?,
            ciudad = ?,
            estado_destino = ?,
            contacto_destino = ?,
            telefono_destino = ?,
            lat = ?,
            lng = ?,
            nombre_paqueteria = ?,
            tipo_cobro = ?,
            atn = ?,
            num_cliente = ?,
            clave_sat = ?,
            usuario_capturo = ?,
            fecha_actualizacion = NOW()
            WHERE pedido_id = ?";

        $stmtUpdate = $conn->prepare($sqlUpdate);
        $stmtUpdate->bind_param(
            "sssssssssssddssssssi",
            $nombre_destinatario,
            $calle,
            $no_exterior,
            $no_interior,
            $entre_calles,
            $colonia,
            $codigo_postal,
            $ciudad,
            $estado_destino,
            $contacto_destino,
            $telefono_destino,
            $lat,
            $lng,
            $nombre_paqueteria,
            $tipo_cobro,
            $atn,
            $num_cliente,
            $clave_sat,
            $usuario_capturo,
            $pedido_id
        );
        $stmtUpdate->execute();
        $stmtUpdate->close();
    } else {
        // Insertar nuevo registro
        $sqlInsert = "INSERT INTO pedidos_destinatario (
            pedido_id, nombre_destinatario, calle, no_exterior, no_interior,
            entre_calles, colonia, codigo_postal, ciudad, estado_destino,
            contacto_destino, telefono_destino, lat, lng,
            nombre_paqueteria, tipo_cobro, atn, num_cliente, clave_sat,
            usuario_capturo
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmtInsert = $conn->prepare($sqlInsert);
        $stmtInsert->bind_param(
            "isssssssssssddssssss",
            $pedido_id,
            $nombre_destinatario,
            $calle,
            $no_exterior,
            $no_interior,
            $entre_calles,
            $colonia,
            $codigo_postal,
            $ciudad,
            $estado_destino,
            $contacto_destino,
            $telefono_destino,
            $lat,
            $lng,
            $nombre_paqueteria,
            $tipo_cobro,
            $atn,
            $num_cliente,
            $clave_sat,
            $usuario_capturo
        );
        $stmtInsert->execute();
        $stmtInsert->close();
    }

    // Actualizar campo de control en pedidos
    $sqlUpdatePedido = "UPDATE pedidos SET tiene_destinatario_capturado = 1 WHERE ID = ?";
    $stmtUpdatePedido = $conn->prepare($sqlUpdatePedido);
    $stmtUpdatePedido->bind_param("i", $pedido_id);
    $stmtUpdatePedido->execute();
    $stmtUpdatePedido->close();

    // Actualizar Coord_Destino si tenemos coordenadas
    if ($lat !== null && $lng !== null) {
        $coordDestino = "$lat, $lng";
        $sqlUpdateCoord = "UPDATE pedidos SET Coord_Destino = ? WHERE ID = ?";
        $stmtUpdateCoord = $conn->prepare($sqlUpdateCoord);
        $stmtUpdateCoord->bind_param("si", $coordDestino, $pedido_id);
        $stmtUpdateCoord->execute();
        $stmtUpdateCoord->close();
    }

    $conn->commit();

    echo json_encode([
        "success" => true,
        "message" => $existe ? "Datos actualizados correctamente" : "Datos guardados correctamente"
    ]);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Error al guardar: " . $e->getMessage()
    ]);
}

$conn->close();
