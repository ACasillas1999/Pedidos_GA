-- Actualizar campo responsable para que sea texto en lugar de ID
-- Fecha: 2025-11-04

-- Si ya tienes el campo id_responsable (INT), primero eliminarlo
ALTER TABLE vehiculos DROP COLUMN IF EXISTS id_responsable;

-- Agregar nuevo campo responsable como texto
ALTER TABLE vehiculos
ADD COLUMN responsable VARCHAR(100) NULL
COMMENT 'Nombre del responsable del vehículo particular (puede escribirse manualmente)';
