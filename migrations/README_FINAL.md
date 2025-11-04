# Migración FINAL: Vehículos Particulares + Responsables (Versión Usuario)

## Fecha: 2025-11-04

## Descripción
Esta migración agrega la funcionalidad completa para distinguir entre vehículos particulares y de servicio. Los vehículos particulares no pueden tener chofer asignado, pero sí pueden tener un responsable asignado que se selecciona desde la tabla `usuarios` o se puede escribir manualmente.

## Pasos para aplicar la migración

### Opción A: Ejecutar el script completo

Desde phpMyAdmin o línea de comandos:

```bash
mysql -u root gpoascen_pedidos_app < migrations/migracion_final_completa.sql
```

### Opción B: Ejecutar comandos manualmente

```sql
-- Paso 1: Campo para tipo de vehículo
ALTER TABLE vehiculos
ADD COLUMN es_particular TINYINT(1) NOT NULL DEFAULT 0
COMMENT '0=Servicio, 1=Particular';

-- Paso 2: Limpiar campo antiguo si existe
ALTER TABLE vehiculos DROP COLUMN IF EXISTS id_responsable;

-- Paso 3: Campo responsable (texto libre)
ALTER TABLE vehiculos
ADD COLUMN responsable VARCHAR(100) NULL
COMMENT 'Nombre del responsable (texto libre o desde usuarios)';
```

### Verificar la migración

```sql
DESCRIBE vehiculos;
```

Deberías ver:
- `es_particular` TINYINT(1) DEFAULT 0
- `responsable` VARCHAR(100) DEFAULT NULL

## Cambios realizados

### 🗄️ Base de datos
- ✅ Campo `es_particular` para marcar vehículos particulares
- ✅ Campo `responsable` como VARCHAR (texto libre)
- ✅ Eliminada columna `id_responsable` (versión anterior con FK)

### 🔧 Backend (PHP)

#### [detalles_vehiculo.php](../detalles_vehiculo.php)
- ✅ **Input con datalist** en lugar de select
- ✅ Carga usuarios desde tabla `usuarios`
- ✅ Permite escribir manualmente si el usuario no existe
- ✅ Muestra Rol y Sucursal en las opciones del datalist
- ✅ Autocompletado con `autocomplete="off"`
- ✅ Pre-carga el responsable actual si existe

**Líneas modificadas:**
- 397-425: POST handlers para asignar/desasignar responsable
- 1131-1176: Formulario HTML con input + datalist

#### [vehiculos.php](../vehiculos.php)
- ✅ Lee campo `responsable` (texto) directamente
- ✅ Muestra badge con emoji 👤 y nombre del responsable
- ✅ Sin necesidad de JOIN con tabla usuarios
- ✅ Simplificado: solo muestra el texto guardado

**Líneas modificadas:**
- 317-318: Obtiene responsable como texto
- 1233-1239: Badge de responsable simplificado

### 🎨 Interfaz

#### Formulario de responsable (detalles_vehiculo.php)
```html
<input
  type="text"
  name="responsable"
  placeholder="Selecciona o escribe el nombre..."
  list="listaUsuarios"
  autocomplete="off">
<datalist id="listaUsuarios">
  <!-- Opciones desde tabla usuarios -->
</datalist>
```

**Características:**
- ✅ Dropdown con lista de usuarios desde tabla `usuarios`
- ✅ Muestra username, Rol y Sucursal en cada opción
- ✅ Permite escribir texto libre si no está en la lista
- ✅ Autocompletado mientras escribes
- ✅ Pre-carga valor actual

#### Cards de vehículos (vehiculos.php)
- Badge azul "🏠 Particular" en esquina superior derecha
- Chip azul con "👤 [Nombre Responsable]" si tiene asignado
- Footer: "Responsable: [nombre]" o "Sin responsable"

## Funcionalidad

### ✨ Asignar responsable a vehículo particular

1. Ve a detalles del vehículo particular
2. Pestaña "Historial de Conductores"
3. Verás input con autocompletado
4. **Opción 1**: Haz clic y selecciona un usuario de la lista
5. **Opción 2**: Escribe directamente un nombre (ej. "Juan Pérez")
6. Haz clic en "Guardar"

### 📋 Lista de usuarios

El datalist carga desde tabla `usuarios` con formato:
```
username — Rol (Sucursal)
```

