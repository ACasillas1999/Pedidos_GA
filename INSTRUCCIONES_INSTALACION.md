# Instrucciones de Instalación - Modal de Captura de Destinatario

## Resumen de Cambios

Se ha implementado un sistema completo para capturar datos detallados del destinatario en pedidos de paquetería, incluyendo:
- Modal interactivo con mapa de Mapbox y autocompletado de direcciones
- Almacenamiento de coordenadas precisas para el mapa de calor
- Campos flexibles para datos de paquetería (no solo D8A)
- Indicador visual de pedidos con datos capturados

## Paso 1: Ejecutar Script SQL

Debes ejecutar el siguiente script SQL en tu base de datos `gpoascen_pedidos_app`:

**Opción A: Desde phpMyAdmin**
1. Abre phpMyAdmin en http://192.168.60.194/phpmyadmin
2. Selecciona la base de datos `gpoascen_pedidos_app`
3. Ve a la pestaña "SQL"
4. Copia y pega el contenido del archivo `sql_updates/crear_tabla_destinatarios.sql`
5. Haz clic en "Continuar"

**Opción B: Desde línea de comandos**
```bash
cd "\\192.168.60.194\xampp\mysql\bin"
.\mysql.exe -u root gpoascen_pedidos_app < "\\192.168.60.194\xampp\htdocs\Pedidos_GA\sql_updates\crear_tabla_destinatarios.sql"
```

## Paso 2: Verificar Archivos Creados/Modificados

### Archivos Nuevos:
- ✅ `guardar_destinatario.php` - API para guardar datos del destinatario
- ✅ `obtener_destinatario.php` - API para obtener datos del destinatario
- ✅ `sql_updates/crear_tabla_destinatarios.sql` - Script SQL para crear tabla

### Archivos Modificados:
- ✅ `filtrar.php` - Botón con modal interactivo de Mapbox
- ✅ `descargar_plantilla_paqueteria.php` - Usa datos del destinatario
- ✅ `api_mapa_datos.php` - Prioriza coordenadas capturadas

## Paso 3: Actualizar Plantilla de Word (Opcional)

Si deseas aprovechar todos los campos nuevos en el documento Word generado, puedes actualizar la plantilla en:

`Machotes/Paqueteria/Plantilla_Paqueteria.docx`

### Marcadores Disponibles (usar con ${nombre}):

**Remitente:**
- `${Referencia}` - Folio de factura o ID del pedido
- `${Sucursal_Origen}` - Sucursal que envía
- `${Tipo_Envio}` - Tipo de envío (PAQUETERÍA)
- `${Estado}` - Estado del pedido
- `${Vendedor}` - Vendedor asignado
- `${Chofer}` - Chofer asignado
- `${Fecha_Recepcion}` - Fecha de recepción de factura
- `${FechaHoy}` - Fecha actual en español

**Destinatario:**
- `${Nombre_Destinatario}` - Nombre del destinatario
- `${Direccion}` - Dirección completa formateada
- `${Calle}` - Calle (campo individual)
- `${No_Exterior}` - Número exterior
- `${No_Interior}` - Número interior
- `${Entre_Calles}` - Entre qué calles
- `${Colonia}` - Colonia
- `${Codigo_Postal}` - Código postal
- `${Ciudad}` - Ciudad
- `${Estado_Destino}` - Estado
- `${Telefono}` - Teléfono del destinatario
- `${Contacto}` - Contacto adicional

**Paquetería:**
- `${Nombre_Paqueteria}` - Nombre de la paquetería (D8A, Estafeta, FedEx, etc.)
- `${Tipo_Cobro}` - Tipo de cobro (OCURRE X COBRAR, PREPAGADO, etc.)
- `${ATN}` - Atención a
- `${Num_Cliente}` - Número de cliente
- `${Clave_SAT}` - Clave SAT

**Otros:**
- `${Observaciones}` - Campo vacío para editar después

## Paso 4: Probar la Funcionalidad

1. Ingresa al sistema como usuario con rol **JC** (Jefe de Choferes) o **Admin**
2. Ve a la vista de pedidos (filtrar.php)
3. Busca un pedido con tipo de envío "PAQUETERÍA"
4. Verás un botón gris que dice **"Capturar Destino"**
5. Haz clic en el botón para abrir el modal
6. En el modal podrás:
   - Buscar la dirección con autocompletado
   - Hacer clic en el mapa para seleccionar ubicación
   - Arrastrar el marcador para ajustar coordenadas
   - Llenar todos los campos del destinatario
   - Agregar datos de paquetería (flexibles, no predefinidos)
7. Al guardar, el botón cambiará a verde con un check ✓ y dirá **"Plantilla"**
8. Próximas veces que hagas clic, podrás elegir entre:
   - Descargar Plantilla
   - Editar Destinatario

## Paso 5: Verificar Integración con Mapa de Calor

1. Ve al mapa de calor (`mapa_calor.php`)
2. Los pedidos de paquetería con destinatario capturado ahora mostrarán coordenadas más precisas
3. Esto mejorará el análisis de zonas de entrega

## Características Implementadas

✅ **Modal Interactivo con Mapbox**
- Mapa interactivo para seleccionar ubicación exacta
- Geocoder con autocompletado de direcciones de México
- Marcador arrastrable para ajustar coordenadas
- Auto-llenado de campos al buscar dirección

✅ **Campos Flexibles de Paquetería**
- Nombre de paquetería (texto libre: D8A, Estafeta, FedEx, etc.)
- Tipo de cobro (texto libre: OCURRE X COBRAR, PREPAGADO, etc.)
- ATN, Número de Cliente, Clave SAT

✅ **Indicador Visual**
- Botón gris "Capturar Destino" cuando no hay datos
- Botón verde con ✓ "Plantilla" cuando ya tiene datos capturados

✅ **Integración con Plantilla Word**
- Los datos capturados se usan automáticamente al generar la plantilla
- Fallback a datos originales del pedido si no hay datos capturados

✅ **Mejora del Mapa de Calor**
- Prioriza coordenadas capturadas (más precisas)
- Fallback a coordenadas existentes si no hay datos capturados

## Permisos de Acceso

- **Admin**: Acceso completo
- **JC (Jefe de Choferes)**: Acceso completo
- **Otros roles**: Sin acceso al modal de captura

## Notas Técnicas

- Token de Mapbox ya está configurado (el mismo del mapa de calor)
- Las coordenadas se guardan con 8 decimales de precisión
- Se valida que las coordenadas estén dentro del rango de Guadalajara
- La tabla `pedidos_destinatario` tiene relación 1:1 con `pedidos` (un destinatario por pedido)
- Al guardar/actualizar datos, se actualiza automáticamente `Coord_Destino` en la tabla `pedidos`

## Troubleshooting

**El modal no se abre:**
- Verifica que SweetAlert2 esté cargado en la página
- Revisa la consola del navegador para errores JavaScript

**El mapa no se muestra:**
- Verifica que el token de Mapbox sea válido
- Revisa que los scripts de Mapbox se carguen correctamente

**Error al guardar:**
- Verifica que la tabla `pedidos_destinatario` exista en la base de datos
- Verifica permisos de escritura en la base de datos

**No actualiza el botón después de guardar:**
- Verifica que el campo `tiene_destinatario_capturado` exista en la tabla `pedidos`
- La página se recarga automáticamente después de guardar

## Soporte

Si encuentras algún problema, verifica:
1. Que el script SQL se haya ejecutado correctamente
2. Que todos los archivos estén en su lugar
3. Los logs de errores de PHP en `xampp/apache/logs/error.log`
4. La consola del navegador para errores JavaScript
