-- Tabla para registrar múltiples incidencias por pedido en la App de Choferes
CREATE TABLE IF NOT EXISTS `pedidos_incidencias` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `pedido_id` INT NOT NULL,
  `motivo` VARCHAR(150) NOT NULL COMMENT 'Faltante, Sobrante, Producto erróneo, Cliente no recibe pedido, etc.',
  `comentarios` TEXT NULL,
  `archivo_ruta` VARCHAR(255) NULL,
  `archivo_nombre` VARCHAR(255) NULL,
  `archivo_tipo` VARCHAR(50) NULL COMMENT 'imagen, video, pdf, documento',
  `chofer` VARCHAR(100) NULL,
  `fecha_registro` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_pedido_id` (`pedido_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
