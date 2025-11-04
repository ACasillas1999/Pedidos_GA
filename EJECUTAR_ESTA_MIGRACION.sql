-- =========================================================
-- ⚠️ EJECUTA ESTE SCRIPT EN phpMyAdmin
-- =========================================================
-- 1. Abre: http://192.168.60.194/phpmyadmin
-- 2. Selecciona la base de datos de Pedidos_GA (probablemente gpoascen_pedidos_app)
-- 3. Ve a la pestaña "SQL"
-- 4. Copia y pega TODO este script
-- 5. Haz clic en "Continuar"
-- =========================================================

-- Paso 1: Agregar campo es_particular (si no existe)
ALTER TABLE vehiculos
ADD COLUMN IF NOT EXISTS es_particular TINYINT(1) NOT NULL DEFAULT 0
COMMENT '0=Servicio, 1=Particular';

-- Paso 2: Eliminar columna id_responsable si existe (versión anterior)
ALTER TABLE vehiculos DROP COLUMN IF EXISTS id_responsable;

-- Paso 3: Agregar campo responsable como texto
ALTER TABLE vehiculos
ADD COLUMN IF NOT EXISTS responsable VARCHAR(100) NULL
COMMENT 'Nombre del responsable (texto libre o desde usuarios)';

-- Verificar que todo está correcto
SELECT 'Migración completada!' AS mensaje;
DESCRIBE vehiculos;
