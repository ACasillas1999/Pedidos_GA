-- Script para crear tabla de destinatarios y agregar campo de control
-- Fecha: 2025-11-07
-- Propósito: Almacenar datos de destinatarios para pedidos de paquetería

-- Crear tabla pedidos_destinatario
CREATE TABLE IF NOT EXISTS `pedidos_destinatario` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `pedido_id` INT(11) NOT NULL,

  -- Datos del destinatario
  `nombre_destinatario` VARCHAR(200) DEFAULT NULL,
  `calle` VARCHAR(255) DEFAULT NULL,
  `no_exterior` VARCHAR(50) DEFAULT NULL,
  `no_interior` VARCHAR(50) DEFAULT NULL,
  `entre_calles` VARCHAR(255) DEFAULT NULL,
  `colonia` VARCHAR(100) DEFAULT NULL,
  `codigo_postal` VARCHAR(10) DEFAULT NULL,
  `ciudad` VARCHAR(100) DEFAULT NULL,
  `estado_destino` VARCHAR(100) DEFAULT NULL,
  `contacto_destino` VARCHAR(100) DEFAULT NULL,
  `telefono_destino` VARCHAR(55) DEFAULT NULL,

  -- Coordenadas exactas
  `lat` DECIMAL(10, 8) DEFAULT NULL,
  `lng` DECIMAL(11, 8) DEFAULT NULL,

  -- Datos de paquetería (campos flexibles)
  `nombre_paqueteria` VARCHAR(100) DEFAULT NULL COMMENT 'Ej: D8A, Estafeta, FedEx',
  `tipo_cobro` VARCHAR(100) DEFAULT NULL COMMENT 'Ej: OCURRE X COBRAR, PREPAGADO',
  `atn` VARCHAR(100) DEFAULT NULL COMMENT 'Atención a',
  `num_cliente` VARCHAR(100) DEFAULT NULL COMMENT 'Número de cliente',
  `clave_sat` VARCHAR(100) DEFAULT NULL COMMENT 'Clave SAT',

  -- Metadatos
  `fecha_captura` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `usuario_capturo` VARCHAR(100) DEFAULT NULL,

  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_pedido` (`pedido_id`),
  KEY `idx_pedido_id` (`pedido_id`),
  KEY `idx_coordenadas` (`lat`, `lng`),

  CONSTRAINT `fk_destinatario_pedido`
    FOREIGN KEY (`pedido_id`)
    REFERENCES `pedidos` (`ID`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Agregar campo de control en tabla pedidos
ALTER TABLE `pedidos`
ADD COLUMN IF NOT EXISTS `tiene_destinatario_capturado` TINYINT(1) DEFAULT 0
COMMENT '0=Sin capturar, 1=Capturado';

-- Crear índice para búsquedas rápidas
ALTER TABLE `pedidos`
ADD INDEX IF NOT EXISTS `idx_tiene_destinatario` (`tiene_destinatario_capturado`);

-- Comentario de la tabla
ALTER TABLE `pedidos_destinatario` COMMENT = 'Almacena información detallada del destinatario para pedidos de paquetería';
