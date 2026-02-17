<?php
/**
 * Funciones para determinar si un pedido es LOCAL o FORÁNEO
 * basándose en las coordenadas de destino y la Zona Metropolitana de Guadalajara
 */

require_once __DIR__ . '/config_zona_gdl.php';

/**
 * Determina si un punto está dentro de un polígono usando el algoritmo Ray Casting
 * 
 * @param float $lat Latitud del punto a verificar
 * @param float $lng Longitud del punto a verificar
 * @param array $poligono Array de puntos que definen el polígono
 * @return bool True si el punto está dentro del polígono, False si está fuera
 */
function puntoEnPoligono($lat, $lng, $poligono) {
    $numVertices = count($poligono);
    $dentro = false;
    
    // Algoritmo Ray Casting
    // Cuenta cuántas veces una línea horizontal desde el punto cruza los bordes del polígono
    // Si cruza un número impar de veces, el punto está dentro
    for ($i = 0, $j = $numVertices - 1; $i < $numVertices; $j = $i++) {
        $xi = $poligono[$i]['lat'];
        $yi = $poligono[$i]['lng'];
        $xj = $poligono[$j]['lat'];
        $yj = $poligono[$j]['lng'];
        
        $intersecta = (($yi > $lng) != ($yj > $lng))
            && ($lat < ($xj - $xi) * ($lng - $yi) / ($yj - $yi) + $xi);
        
        if ($intersecta) {
            $dentro = !$dentro;
        }
    }
    
    return $dentro;
}

/**
 * Parsea una cadena de coordenadas en formato "lat, lng" o "lat,lng"
 * 
 * @param string $coordenadas Cadena con coordenadas (ej: "20.6597, -103.3494")
 * @return array|null Array con ['lat' => float, 'lng' => float] o null si es inválido
 */
function parsearCoordenadas($coordenadas) {
    if (empty($coordenadas)) {
        return null;
    }
    
    // Limpiar espacios y separar por coma
    $coordenadas = trim($coordenadas);
    $partes = explode(',', $coordenadas);
    
    if (count($partes) != 2) {
        return null;
    }
    
    $lat = floatval(trim($partes[0]));
    $lng = floatval(trim($partes[1]));
    
    // Validar que las coordenadas estén en rangos válidos
    if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
        return null;
    }
    
    // Validar que no sean coordenadas (0, 0) que probablemente son errores
    if ($lat == 0 && $lng == 0) {
        return null;
    }
    
    return ['lat' => $lat, 'lng' => $lng];
}

/**
 * Determina el tipo de zona (LOCAL o FORÁNEO) basándose en las coordenadas de destino
 * 
 * @param string $coordenadas Cadena con coordenadas de destino (ej: "20.6597, -103.3494")
 * @return string|null 'LOCAL', 'FORANEO', o null si las coordenadas son inválidas
 */
function determinarTipoZona($coordenadas) {
    // Parsear coordenadas
    $punto = parsearCoordenadas($coordenadas);
    
    if ($punto === null) {
        return null; // Coordenadas inválidas
    }
    
    // Obtener polígono de la ZMG
    $poligono = obtenerPoligonoZMG();
    
    // Verificar si el punto está dentro del polígono
    $estaDentro = puntoEnPoligono($punto['lat'], $punto['lng'], $poligono);
    
    return $estaDentro ? 'LOCAL' : 'FORANEO';
}

/**
 * Calcula la distancia aproximada en kilómetros entre dos puntos usando la fórmula de Haversine
 * (Útil para debugging o estadísticas)
 * 
 * @param float $lat1 Latitud del primer punto
 * @param float $lng1 Longitud del primer punto
 * @param float $lat2 Latitud del segundo punto
 * @param float $lng2 Longitud del segundo punto
 * @return float Distancia en kilómetros
 */
function calcularDistancia($lat1, $lng1, $lat2, $lng2) {
    $radioTierra = 6371; // Radio de la Tierra en kilómetros
    
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    
    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLng / 2) * sin($dLng / 2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    
    return $radioTierra * $c;
}

/**
 * Obtiene la distancia desde el centro de Guadalajara a un punto dado
 * 
 * @param string $coordenadas Cadena con coordenadas (ej: "20.6597, -103.3494")
 * @return float|null Distancia en kilómetros o null si las coordenadas son inválidas
 */
function distanciaDesdeGDL($coordenadas) {
    $punto = parsearCoordenadas($coordenadas);
    
    if ($punto === null) {
        return null;
    }
    
    $centro = obtenerCentroZMG();
    
    return calcularDistancia(
        $centro['lat'],
        $centro['lng'],
        $punto['lat'],
        $punto['lng']
    );
}
?>
