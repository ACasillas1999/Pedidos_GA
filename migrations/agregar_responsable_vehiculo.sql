-- Agregar campo para responsable de vehículos particulares
-- Fecha: 2025-11-04

ALTER TABLE vehiculos
ADD COLUMN id_responsable INT NULL
COMMENT 'ID del usuario responsable del vehículo particular (solo para es_particular=1)';

-- Opcional: agregar foreign key si existe tabla de usuarios
-- ALTER TABLE vehiculos
-- ADD CONSTRAINT fk_vehiculo_responsable
-- FOREIGN KEY (id_responsable) REFERENCES choferes(ID)
-- ON DELETE SET NULL;
