# Migración: Razón Social para Vehículos

## Descripción del Cambio

Se ha agregado un nuevo campo a la tabla `vehiculos` para permitir un mejor control de la información administrativa:

**`razon_social`**: Empresa/razón social bajo la cual se compró el vehículo

### Caso de Uso
Un vehículo puede estar registrado bajo una razón social (por ejemplo, DEASA) pero ser operado por una sucursal diferente (por ejemplo, Tapatía). El campo **Sucursal** ya existente representa donde opera el vehículo, mientras que el nuevo campo **razon_social** representa quien lo compró.

## Pasos de Instalación

### 1. Ejecutar la Migración SQL

Ejecuta el siguiente archivo SQL en tu base de datos MySQL:

```bash
mysql -u usuario -p gpoascen_pedidos_app < migrations/agregar_razon_social_lugar_opera.sql
```

O desde phpMyAdmin:
1. Abre phpMyAdmin
2. Selecciona la base de datos `gpoascen_pedidos_app`
3. Ve a la pestaña "SQL"
4. Copia y pega el contenido de `agregar_razon_social_lugar_opera.sql`
5. Haz clic en "Continuar"

### 2. Verificar los Cambios

Verifica que la columna se haya agregado correctamente:

```sql
DESCRIBE vehiculos;
```

Deberías ver la nueva columna:
- `razon_social` VARCHAR(255) NULL

## Archivos Modificados

### Backend (PHP)
1. **`NuevoVehiculo.php`**
   - Agregado campo en el formulario para razón social
   - Actualizada lógica de inserción para guardar este campo

2. **`vehiculos.php`**
   - Actualizado array de vehículos para incluir razon_social
   - Modificadas funciones de búsqueda para incluir razon_social
   - Actualizada función `vehicleCard` para mostrar razón social y donde opera (Sucursal)

### Base de Datos
- **`migrations/agregar_razon_social_lugar_opera.sql`**
   - Script de migración que agrega la columna a la tabla

## Funcionalidad Implementada

### 1. Formulario de Agregar Vehículo
- Nuevo campo desplegable para **Razón Social** (opcional)
- El campo **Sucursal** ya existente representa donde opera el vehículo
- El campo es opcional, permitiendo NULL en la base de datos

### 2. Visualización en Cards de Vehículos
- Si el vehículo tiene `razon_social`, se muestra con icono 🏢 en color verde
- Siempre se muestra el campo **Opera en** (Sucursal) con icono 📍 en color rojo
- Los campos se muestran en la sección de información del vehículo

### 3. Búsqueda y Filtros
- El buscador global ahora incluye razón social
- Los filtros por sucursal funcionan de forma independiente

## Ejemplo Visual

### Antes:
```
[Card de Vehículo]
NISSAN CABSTAR 2013
Tipo: NISSAN CABSTAR 2013 · Placa JT82413 · Sucursal: TAPATIA
```

### Después:
```
[Card de Vehículo]
NISSAN CABSTAR 2013
Tipo: NISSAN CABSTAR 2013 · Placa JT82413
🏢 Razón Social: DEASA
📍 Opera en: TAPATIA
```

## Notas Importantes

1. **Campo Opcional**: El campo razon_social es opcional. Los vehículos existentes tendrán este campo como NULL.

2. **Compatibilidad**: Los vehículos que no tienen razón social configurada seguirán funcionando normalmente.

3. **Actualización de Datos Existentes**: Si deseas actualizar vehículos existentes, puedes hacerlo mediante SQL:

```sql
UPDATE vehiculos
SET razon_social = 'DEASA'
WHERE id_vehiculo = 2;
```

## Rollback (Deshacer Cambios)

Si necesitas revertir estos cambios, ejecuta:

```sql
ALTER TABLE vehiculos DROP COLUMN razon_social;
```

## Soporte

Para preguntas o problemas con esta implementación, contacta al equipo de desarrollo.

---
**Fecha de Implementación**: 2025-01-04
**Versión**: 1.0
