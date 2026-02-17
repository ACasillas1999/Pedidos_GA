
CREATE TABLE IF NOT EXISTS zonas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    coordenadas JSON NOT NULL COMMENT 'Array de objetos {lat, lng}',
    color VARCHAR(20) DEFAULT '#ed6b1f',
    estado ENUM('ACTIVO', 'INACTIVO') DEFAULT 'ACTIVO',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar la zona inicial (ZMG) si no existe
INSERT INTO zonas (nombre, descripcion, coordenadas, color, estado)
SELECT * FROM (SELECT 
    'Zona Metropolitana de Guadalajara' as nombre,
    'Polígono inicial definido en código' as descripcion,
    '[
    {"lat": 20.7800, "lng": -103.4200},
    {"lat": 20.7800, "lng": -103.3000},
    {"lat": 20.7200, "lng": -103.2000},
    {"lat": 20.6500, "lng": -103.1500},
    {"lat": 20.5500, "lng": -103.2000},
    {"lat": 20.4200, "lng": -103.3000},
    {"lat": 20.4500, "lng": -103.4500},
    {"lat": 20.6500, "lng": -103.5200},
    {"lat": 20.7500, "lng": -103.5000}
]' as coordenadas,
    '#ed6b1f' as color,
    'ACTIVO' as estado
) AS tmp
WHERE NOT EXISTS (
    SELECT nombre FROM zonas WHERE nombre = 'Zona Metropolitana de Guadalajara'
) LIMIT 1;
