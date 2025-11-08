# ⚠️ EJECUTAR PRIMERO - Modal de Destinatario

## Paso 1: Ejecutar el Script SQL

Antes de probar el modal, **DEBES ejecutar el script SQL** para crear las tablas necesarias.

### Opción A: Desde el navegador con phpMyAdmin

1. Abre tu navegador y ve a: **http://192.168.60.194/phpmyadmin**
2. En el panel izquierdo, haz clic en la base de datos **`gpoascen_pedidos_app`**
3. Haz clic en la pestaña **"SQL"** en la parte superior
4. **Copia y pega** todo el contenido del archivo `sql_updates/crear_tabla_destinatarios.sql`
5. Haz clic en el botón **"Continuar"** o **"Go"**
6. Deberías ver un mensaje de éxito

### Opción B: Desde línea de comandos

Abre PowerShell o CMD y ejecuta:

```bash
cd "\\192.168.60.194\xampp\mysql\bin"
.\mysql.exe -u root gpoascen_pedidos_app < "\\192.168.60.194\xampp\htdocs\Pedidos_GA\sql_updates\crear_tabla_destinatarios.sql"
```

## Paso 2: Verificar que se creó correctamente

En phpMyAdmin:
1. Actualiza la lista de tablas (F5)
2. Deberías ver una nueva tabla llamada **`pedidos_destinatario`**
3. Haz clic en la tabla y verifica que tenga los campos:
   - id
   - pedido_id
   - nombre_destinatario
   - calle, no_exterior, no_interior, etc.
   - lat, lng
   - nombre_paqueteria, tipo_cobro, atn, num_cliente, clave_sat

4. También verifica que la tabla **`pedidos`** tenga un nuevo campo llamado **`tiene_destinatario_capturado`**

## Paso 3: Probar el Modal

1. Ingresa al sistema como usuario **JC** o **Admin**
2. Ve a la lista de pedidos
3. Busca un pedido con tipo de envío **"PAQUETERÍA"**
4. Deberías ver un botón gris que dice **"Capturar Destino"**
5. Haz clic en el botón
6. Debería abrirse un modal con:
   - Un mapa interactivo
   - Barra de búsqueda de direcciones
   - Formularios para capturar datos

## ⚠️ Si el modal no se abre

Abre la consola del navegador (F12) y verifica si hay errores. Los errores comunes son:

1. **"mapboxgl is not defined"** - Los scripts de Mapbox no se cargaron
2. **"Swal is not defined"** - SweetAlert2 no se cargó
3. **Error de SQL** - No ejecutaste el script de base de datos

## 🎯 Archivos Modificados/Creados

- ✅ `Pedidos_GA.php` - Agregado Mapbox y scripts
- ✅ `filtrar.php` - Modificado botón de plantilla
- ✅ `js/modal_destinatario.js` - Nuevo script del modal
- ✅ `guardar_destinatario.php` - API para guardar
- ✅ `obtener_destinatario.php` - API para obtener
- ✅ `descargar_plantilla_paqueteria.php` - Actualizado para usar destinatario
- ✅ `api_mapa_datos.php` - Actualizado para coordenadas precisas
- ✅ `sql_updates/crear_tabla_destinatarios.sql` - Script SQL

## 📞 Soporte

Si tienes problemas:
1. Verifica que ejecutaste el script SQL
2. Revisa la consola del navegador (F12)
3. Verifica que los archivos estén en su lugar
