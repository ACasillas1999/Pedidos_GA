<?php
session_name("GA");
session_start();

// Verificación de autenticación
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

// Control de acceso: Solo Admin y JC
if ($_SESSION["Rol"] !== "Admin" && $_SESSION["Rol"] !== "JC") {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Acceso denegado']);
    exit;
}

header('Content-Type: application/json');

require_once __DIR__ . "/Conexiones/Conexion.php";

try {
    // Obtener parámetros de filtro
    $fecha_desde = isset($_GET['fecha_desde']) && $_GET['fecha_desde'] !== '' ? $_GET['fecha_desde'] : null;
    $fecha_hasta = isset($_GET['fecha_hasta']) && $_GET['fecha_hasta'] !== '' ? $_GET['fecha_hasta'] : null;
    $sucursal = isset($_GET['sucursal']) && $_GET['sucursal'] !== '' ? $_GET['sucursal'] : null;

    // Construir consulta SQL base
    $sql = "SELECT
                ID,
                Coord_Destino,
                DIRECCION,
                tipo_envio,
                FECHA_ENTREGA_CLIENTE,
                SUCURSAL,
                NOMBRE_CLIENTE
            FROM pedidos
            WHERE (tipo_envio = 'domicilio' OR tipo_envio = 'paquetería' OR tipo_envio = 'paqueteria')";

    $params = [];
    $types = "";

    // Agregar filtro de fecha solo si se proporcionan ambas fechas
    if ($fecha_desde !== null && $fecha_hasta !== null) {
        $sql .= " AND FECHA_RECEPCION_FACTURA BETWEEN ? AND ?";
        $params[] = $fecha_desde;
        $params[] = $fecha_hasta;
        $types .= "ss";
    }

    // Agregar filtro de sucursal si se proporciona
    if ($sucursal !== null) {
        $sql .= " AND SUCURSAL = ?";
        $params[] = $sucursal;
        $types .= "s";
    }

    $stmt = $conn->prepare($sql);

    // Solo hacer bind_param si hay parámetros
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $coordenadas_domicilio = [];
    $coordenadas_paqueteria = [];
    $direcciones_sin_coordenadas = [];
    $stats_por_sucursal = [];

    while ($row = $result->fetch_assoc()) {
        $coord_destino = trim($row['Coord_Destino']);
        $tipo_envio = strtolower(trim($row['tipo_envio']));
        $sucursal_row = trim($row['SUCURSAL']);

        // Normalizar tipo de envío
        if ($tipo_envio === 'paquetería') {
            $tipo_envio = 'paqueteria';
        }

        // Inicializar estadísticas de sucursal si no existe
        if (!isset($stats_por_sucursal[$sucursal_row])) {
            $stats_por_sucursal[$sucursal_row] = [
                'total' => 0,
                'domicilio' => 0,
                'paqueteria' => 0
            ];
        }

        // Verificar si tiene coordenadas válidas
        if (!empty($coord_destino) && $coord_destino !== '0' && strpos($coord_destino, ',') !== false) {
            $coords = explode(',', $coord_destino);

            if (count($coords) >= 2) {
                $lat = floatval(trim($coords[0]));
                $lng = floatval(trim($coords[1]));

                // Validar que sean coordenadas válidas (dentro de un rango razonable para Guadalajara)
                if ($lat >= 19.0 && $lat <= 21.5 && $lng >= -104.5 && $lng <= -102.0) {
                    $coordenada = [
                        'lat' => $lat,
                        'lng' => $lng,
                        'id' => $row['ID'],
                        'sucursal' => $sucursal_row
                    ];

                    if ($tipo_envio === 'domicilio') {
                        $coordenadas_domicilio[] = $coordenada;
                        $stats_por_sucursal[$sucursal_row]['domicilio']++;
                    } else if ($tipo_envio === 'paqueteria') {
                        $coordenadas_paqueteria[] = $coordenada;
                        $stats_por_sucursal[$sucursal_row]['paqueteria']++;
                    }
                    $stats_por_sucursal[$sucursal_row]['total']++;
                    continue;
                }
            }
        }

        // Si no tiene coordenadas válidas, guardar la dirección para geocodificar
        if (!empty($row['DIRECCION']) && trim($row['DIRECCION']) !== '') {
            $direcciones_sin_coordenadas[] = [
                'id' => $row['ID'],
                'direccion' => trim($row['DIRECCION']),
                'tipo_envio' => $tipo_envio,
                'sucursal' => $sucursal_row
            ];
        }
    }

    $stmt->close();

    // Geocodificar direcciones sin coordenadas (limitado a 50 por petición para no sobrecargar)
    $max_geocode = 50;
    $geocoded = 0;

    foreach ($direcciones_sin_coordenadas as $item) {
        if ($geocoded >= $max_geocode) break;

        $coords = geocodificarDireccion($item['direccion']);

        if ($coords !== null) {
            $coordenada = [
                'lat' => $coords['lat'],
                'lng' => $coords['lng'],
                'id' => $item['id'],
                'sucursal' => $item['sucursal']
            ];

            if ($item['tipo_envio'] === 'domicilio') {
                $coordenadas_domicilio[] = $coordenada;
                $stats_por_sucursal[$item['sucursal']]['domicilio']++;
            } else if ($item['tipo_envio'] === 'paqueteria') {
                $coordenadas_paqueteria[] = $coordenada;
                $stats_por_sucursal[$item['sucursal']]['paqueteria']++;
            }
            $stats_por_sucursal[$item['sucursal']]['total']++;

            $geocoded++;

            // Pequeña pausa para no saturar la API
            usleep(100000); // 0.1 segundos
        }
    }

    // Preparar estadísticas generales
    $stats = [
        'total' => count($coordenadas_domicilio) + count($coordenadas_paqueteria),
        'domicilio' => count($coordenadas_domicilio),
        'paqueteria' => count($coordenadas_paqueteria),
        'por_sucursal' => $stats_por_sucursal
    ];

    // Responder con los datos
    echo json_encode([
        'success' => true,
        'domicilio' => $coordenadas_domicilio,
        'paqueteria' => $coordenadas_paqueteria,
        'stats' => $stats
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al procesar la solicitud: ' . $e->getMessage()
    ]);
}

