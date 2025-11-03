-- Agregar campo para distinguir entre vehículos particulares y de servicio
-- Fecha: 2025-11-03

ALTER TABLE vehiculos
ADD COLUMN es_particular TINYINT(1) NOT NULL DEFAULT 0
COMMENT '0=Servicio (puede tener chofer asignado), 1=Particular (no puede tener chofer)';
