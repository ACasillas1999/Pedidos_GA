<?php
session_name("GA");
session_start();

// Verificar si el usuario está logeado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

require_once __DIR__ . "/Conexiones/Conexion.php";

header('Content-Type: application/json');

// Obtener parámetros
$sucursal = $_GET['sucursal'] ?? 'TODAS';
$maxPedidosPorGrupo = intval($_GET['max_pedidos'] ?? 10);
$radioMaxKm = floatval($_GET['radio_km'] ?? 5); // Radio máximo en km para agrupar

try {
    // Construir query para obtener pedidos pendientes con coordenadas
    $sql = "SELECT ID, FACTURA, NOMBRE_CLIENTE, DIRECCION, TELEFONO,
                   Coord_Destino, SUCURSAL, precio_factura_real, tipo_envio,
                   Chofer_Asignado
            FROM pedidos
            WHERE ESTADO IN ('ACTIVO', 'EN TIENDA', 'REPROGRAMADO')
            AND Coord_Destino IS NOT NULL
            AND Coord_Destino != ''
            AND ID NOT IN (SELECT pedido_id FROM pedidos_grupos pg
                          INNER JOIN grupos_rutas gr ON pg.grupo_id = gr.id
                          WHERE gr.estado = 'ACTIVO')";

    if ($sucursal !== 'TODAS') {
        $sql .= " AND SUCURSAL = ?";
    }

    $sql .= " ORDER BY SUCURSAL, ID";

    $stmt = $conn->prepare($sql);

    if ($sucursal !== 'TODAS') {
        $stmt->bind_param("s", $sucursal);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $pedidos = [];
    while ($row = $result->fetch_assoc()) {
        // Parsear coordenadas
        $coordString = trim($row['Coord_Destino']);
        $lat = null;
        $lng = null;

        try {
            // Intentar JSON
            $coords = json_decode($coordString, true);
            if ($coords && isset($coords['lat']) && isset($coords['lng'])) {
                $lat = floatval($coords['lat']);
                $lng = floatval($coords['lng']);
            } else {
                // Intentar formato "lat, lng"
                if (strpos($coordString, ',') !== false) {
                    $parts = explode(',', $coordString);
                    if (count($parts) === 2) {
                        $lat = floatval(trim($parts[0]));
                        $lng = floatval(trim($parts[1]));
                    }
                }
            }

            if ($lat && $lng && $lat != 0 && $lng != 0) {
                $row['lat'] = $lat;
                $row['lng'] = $lng;
                $pedidos[] = $row;
            }
        } catch (Exception $e) {
            // Ignorar pedidos con coordenadas inválidas
            continue;
        }
    }

    if (empty($pedidos)) {
        echo json_encode([
            'success' => true,
            'grupos_sugeridos' => [],
            'message' => 'No hay pedidos pendientes con coordenadas válidas'
        ]);
        exit;
    }

    // Algoritmo de clustering: K-means simplificado / DBSCAN simplificado
    $gruposSugeridos = agruparPedidosPorProximidad($pedidos, $maxPedidosPorGrupo, $radioMaxKm);

    // Calcular estadísticas para cada grupo
    foreach ($gruposSugeridos as &$grupo) {
        $grupo['distancia_estimada'] = calcularDistanciaTotal($grupo['pedidos']);
        $grupo['eficiencia'] = calcularEficiencia($grupo['pedidos'], $grupo['distancia_estimada']);
    }

    // Ordenar por eficiencia
    usort($gruposSugeridos, function($a, $b) {
        return $b['eficiencia'] <=> $a['eficiencia'];
    });

    echo json_encode([
        'success' => true,
        'grupos_sugeridos' => $gruposSugeridos,
        'total_pedidos' => count($pedidos),
        'total_grupos' => count($gruposSugeridos)
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

$conn->close();

// ========== FUNCIONES AUXILIARES ==========

/**
 * Agrupar pedidos por proximidad geográfica
 */
function agruparPedidosPorProximidad($pedidos, $maxPedidosPorGrupo, $radioMaxKm) {
    $grupos = [];
    $pedidosRestantes = $pedidos;
    $grupoId = 1;

    while (!empty($pedidosRestantes)) {
        $centroPedido = array_shift($pedidosRestantes);
        $grupoActual = [$centroPedido];

        // Buscar pedidos cercanos al centro
        $nuevosPedidosRestantes = [];

        foreach ($pedidosRestantes as $pedido) {
            if (count($grupoActual) >= $maxPedidosPorGrupo) {
                $nuevosPedidosRestantes[] = $pedido;
                continue;
            }

            // Calcular distancia al centro del grupo (centroide)
            $centroide = calcularCentroide($grupoActual);
            $distancia = calcularDistanciaHaversine(
                $centroide['lat'],
                $centroide['lng'],
                $pedido['lat'],
                $pedido['lng']
            );

            if ($distancia <= $radioMaxKm) {
                $grupoActual[] = $pedido;
            } else {
                $nuevosPedidosRestantes[] = $pedido;
            }
        }

        $pedidosRestantes = $nuevosPedidosRestantes;

        // Agregar grupo solo si tiene al menos 2 pedidos
        if (count($grupoActual) >= 2) {
            $centroide = calcularCentroide($grupoActual);

            $grupos[] = [
                'grupo_id' => $grupoId++,
                'nombre_sugerido' => 'Grupo ' . $grupoActual[0]['SUCURSAL'] . ' - Zona ' . $grupoId,
                'sucursal' => $grupoActual[0]['SUCURSAL'],
                'num_pedidos' => count($grupoActual),
                'pedidos' => $grupoActual,
                'centroide' => $centroide,
                'radio_km' => calcularRadioMaximo($grupoActual, $centroide)
            ];
        }
    }

    return $grupos;
}

/**
 * Calcular el centroide (punto central) de un grupo de pedidos
 */
function calcularCentroide($pedidos) {
    $totalLat = 0;
    $totalLng = 0;
    $count = count($pedidos);

    foreach ($pedidos as $pedido) {
        $totalLat += $pedido['lat'];
        $totalLng += $pedido['lng'];
    }

    return [
        'lat' => $totalLat / $count,
        'lng' => $totalLng / $count
    ];
}

/**
 * Calcular el radio máximo del grupo (distancia más lejana al centroide)
 */
function calcularRadioMaximo($pedidos, $centroide) {
    $maxDistancia = 0;

    foreach ($pedidos as $pedido) {
        $distancia = calcularDistanciaHaversine(
            $centroide['lat'],
            $centroide['lng'],
            $pedido['lat'],
            $pedido['lng']
        );

        if ($distancia > $maxDistancia) {
            $maxDistancia = $distancia;
        }
    }

    return round($maxDistancia, 2);
}

/**
 * Calcular distancia entre dos puntos usando fórmula de Haversine
 */
function calcularDistanciaHaversine($lat1, $lng1, $lat2, $lng2) {
    $radioTierra = 6371; // km

    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);

    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLng / 2) * sin($dLng / 2);

    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    return $radioTierra * $c;
}

/**
 * Calcular distancia total aproximada del grupo (suma de distancias consecutivas)
 */
function calcularDistanciaTotal($pedidos) {
    if (count($pedidos) < 2) {
        return 0;
    }

    $distanciaTotal = 0;

    for ($i = 0; $i < count($pedidos) - 1; $i++) {
        $distanciaTotal += calcularDistanciaHaversine(
            $pedidos[$i]['lat'],
            $pedidos[$i]['lng'],
            $pedidos[$i + 1]['lat'],
            $pedidos[$i + 1]['lng']
        );
    }

    return round($distanciaTotal, 2);
}

/**
 * Calcular eficiencia del grupo
 * Eficiencia = num_pedidos / distancia_total
 * Mayor eficiencia = más pedidos en menos distancia
 */
function calcularEficiencia($pedidos, $distanciaTotal) {
    if ($distanciaTotal == 0) {
        return 0;
    }

    $numPedidos = count($pedidos);
    $eficiencia = ($numPedidos / $distanciaTotal) * 10; // Multiplicar por 10 para obtener un número más legible

    return round($eficiencia, 2);
}
?>
