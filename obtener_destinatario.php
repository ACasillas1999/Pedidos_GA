<?php
// obtener_destinatario.php
// API para obtener datos del destinatario de un pedido de paquetería
declare(strict_types=1);

session_name("GA");
session_start();

// Verificar autenticación
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "No autenticado"]);
    exit;
}

header('Content-Type: application/json');

require_once __DIR__ . "/Conexiones/Conexion.php";

// Validar parámetro pedido_id
$pedido_id = isset($_GET['pedido_id']) ? intval($_GET['pedido_id']) : 0;
if ($pedido_id <= 0) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "ID de pedido inválido"]);
    exit;
}

// Consultar datos del destinatario
$sql = "SELECT
    pedido_id,
    nombre_destinatario,
    calle,
    no_exterior,
    no_interior,
    entre_calles,
    colonia,
    codigo_postal,
    ciudad,
    estado_destino,
    contacto_destino,
    telefono_destino,
    lat,
    lng,
    nombre_paqueteria,
    tipo_cobro,
    atn,
    num_cliente,
    clave_sat,
    fecha_captura,
    fecha_actualizacion,
    usuario_capturo
FROM pedidos_destinatario
WHERE pedido_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $pedido_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // No hay datos capturados
    echo json_encode([
        "success" => true,
        "existe" => false,
        "data" => null
    ]);
    exit;
}

$row = $result->fetch_assoc();
$stmt->close();
$conn->close();

echo json_encode([
    "success" => true,
    "existe" => true,
    "data" => [
        "pedido_id" => $row['pedido_id'],
        "nombre_destinatario" => $row['nombre_destinatario'],
        "calle" => $row['calle'],
        "no_exterior" => $row['no_exterior'],
        "no_interior" => $row['no_interior'],
        "entre_calles" => $row['entre_calles'],
        "colonia" => $row['colonia'],
        "codigo_postal" => $row['codigo_postal'],
        "ciudad" => $row['ciudad'],
        "estado_destino" => $row['estado_destino'],
        "contacto_destino" => $row['contacto_destino'],
        "telefono_destino" => $row['telefono_destino'],
        "lat" => $row['lat'] !== null ? floatval($row['lat']) : null,
        "lng" => $row['lng'] !== null ? floatval($row['lng']) : null,
        "nombre_paqueteria" => $row['nombre_paqueteria'],
        "tipo_cobro" => $row['tipo_cobro'],
        "atn" => $row['atn'],
        "num_cliente" => $row['num_cliente'],
        "clave_sat" => $row['clave_sat'],
        "fecha_captura" => $row['fecha_captura'],
        "fecha_actualizacion" => $row['fecha_actualizacion'],
        "usuario_capturo" => $row['usuario_capturo']
    ]
]);
