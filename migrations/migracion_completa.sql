-- =========================================================
-- Migración completa: Vehículos Particulares + Responsables
-- Fecha: 2025-11-04
-- =========================================================

-- PASO 1: Agregar campo para distinguir vehículos particulares
ALTER TABLE vehiculos
ADD COLUMN es_particular TINYINT(1) NOT NULL DEFAULT 0
COMMENT '0=Servicio (puede tener chofer asignado), 1=Particular (no puede tener chofer)';

-- PASO 2: Agregar campo para responsable de vehículos particulares
ALTER TABLE vehiculos
ADD COLUMN id_responsable INT NULL
COMMENT 'ID del usuario responsable del vehículo particular (solo para es_particular=1)';

-- Verificación
SELECT 'Migración completada. Verificando estructura...' AS status;
DESCRIBE vehiculos;
