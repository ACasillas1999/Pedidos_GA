# Sistema de Logistica Pedidos GA
Documento tecnico integral para equipos tecnicos y gerenciales.

## Portada
- Proyecto: Plataforma de Logistica y Pedidos (Pedidos GA)
- Version del documento: 1.4
- Fecha: _(actualizar al publicar)_
- Responsables: Equipo de Desarrollo / Operaciones

## Resumen ejecutivo
La plataforma centraliza pedidos, rutas, choferes, vehiculos, servicios, gasolina, inventario, reportes y aplicacion movil. Las mejoras recientes refuerzan trazabilidad (validaciones y precios por factura), productividad (optimizacion de rutas, drag & drop, tableros), visibilidad (filtros avanzados, dashboards, mapa de calor, estadisticas de inventario) y comunicacion (notificaciones WhatsApp). Se agregaron flujos completos de gasolina con importacion masiva, tablero de servicios drag & drop que impacta inventario, controles de observaciones, y mejoras en la app movil (kilometraje obligatorio, condiciones del vehiculo, restriccion por version y vehiculo asignado).

## Tecnologias utilizadas
- Backend: PHP 7+ (scripts y endpoints por modulo), MySQL/MariaDB.
- Frontend: HTML5, CSS (`styles*.css`), JavaScript (DOM, AJAX).
- Mensajeria: Integracion con plantillas/controladores de WhatsApp (`Mensajes_WP/*`, `Mensaje_WP.php`).
- Mapas y geodatos: Mini mapa para captura de coordenadas; calculo y visualizacion de distancias (`update_distance.php`, `api_mapa_datos.php`, `mapa_calor.php`).
- Exportes y reportes: Generacion/descarga en Excel (`export_reporte_precios.php`, `reporte_precios_facturas.php`).
- Sesion y seguridad: `check_session.php`, `logout.php`, validaciones de asignacion y version en app.

## Arquitectura del sistema
- Modelo: Aplicacion web PHP modular (paginas autocontenidas + endpoints AJAX + app movil consumiendo endpoints dedicados).
- Capas:
  - Presentacion: Vistas PHP/HTML con JS y CSS por pagina.
  - Logica de negocio: Scripts por dominio (pedidos, rutas, vehiculos, choferes, servicios, inventario, gasolina, reportes).
  - Integraciones: WhatsApp, Excel, mapas/geo.
  - Datos: MySQL via conexiones compartidas (`Conexiones/Conexion.php`, `App/Conexiones/Conexion.php`).
- Navegacion: Sidebar con acordeones (pedidos/rutas, vehiculos/choferes, servicios/gasolina/inventario, estadisticas/reportes, app).

## Estructura del proyecto (archivos clave)
- Raiz:
  - Pedidos y rutas: `Pedidos_GA.php`, `pedidos_kanban.php`, `pedidos_cards.php`, `detalle_pedido.php`, `detalle_ruta.php`, `crear_grupo_ruta.php`, `obtener_pedidos*.php`, `obtener_grupos.php`, `verificar_pedidos_en_grupos.php`.
  - Altas/actualizaciones: `ActualizarPedido.php`, `ActualizarPedidosMasivo.php`, `FuncionActualizarPedido.php`.
  - Choferes: `Choferes.php`, `ConsultarChofer.php`, `Actualizar_choferes.php`, `Funcion_ActualizarChofer.php`, `subir_foto_chofer.php`.
  - Vehiculos: `vehiculos.php`, `detalles_vehiculo.php`, `asignar_vehiculo.php`, `desasignar_vehiculo.php`, `ActualizarVehiculo.php`.
  - Reportes/estadisticas: `Estadisticas*.php`, `reporte_precios_facturas.php`, `export_reporte_precios.php`, `facturas_por_chofer*.php`, `total_facturas_por_sucursal.php`.
  - Servicios y soporte: `Servicios.php`, `Detalle_Servicio.php`, `RegistrarServicio.php`, `RegistrarGasolina.php`, `guardar_destinatario.php`, `descargar_plantilla_paqueteria.php`.
  - Utilitarios: `Mensaje_WP.php`, `enviar_correo.php`, `mapa_calor.php`, `api_mapa_datos.php`, `update_distance.php`, `check_session.php`, `logout.php`, `upload.php`.
