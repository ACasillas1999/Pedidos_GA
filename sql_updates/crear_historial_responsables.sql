-- Crear tabla historial_responsables para registrar cambios de responsables en vehículos particulares
-- Fecha: 2025-01-08

CREATE TABLE IF NOT EXISTS historial_responsables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_vehiculo INT NOT NULL,
    nombre_responsable VARCHAR(255) NOT NULL,
    fecha_inicio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_fin DATETIME DEFAULT NULL,
    creado_por VARCHAR(100) DEFAULT NULL,
    INDEX idx_vehiculo (id_vehiculo),
    INDEX idx_fecha_inicio (fecha_inicio),
    INDEX idx_fecha_fin (fecha_fin),
    FOREIGN KEY (id_vehiculo) REFERENCES vehiculos(id_vehiculo) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Crear registro inicial para vehículos particulares que ya tienen responsable asignado
INSERT INTO historial_responsables (id_vehiculo, nombre_responsable, fecha_inicio, creado_por)
SELECT
    id_vehiculo,
    responsable,
    NOW() as fecha_inicio,
    'sistema' as creado_por
FROM vehiculos
WHERE es_particular = 1
AND responsable IS NOT NULL
AND responsable != ''
AND NOT EXISTS (
    SELECT 1 FROM historial_responsables hr
    WHERE hr.id_vehiculo = vehiculos.id_vehiculo
    AND hr.nombre_responsable = vehiculos.responsable
    AND hr.fecha_fin IS NULL
);
