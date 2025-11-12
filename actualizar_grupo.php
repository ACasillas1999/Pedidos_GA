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
    echo json_encode(["success" => false, "message" => "No tiene permisos para modificar grupos"]);
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

$accion = $input['accion'] ?? '';
$grupoId = intval($input['grupo_id'] ?? 0);

if ($grupoId <= 0) {
    echo json_encode(["success" => false, "message" => "ID de grupo inválido"]);
    exit;
}

$usuarioSesion = $_SESSION["username"] ?? "Sistema";

try {
    switch ($accion) {
        case 'agregar_pedido':
            // Agregar un pedido a un grupo existente
            $pedidoId = intval($input['pedido_id'] ?? 0);

            if ($pedidoId <= 0) {
                echo json_encode(["success" => false, "message" => "ID de pedido inválido"]);
                exit;
            }

            $conn->begin_transaction();

            // Verificar que el grupo exista y esté activo
            $sqlCheck = "SELECT chofer_asignado, nombre_grupo FROM grupos_rutas WHERE id = ? AND estado = 'ACTIVO'";
            $stmtCheck = $conn->prepare($sqlCheck);
            $stmtCheck->bind_param("i", $grupoId);
            $stmtCheck->execute();
            $resultCheck = $stmtCheck->get_result();

            if ($grupo = $resultCheck->fetch_assoc()) {
                $chofer = $grupo['chofer_asignado'];
                $nombreGrupo = $grupo['nombre_grupo'];

                // Obtener el último orden de entrega
                $sqlOrden = "SELECT MAX(orden_entrega) as max_orden FROM pedidos_grupos WHERE grupo_id = ?";
                $stmtOrden = $conn->prepare($sqlOrden);
                $stmtOrden->bind_param("i", $grupoId);
                $stmtOrden->execute();
                $resultOrden = $stmtOrden->get_result();
                $rowOrden = $resultOrden->fetch_assoc();
                $nuevoOrden = ($rowOrden['max_orden'] ?? 0) + 1;
                $stmtOrden->close();

                // Actualizar el pedido con el chofer del grupo
                $sqlUpdate = "UPDATE pedidos SET CHOFER_ASIGNADO = ? WHERE ID = ?";
                $stmtUpdate = $conn->prepare($sqlUpdate);
                $stmtUpdate->bind_param("si", $chofer, $pedidoId);
                $stmtUpdate->execute();
                $stmtUpdate->close();

                // Asociar el pedido al grupo
                $sqlAsociar = "INSERT INTO pedidos_grupos (pedido_id, grupo_id, orden_entrega) VALUES (?, ?, ?)";
                $stmtAsociar = $conn->prepare($sqlAsociar);
                $stmtAsociar->bind_param("iii", $pedidoId, $grupoId, $nuevoOrden);
                $stmtAsociar->execute();
                $stmtAsociar->close();

                // Registrar en historial
                $sqlHistorial = "INSERT INTO historial_cambios (Pedido_ID, Usuario_ID, Cambio, Fecha_Hora) VALUES (?, ?, ?, NOW())";
                $descripcionCambio = "Agregado al grupo '$nombreGrupo' (ID: $grupoId)";
                $stmtHistorial = $conn->prepare($sqlHistorial);
                $stmtHistorial->bind_param("iss", $pedidoId, $usuarioSesion, $descripcionCambio);
                $stmtHistorial->execute();
                $stmtHistorial->close();

                $conn->commit();

                echo json_encode([
                    "success" => true,
                    "message" => "Pedido agregado al grupo exitosamente"
                ]);
            } else {
                echo json_encode(["success" => false, "message" => "Grupo no encontrado o inactivo"]);
            }

            $stmtCheck->close();
            break;

        case 'remover_pedido':
            // Remover un pedido de un grupo
            $pedidoId = intval($input['pedido_id'] ?? 0);

            if ($pedidoId <= 0) {
                echo json_encode(["success" => false, "message" => "ID de pedido inválido"]);
                exit;
            }

            $conn->begin_transaction();

            // Remover asociación del grupo
            $sqlRemover = "DELETE FROM pedidos_grupos WHERE pedido_id = ? AND grupo_id = ?";
            $stmtRemover = $conn->prepare($sqlRemover);
            $stmtRemover->bind_param("ii", $pedidoId, $grupoId);
            $stmtRemover->execute();

            if ($stmtRemover->affected_rows > 0) {
                // Registrar en historial
                $sqlHistorial = "INSERT INTO historial_cambios (Pedido_ID, Usuario_ID, Cambio, Fecha_Hora) VALUES (?, ?, ?, NOW())";
                $descripcionCambio = "Removido del grupo (ID: $grupoId)";
                $stmtHistorial = $conn->prepare($sqlHistorial);
                $stmtHistorial->bind_param("iss", $pedidoId, $usuarioSesion, $descripcionCambio);
                $stmtHistorial->execute();
                $stmtHistorial->close();

                $conn->commit();

                echo json_encode([
                    "success" => true,
                    "message" => "Pedido removido del grupo exitosamente"
                ]);
            } else {
                echo json_encode(["success" => false, "message" => "El pedido no pertenece a este grupo"]);
            }

            $stmtRemover->close();
            break;

        case 'actualizar_orden':
            // Actualizar el orden de entrega de los pedidos en un grupo
            $nuevosOrdenes = $input['ordenes'] ?? [];

            if (empty($nuevosOrdenes) || !is_array($nuevosOrdenes)) {
                echo json_encode(["success" => false, "message" => "Datos de orden inválidos"]);
                exit;
            }

            $conn->begin_transaction();

            foreach ($nuevosOrdenes as $item) {
                $pedidoId = intval($item['pedido_id'] ?? 0);
                $orden = intval($item['orden'] ?? 0);

                if ($pedidoId > 0 && $orden > 0) {
                    $sqlUpdate = "UPDATE pedidos_grupos SET orden_entrega = ? WHERE pedido_id = ? AND grupo_id = ?";
                    $stmtUpdate = $conn->prepare($sqlUpdate);
                    $stmtUpdate->bind_param("iii", $orden, $pedidoId, $grupoId);
                    $stmtUpdate->execute();
                    $stmtUpdate->close();
                }
            }

            $conn->commit();

            echo json_encode([
                "success" => true,
                "message" => "Orden de entrega actualizado exitosamente"
            ]);
            break;

        case 'desactivar':
            // Desactivar un grupo (no eliminar)
            $quitarChofer = $input['quitar_chofer'] ?? false;

            $conn->begin_transaction();

            try {
                // Primero, obtener los pedidos del grupo ANTES de hacer cambios
                $sqlPedidos = "SELECT pedido_id FROM pedidos_grupos WHERE grupo_id = ?";
                $stmtPedidos = $conn->prepare($sqlPedidos);
                $stmtPedidos->bind_param("i", $grupoId);
                $stmtPedidos->execute();
                $resultPedidos = $stmtPedidos->get_result();

                $pedidosIds = [];
                while ($row = $resultPedidos->fetch_assoc()) {
                    $pedidosIds[] = $row['pedido_id'];
                }
                $stmtPedidos->close();

                // Desactivar el grupo
                $sqlDesactivar = "UPDATE grupos_rutas SET estado = 'INACTIVO' WHERE id = ?";
                $stmtDesactivar = $conn->prepare($sqlDesactivar);
                $stmtDesactivar->bind_param("i", $grupoId);
                $stmtDesactivar->execute();
                $stmtDesactivar->close();

                // IMPORTANTE: Siempre remover las asociaciones de pedidos_grupos
                // Esto libera los pedidos para que puedan ser asignados a nuevos grupos
                $sqlRemoverAsociaciones = "DELETE FROM pedidos_grupos WHERE grupo_id = ?";
                $stmtRemoverAsoc = $conn->prepare($sqlRemoverAsociaciones);
                $stmtRemoverAsoc->bind_param("i", $grupoId);
                $stmtRemoverAsoc->execute();
                $stmtRemoverAsoc->close();

                // Si se debe quitar el chofer de los pedidos
                if ($quitarChofer) {
                    foreach ($pedidosIds as $pedidoId) {
                        // Quitar el chofer del pedido
                        $sqlUpdate = "UPDATE pedidos SET CHOFER_ASIGNADO = NULL WHERE ID = ?";
                        $stmtUpdate = $conn->prepare($sqlUpdate);
                        $stmtUpdate->bind_param("i", $pedidoId);
                        $stmtUpdate->execute();
                        $stmtUpdate->close();

                        // Registrar en historial
                        $sqlHistorial = "INSERT INTO historial_cambios (Pedido_ID, Usuario_ID, Cambio, Fecha_Hora) VALUES (?, ?, ?, NOW())";
                        $descripcionCambio = "Chofer removido al desactivar grupo (ID: $grupoId)";
                        $stmtHistorial = $conn->prepare($sqlHistorial);
                        $stmtHistorial->bind_param("iss", $pedidoId, $usuarioSesion, $descripcionCambio);
                        $stmtHistorial->execute();
                        $stmtHistorial->close();
                    }
                } else {
                    // Solo registrar en historial que se desactivó el grupo pero se mantuvo el chofer
                    foreach ($pedidosIds as $pedidoId) {
                        $sqlHistorial = "INSERT INTO historial_cambios (Pedido_ID, Usuario_ID, Cambio, Fecha_Hora) VALUES (?, ?, ?, NOW())";
                        $descripcionCambio = "Grupo desactivado (ID: $grupoId), chofer mantenido";
                        $stmtHistorial = $conn->prepare($sqlHistorial);
                        $stmtHistorial->bind_param("iss", $pedidoId, $usuarioSesion, $descripcionCambio);
                        $stmtHistorial->execute();
                        $stmtHistorial->close();
                    }
                }

                $conn->commit();

                echo json_encode([
                    "success" => true,
                    "message" => "Grupo desactivado exitosamente"
                ]);
            } catch (Exception $e) {
                $conn->rollback();
                echo json_encode([
                    "success" => false,
                    "message" => "Error al desactivar grupo: " . $e->getMessage()
                ]);
            }
            break;

        case 'actualizar_info':
            // Actualizar información básica del grupo
            $nombreGrupo = $input['nombre_grupo'] ?? '';
            $notas = $input['notas'] ?? '';

            if (empty($nombreGrupo)) {
                echo json_encode(["success" => false, "message" => "El nombre del grupo es requerido"]);
                exit;
            }

            $sqlUpdate = "UPDATE grupos_rutas SET nombre_grupo = ?, notas = ? WHERE id = ?";
            $stmtUpdate = $conn->prepare($sqlUpdate);
            $stmtUpdate->bind_param("ssi", $nombreGrupo, $notas, $grupoId);
            $stmtUpdate->execute();

            if ($stmtUpdate->affected_rows > 0) {
                echo json_encode([
                    "success" => true,
                    "message" => "Información del grupo actualizada"
                ]);
            } else {
                echo json_encode(["success" => false, "message" => "No se realizaron cambios"]);
            }

            $stmtUpdate->close();
            break;

        case 'mover_pedido':
            // Mover un pedido de un grupo a otro
            $pedidoId = intval($input['pedido_id'] ?? 0);
            $grupoDestino = intval($input['grupo_id_destino'] ?? 0);
            $grupoOrigen = $grupoId; // alias más claro

            if ($pedidoId <= 0 || $grupoDestino <= 0 || $grupoOrigen <= 0) {
                echo json_encode(["success" => false, "message" => "Datos inválidos para mover pedido"]);
                exit;
            }

            // Validar que ambos grupos existan y estén activos
            $sqlG = "SELECT id, nombre_grupo, sucursal, chofer_asignado FROM grupos_rutas WHERE id = ? AND estado = 'ACTIVO'";
            $stmtGO = $conn->prepare($sqlG);
            $stmtGO->bind_param("i", $grupoOrigen);
            $stmtGO->execute();
            $resGO = $stmtGO->get_result();
            $orig = $resGO->fetch_assoc();
            $stmtGO->close();

            $stmtGD = $conn->prepare($sqlG);
            $stmtGD->bind_param("i", $grupoDestino);
            $stmtGD->execute();
            $resGD = $stmtGD->get_result();
            $dest = $resGD->fetch_assoc();
            $stmtGD->close();

            if (!$orig || !$dest) {
                echo json_encode(["success" => false, "message" => "Grupo de origen o destino no válido/activo"]);
                exit;
            }

            // Validar compatibilidad de sucursales: misma sucursal o ILUMINACION<->TAPATIA
            $okSucursal = ($orig['sucursal'] === $dest['sucursal']) ||
                          (in_array($orig['sucursal'], ['ILUMINACION','TAPATIA']) && in_array($dest['sucursal'], ['ILUMINACION','TAPATIA']));
            if (!$okSucursal) {
                echo json_encode(["success" => false, "message" => "Sucursales incompatibles entre grupos"]);
                exit;
            }

            // Obtener nuevo orden en destino
            $sqlOrden = "SELECT COALESCE(MAX(orden_entrega),0) as max_orden FROM pedidos_grupos WHERE grupo_id = ?";
            $stmtOrden = $conn->prepare($sqlOrden);
            $stmtOrden->bind_param("i", $grupoDestino);
            $stmtOrden->execute();
            $rOrd = $stmtOrden->get_result()->fetch_assoc();
            $stmtOrden->close();
            $nuevoOrden = intval($rOrd['max_orden']) + 1;

            $conn->begin_transaction();
            try {
                // Quitar del grupo origen
                $sqlDel = "DELETE FROM pedidos_grupos WHERE pedido_id = ? AND grupo_id = ?";
                $stmtDel = $conn->prepare($sqlDel);
                $stmtDel->bind_param("ii", $pedidoId, $grupoOrigen);
                $stmtDel->execute();
                $stmtDel->close();

                // Asociar al grupo destino con nuevo orden
                $sqlIns = "INSERT INTO pedidos_grupos (pedido_id, grupo_id, orden_entrega) VALUES (?, ?, ?)";
                $stmtIns = $conn->prepare($sqlIns);
                $stmtIns->bind_param("iii", $pedidoId, $grupoDestino, $nuevoOrden);
                $stmtIns->execute();
                $stmtIns->close();

                // Actualizar chofer del pedido al chofer del grupo destino
                $sqlUpd = "UPDATE pedidos SET CHOFER_ASIGNADO = ? WHERE ID = ?";
                $stmtUpd = $conn->prepare($sqlUpd);
                $stmtUpd->bind_param("si", $dest['chofer_asignado'], $pedidoId);
                $stmtUpd->execute();
                $stmtUpd->close();

                // Historial
                $sqlH = "INSERT INTO historial_cambios (Pedido_ID, Usuario_ID, Cambio, Fecha_Hora) VALUES (?, ?, ?, NOW())";
                $desc = "Movido del grupo '".$orig['nombre_grupo']."' al grupo '".$dest['nombre_grupo']."'";
                $stmtH = $conn->prepare($sqlH);
                $stmtH->bind_param("iss", $pedidoId, $usuarioSesion, $desc);
                $stmtH->execute();
                $stmtH->close();

                $conn->commit();
                echo json_encode(["success" => true, "message" => "Pedido movido correctamente"]);
            } catch (Exception $e) {
                $conn->rollback();
                echo json_encode(["success" => false, "message" => "Error al mover pedido: ".$e->getMessage()]);
            }
            break;

        case 'listar_grupos_activos':
            // Listar grupos activos, opcionalmente compatibles con una sucursal indicada
            $suc = trim($input['sucursal'] ?? '');
            $excluirId = intval($input['excluir_id'] ?? 0);
            $params = [];
            $types = '';
            $where = "WHERE estado = 'ACTIVO'";
            if ($excluirId > 0) {
                $where .= " AND id <> ?";
                $types .= 'i';
                $params[] = $excluirId;
            }

            $sqlList = "SELECT id, nombre_grupo, sucursal, chofer_asignado FROM grupos_rutas $where ORDER BY fecha_creacion DESC";
            $stmtL = $conn->prepare($sqlList);
            if (!empty($types)) {
                $stmtL->bind_param($types, ...$params);
            }
            $stmtL->execute();
            $resL = $stmtL->get_result();
            $grupos = [];
            while ($g = $resL->fetch_assoc()) {
                if ($suc !== '') {
                    $compatible = ($g['sucursal'] === $suc) || (in_array($g['sucursal'], ['ILUMINACION','TAPATIA']) && in_array($suc, ['ILUMINACION','TAPATIA']));
                    if (!$compatible) continue;
                }
                $grupos[] = $g;
            }
            $stmtL->close();
            echo json_encode(["success" => true, "grupos" => $grupos]);
            break;

        default:
            echo json_encode(["success" => false, "message" => "Acción no válida"]);
    }

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        "success" => false,
        "message" => "Error: " . $e->getMessage()
    ]);
}

$conn->close();
?>
