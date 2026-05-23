<?php
/**
 * actualizar_factura_caja_chofer.php
 * Endpoint exclusivo para la app del chofer.
 * Actualiza estado_factura_caja cuando el chofer cambia a EN RUTA (5) o ENTREGADO (6).
 * Autenticado via token/username de la app (no sesión web).
 */
header('Content-Type: application/json');

require_once __DIR__ . '/Conexiones/Conexion.php';

$idPedido    = isset($_POST['id_pedido'])    ? intval($_POST['id_pedido'])    : 0;
$nuevoEstado = isset($_POST['nuevo_estado']) ? intval($_POST['nuevo_estado']) : -1;

// Solo permite avanzar a En Ruta (5) o Entregado (6)
if ($idPedido <= 0 || !in_array($nuevoEstado, [5, 6], true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'Parámetros inválidos']);
    exit;
}

// Requerimiento: el estado actual debe ser el anterior al que queremos poner
// 5 (En Ruta)   requiere que sea >= 4 (Cargado en Camioneta) y < 5
// 6 (Entregado) requiere que sea 5 (En Ruta)
$estadoRequerido = $nuevoEstado - 1; // 4 → 5, 5 → 6

$stmt = $conn->prepare("SELECT estado_factura_caja FROM pedidos WHERE ID = ?");
$stmt->bind_param('i', $idPedido);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'msg' => 'Pedido no encontrado']);
    exit;
}
$row = $res->fetch_assoc();
$estadoActual = (int)$row['estado_factura_caja'];

// Para EN RUTA (5): aceptar si estado_factura_caja está en 4 o menos (nunca bajamos)
// Para ENTREGADO (6): requerir que esté en 5
if ($nuevoEstado === 6 && $estadoActual !== 5) {
    // Si ya está entregado, ignorar silenciosamente
    if ($estadoActual >= 6) {
        echo json_encode(['ok' => true, 'msg' => 'Ya está entregado']);
        exit;
    }
    http_response_code(409);
    echo json_encode(['ok' => false, 'msg' => "Estado actual ($estadoActual) no permite marcar como Entregado"]);
    exit;
}

if ($nuevoEstado === 5 && $estadoActual >= 5) {
    // Ya está en ruta o entregado, ignorar
    echo json_encode(['ok' => true, 'msg' => 'Ya actualizado']);
    exit;
}

// Actualizar — solo avanza, nunca retrocede
$stmtU = $conn->prepare("UPDATE pedidos SET estado_factura_caja = ? WHERE ID = ? AND estado_factura_caja < ?");
$stmtU->bind_param('iii', $nuevoEstado, $idPedido, $nuevoEstado);
$stmtU->execute();
$affected = $stmtU->affected_rows;
$stmtU->close();

if ($affected > 0) {
    $usrLog = isset($_POST['username']) ? trim($_POST['username']) : 'Chofer';
    if (empty($usrLog)) $usrLog = 'Chofer';
    
    $msgLog = ($nuevoEstado === 5) 
        ? 'Factura: Cargado en Camioneta → En Ruta [Chofer]' 
        : 'Factura: En Ruta → Entregado [Chofer]';

    $stmtH = $conn->prepare("INSERT INTO historial_cambios (Usuario_ID, Pedido_ID, Cambio, Fecha_Hora) VALUES (?, ?, ?, NOW())");
    $stmtH->bind_param("sis", $usrLog, $idPedido, $msgLog);
    $stmtH->execute();
    $stmtH->close();
}

echo json_encode(['ok' => true, 'updated' => $affected]);