- `App/`: Endpoints para app movil/web (pedidos, checklist, kilometraje, autenticacion, fotos, versiones, rutas, servicios).
- `Mensajes_WP/`: Plantillas y controladores de notificaciones WhatsApp.
- `Servicios/`: APIs y vistas de servicios, inventario y estadisticas (`servicios_api.php`, `inventario_api.php`, `inventario_estadisticas.php`, `servicios_estadisticas.php`).
- `Registrar/`, `RegistrarChofer/`: Flujos de alta de usuarios y choferes.
- `Gas/`: Modulo de gasolina (`Gas.php`).

## Tabla resumen de cambios clave
| Modulo | Cambio | Que aporta |
| --- | --- | --- |
| Global | Notificacion WhatsApp al asignar pedido | Confirma al chofer su tarea y reduce errores de comunicacion |
| Global | Sidebar con acordeones | Navegacion mas rapida y organizada por dominio |
| Pedidos GA | Filtros, descarga app, guia, precio factura, agrupacion y seleccion de grupos | Mejora busqueda, onboarding y preparacion de rutas |
| Detalle ruta | Desactivar grupo, optimizar, drag & drop | Control fino de rutas y prioridades |
| Agregar pedido | Multiples facturas, precio, mini mapa | Captura completa en un solo paso |
| Actualizar pedido | Checkbox Validar y bloqueo sin validar | Calidad de datos y control operativo |
| Estadisticas | Filtros de fecha y contenedores redisenados | Lectura mas clara de indicadores |
| Mapa de calor | Filtros, zona y tamano | Analisis geografico accionable |
| Reporte de precios | Filtros, anomalias (<1000), correcciones, Excel | Auditoria y seguimiento comercial |
| Vehiculos | Dashboard por razon social/ubicacion y gestion de choferes | Gobierno de flota centralizado |
| Detalle vehiculo | Registros e historiales completos | Mantenimiento preventivo y trazabilidad |
| Detalle chofer | Contacto, resumen, historicos, observaciones | Seguimiento integral de desempeno |
| Gasolina | Filtros, importacion/exporte, pendientes de alta | Control de combustible y normalizacion |
| Servicios | Tablero drag & drop, filtros, vista lista, modal de alta | Planificacion y ejecucion de servicios |
| Inventario | Altas/ajustes/eliminacion con filtros | Control de stock y aplicacion a vehiculos |
| Estadisticas inventario | Top bajo stock, mayor valor, KPI por fechas | Vigilancia de riesgos y costos |
| App movil | Controles de version, vehiculo, kilometraje, rutas y formularios | Operacion en campo segura y guiada |

## Flujos principales (resumidos)
- Alta de pedido: datos + multiples facturas y precio + coordenada en mini mapa -> validar -> asignar chofer (WhatsApp) -> agrupar en ruta -> optimizar y ordenar (drag & drop) -> seguimiento y estadisticas.
- Gestion de rutas: crear grupos -> desactivar/separar por factura si aplica -> optimizar -> ajustar orden manual -> guardar/publicar.
- Vehiculos/choferes: alta/edicion -> asignacion por ubicacion -> registro kilometraje/servicio/gasolina -> historicos y observaciones.
- Servicios/gasolina/inventario: alta y registro -> impacto en inventario (cuando servicio pasa a programado) -> observaciones -> estadisticas.
- Reporte de precios: filtrar fecha/sucursal/vendedor -> detectar <1000 y correcciones -> pendientes -> exportar Excel.
- Mapa de calor: filtros -> zona y tamano -> resumen para decisiones de cobertura.
- App movil: validar version/vehiculo/kilometraje -> checklist de condiciones -> gestionar rutas y facturas -> exportar a Google Maps.

## Documentacion por modulo y cambios
### 1. Funcionalidad global
- Notificacion automatica via WhatsApp al asignar pedido: al confirmar asignacion se envia mensaje con datos clave al chofer usando `Mensajes_WP/*`; reduce llamadas y asegura acuse.
- Sidebar con acordeones: accesos agrupados por dominio (pedidos/rutas, vehiculos/choferes, servicios/inventario, reportes, app) para menor scroll y acceso rapido.

### 2. Pedidos GA (Inicio)
- Barra de filtrado actualizada: filtros por fecha, estado, sucursal, tipo; combinables sin recargar la pagina.
- Boton de descarga de la aplicacion: enlace directo a instalador/tienda para choferes y operadores.
- Guia de pedidos generada: ayuda contextual sobre estados, acciones y filtros; reduce curva de aprendizaje.
- Columna Precio Factura: visibilidad del monto facturado en listado para control y conciliacion.
- Asignacion de grupo de pedidos para generar ruta: permite armar grupos con prioridad y orden de trabajo.
- Seleccion de grupos para mostrar ruta: el operador elige que grupos se proyectan en mapa/listado para enfocar operaciones.

