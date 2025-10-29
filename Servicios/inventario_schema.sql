-- Esquema para inventario y servicios (costos y compatibilidad de vehículos)
-- Revísalo y ejecútalo en tu BD (gpoascen_pedidos_app)

-- INVENTARIO: costos y SKU
ALTER TABLE `inventario`
  ADD COLUMN IF NOT EXISTS `sku` VARCHAR(64) NULL AFTER `stock_maximo`,
  ADD COLUMN IF NOT EXISTS `costo` DECIMAL(10,2) NULL DEFAULT 0 AFTER `sku`,
  ADD COLUMN IF NOT EXISTS `precio` DECIMAL(10,2) NULL DEFAULT 0 AFTER `costo`,
  ADD COLUMN IF NOT EXISTS `activo` TINYINT(1) NOT NULL DEFAULT 1 AFTER `precio`,
  -- Unidad base: pieza, litro, metro, etc. (consumo/servicio)
  ADD COLUMN IF NOT EXISTS `base_unidad` VARCHAR(32) NULL AFTER `activo`,
  -- Empaque y factor: p.e. caja, rollo, etc.
  ADD COLUMN IF NOT EXISTS `presentacion_unidad` VARCHAR(32) NULL AFTER `base_unidad`,
  ADD COLUMN IF NOT EXISTS `presentacion_cantidad` DECIMAL(10,3) NOT NULL DEFAULT 1.000 AFTER `presentacion_unidad`;

-- Compatibilidad inventario-vehículo (si no existe)
CREATE TABLE IF NOT EXISTS `inventario_vehiculo` (
  `id_inventario` INT NOT NULL,
  `id_vehiculo` INT NOT NULL,
  PRIMARY KEY (`id_inventario`, `id_vehiculo`),
  KEY `idx_invveh_vehiculo` (`id_vehiculo`),
  CONSTRAINT `fk_invveh_inv` FOREIGN KEY (`id_inventario`) REFERENCES `inventario` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_invveh_veh` FOREIGN KEY (`id_vehiculo`) REFERENCES `vehiculos` (`id_vehiculo`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- SERVICIOS: costos base y precio de venta
ALTER TABLE `servicios`
  ADD COLUMN IF NOT EXISTS `costo_mano_obra` DECIMAL(10,2) NULL DEFAULT 0 AFTER `duracion_minutos`,
  ADD COLUMN IF NOT EXISTS `precio` DECIMAL(10,2) NULL DEFAULT 0 AFTER `costo_mano_obra`;

-- Materiales por servicio (si no existe)
CREATE TABLE IF NOT EXISTS `servicio_insumo` (
  `id_servicio` INT NOT NULL,
  `id_inventario` INT NOT NULL,
  `cantidad` DECIMAL(10,3) NOT NULL DEFAULT 1.000,
  KEY `idx_sinsumo_srv` (`id_servicio`),
  KEY `idx_sinsumo_inv` (`id_inventario`),
  CONSTRAINT `fk_sinsumo_srv` FOREIGN KEY (`id_servicio`) REFERENCES `servicios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sinsumo_inv` FOREIGN KEY (`id_inventario`) REFERENCES `inventario` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Si la tabla ya existe con cantidad INT, convertir a DECIMAL para permitir consumos fraccionarios
ALTER TABLE `servicio_insumo`
  MODIFY COLUMN `cantidad` DECIMAL(10,3) NOT NULL DEFAULT 1.000;

-- Compatibilidad servicio-vehículo (si no existe)
CREATE TABLE IF NOT EXISTS `servicio_vehiculo` (
  `id_servicio` INT NOT NULL,
  `id_vehiculo` INT NOT NULL,
  PRIMARY KEY (`id_servicio`,`id_vehiculo`),
  KEY `idx_sveh_vehiculo` (`id_vehiculo`),
  CONSTRAINT `fk_sveh_srv` FOREIGN KEY (`id_servicio`) REFERENCES `servicios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sveh_veh` FOREIGN KEY (`id_vehiculo`) REFERENCES `vehiculos` (`id_vehiculo`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Config de servicios: costo por minuto de mano de obra
CREATE TABLE IF NOT EXISTS `servicios_config` (
  `clave` VARCHAR(64) NOT NULL,
  `valor` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Establece el costo por minuto (ejemplo: 12 pesos/mes con 160 horas/mes -> 12 / (160*60) = 0.00125)
-- Reemplaza por tu valor real. Ejemplo: costo por minuto de $1.50
INSERT INTO `servicios_config` (`clave`,`valor`) VALUES ('costo_minuto_mo','1.50')
ON DUPLICATE KEY UPDATE `valor`=VALUES(`valor`);

-- Nota: Si tu MySQL no soporta ADD COLUMN IF NOT EXISTS, usa SHOW COLUMNS y luego ejecuta ADD COLUMN.
