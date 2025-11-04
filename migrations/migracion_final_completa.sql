-- =========================================================
-- Migración FINAL: Vehículos Particulares + Responsables
-- Fecha: 2025-11-04
-- =========================================================

-- PASO 1: Agregar campo para distinguir vehículos particulares
ALTER TABLE vehiculos
ADD COLUMN es_particular TINYINT(1) NOT NULL DEFAULT 0
COMMENT '0=Servicio (puede tener chofer asignado), 1=Particular (no puede tener chofer)';

-- PASO 2: Eliminar columna id_responsable si existe (versión anterior)
ALTER TABLE vehiculos DROP COLUMN IF EXISTS id_responsable;

-- PASO 3: Agregar campo responsable como texto (permite escribir manualmente)
ALTER TABLE vehiculos
ADD COLUMN responsable VARCHAR(100) NULL
COMMENT 'Nombre del responsable del vehículo particular (texto libre o desde tabla usuarios)';

-- Verificación
SELECT 'Migración completada exitosamente!' AS status;
DESCRIBE vehiculos;
