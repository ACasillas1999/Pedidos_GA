-- Agregar columna tipo_zona a la tabla pedidos
-- Esta columna almacenará si el pedido es LOCAL (dentro de ZMG) o FORANEO (fuera de ZMG)

ALTER TABLE pedidos 
ADD COLUMN tipo_zona VARCHAR(20) DEFAULT NULL 
COMMENT 'LOCAL o FORANEO según coordenadas de destino';

-- Crear índice para mejorar el rendimiento de consultas filtradas por zona
CREATE INDEX idx_tipo_zona ON pedidos(tipo_zona);
