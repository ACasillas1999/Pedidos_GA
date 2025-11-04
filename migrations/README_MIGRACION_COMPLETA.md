# Migración: Vehículos Particulares vs Servicio + Responsables

## Fecha: 2025-11-04

## Descripción
Esta migración agrega la funcionalidad para distinguir entre vehículos particulares y de servicio. Los vehículos particulares no pueden tener chofer asignado, pero sí pueden tener un responsable asignado.

## Pasos para aplicar la migración

### 1. Ejecutar las migraciones SQL

Ejecuta estos comandos **en orden**:

#### Paso 1: Agregar campo `es_particular`

```sql
ALTER TABLE vehiculos
ADD COLUMN es_particular TINYINT(1) NOT NULL DEFAULT 0
COMMENT '0=Servicio (puede tener chofer asignado), 1=Particular (no puede tener chofer)';
```

#### Paso 2: Agregar campo `id_responsable`

```sql
ALTER TABLE vehiculos
ADD COLUMN id_responsable INT NULL
COMMENT 'ID del usuario responsable del vehículo particular (solo para es_particular=1)';
```

### 2. Verificar la migración

Ejecuta esta consulta para verificar que los campos se agregaron correctamente:

```sql
DESCRIBE vehiculos;
```

Deberías ver:
- Campo `es_particular` de tipo `tinyint(1)` con valor por defecto `0`
- Campo `id_responsable` de tipo `int(11)` con valor por defecto `NULL`

### 3. Marcar vehículos existentes como particulares (opcional)

Si tienes vehículos que ya son particulares, márcalos así:

```sql
-- Ejemplo: marcar vehículo con ID 83 como particular
UPDATE vehiculos SET es_particular = 1 WHERE id_vehiculo = 83;
```

## Cambios realizados

### Base de datos
- ✅ Agregado campo `es_particular` a la tabla `vehiculos`
- ✅ Agregado campo `id_responsable` a la tabla `vehiculos`

### Backend (PHP)
- ✅ Modificado `NuevoVehiculo.php` para incluir checkbox y guardar el tipo de vehículo
- ✅ Modificado `vehiculos.php` para incluir el campo en las consultas y mostrar responsable
- ✅ Modificado `vehiculos_disponibles.php` para excluir vehículos particulares de la lista
- ✅ Modificado `asignar_vehiculo.php` para validar que no se asignen choferes a particulares
- ✅ Modificado `detalles_vehiculo.php` para:
  - Agregar validación POST de asignación de chofer
  - Agregar formulario de asignación de responsable para particulares
  - Ocultar formulario de chofer en particulares

### Frontend (JavaScript)
- ✅ Actualizado `vehicleCard()` para mostrar badge "Particular"
- ✅ Agregado badge de responsable en vehículos particulares
- ✅ Ocultado botón "Quitar" en vehículos particulares
- ✅ Cambiado texto del footer según tipo de vehículo y responsable

## Funcionalidad

### Al agregar un vehículo nuevo
- Aparece un checkbox "🏠 Marcar como vehículo particular"
- Si se marca, el vehículo NO podrá tener chofer asignado
- Se puede asignar un responsable en lugar de chofer

### En la vista de vehículos (vehiculos.php)
- Los vehículos particulares muestran un badge azul "🏠 Particular" en la esquina superior derecha
- Si tiene responsable asignado, muestra un chip azul con "Resp: [nombre]" y avatar
- El footer muestra "Responsable: [nombre]" o "Sin responsable"
- No aparece el botón "Quitar" ya que no pueden tener chofer

### En detalles del vehículo (detalles_vehiculo.php)
- **Si es particular:**
  - Muestra mensaje "🏠 Vehículo particular - Asignar responsable"
  - Formulario para seleccionar responsable desde lista de usuarios activos
  - Botón para quitar responsable si ya tiene uno asignado
  - NO se muestra formulario de asignación de chofer

- **Si es de servicio:**
  - Funciona normal con asignación de chofer
  - NO se puede asignar responsable

### En la asignación de choferes
- Los vehículos particulares NO aparecen en la lista de vehículos disponibles
- Si se intenta asignar manualmente, la API rechaza la operación con un error
- Validación en 5 capas diferentes para máxima seguridad

## Notas importantes

1. **Compatibilidad**: Todos los vehículos existentes se marcan automáticamente como "de servicio" (es_particular = 0) por el valor DEFAULT

2. **Diferencia entre chofer y responsable**:
   - **Chofer**: Se asigna a vehículos de servicio, se registra en historial de conductores, se usa para pedidos
   - **Responsable**: Se asigna a vehículos particulares, NO se registra en historial de conductores, solo indica quién tiene el vehículo

3. **Validación en múltiples capas**:
   - Frontend: No muestra vehículos particulares en el selector de chofer
   - Backend: Valida antes de asignar que el vehículo no sea particular
   - UI: Oculta formularios de asignación según tipo de vehículo

4. **Reversibilidad**: Para cambiar tipo de vehículo:
   ```sql
   -- Marcar como particular
   UPDATE vehiculos SET es_particular = 1 WHERE id_vehiculo = X;

   -- Marcar como servicio (y quitar responsable)
   UPDATE vehiculos SET es_particular = 0, id_responsable = NULL WHERE id_vehiculo = X;
   ```

## Testing recomendado

### Para vehículos particulares:
1. ✅ Crear un vehículo nuevo marcado como particular
2. ✅ Verificar que aparece el badge "Particular" en la card
3. ✅ Verificar que NO aparece en lista de vehículos disponibles para asignar chofer
4. ✅ Intentar asignar chofer desde detalles → Debe mostrar solo formulario de responsable
5. ✅ Asignar un responsable → Debe aparecer en la card con chip azul
6. ✅ Quitar responsable → Debe desaparecer el chip

### Para vehículos de servicio:
1. ✅ Crear un vehículo de servicio normal
2. ✅ Verificar que NO aparece badge "Particular"
3. ✅ Verificar que SÍ aparece en lista de vehículos disponibles
4. ✅ Asignar chofer → Debe funcionar normalmente
5. ✅ Verificar que NO se puede asignar responsable

## Rollback (en caso de problemas)

Si necesitas revertir los cambios:

```sql
-- Eliminar campo responsable
ALTER TABLE vehiculos DROP COLUMN id_responsable;

-- Eliminar campo es_particular
ALTER TABLE vehiculos DROP COLUMN es_particular;
```

**IMPORTANTE**: Haz un backup de la base de datos antes de aplicar cualquier migración.

## Archivos de migración SQL

- `agregar_tipo_vehiculo.sql` - Agrega campo `es_particular`
- `agregar_responsable_vehiculo.sql` - Agrega campo `id_responsable`