$conn->close();

/**
 * Geocodifica una dirección usando Mapbox Geocoding API
 * @param string $direccion La dirección a geocodificar
 * @return array|null Array con 'lat' y 'lng', o null si falla
 */
function geocodificarDireccion($direccion) {
    // Agregar contexto de Guadalajara para mejor precisión
    $query = urlencode($direccion . ', Guadalajara, Jalisco, México');

    // Token de Mapbox (usar el mismo del proyecto)
    $accessToken = 'pk.eyJ1IjoiYWNhc2lsbGFzNzY2IiwiYSI6ImNsdW12cTZyMjB4NnMya213MDdseXp6ZGgifQ.t7-l1lQfd8mgHILM5YrdNw';

    $url = "https://api.mapbox.com/geocoding/v5/mapbox.places/{$query}.json?access_token={$accessToken}&limit=1&country=MX&proximity=-103.3494,20.6597";

    // Hacer la petición
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $response !== false) {
        $data = json_decode($response, true);

        if (isset($data['features']) && count($data['features']) > 0) {
            $coords = $data['features'][0]['geometry']['coordinates'];

            // Mapbox devuelve [lng, lat], necesitamos [lat, lng]
            $lng = floatval($coords[0]);
            $lat = floatval($coords[1]);

            // Validar que estén en el rango de Guadalajara
            if ($lat >= 19.0 && $lat <= 21.5 && $lng >= -104.5 && $lng <= -102.0) {
                return [
                    'lat' => $lat,
                    'lng' => $lng
                ];
            }
        }
    }

    return null;
}
?>
