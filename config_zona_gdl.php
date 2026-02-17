<?php
/**
 * Configuración de la Zona Metropolitana de Guadalajara
 * 
 * Este archivo define el polígono que delimita la Zona Metropolitana de Guadalajara.
 * Los pedidos con coordenadas de destino dentro de este polígono se clasifican como LOCAL.
 * Los pedidos fuera de este polígono se clasifican como FORÁNEO.
 * 
 * CÓMO AJUSTAR LA ZONA:
 * - Puedes agregar o quitar puntos del array $poligonoZMG
 * - Los puntos deben estar en orden (sentido horario o antihorario)
 * - Cada punto es un array con 'lat' (latitud) y 'lng' (longitud)
 * - Para expandir la zona, agrega puntos más alejados del centro
 * - Para reducir la zona, agrega puntos más cercanos al centro
 * 
 * HERRAMIENTAS ÚTILES:
 * - Google Maps: Click derecho en un punto -> "¿Qué hay aquí?" para obtener coordenadas
 * - https://www.keene.edu/campus/maps/tool/ - Herramienta para dibujar polígonos
 */

// Polígono que define la Zona Metropolitana de Guadalajara
// AHORA SE OBTIENE DESDE LA BASE DE DATOS (tabla 'zonas')

/**
 * Retorna el polígono de la Zona Metropolitana de Guadalajara
 * 
 * @return array Array de puntos que definen el polígono
 */
function obtenerPoligonoZMG() {
    global $conn;
    
    // Si la conexión no existe (por si se llama desde un contexto sin conexión global), intentar crearla o requerirla
    if (!isset($conn) || $conn->connect_error) {
        require_once __DIR__ . "/Conexiones/Conexion.php";
    }

    $sql = "SELECT coordenadas FROM zonas WHERE nombre = 'Zona Metropolitana de Guadalajara' AND estado = 'ACTIVO' LIMIT 1";
    $result = $conn->query($sql);

    if ($result && $row = $result->fetch_assoc()) {
        $coords = json_decode($row['coordenadas'], true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($coords)) {
            return $coords;
        }
    }

    // FALLBACK: Si falla la BD, usar el polígono default hardcoded
    return [
        ['lat' => 20.7800, 'lng' => -103.4200],
        ['lat' => 20.7800, 'lng' => -103.3000],
        ['lat' => 20.7200, 'lng' => -103.2000],
        ['lat' => 20.6500, 'lng' => -103.1500],
        ['lat' => 20.5500, 'lng' => -103.2000],
        ['lat' => 20.4200, 'lng' => -103.3000],
        ['lat' => 20.4500, 'lng' => -103.4500],
        ['lat' => 20.6500, 'lng' => -103.5200],
        ['lat' => 20.7500, 'lng' => -103.5000],
    ];
}

/**
 * Obtiene el centro aproximado de la ZMG (para referencia)
 * 
 * @return array Coordenadas del centro ['lat' => float, 'lng' => float]
 */
function obtenerCentroZMG() {
    return [
        'lat' => 20.6597,  // Centro de Guadalajara
        'lng' => -103.3494
    ];
}

/**
 * Obtiene información sobre la zona metropolitana
 * 
 * @return array Información descriptiva de la zona
 */
function obtenerInfoZMG() {
    return [
        'nombre' => 'Zona Metropolitana de Guadalajara',
        'municipios' => [
            'Guadalajara',
            'Zapopan',
            'Tlaquepaque',
            'Tonalá',
            'Tlajomulco de Zúñiga',
            'El Salto',
            'Juanacatlán',
            'Ixtlahuacán de los Membrillos'
        ],
        'area_aproximada_km2' => 2734,
        'radio_aproximado_km' => 30
    ];
}
?>