### 3. Detalles de ruta
- Desactivar grupo (separar por factura): pausa o divide grupos cuando las facturas requieren tratamiento individual; evita envios no deseados.
- Optimizacion de ruta: orden sugerido para minimizar distancia/tiempo con los puntos del grupo activo.
- Drag and drop de prioridad: ajuste manual del orden propuesto para reflejar restricciones reales de campo o negocio.

### 4. Agregar pedido
- Multiples facturas por pedido: captura varias facturas en un solo formulario para un mismo destino.
- Campo de precio: registra monto facturado y alimenta columnas y reportes.
- Coordenada destino desde mini mapa: seleccion guiada de lat/long para evitar errores de direccion y mejorar calculo de rutas.

### 5. Detalles del pedido
- Nuevo campo Precio Factura: validacion visual del valor cargado; facilita deteccion de discrepancias.

### 6. Actualizar pedido
- Checkbox de Validar: control de calidad (datos completos, precio, coordenadas).
- Restriccion de asignacion de chofer si no esta validado: impide notificar pedidos incompletos y obliga a cumplir el checklist.

### 7. Estadisticas
- Filtro de rango de fecha actualizado: seleccion precisa para recalcular KPIs y graficos.
- Contenedores redisenados: bloques mas legibles para indicadores de pedidos, rutas, tiempos y costos.

### 8. Mapa de Calor
- Filtro de rango de fecha, tipo de pedido y sucursal: permite analizar periodos y segmentos especificos.
- Seleccion de zona de mapa y tamano (km): define area de interes para medir densidad y demanda.
- Resumen general de la zona: totales y concentraciones para decidir cobertura o reasignar recursos.

### 9. Reporte de precios
- Filtrado por rango de fecha, sucursal y vendedor: analisis focalizado por equipo y periodo.
- Resumen general: totales y promedios de precios facturados.
- Pedidos menores a 1000: bandera de alerta para rentabilidad o captura erronea.
- Correccion de precio (a favor/en contra): registro de ajustes para conciliacion contable.
- Estadisticas por vendedor: compara desempeno y consistencia de precios.
- Pedidos pendientes (atrasados de revisar): lista de items a resolver antes de cierre.
- Exportar en Excel: permite auditorias y envio a finanzas aplicando los filtros activos.

### 10. Vehiculos (Inicio)
- Dashboard por razon social y ubicacion fisica: vista resumida de flota por sede/propietario.
- Gestionar choferes por ubicacion fisica: asignaciones considerando disponibilidad local.
- Asignacion/Designacion/Cambio de vehiculo a chofer: flujo controlado para mover unidades y mantener trazabilidad.

### 11. Detalles de vehiculo
- Informacion general: ficha resumida con datos basicos.
- Kilometraje proximo servicio: alerta preventiva para programar mantenimiento.
- Asignacion de sucursal y chofer: define ubicacion y responsable.
- Registro de kilometraje, servicio y gasolina: captura periodica para control de gasto y desgaste.
- Historial de kilometraje y de conductores: trazabilidad de uso y responsables en el tiempo.
- Observaciones: notas operativas y hallazgos.
- Gasolina: consulta de consumos; soporta cortes y auditoria.
- Servicios: registros de trabajos realizados en la unidad.

### 12. Detalles de Chofer
- Editar chofer: actualizacion de datos y documentos.
- Acceso a WhatsApp o llamada: contacto inmediato desde la ficha.
- Resumen por rango de fecha de pedidos: productividad del chofer en periodo.
- Pedidos recientes: seguimiento operativo diario.
- Vehiculo asignado: muestra resumen del vehiculo actual.
- Historial de vehiculos: asignaciones pasadas para trazabilidad.
- Observaciones acumuladas: lista de incidencias o notas del chofer.
- Contacto: datos de comunicacion centralizados.

### 13. Gas (Gasolina)
- Filtrado por rango de fecha o mensual: consultas por periodo.
- Registro de gasolina por vehiculo en fecha especifica.
- Historial de importacion y pendientes de alta: entradas que no se cargan por vehiculos inexistentes quedan pendientes hasta sincronizar nuevos vehiculos.
- Descarga de machote: formato requerido para importacion masiva.
- Exportacion del reporte consultado: descarga de datos filtrados.
- Importacion masiva: requiere usar el machote; de lo contrario la carga falla y los registros no se incorporan.
- Requisito operativo: todos los vehiculos deben existir para que la carga pase de pendientes a activos.

