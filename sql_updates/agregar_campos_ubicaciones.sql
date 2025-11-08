-- Script para agregar campos a tabla ubicaciones
-- Fecha: 2025-11-07
-- Propósito: Agregar nombre completo y teléfono de cada sucursal

-- Agregar columnas
ALTER TABLE `ubicaciones`
ADD COLUMN `NombreCompleto` VARCHAR(255) DEFAULT NULL AFTER `Ubicacion`,
ADD COLUMN `Telefono` VARCHAR(20) DEFAULT NULL AFTER `Direccion`;

-- Actualizar datos existentes
UPDATE `ubicaciones` SET `NombreCompleto` = 'ASCENCIO INDUSTRIAL ELECTRICA SA DE CV', `Telefono` = '+523338109101' WHERE `Ubicacion` = 'AIESA';
UPDATE `ubicaciones` SET `NombreCompleto` = 'DISTRIBUIDORA ELECTRICA ASCENCIO SA DE CV', `Telefono` = '+523336141989' WHERE `Ubicacion` = 'DEASA';
UPDATE `ubicaciones` SET `NombreCompleto` = 'DISTRIBUIDORA DE MATERIALES ELECTRICOS DE GUADALAJARA SA DE CV', `Telefono` = '+523336193060' WHERE `Ubicacion` = 'DIMEGSA';
UPDATE `ubicaciones` SET `NombreCompleto` = 'FERRETERIA Y SANITARIOS ASCENCIO SA DE CV', `Telefono` = '+523336580313' WHERE `Ubicacion` = 'FESA';
UPDATE `ubicaciones` SET `NombreCompleto` = 'GRUPO ASCENCIO DEL BAJIO SA DE CV', `Telefono` = '+524777136491' WHERE `Ubicacion` = 'GABSA';
UPDATE `ubicaciones` SET `NombreCompleto` = 'ELECTRO ILUMINACION TAPATIA SA DE CV', `Telefono` = '+523336501060' WHERE `Ubicacion` = 'ILUMINACION';
UPDATE `ubicaciones` SET `NombreCompleto` = 'SURTIDOR ELECTRICO GARIBALDI SA DE CV', `Telefono` = '+523338252572' WHERE `Ubicacion` = 'SEGSA';
UPDATE `ubicaciones` SET `NombreCompleto` = 'ELECTRO INDUSTRIAL TAPATIA SA DE CV', `Telefono` = '+523336191453' WHERE `Ubicacion` = 'TAPATIA';
UPDATE `ubicaciones` SET `NombreCompleto` = 'DISTRIBUIDORA DE MATERIALES ELECTRICOS DE GUADALAJARA SA DE CV', `Telefono` = '+523292962100' WHERE `Ubicacion` = 'VALLARTA';
UPDATE `ubicaciones` SET `NombreCompleto` = 'ASCENCIO INDUSTRIAL ELECTRICA SA DE CV', `Telefono` = '+524429269017' WHERE `Ubicacion` = 'QUERETARO';
UPDATE `ubicaciones` SET `NombreCompleto` = 'ASCENCIO SA DE CV', `Telefono` = '+523336141989' WHERE `Ubicacion` = 'CODI';

-- Verificar resultados
SELECT Ubicacion, NombreCompleto, Telefono FROM ubicaciones ORDER BY Ubicacion;
