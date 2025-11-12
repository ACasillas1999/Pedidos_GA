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
