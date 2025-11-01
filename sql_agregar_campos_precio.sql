-- Script para agregar campos de precio de factura al sistema de pedidos
-- Ejecutar este script en la base de datos

ALTER TABLE pedidos
ADD COLUMN precio_factura_vendedor DECIMAL(10,2) DEFAULT NULL COMMENT 'Precio ingresado por vendedor al crear el pedido',
ADD COLUMN precio_factura_real DECIMAL(10,2) DEFAULT NULL COMMENT 'Precio real validado/corregido por Jefe de Choferes',
ADD COLUMN precio_validado_jc TINYINT(1) DEFAULT 0 COMMENT '0=No validado, 1=Validado por JC',
ADD COLUMN fecha_validacion_precio DATETIME DEFAULT NULL COMMENT 'Fecha y hora cuando JC validó el precio',
ADD COLUMN usuario_validacion_precio VARCHAR(55) DEFAULT NULL COMMENT 'Usuario (JC) que validó el precio';

-- Verificar que las columnas se agregaron correctamente
DESCRIBE pedidos;
