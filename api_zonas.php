<?php
header('Content-Type: application/json');
require_once __DIR__ . "/Conexiones/Conexion.php";

session_name("GA");
session_start();

// Validar sesión
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Obtener zonas
    $sql = "SELECT * FROM zonas WHERE estado = 'ACTIVO'";
    $result = $conn->query($sql);
    
    $zonas = [];
    while ($row = $result->fetch_assoc()) {
        $row['coordenadas'] = json_decode($row['coordenadas']);
        $zonas[] = $row;
    }
    
    echo json_encode(['success' => true, 'zonas' => $zonas]);

} elseif ($method === 'POST') {
    // Validar permisos (Solo Admin o JC)
    $rol = $_SESSION["Rol"] ?? "";
    if (!in_array($rol, ['Admin', 'JC'])) {
        http_response_code(403);
        echo json_encode(['error' => 'Permisos insuficientes']);
        exit;
    }

    // Leer datos
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['id']) || !isset($input['coordenadas'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Datos incompletos']);
        exit;
    }

    $id = intval($input['id']);
    $coordenadas = json_encode($input['coordenadas']); // Asegurar que sea JSON válido
    
    // Validar JSON
    if ($coordenadas === false) {
        http_response_code(400);
        echo json_encode(['error' => 'Formato de coordenadas inválido']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE zonas SET coordenadas = ? WHERE id = ?");
    $stmt->bind_param("si", $coordenadas, $id);
    
    if ($stmt->execute()) {
        $response = ['success' => true, 'message' => 'Zona actualizada correctamente'];

        // Lógica de Reclasificación
        if (isset($input['recalcular']) && $input['recalcular'] === true) {
            require_once __DIR__ . "/funciones_zona.php"; // Para usar puntoEnPoligono y parsearCoordenadas

            // 1. Obtener todos los pedidos con coordenadas
            $sqlPedidos = "SELECT ID, Coord_Destino, tipo_zona FROM pedidos WHERE Coord_Destino IS NOT NULL AND Coord_Destino != ''";
            $resultPedidos = $conn->query($sqlPedidos);
            
            $actualizados = 0;
            $nuevosCoordsArray = json_decode($coordenadas, true); // Usar las coordenadas que acabamos de guardar

            if ($resultPedidos->num_rows > 0) {
                while ($pedido = $resultPedidos->fetch_assoc()) {
                    $punto = parsearCoordenadas($pedido['Coord_Destino']);
                    
                    if ($punto) {
                        $estaDentro = puntoEnPoligono($punto['lat'], $punto['lng'], $nuevosCoordsArray);
                        $nuevoTipo = $estaDentro ? 'LOCAL' : 'FORANEO';
                        
                        // Solo actualizar si cambió
                        if ($pedido['tipo_zona'] !== $nuevoTipo) {
                            $updateSql = "UPDATE pedidos SET tipo_zona = '$nuevoTipo' WHERE ID = " . $pedido['ID'];
                            $conn->query($updateSql);
                            $actualizados++;
                        }
                    }
                }
            }
            $response['recalculados'] = $actualizados;
            $response['message'] .= ". Se actualizaron $actualizados pedidos existentes.";
        }

        echo json_encode($response);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error al actualizar: ' . $conn->error]);
    }
    
    $stmt->close();
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
}

$conn->close();
?>
