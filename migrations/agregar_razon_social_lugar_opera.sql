-- =====================================================
-- Migración: Agregar campo de razón social
-- Fecha: 2025-01-04
-- Descripción:
--   Los vehículos pueden estar registrados bajo una razón social
--   diferente a la sucursal donde operan.
--   Ejemplo: Comprado por DEASA, pero opera en Tapatía (campo Sucursal)
-- =====================================================

-- Agregar campo razon_social (quien compra/posee el vehículo)
ALTER TABLE vehiculos
ADD COLUMN razon_social VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL
COMMENT 'Razón social bajo la cual se compró el vehículo'
AFTER Sucursal;

-- Nota:
-- - El campo Sucursal ya existente representa donde opera el vehículo
-- - El nuevo campo razon_social representa quien lo compró/posee
-- - Los vehículos existentes tendrán este campo como NULL.

-- Verificar la estructura actualizada
-- DESCRIBE vehiculos;
