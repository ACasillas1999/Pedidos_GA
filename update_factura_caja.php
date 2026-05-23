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

// Acciones válidas
$accionesValidas = ['entregar_jefe','pendiente_surtido','surtido','cargado_camioneta'];

if ($id <= 0 || !in_array($accion, $accionesValidas, true)) {
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

// Helper para registrar historial
function logHistorial($conn, $usuario, $id, $cambio) {
  @$conn->query(sprintf(
    "INSERT INTO historial_cambios (Usuario_ID, Pedido_ID, Cambio, Fecha_Hora)
     VALUES ('%s', %d, '%s', NOW())",
    $conn->real_escape_string($usuario), $id, $conn->real_escape_string($cambio)
  ));
}

// Helper para generar el HTML de la celda según el nuevo estado
function badgeHtml($nuevoEstado, $rol, $id) {
  switch ($nuevoEstado) {
    case 0:
      $badge = "<span class='badge badge-azul'>En Caja</span>";
      $btn = in_array($rol, ['Admin','JC'])
        ? "<button type='button' class='btn btn-sm btn-primary accion-factura' data-id='{$id}' data-accion='entregar_jefe'>Entregar a Jefe</button>"
        : '';
      break;
    case 1:
      $badge = "<span class='badge badge-amarillo'>Con Jefe de choferes</span>";
      $btn = in_array($rol, ['Admin','JC'])
        ? "<button type='button' class='btn btn-sm btn-warning accion-factura' data-id='{$id}' data-accion='pendiente_surtido'>Pendiente de Surtido</button>"
        : '';
      break;
    case 2:
      $badge = "<span class='badge badge-naranja'>Pendiente de Surtido</span>";
      $btn = in_array($rol, ['Admin','JC'])
        ? "<button type='button' class='btn btn-sm btn-info accion-factura' data-id='{$id}' data-accion='surtido'>✔ Surtido</button>"
        : '';
      break;
    case 3:
      $badge = "<span class='badge badge-cyan'>Surtido</span>";
      $btn = in_array($rol, ['Admin','JC'])
        ? "<button type='button' class='btn btn-sm btn-success accion-factura' data-id='{$id}' data-accion='cargado_camioneta'>🚚 Cargado en Camioneta</button>"
        : '';
      break;
    case 4:
      $badge = "<span class='badge badge-morado'>Cargado en Camioneta</span>";
      $btn = ''; // Lo mueve el chofer desde la app al poner "En Ruta"
      break;
    case 5:
      $badge = "<span class='badge badge-ruta'>En Ruta</span>";
      $btn = '';
      break;
    case 6:
    default:
      $badge = "<span class='badge badge-verde'>Entregado</span>";
      $btn = '';
      break;
  }
  $btnHtml = $btn ? "<div style='margin-top:6px'>{$btn}</div>" : '';
  return $badge . $btnHtml;
}

// Mapa de transiciones: accion => [estadoRequerido, nuevoEstado, mensajeLog]
$transiciones = [
  'entregar_jefe'      => [0, 1, 'Factura: En Caja → Con Jefe de choferes'],
  'pendiente_surtido'  => [1, 2, 'Factura: Con Jefe de choferes → Pendiente de Surtido'],
  'surtido'            => [2, 3, 'Factura: Pendiente de Surtido → Surtido'],
  'cargado_camioneta'  => [3, 4, 'Factura: Surtido → Cargado en Camioneta'],
];

[$estadoRequerido, $nuevoEstado, $mensajeLog] = $transiciones[$accion];

if ($estado !== $estadoRequerido) {
  http_response_code(409);
  echo json_encode(['ok'=>false,'msg'=>"Estado inválido para la acción '$accion' (estado actual: $estado)"]);
  exit;
}

$stmtU = $conn->prepare("UPDATE pedidos SET estado_factura_caja = ? WHERE ID = ? AND estado_factura_caja = ?");
$stmtU->bind_param("iii", $nuevoEstado, $id, $estadoRequerido);
$stmtU->execute();

if ($stmtU->affected_rows < 1) {
  http_response_code(409);
  echo json_encode(['ok'=>false,'msg'=>'No se pudo actualizar (posible cambio concurrente)']);
  exit;
}

logHistorial($conn, $usuario, $id, $mensajeLog . ' [Oficina]');

echo json_encode(['ok'=>true,'html'=> badgeHtml($nuevoEstado, $rol, $id)]);