Ejemplos:
- `JC.DEASA — JC (DEASA)`
- `Admin — Admin (TODAS)`
- `m.lemus — VR (DIMEGSA)`

### 🔒 Validaciones

- ✅ Solo vehículos particulares pueden tener responsable
- ✅ Campo obligatorio (no se puede guardar vacío)
- ✅ Acepta cualquier texto (usuarios de lista o manual)
- ✅ Se puede cambiar o quitar en cualquier momento

## Ejemplos de uso

### Marcar vehículo como particular y asignar responsable

```sql
-- Marcar vehículo 83 como particular
UPDATE vehiculos SET es_particular = 1 WHERE id_vehiculo = 83;

-- Asignar responsable manualmente (opcional)
UPDATE vehiculos SET responsable = 'Juan Pérez' WHERE id_vehiculo = 83;
```

### Ver todos los vehículos particulares con responsable

```sql
SELECT id_vehiculo, placa, tipo, responsable, Sucursal
FROM vehiculos
WHERE es_particular = 1;
```

### Buscar vehículos sin responsable

```sql
SELECT id_vehiculo, placa, tipo
FROM vehiculos
WHERE es_particular = 1
  AND (responsable IS NULL OR responsable = '');
```

## Ventajas del nuevo sistema

### ✅ Flexibilidad
- No requiere que el responsable esté en la base de datos
- Útil para vehículos prestados temporalmente
- Permite nombres completos legibles

### ✅ Simplicidad
- Sin foreign keys que puedan fallar
- Sin JOINs complejos en consultas
- Más rápido de implementar y mantener

### ✅ Usabilidad
- Autocompletado desde usuarios existentes
- Permite escribir texto libre
- Pre-carga el valor actual

## Notas técnicas

### Diferencias con versión anterior

| Aspecto | Versión Anterior | Versión Nueva |
|---------|------------------|---------------|
| Campo | `id_responsable INT` | `responsable VARCHAR(100)` |
| Fuente | Tabla `choferes` | Tabla `usuarios` + texto libre |
| Control | SELECT dropdown | INPUT + DATALIST |
| Validación | FK constraint | Ninguna (texto libre) |

### Compatibilidad

Si ya ejecutaste la migración anterior con `id_responsable`:
1. El script borra automáticamente la columna antigua
2. Crea la nueva columna `responsable` como texto
3. Los datos anteriores se pierden (necesitas reasignar)

## Testing

### Caso 1: Seleccionar desde lista
1. ✅ Marca vehículo 83 como particular
2. ✅ Ve a detalles → Historial Conductores
3. ✅ Haz clic en input → aparece lista de usuarios
4. ✅ Selecciona "JC.DEASA"
5. ✅ Guarda → debe aparecer en la card

### Caso 2: Escribir manualmente
1. ✅ En el input, escribe "Roberto Gómez"
2. ✅ Guarda sin seleccionar de la lista
3. ✅ Debe guardarse correctamente
4. ✅ Aparece en la card como "👤 Roberto Gómez"

### Caso 3: Cambiar responsable
1. ✅ Cambia a otro usuario de la lista
2. ✅ Debe actualizar en la card

### Caso 4: Quitar responsable
1. ✅ Haz clic en "Quitar responsable"
2. ✅ Confirma
3. ✅ Debe desaparecer de la card

## Rollback

Si necesitas revertir:

```sql
ALTER TABLE vehiculos DROP COLUMN responsable;
ALTER TABLE vehiculos DROP COLUMN es_particular;
```

**⚠️ IMPORTANTE**: Haz backup antes de aplicar la migración.

## Archivos modificados

### Backend
- `detalles_vehiculo.php` (líneas 397-425, 1131-1176)
- `vehiculos.php` (líneas 317-318, 362, 1233-1239, 1267)
- `NuevoVehiculo.php` (checkbox es_particular)
- `vehiculos_disponibles.php` (filtro es_particular)
- `asignar_vehiculo.php` (validación es_particular)

### SQL
- `migrations/migracion_final_completa.sql` ⭐ **USAR ESTE**
- `migrations/actualizar_responsable_texto.sql`
- `migrations/README_FINAL.md` ⭐ **LEER ESTE**

---

**Versión:** 2.0 Final
**Autor:** Claude
**Fecha:** 2025-11-04
