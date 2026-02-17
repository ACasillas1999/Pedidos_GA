# SISTEMA DE LOGÍSTICA PEDIDOS GA
## Documentación Completa de Funcionalidades

---

**Versión del Documento:** 2.0
**Fecha:** 02 de Diciembre de 2025
**Empresa:** Grupo Ascendente
**Responsable:** Equipo de Desarrollo y Operaciones

---

## ÍNDICE DE CONTENIDO

1. [Resumen Ejecutivo](#resumen-ejecutivo)
2. [Arquitectura y Tecnologías](#arquitectura-y-tecnologías)
3. [Módulo de Pedidos](#módulo-de-pedidos)
4. [Módulo de Rutas](#módulo-de-rutas)
5. [Módulo de Vehículos](#módulo-de-vehículos)
6. [Módulo de Choferes](#módulo-de-choferes)
7. [Módulo de Gasolina](#módulo-de-gasolina)
8. [Módulo de Servicios](#módulo-de-servicios)
9. [Módulo de Inventario](#módulo-de-inventario)
10. [Módulo de Observaciones](#módulo-de-observaciones)
11. [Estadísticas y Reportes](#estadísticas-y-reportes)
12. [Mapa de Calor](#mapa-de-calor)
13. [Reporte de Precios](#reporte-de-precios)
14. [Aplicación Móvil](#aplicación-móvil)
15. [Notificaciones WhatsApp](#notificaciones-whatsapp)
16. [Conclusiones y Recomendaciones](#conclusiones-y-recomendaciones)

---

## RESUMEN EJECUTIVO

El **Sistema de Logística Pedidos GA** es una plataforma web integral que centraliza la gestión de pedidos, rutas de entrega, vehículos, choferes, servicios de mantenimiento, control de gasolina, inventario de repuestos, reportes de análisis y una aplicación móvil para operadores en campo.

### Objetivos Principales

- Optimizar la gestión de pedidos y rutas de entrega
- Controlar la flota vehicular y su mantenimiento
- Monitorear el consumo de gasolina y repuestos
- Facilitar la comunicación con choferes vía WhatsApp
- Generar reportes y estadísticas para toma de decisiones
- Proporcionar herramientas móviles para operación en campo

### Beneficios Clave

Las mejoras recientes han reforzado la **trazabilidad** con validaciones y control de precios por factura, aumentado la **productividad** mediante optimización de rutas y drag & drop, mejorado la **visibilidad** con filtros avanzados y dashboards, y fortalecido la **comunicación** mediante notificaciones automáticas por WhatsApp.

---

## ARQUITECTURA Y TECNOLOGÍAS

### 2.1 Stack Tecnológico

- **Backend:** PHP 7+ con arquitectura modular
- **Base de Datos:** MySQL/MariaDB
- **Frontend:** HTML5, CSS3, JavaScript (Vanilla JS, AJAX)
- **Mapas:** Mapbox GL JS para visualización geográfica
- **Mensajería:** Integración con API de WhatsApp
- **Exportación:** PHPExcel para reportes Excel
- **PDF:** MPDF para generación de documentos
- **Autenticación:** Sistema de sesiones PHP seguras
- **Versionado:** Control de versiones para app móvil

### 2.2 Arquitectura del Sistema

El sistema utiliza una arquitectura modular con tres capas principales:

1. **Capa de Presentación:** Interfaces HTML/PHP con JavaScript para interactividad
2. **Capa de Lógica de Negocio:** Scripts PHP organizados por dominio funcional
3. **Capa de Datos:** Base de datos MySQL con conexiones compartidas y transacciones

### 2.3 Navegación del Sistema

El sistema cuenta con un **sidebar organizado mediante acordeones** que agrupa las funcionalidades por categorías:

- **Operaciones Principales:** Agregar Pedido, Estadísticas, Inicio
- **Administración:** Usuarios, Permisos, Configuración
- **Pedidos y Rutas:** Gestión de pedidos, grupos de rutas, optimización
- **Vehículos y Choferes:** Control de flota y personal
- **Servicios:** Mantenimiento, Gasolina, Inventario
- **Reportes:** Estadísticas, Mapa de Calor, Reporte de Precios
- **Aplicación Móvil:** Descargas y configuración

---

## MÓDULO DE PEDIDOS

### 3.1 Vista Principal (Pedidos GA)

La pantalla principal de pedidos es el **centro de operaciones** del sistema. Permite visualizar, filtrar y gestionar todos los pedidos de manera eficiente.

#### Funcionalidades principales:

- ✅ Barra de filtrado actualizada con múltiples criterios combinables
- ✅ Filtros por: Fecha, Estado, Sucursal, Tipo de pedido, Chofer asignado
- ✅ Búsqueda en tiempo real sin recargar la página
- ✅ **Columna de Precio Factura** para control y conciliación
- ✅ Botón de descarga de aplicación móvil
- ✅ **Guía de pedidos generada** con ayuda contextual
- ✅ Selección múltiple para asignación masiva
- ✅ **Asignación de pedidos a grupos de ruta**
- ✅ Visualización de pedidos por estado (Kanban/Cards/Lista)
- ✅ Indicadores visuales de estado y prioridad
- ✅ Acceso rápido a detalles del pedido
- ✅ Exportación de datos filtrados

### 3.2 Agregar Pedido

Esta funcionalidad permite registrar nuevos pedidos con toda la información necesaria.

#### Características:

- **Formulario de captura con múltiples facturas** en un mismo pedido
- **Campo de precio por factura** para control financiero
- **Selección de coordenadas de destino mediante mini mapa interactivo**
- Validación de campos obligatorios
- Captura de información del cliente y destinatario
- Selección de tipo de envío (Domicilio/Paquetería)
- Asignación de sucursal de origen
- Notas y observaciones adicionales
- Carga de documentos adjuntos (PDFs, imágenes)
- Preview de información antes de guardar

### 3.3 Detalles del Pedido

Pantalla detallada con toda la información del pedido seleccionado.

#### Información mostrada:

- **Datos generales:** Número de pedido, fecha, sucursal
- **Cliente:** Nombre, teléfono, dirección
- **Facturas asociadas** con montos individuales
- **Precio total del pedido** (nuevo campo)
- Coordenadas de origen y destino en mapa
- Estado actual y historial de cambios
- Chofer asignado con datos de contacto
- Grupo de ruta al que pertenece
- Fecha estimada de entrega
- Fotografías de evidencia (si aplica)
- Observaciones y notas
- **Botones de acción:** Editar, Cancelar, Reprogramar

### 3.4 Actualizar Pedido

Permite modificar la información de pedidos existentes con controles de validación.

#### Funcionalidades clave:

- ⚠️ **Checkbox "Validar"** para control de calidad de datos
- ⚠️ **Restricción de asignación de chofer si no está validado**
- Validación de datos completos, precio y coordenadas
- Modificación de estado del pedido
- Reasignación de chofer con notificación automática
- Cambio de grupo de ruta
- Actualización de fechas y prioridades
- Registro de motivos de cambio
- Historial de modificaciones con usuario y fecha
- **Notificación automática al chofer por WhatsApp al asignar**

### 3.5 Estados de Pedidos

El sistema maneja los siguientes estados:

| Estado | Descripción |
|--------|-------------|
| **PENDIENTE** | Pedido registrado, esperando asignación |
| **ASIGNADO** | Chofer asignado, pendiente de salir a ruta |
| **EN RUTA** | Pedido en proceso de entrega |
| **ENTREGADO** | Entrega exitosa con evidencia |
| **EN TIENDA** | Cliente recogerá en sucursal |
| **REPROGRAMADO** | Requiere nueva fecha de entrega |
| **CANCELADO** | Pedido cancelado por cliente o sistema |
| **CANCELADO CLIENTE** | Cancelación específica del cliente |

---

## MÓDULO DE RUTAS

### 4.1 Grupos de Ruta

Los grupos de ruta permiten organizar múltiples pedidos que serán entregados por un mismo chofer en una jornada.

#### Funcionalidades:

- Crear grupos de ruta por sucursal y chofer
- Asignar múltiples pedidos a un grupo
- **Selección de grupos para visualización en mapa**
- Filtrado de pedidos disponibles para agrupar
- Verificación de pedidos ya asignados a otros grupos
- Cálculo automático de totales por grupo
- Indicadores de capacidad y prioridad
- Estado del grupo (Activo, En Ruta, Completado)
- Notas y observaciones del grupo
- Historial de grupos por chofer

### 4.2 Detalles de Ruta

Pantalla avanzada para gestionar y optimizar las rutas de entrega con herramientas de planificación.

#### Características principales:

- 🎯 **Desactivar grupo (separar por factura):** Permite pausar o dividir grupos cuando las facturas requieren tratamiento individual
- 🚀 **Optimización automática de ruta:** Calcula el orden óptimo para minimizar distancia y tiempo
- 🖱️ **Drag and Drop de prioridad:** Ajuste manual del orden de entregas arrastrando pedidos
- Visualización en mapa con puntos ordenados
- Cálculo de distancia total y tiempo estimado
- Información detallada de cada parada
- Modificar orden manualmente si es necesario
- Exportar ruta a Google Maps
- Añadir/quitar pedidos del grupo
- Marcar pedidos como prioritarios
- Guardar y publicar ruta finalizada
- Compartir ruta con el chofer

### 4.3 Optimización de Rutas

El sistema utiliza algoritmos avanzados para optimizar las rutas de entrega.

#### Criterios de optimización:

- Minimización de distancia total recorrida
- Consideración de prioridades de pedidos
- Tiempo estimado de entrega
- Capacidad del vehículo
- Ventanas horarias de entrega
- Restricciones de tráfico
- Punto de partida desde sucursal
- Retorno a sucursal al finalizar

---

## MÓDULO DE VEHÍCULOS

### 5.1 Gestión de Vehículos (Inicio)

Dashboard completo para administrar toda la flota vehicular con vista organizada por razón social y ubicación física.

#### Funcionalidades principales:

- 📊 **Dashboard por razón social y ubicación física**
- Vista resumida de flota por sede/propietario
- Indicadores de estado de vehículos (Disponible, En Ruta, En Taller)
- Filtros por sucursal, estado, tipo de vehículo
- Cards clickeables con información relevante
- Avatar o foto del vehículo
- Placa, modelo y año
- Chofer asignado actualmente
- Kilometraje actual
- Próximo servicio programado
- 🔧 **Badge "En Taller"** cuando aplica
- **Gestión de choferes por ubicación física**
- **Asignación/Designación/Cambio de vehículo a chofer**
- Acceso rápido a detalles completos

### 5.2 Detalles de Vehículo

Pantalla completa con toda la información del vehículo y sus registros históricos.

#### Secciones de información:

**INFORMACIÓN GENERAL:**
- Datos básicos: Placa, marca, modelo, año
- Razón social propietaria
- Asignación de sucursal del vehículo
- Tipo de vehículo y capacidad
- Estado actual (Disponible/En Ruta/En Taller)
- Fotografía del vehículo

**KILOMETRAJE:**
- Kilometraje actual registrado
- ⚠️ **Kilometraje próximo servicio con alerta preventiva**
- Registro de nuevo kilometraje
- Historial completo de kilometraje con fechas
- Gráfico de evolución de kilometraje

**ASIGNACIONES:**
- Chofer asignado actualmente
- Historial de conductores con períodos
- Botón para asignar/cambiar chofer
- Fechas de asignación y desasignación

**SERVICIOS:**
- Registro de servicios realizados
- Fecha del último servicio
- Próximo servicio programado
- Historial completo de mantenimientos
- Costos de servicios

**GASOLINA:**
- Consumos de gasolina registrados
- Litros cargados por fecha
- Costo por carga
- Estadísticas de consumo
- Rendimiento promedio

**OBSERVACIONES:**
- Notas operativas del vehículo
- Hallazgos de inspecciones
- Incidencias reportadas
- Historial de observaciones

### 5.3 Asignación de Vehículos

Control completo del proceso de asignación de vehículos a choferes.

#### Proceso de asignación:

1. Seleccionar vehículo disponible
2. Verificar que el vehículo no esté en taller
3. Seleccionar chofer activo de la misma sucursal
4. Verificar que el chofer no tenga otro vehículo asignado
5. Registrar fecha de asignación
6. Guardar en historial de asignaciones
7. Actualizar estado del vehículo
8. Notificar al chofer de la asignación
9. Generar registro de trazabilidad

---

## MÓDULO DE CHOFERES

### 6.1 Gestión de Choferes

Dashboard para administrar el personal de choferes con información completa y métricas de desempeño.

#### Funcionalidades:

- Listado completo de choferes activos
- Búsqueda por nombre, sucursal o teléfono
- Filtros por estado y sucursal
- Cards con foto de perfil o avatar inicial
- Información visible: Nombre, sucursal, teléfono, vehículo asignado
- Métricas de desempeño en período seleccionado
- 📱 **Botón de acceso a WhatsApp directo**
- 📞 **Botón de llamada telefónica**
- Estado del chofer (Activo/Inactivo)
- Indicador de vehículo asignado
- Acceso a detalles completos

### 6.2 Detalles de Chofer

Vista detallada con toda la información personal, operativa e histórica del chofer.

#### Información completa:

**DATOS PERSONALES:**
- Nombre completo
- Fotografía de perfil
- Número de teléfono
- Correo electrónico
- Fecha de ingreso
- Sucursal asignada
- Estado (Activo/Inactivo)
- Botón de editar información

**CONTACTO RÁPIDO:**
- Acceso directo a WhatsApp
- Botón para llamada telefónica
- Envío de mensajes predefinidos

**RESUMEN DE DESEMPEÑO:**
- 📅 **Filtro por rango de fechas**
- Total de pedidos asignados
- Pedidos entregados
- Pedidos en ruta
- Pedidos reprogramados
- Pedidos cancelados
- Porcentaje de efectividad
- Gráficos de desempeño

**PEDIDOS RECIENTES:**
- Últimos 10 pedidos
- Estado actual de cada uno
- Acceso rápido a detalles

**VEHÍCULO ASIGNADO:**
- Información del vehículo actual
- Placa y modelo
- Fecha de asignación
- Estado del vehículo

**HISTORIAL DE VEHÍCULOS:**
- Vehículos asignados anteriormente
- Períodos de asignación
- Trazabilidad completa

**OBSERVACIONES:**
- Lista de incidencias reportadas
- Notas operativas
- Observaciones de desempeño
- Historial acumulado

---

## MÓDULO DE GASOLINA

### 7.1 Control de Gasolina

Módulo completo para gestionar y monitorear el consumo de combustible de toda la flota vehicular.

#### Funcionalidades principales:

**FILTRADO Y CONSULTAS:**
- 📅 Filtrado por rango de fechas
- 📆 Filtrado mensual con selector de mes/año
- 🚗 Filtro por vehículo específico
- 🏢 Filtro por sucursal
- 🔍 Búsqueda avanzada de registros

**REGISTRO INDIVIDUAL:**
- Captura de gasolina para vehículo en fecha específica
- Litros cargados
- Costo de la carga
- Kilometraje al momento de la carga
- Estación de servicio
- Tipo de combustible
- Observaciones

**IMPORTACIÓN MASIVA:**
- 📥 **Descarga de machote en formato Excel**
- 📤 **Importación masiva desde archivo Excel**
- ⚠️ **Validación de formato requerido**
- ⚠️ **La importación DEBE usar el machote, de lo contrario falla**
- Validación de vehículos existentes
- ⚠️ **Registros con vehículos inexistentes quedan en PENDIENTES DE ALTA**

**PENDIENTES DE ALTA:**
- Lista de registros que no se cargaron
- Motivo: Vehículos no registrados en el sistema
- Se procesan automáticamente al registrar el vehículo faltante
- Sincronización automática después de alta de vehículo

**HISTORIAL DE IMPORTACIÓN:**
- Registro de todas las importaciones realizadas
- Fecha y usuario que importó
- Número de registros procesados
- Registros exitosos vs pendientes
- Archivo fuente de importación

**EXPORTACIÓN DE REPORTES:**
- Descarga del reporte consultado en Excel
- Aplica los filtros activos
- Incluye totales y estadísticas
- Formato listo para análisis

### 7.2 Requisitos Operativos

⚠️ **IMPORTANTE - Condiciones para correcto funcionamiento:**

- ⚠️ Todos los vehículos DEBEN estar registrados en el sistema antes de importar
- ⚠️ La importación masiva REQUIERE usar el machote descargado del sistema
- ⚠️ Si se usa otro formato, la importación fallará completamente
- ⚠️ Los registros sin vehículo válido quedarán en PENDIENTES hasta sincronizar
- ✅ Una vez registrado el vehículo faltante, los pendientes se procesan automáticamente
- 💡 Se recomienda revisar el módulo de vehículos antes de cada importación masiva

---

## MÓDULO DE SERVICIOS

### 8.1 Gestión de Servicios

Sistema completo de administración de servicios y mantenimiento vehicular con tablero Kanban y control de inventario.

#### Funcionalidades principales:

**TABLERO DRAG AND DROP:**
- 📋 **Tablero Kanban por estatus de servicio**
- Estados: Pendiente, Programado, En Taller, Completado, Cancelado
- 🖱️ Mover servicios entre estados arrastrando cards
- Reorganizar prioridades dentro de cada columna
- Colores diferenciados por tipo de servicio
- Indicadores de urgencia y prioridad

**FILTROS Y VISTAS:**
- Filtrado por rango de fechas
- Filtro por vehículo
- Filtro por tipo de servicio
- Filtro por sucursal
- 📋 **Vista en lista** (alternativa al tablero)
- Cambio entre vista tablero y lista

**MODAL AGREGAR SERVICIO:**
- Selección de vehículo
- Selección de tipo de servicio del catálogo
- Fecha programada
- Prioridad del servicio
- Descripción y observaciones
- Materiales requeridos (del inventario)
- Estimación de costo

**IMPACTO EN INVENTARIO:**
- ⚠️ **El inventario solo se descuenta cuando el servicio pasa a PROGRAMADO**
- No se descuenta al crear el servicio
- Validación de existencias antes de programar
- Alerta si no hay suficiente inventario
- Registro de materiales utilizados

**VEHÍCULO EN TALLER:**
- ⚠️ Al marcar servicio **"En Taller"**, el vehículo queda **DESHABILITADO**
- No se puede asignar a nuevos pedidos
- Indicador visible en módulo de vehículos
- Se libera automáticamente al completar o cancelar servicio
- Historial de tiempos en taller

### 8.2 Agregar Servicio (Catálogo)

Administración del catálogo de tipos de servicios disponibles.

#### Funcionalidades:

- Listado de todos los servicios disponibles
- Opciones de editar y eliminar servicios

**FORMULARIO DE ALTA:**
- Nombre del servicio
- Descripción detallada
- Materiales requeridos del inventario
- Vehículos a los que aplica
- Periodicidad recomendada (opcional)
- Costo estimado promedio
- Control de materiales por servicio
- Define qué se descuenta cuando se programa/ejecuta

### 8.3 Flujo de Estados del Servicio

```
PENDIENTE → Servicio identificado pero sin programar
    ↓
PROGRAMADO → Fecha asignada, inventario descontado, servicio agendado
    ↓
EN TALLER → Vehículo deshabilitado, servicio en ejecución
    ↓
COMPLETADO → Servicio finalizado, vehículo liberado
```

**Alternativa:**
```
CANCELADO → Servicio cancelado, inventario devuelto (si aplica)
```

---

## MÓDULO DE INVENTARIO

### 9.1 Gestión de Inventario

Control completo de repuestos y materiales para mantenimiento vehicular con trazabilidad de movimientos.

#### Funcionalidades principales:

**LISTADO DE MATERIALES:**
- Todos los productos en stock
- Código de producto
- Nombre y descripción
- Cantidad en stock
- Unidad de medida
- Precio unitario
- Valor total en inventario
- Ubicación física
- Punto de reorden

**FILTROS:**
- Filtro por tipo de stock (Repuestos, Lubricantes, Llantas, etc.)
- Filtro por sucursal
- Búsqueda por nombre o código
- Stock bajo (menor al punto de reorden)
- Stock crítico (agotado o muy bajo)

**OPERACIONES:**
- ➕ Agregar nuevo producto al inventario
- ✏️ Editar información de productos existentes
- 🔄 Ajustar cantidades (entradas y salidas)
- ❌ Eliminar productos (con validaciones)
- 🔄 Transferencias entre sucursales

**ALTA DE PRODUCTO:**
- Código de producto
- Nombre descriptivo
- Categoría o tipo
- Cantidad inicial
- Unidad de medida
- Precio unitario
- Proveedor
- Punto de reorden
- **Vehículos a los que aplica**
- Ubicación de almacenamiento

**TRAZABILIDAD:**
- Historial de movimientos por producto
- Entradas (compras, devoluciones)
- Salidas (servicios, ajustes)
- Usuario que realizó el movimiento
- Fecha y hora de cada movimiento
- Servicio asociado (si aplica)

### 9.2 Estadísticas de Inventario

Dashboard con KPIs y análisis del inventario para toma de decisiones.

#### Métricas e indicadores:

**FILTRO POR RANGO DE FECHAS:**
- Análisis de movimientos en período seleccionado

**TOP DE MATERIAL CON STOCK BAJO:**
- Productos por debajo del punto de reorden
- ⚠️ Alerta temprana para reposición
- Priorización de compras

**TOP DE MATERIAL EN STOCK CON MAYOR VALOR:**
- Productos con mayor valor monetario en stock
- Control de items costosos
- Análisis de inversión en inventario

**ESTADÍSTICAS GENERALES:**
- Total de productos registrados
- Valor total del inventario
- Productos con stock crítico
- Consumos del período
- Entradas del período
- Ajustes realizados
- 📊 Gráficos de tendencias
- Análisis por categoría

---

## MÓDULO DE OBSERVACIONES

### 10.1 Gestión de Observaciones

Módulo para recibir, gestionar y dar seguimiento a incidencias reportadas por choferes y personal operativo.

#### Funcionalidades principales:

**RECEPCIÓN DE INCIDENCIAS:**
- Recibe reportes de choferes desde la app móvil
- Registro manual desde el sistema web
- Captura de observaciones durante inspecciones
- **Crea registros fechados automáticamente**

**INFORMACIÓN CAPTURADA:**
- Vehículo afectado
- Tipo de observación (categoría)
- Descripción detallada
- Nivel de severidad
- Chofer que reporta
- Fecha y hora del reporte
- Fotografías de evidencia (opcional)
- Ubicación del reporte

**FILTROS:**
- Filtro por tipo de observación
- Filtro por vehículo
- Filtro por chofer
- Filtro por severidad
- Filtro por estado (Pendiente, En Proceso, Resuelta)
- Rango de fechas

**TIPOS DE OBSERVACIÓN:**
- 🔧 Mecánica
- ⚡ Eléctrica
- 🚗 Llantas
- 🛑 Frenos
- 🎨 Carrocería
- 🧹 Limpieza
- 📄 Documentación
- 🛡️ Seguridad
- 📌 Otras

**ACCIONES:**
- **Crear servicio basado en observación**
- Asignar prioridad
- Agregar comentarios de seguimiento
- Marcar como resuelta
- Vincular con orden de servicio
- Notificar al chofer de la resolución

### 10.2 Historial de Resolución

Análisis de observaciones resueltas para **evaluar tipos de servicios generados y tiempos de respuesta**.

#### Métricas e indicadores:

- Evaluación de tipos de servicios más frecuentes
- Tiempo promedio de resolución por tipo
- Observaciones pendientes vs resueltas
- Choferes con más reportes
- Vehículos con más incidencias
- Tendencias de problemas por período
- Efectividad de las resoluciones
- Reincidencia de problemas

---

## ESTADÍSTICAS Y REPORTES

### 11.1 Estadísticas Generales

Dashboard principal con KPIs y métricas clave del sistema para análisis de desempeño operativo.

#### Funcionalidades:

**FILTROS:**
- 📅 **Rango de fechas actualizado** con selector visual
- Filtro por sucursal
- Filtro por tipo de pedido
- Comparativa con períodos anteriores

**CONTENEDORES REDISEÑADOS:**
- 📦 **Bloques más legibles y organizados**
- Indicadores visuales con colores
- Iconos representativos
- Métricas destacadas

**MÉTRICAS PRINCIPALES:**
- Total de pedidos en período
- Pedidos entregados
- Pedidos en ruta
- Pedidos pendientes
- Pedidos reprogramados
- Pedidos cancelados
- 📈 Tasa de efectividad
- ⏱️ Tiempo promedio de entrega
- Pedidos por chofer
- Pedidos por sucursal
- 💰 Ingresos por pedidos
- 💵 Costos operativos

**GRÁFICOS:**
- 📊 Evolución de pedidos por día
- 📈 Distribución por estado
- 📊 Comparativa por sucursal
- 📉 Desempeño por chofer
- 📈 Tendencias temporales

---

## MAPA DE CALOR

### 12.1 Análisis Geográfico

Herramienta avanzada de análisis geográfico que visualiza la densidad y distribución de pedidos en el mapa.

#### Funcionalidades principales:

**FILTROS DISPONIBLES:**
- 📅 **Filtro de rango de fechas** (desde-hasta)
- ♾️ Opción **"Todos los tiempos"** para análisis histórico completo
- 📦 **Filtro por tipo de pedido** (Domicilio/Paquetería)
- ✅ Toggles independientes para cada tipo
- 🏢 **Filtro por sucursal específica**
- 🌐 Opción de "Todas las sucursales"

**VISUALIZACIÓN DE SUCURSALES:**
- ☑️ Checkboxes para mostrar/ocultar sucursales
- 📍 Marcadores en el mapa por sucursal
- 🎨 Colores diferenciados por ubicación
- 🔘 Toggle individual para cada sucursal

**ANÁLISIS POR ZONA:**
- 🎯 **Selección de zona en el mapa**
- 🖱️ Botón **"Elegir centro en mapa"**
- 📏 **Configuración de radio en metros** (100-20,000m)
- ⭕ Dibujo de círculo de análisis
- 🔢 Cálculo de pedidos dentro de la zona
- 🧹 Botón **"Limpiar zona"** para reiniciar

**RESUMEN GENERAL DE LA ZONA:**
- Total de pedidos en el área seleccionada
- Distribución por tipo (Domicilio/Paquetería)
- Densidad de pedidos por km²
- Sucursales que atienden la zona
- Cobertura actual vs demanda
- 💡 Sugerencias de mejora

**VISUALIZACIÓN:**
- 🔥 **Mapa de calor** con gradiente de intensidad
- 🎨 **Leyenda:** Baja, Media, Alta densidad
- 📍 Puntos de entrega individuales
- 🛣️ Rutas frecuentes destacadas
- ⚠️ Zonas de alta demanda resaltadas

**ESTADÍSTICAS:**
- Panel con estadísticas generales
- Total de pedidos filtrados
- Desglose por tipo
- Estadísticas por sucursal
- Comparativas y tendencias

### 12.2 Casos de Uso

- Identificar zonas con alta demanda para apertura de nuevas sucursales
- Analizar cobertura actual vs necesidades del mercado
- Optimizar asignación de recursos por zona geográfica
- Detectar áreas desatendidas o con bajo servicio
- Planificar expansión de rutas y cobertura
- Evaluar desempeño por zona geográfica
- Análisis de competencia y oportunidades de mercado
- Determinar radio óptimo de cobertura por sucursal

---

## REPORTE DE PRECIOS

### 13.1 Análisis de Precios y Facturación

Herramienta completa para auditoría y análisis de precios de facturas con detección de anomalías.

#### Funcionalidades principales:

**FILTROS:**
- 📅 Filtrado por rango de fechas
- 🏢 Filtro por sucursal
- 👤 Filtro por vendedor
- 🔗 Combinación de múltiples filtros

**RESUMEN GENERAL:**
- Total de pedidos analizados
- Suma total de precios facturados
- Precio promedio por pedido
- Precio mínimo registrado
- Precio máximo registrado
- 📊 Desviación estándar

**PEDIDOS MENORES A $1,000:**
- ⚠️ **Listado de pedidos con precio bajo**
- 🚨 **Bandera de alerta para rentabilidad**
- Posibles errores de captura
- Validación de precios sospechosos
- Detalle completo de cada caso

**CORRECCIÓN DE PRECIO:**
- 📝 Registro de ajustes realizados
- ⬇️ **Correcciones a favor del cliente**
- ⬆️ **Correcciones a favor de la empresa**
- Motivo de la corrección
- Usuario que autorizó
- Fecha de corrección
- 💼 Impacto en conciliación contable

**ESTADÍSTICAS POR VENDEDOR:**
- Total de pedidos por vendedor
- Suma de precios por vendedor
- Precio promedio por vendedor
- 📊 Comparativa entre vendedores
- Consistencia de precios
- Desempeño individual
- 🏆 Ranking de vendedores

**PEDIDOS PENDIENTES:**
- ⏰ **Lista de pedidos atrasados de revisar**
- Pedidos sin validación de precio
- Pendientes de autorización
- Alertas de seguimiento
- Priorización de revisión

**EXPORTAR EN EXCEL:**
- 📥 Descarga del reporte completo
- Aplica filtros activos
- Incluye todas las secciones
- Formato para auditorías
- Listo para envío a finanzas

### 13.2 Casos de Uso

- Auditoría de precios facturados por período
- Detección de errores de captura de precios
- Análisis de rentabilidad por tipo de pedido
- Evaluación de desempeño de vendedores
- Conciliación contable de facturas
- Identificación de patrones de precios anómalos
- Seguimiento de correcciones y ajustes
- Reportes para área financiera

---

## APLICACIÓN MÓVIL

### 14.1 Descripción General

Aplicación móvil para choferes y personal operativo en campo, sincronizada con el sistema web.

#### Funcionalidades principales:

**CONTROLES DE ACCESO:**
- ✅ **Validación de versión de la app**
- ⚠️ **Restricción si la versión no es vigente**
- 🔄 Forzar actualización cuando hay nueva versión
- 🚗 **Verificación de vehículo asignado**
- ⚠️ **Restricción si no tiene vehículo asignado**
- 🔐 Control de sesión persistente

**MODAL OBLIGATORIO DE KILOMETRAJE:**
- ⚠️ Al iniciar la jornada, **captura obligatoria de kilometraje**
- 🚫 No permite continuar sin registrar
- ✅ Validación de kilometraje coherente
- 🔄 Comparación con último registro
- 🔧 **Genera servicio automático si supera umbral de servicio**
- ⚠️ Alerta si el kilometraje excede el programado para mantenimiento

**FORMULARIO DE CONDICIONES DEL VEHÍCULO:**
- ✅ **Checklist de condiciones al iniciar**
- ⚠️ **Obligatorio completar antes de salir**
- Validación de cada punto crítico
- 📸 Captura de fotografías de evidencia
- 🚨 Reporte de anomalías detectadas
- 📝 Genera observaciones automáticas

**GESTIÓN DE PEDIDOS:**
- Vista de pedidos asignados al chofer
- Detalle completo de cada pedido
- Información de cliente y dirección
- Facturas asociadas
- 🗺️ Botón de navegación a destino
- 🔄 Actualización de estado del pedido
- 📸 Captura de evidencia fotográfica
- ✍️ Registro de firma del cliente
- 📝 Observaciones de entrega

**AGRUPACIÓN DE FACTURAS:**
- Vista de grupos de ruta asignados
- Listado de facturas por grupo
- Orden de entrega sugerido
- 🗺️ Mapa de ruta completo
- ✅ Marcar facturas como entregadas
- 🔄 Reordenar entregas manualmente

**MAPA DE RUTA:**
- Visualización de todas las entregas
- 📍 Punto actual del chofer (GPS)
- 🔢 Puntos de destino numerados
- 🛣️ Ruta optimizada trazada
- 🗺️ **Exportar ruta a Google Maps**
- 🧭 Navegación turn-by-turn
- 🔄 Actualización en tiempo real

**MÓDULO DE VEHÍCULO:**
- Información del vehículo asignado
- Placa, modelo, año
- Kilometraje actual
- Próximo servicio
- Historial de servicios
- ⛽ Registro de gasolina desde app
- 🚨 Reporte de incidencias

**EXCEPCIONES Y CONECTIVIDAD:**
- 📡 Manejo de pérdida de conexión
- 📴 **Modo offline con sincronización posterior**
- 💾 Caché de información crítica
- 📤 Cola de sincronización
- 🔔 Notificaciones de errores
- 🔄 Reintentos automáticos

### 14.2 Restricciones y Validaciones

⚠️ **IMPORTANTE - La app móvil tiene restricciones obligatorias:**

- ⚠️ No permite operar sin versión vigente - debe actualizar
- ⚠️ No permite operar sin vehículo asignado
- ⚠️ Bloquea hasta registrar kilometraje inicial del día
- ⚠️ Bloquea hasta completar formulario de condiciones del vehículo
- 🔧 Genera servicio automático si kilometraje supera umbral
- 📡 Requiere conexión para sincronización de datos críticos
- ✅ Valida coherencia de datos antes de enviar al servidor

---

## NOTIFICACIONES WHATSAPP

### 15.1 Sistema de Notificaciones

Integración completa con WhatsApp para notificaciones automáticas a choferes y usuarios.

#### Funcionalidades:

**NOTIFICACIÓN AUTOMÁTICA AL ASIGNAR PEDIDO:**
- 📲 **Se envía automáticamente al asignar chofer**
- Mensaje con datos clave del pedido
- Información del cliente y dirección
- Fecha y hora de entrega
- Número de facturas
- Contacto del cliente
- 📉 Reduce llamadas telefónicas
- ✅ Asegura acuse de recibo

**PLANTILLAS DE MENSAJES:**
- 📋 Plantilla de asignación de pedido
- 🔄 Plantilla de cambio de ruta
- ⚠️ Plantilla de pedido urgente
- ❌ Plantilla de cancelación
- ⏰ Plantilla de recordatorio
- ✏️ Plantillas personalizables

**MENSAJES DESDE EL SISTEMA:**
- 📱 Botón de WhatsApp en detalle de chofer
- 📝 Envío de mensajes personalizados
- 📋 Mensajes predefinidos comunes
- 📜 Historial de mensajes enviados

**CASOS DE USO:**
- ✅ Confirmación de asignación
- 🔄 Cambios de última hora
- ⚠️ Alertas de urgencia
- ⏰ Recordatorios de tareas
- 💬 Comunicación bidireccional
- 🔄 Coordinación en tiempo real

---

## CONCLUSIONES Y RECOMENDACIONES

### 16.1 Mejores Prácticas Operativas

**PEDIDOS:**
- ✅ Exigir validación de pedidos antes de asignar chofer
- 📝 Registrar cambios de precio con trazabilidad completa
- 📄 Usar la función de múltiples facturas para agilizar captura
- 🗺️ Aprovechar el mini mapa para coordenadas precisas

**RUTAS:**
- 🚀 Utilizar la optimización automática como base
- 🖱️ Ajustar manualmente con drag & drop según necesidades del negocio
- 📚 Capacitar usuarios en priorización efectiva
- ✅ Revisar grupos antes de publicar la ruta

**VEHÍCULOS:**
- 🔧 Programar mantenimientos preventivos según kilometraje
- 📝 Mantener actualizado el registro de servicios
- ⛽ Monitorear consumos de gasolina regularmente
- 🔄 Sincronizar catálogo de vehículos antes de importaciones

**GASOLINA:**
- 📥 Descargar siempre el machote oficial para importaciones
- ✅ Verificar vehículos registrados antes de importar
- 🔍 Revisar pendientes de alta después de importaciones
- 📊 Exportar reportes periódicamente para análisis

**SERVICIOS E INVENTARIO:**
- 📋 Supervisar tablero de servicios diariamente
- ⚠️ Evitar desabasto monitoreando stock bajo
- 📅 Programar servicios considerando disponibilidad de inventario
- 🚗 Dar seguimiento a vehículos inmovilizados

**COMUNICACIÓN:**
- 📱 Aprovechar notificaciones automáticas de WhatsApp
- 📊 Monitorear entregabilidad de mensajes
- 🔄 Mantener plantillas actualizadas

**APP MÓVIL:**
- 🔄 Reforzar cumplimiento de versión vigente
- 🚗 Asegurar vehículos asignados antes de jornada
- 📊 Verificar registro de kilometraje diario
- ✅ Validar completado de formularios obligatorios

### 16.2 Beneficios del Sistema

✅ Centralización completa de operaciones logísticas
✅ Trazabilidad total de pedidos, vehículos y personal
✅ Optimización de rutas para reducir costos
✅ Control preventivo de mantenimiento vehicular
✅ Visibilidad en tiempo real de operaciones
✅ Reducción de errores mediante validaciones
✅ Comunicación efectiva con choferes
✅ Reportes y análisis para toma de decisiones
✅ Gestión eficiente de inventario de repuestos
✅ Herramientas móviles para operación en campo
✅ Alertas automáticas de situaciones críticas
✅ Integración entre todos los módulos del sistema

### 16.3 Puntos Críticos de Atención

⚠️ Validación obligatoria de pedidos antes de asignar
⚠️ Sincronización de vehículos antes de importar gasolina
⚠️ Uso del machote oficial para importaciones masivas
⚠️ Monitoreo de vehículos en taller
⚠️ Control de inventario al programar servicios
⚠️ Verificación de versión vigente de app móvil
⚠️ Registro diario de kilometraje por choferes
⚠️ Revisión de pedidos pendientes de validación de precio
⚠️ Supervisión de observaciones no resueltas
⚠️ Seguimiento de correcciones de precio

---

## TABLA RESUMEN DE CAMBIOS CLAVE

| Módulo | Cambio | Qué Aporta |
|--------|--------|------------|
| **Global** | Notificación WhatsApp al asignar pedido | Confirma al chofer su tarea y reduce errores de comunicación |
| **Global** | Sidebar con acordeones | Navegación más rápida y organizada por dominio |
| **Pedidos GA** | Filtros, descarga app, guía, precio factura, agrupación y selección de grupos | Mejora búsqueda, onboarding y preparación de rutas |
| **Detalle ruta** | Desactivar grupo, optimizar, drag & drop | Control fino de rutas y prioridades |
| **Agregar pedido** | Múltiples facturas, precio, mini mapa | Captura completa en un solo paso |
| **Actualizar pedido** | Checkbox Validar y bloqueo sin validar | Calidad de datos y control operativo |
| **Estadísticas** | Filtros de fecha y contenedores rediseñados | Lectura más clara de indicadores |
| **Mapa de calor** | Filtros, zona y tamaño | Análisis geográfico accionable |
| **Reporte de precios** | Filtros, anomalías (<1000), correcciones, Excel | Auditoría y seguimiento comercial |
| **Vehículos** | Dashboard por razón social/ubicación y gestión de choferes | Gobierno de flota centralizado |
| **Detalle vehículo** | Registros e historiales completos | Mantenimiento preventivo y trazabilidad |
| **Detalle chofer** | Contacto, resumen, históricos, observaciones | Seguimiento integral de desempeño |
| **Gasolina** | Filtros, importación/exportación, pendientes de alta | Control de combustible y normalización |
| **Servicios** | Tablero drag & drop, filtros, vista lista, modal de alta | Planificación y ejecución de servicios |
| **Inventario** | Altas/ajustes/eliminación con filtros | Control de stock y aplicación a vehículos |
| **Estadísticas inventario** | Top bajo stock, mayor valor, KPI por fechas | Vigilancia de riesgos y costos |
| **App móvil** | Controles de versión, vehículo, kilometraje, rutas y formularios | Operación en campo segura y guiada |

---

**FIN DEL DOCUMENTO**

*Sistema de Logística Pedidos GA*
*Grupo Ascendente © 2025*

---