### 14. Servicios
- Tablero drag and drop por estatus: reordenar y mover servicios en pipeline operativo.
- Filtrado por fecha y vista en lista: alterna entre tablero y listado segun necesidad.
- Impacto en inventario: el inventario solo descuenta cuando el servicio pasa a programado.
- Vehiculo en taller: al marcarlo, la unidad queda deshabilitada hasta cambiar el status.
- Modal para agregar servicio a vehiculo: alta rapida de trabajos especificos.

### 15. Agregar Servicio
- Listado de servicios disponibles con opcion de editar/eliminar.
- Alta de servicio: nombre, materiales requeridos y vehiculos aplicables.
- Control de materiales: define consumibles a descontar cuando se programe/ejecute.

### 16. Observaciones
- Recepcion de incidencias de choferes: crea registros fechados que pueden originar servicios.
- Filtro por tipo de observacion: prioriza por criticidad o categoria.
- Historial de resultas: evalua tipos de servicios generados y tiempos de resolucion.

### 17. Inventario
- Listado de materiales en stock: con opciones de editar, ajustar o eliminar.
- Filtros por tipo de stock y sucursal: permite segmentar por ubicacion o categoria.
- Alta de producto: datos esenciales y vehiculos a los que aplica.

### 18. Estadisticas de inventario
- KPIs por rango de fechas seleccionado.
- Top de material con stock bajo: alerta temprana de reposicion.
- Top de material en stock con mayor valor: control de items costosos.
- Estadisticas generales: resuena consumos, entradas y ajustes.

### 19. Aplicacion movil
- Modal obligatorio de kilometraje y formulario de condiciones del vehiculo.
- Genera servicio automatico si el kilometraje supera el umbral de servicio.
- Agrupacion de facturas y gestion de mapa de ruta; exportar ruta a Google Maps.
- Nuevo modulo de vehiculo y gestion de formulario del vehiculo.
- La aplicacion conserva sesion y maneja excepciones de conexion.
- Actualizacion por API REST; restriccion si la version no es vigente.
- Restricciones si no tiene vehiculo asignado, si no hay kilometraje o si no se llena el formulario de condiciones.

## Casos de uso (ejemplos)
- Asignar pedido a chofer con notificacion: operador valida -> asigna chofer -> sistema envia WhatsApp con datos clave -> chofer confirma recepcion.
- Optimizar y priorizar ruta: supervisor agrupa pedidos -> ejecuta optimizacion -> ajusta orden con drag & drop -> guarda -> chofer sigue prioridad en campo.
- Alta de pedido con multiples facturas: usuario ingresa datos -> agrega facturas y precio -> selecciona coordenada en mini mapa -> guarda -> listo para validacion/asignacion.
- Reporte de precios: analista filtra por fecha/sucursal/vendedor -> revisa pedidos < 1000 y correcciones -> marca pendientes -> exporta a Excel.
- Control de gasolina: operador importa masivo usando machote -> revisa pendientes de alta por vehiculos faltantes -> sincroniza vehiculos -> exporta reporte filtrado.
- Servicios y inventario: jefe de taller mueve servicio a programado -> inventario descuenta materiales -> vehiculo queda deshabilitado si esta en taller -> se libera al cerrar servicio.
- App movil: chofer inicia con version vigente y vehiculo asignado -> captura kilometraje y condiciones -> si excede umbral, se genera servicio -> sigue ruta y exporta a Google Maps.

## Recomendaciones finales
- Exigir validacion de pedidos antes de asignar chofer y registrar cambios de precio con trazabilidad.
- Monitorear integraciones de WhatsApp y exportes Excel para asegurar entregabilidad y formato.
- Capacitar usuarios en filtros (fecha, sucursal, tipo) y en priorizacion de rutas con drag & drop.
- Programar mantenimientos preventivos segun kilometraje proximo servicio y consumos de gasolina.
- Mantener catalogo de vehiculos sincronizado antes de importaciones masivas de gasolina.
- Supervisar tablero de servicios e inventario para evitar desabasto y vehiculos inmovilizados.
- En la app movil, reforzar cumplimiento de version, vehiculo asignado y kilometraje para garantizar datos completos.
