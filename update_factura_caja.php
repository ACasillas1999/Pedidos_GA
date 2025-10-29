<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
session_name("GA");
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
  http_response_code(401);
  echo json_encode(['ok'=>false,'msg'=>'No autenticado']);
  exit;
}

require_once __DIR__ . "/Conexiones/Conexion.php";

$id      = isset($_POST['id_pedido']) ? intval($_POST['id_pedido']) : 0;
$accion  = $_POST['accion'] ?? '';
$rol     = $_SESSION['Rol'] ?? '';
$usuario = $_SESSION['username'] ?? 'sistema';

// Validación básica
if ($id <= 0 || !in_array($accion, ['entregar_jefe','devolver_caja'], true)) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'msg'=>'Parámetros inválidos']);
  exit;
}

// Permisos: solo JC o Admin
if (!in_array($rol, ['Admin','JC'], true)) {
  http_response_code(403);
  echo json_encode(['ok'=>false,'msg'=>'No autorizado (solo JC o Admin)']);
  exit;
}

// Consultar estado actual
$stmt = $conn->prepare("SELECT estado_factura_caja FROM pedidos WHERE ID=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
  http_response_code(404);
  echo json_encode(['ok'=>false,'msg'=>'Pedido no encontrado']);
  exit;
}

$row    = $res->fetch_assoc();
$estado = (int)$row['estado_factura_caja'];

if ($accion === 'entregar_jefe') {
  // Solo avanza de 0 -> 1
  if ($estado !== 0) {
    http_response_code(409);
    echo json_encode(['ok'=>false,'msg'=>'Estado inválido: la factura no está "En Caja"']);
    exit;
  }

  $stmtU = $conn->prepare("
    UPDATE pedidos
    SET estado_factura_caja = 1,
        fecha_entrega_jefe  = NOW(),
        usuario_entrega_jefe= ?
    WHERE ID = ? AND estado_factura_caja = 0
  ");
  $stmtU->bind_param("si", $usuario, $id);
  $stmtU->execute();

  if ($stmtU->affected_rows < 1) {
    http_response_code(409);
    echo json_encode(['ok'=>false,'msg'=>'No se pudo actualizar (concurrencia)']);
    exit;
  }

  // (Opcional) Registrar en historial_cambios
  @$conn->query(sprintf(
    "INSERT INTO historial_cambios (Usuario_ID, Pedido_ID, Cambio, Fecha_Hora)
     VALUES ('%s', %d, 'Factura: En Caja → Entregada a Jefe', NOW())",
    $conn->real_escape_string($usuario), $id
  ));

  // Devolver HTML de la celda (badge + botón siguiente solo para JC/Admin)
  $html = "<span class='badge badge-amarillo'>Con Jefe de choferes</span>";
  if (in_array($rol, ['Admin','JC'], true)) {
    $html .= "<div style='margin-top:6px'>
                <button class='btn btn-sm btn-success accion-factura' data-id='{$id}' data-accion='devolver_caja'>
                  Devolver a Caja
                </button>
              </div>";
  }

  echo json_encode(['ok'=>true,'html'=>$html]);
  exit;
}

if ($accion === 'devolver_caja') {
  // Solo avanza de 1 -> 2
  if ($estado !== 1) {
    http_response_code(409);
    echo json_encode(['ok'=>false,'msg'=>'Estado inválido: la factura no está "Con Jefe de choferes"']);
    exit;
  }

  $stmtU = $conn->prepare("
    UPDATE pedidos
    SET estado_factura_caja   = 2,
        fecha_devolucion_caja = NOW(),
        usuario_devolucion_caja = ?
    WHERE ID = ? AND estado_factura_caja = 1
  ");
  $stmtU->bind_param("si", $usuario, $id);
  $stmtU->execute();

  if ($stmtU->affected_rows < 1) {
    http_response_code(409);
    echo json_encode(['ok'=>false,'msg'=>'No se pudo actualizar (concurrencia)']);
    exit;
  }

  // (Opcional) Registrar en historial_cambios
  @$conn->query(sprintf(
    "INSERT INTO historial_cambios (Usuario_ID, Pedido_ID, Cambio, Fecha_Hora)
     VALUES ('%s', %d, 'Factura: Entregada a Jefe → Devuelta a Caja', NOW())",
    $conn->real_escape_string($usuario), $id
  ));

  $html = "<span class='badge badge-verde'>Devuelta a Caja</span>";

  echo json_encode(['ok'=>true,'html'=>$html]);
  exit;
}

// Si llega aquí, la acción no fue manejada
http_response_code(400);
echo json_encode(['ok'=>false,'msg'=>'Acción no soportada']);
