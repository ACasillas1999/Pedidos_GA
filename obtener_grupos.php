<?php
session_name("GA");
session_start();

// Verificar si el usuario está logeado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    echo json_encode(["success" => false, "message" => "No autorizado"]);
    exit;
}

// Establecer la conexión a la base de datos
require_once __DIR__ . "/Conexiones/Conexion.php";

header('Content-Type: application/json');

$accion = $_GET['accion'] ?? 'listar';

try {
    switch ($accion) {
        case 'listar':
            // Listar todos los grupos activos con conteo de pedidos
            $sql = "SELECT gr.id, gr.nombre_grupo, gr.sucursal, gr.chofer_asignado,
                           gr.fecha_creacion, gr.usuario_creo, gr.estado, gr.notas,
                           COUNT(pg.pedido_id) as total_pedidos,
                           c.username as chofer_nombre
                    FROM grupos_rutas gr
                    LEFT JOIN pedidos_grupos pg ON gr.id = pg.grupo_id
                    LEFT JOIN choferes c ON gr.chofer_asignado = c.username
                    WHERE gr.estado = 'ACTIVO'
                    GROUP BY gr.id
                    ORDER BY gr.fecha_creacion DESC";

            $result = $conn->query($sql);
            $grupos = [];

            while ($row = $result->fetch_assoc()) {
                $grupos[] = $row;
            }

            echo json_encode([
                "success" => true,
                "grupos" => $grupos
            ]);
            break;

        case 'detalle':
            // Obtener detalle de un grupo específico con sus pedidos
            $grupoId = intval($_GET['grupo_id'] ?? 0);

            if ($grupoId <= 0) {
                echo json_encode(["success" => false, "message" => "ID de grupo inválido"]);
                exit;
            }

            // Información del grupo
            $sqlGrupo = "SELECT gr.*, c.username as chofer_nombre, c.Telefono as chofer_telefono
                        FROM grupos_rutas gr
                        LEFT JOIN choferes c ON gr.chofer_asignado = c.username
                        WHERE gr.id = ?";

            $stmtGrupo = $conn->prepare($sqlGrupo);
            $stmtGrupo->bind_param("i", $grupoId);
            $stmtGrupo->execute();
            $resultGrupo = $stmtGrupo->get_result();

            if ($grupo = $resultGrupo->fetch_assoc()) {
                // Pedidos del grupo
                $sqlPedidos = "SELECT p.ID, p.FACTURA, p.NOMBRE_CLIENTE, p.DIRECCION,
                                     p.TELEFONO, p.ESTADO, p.tipo_envio, p.Coord_Destino,
                                     p.precio_factura_real, pg.orden_entrega
                              FROM pedidos p
                              INNER JOIN pedidos_grupos pg ON p.ID = pg.pedido_id
                              WHERE pg.grupo_id = ?
                              ORDER BY pg.orden_entrega ASC";

                $stmtPedidos = $conn->prepare($sqlPedidos);
                $stmtPedidos->bind_param("i", $grupoId);
                $stmtPedidos->execute();
                $resultPedidos = $stmtPedidos->get_result();

                $pedidos = [];
                while ($pedido = $resultPedidos->fetch_assoc()) {
                    $pedidos[] = $pedido;
                }

                $grupo['pedidos'] = $pedidos;
                $stmtPedidos->close();

                echo json_encode([
                    "success" => true,
                    "grupo" => $grupo
                ]);
            } else {
                echo json_encode(["success" => false, "message" => "Grupo no encontrado"]);
            }

            $stmtGrupo->close();
            break;

        case 'por_pedido':
            // Obtener información del grupo al que pertenece un pedido
            $pedidoId = intval($_GET['pedido_id'] ?? 0);

            if ($pedidoId <= 0) {
                echo json_encode(["success" => false, "message" => "ID de pedido inválido"]);
                exit;
            }

            $sql = "SELECT gr.id, gr.nombre_grupo, gr.chofer_asignado, gr.sucursal,
                           pg.orden_entrega,
                           COUNT(pg2.pedido_id) as total_pedidos_grupo
                    FROM grupos_rutas gr
                    INNER JOIN pedidos_grupos pg ON gr.id = pg.grupo_id
                    LEFT JOIN pedidos_grupos pg2 ON gr.id = pg2.grupo_id
                    WHERE pg.pedido_id = ? AND gr.estado = 'ACTIVO'
                    GROUP BY gr.id";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $pedidoId);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($grupo = $result->fetch_assoc()) {
                echo json_encode([
                    "success" => true,
                    "tiene_grupo" => true,
                    "grupo" => $grupo
                ]);
            } else {
                echo json_encode([
                    "success" => true,
                    "tiene_grupo" => false
                ]);
            }

            $stmt->close();
            break;

        default:
            echo json_encode(["success" => false, "message" => "Acción no válida"]);
    }

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error: " . $e->getMessage()
    ]);
}

$conn->close();
?>
