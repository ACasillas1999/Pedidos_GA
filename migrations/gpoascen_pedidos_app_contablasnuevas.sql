-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 04-12-2025 a las 09:45:51
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `gpoascen_pedidos_app`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `checklist_vehicular`
--

CREATE TABLE `checklist_vehicular` (
  `id` int(11) NOT NULL,
  `id_vehiculo` int(11) NOT NULL,
  `id_chofer` int(11) DEFAULT NULL,
  `fecha_inspeccion` datetime NOT NULL,
  `kilometraje` int(11) DEFAULT NULL,
  `seccion` varchar(100) NOT NULL,
  `item` varchar(150) NOT NULL,
  `calificacion` enum('Bien','Mal','N/A') NOT NULL,
  `observaciones_rotulado` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `resuelto` tinyint(1) DEFAULT 0,
  `fecha_resolucion` datetime DEFAULT NULL,
  `orden_resolucion` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `choferes`
--

CREATE TABLE `choferes` (
  `ID` int(11) NOT NULL,
  `username` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `Sucursal` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Numero` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Estado` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `foto_perfil` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estadopedido`
--

CREATE TABLE `estadopedido` (
  `ID` int(11) NOT NULL,
  `ID_Pedido` int(11) NOT NULL,
  `Estado` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `Fecha` date DEFAULT NULL,
  `Hora` time DEFAULT NULL,
  `Coordenada` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `gasolina_import_log`
--

CREATE TABLE `gasolina_import_log` (
  `id` int(11) NOT NULL,
  `usuario` varchar(120) DEFAULT NULL,
  `rol` varchar(50) DEFAULT NULL,
  `resumen` text DEFAULT NULL,
  `errores` longtext DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `gasolina_import_pending`
--

CREATE TABLE `gasolina_import_pending` (
  `id` int(11) NOT NULL,
  `placa` varchar(50) NOT NULL,
  `empresa` varchar(120) DEFAULT '',
  `fecha` date NOT NULL,
  `anio` int(11) NOT NULL,
  `semana` int(11) NOT NULL,
  `importe` decimal(10,2) NOT NULL DEFAULT 0.00,
  `observaciones` varchar(255) DEFAULT '',
  `sucursal` varchar(100) DEFAULT '',
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `gasolina_semanal`
--

CREATE TABLE `gasolina_semanal` (
  `id_registro` int(11) NOT NULL,
  `id_vehiculo` int(11) NOT NULL,
  `anio` smallint(4) NOT NULL,
  `semana` tinyint(2) NOT NULL,
  `importe` decimal(10,2) NOT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `observaciones` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `gasolina_semanal_pendiente`
--

CREATE TABLE `gasolina_semanal_pendiente` (
  `id` int(11) NOT NULL,
  `placa` varchar(120) NOT NULL,
  `empresa` varchar(120) DEFAULT NULL,
  `fecha` date DEFAULT NULL,
  `anio` int(11) DEFAULT NULL,
  `semana` int(11) DEFAULT NULL,
  `importe` decimal(12,2) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `grupos_rutas`
--

CREATE TABLE `grupos_rutas` (
  `id` int(11) NOT NULL,
  `nombre_grupo` varchar(100) DEFAULT NULL,
  `sucursal` varchar(30) DEFAULT NULL,
  `chofer_asignado` varchar(100) DEFAULT NULL,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  `usuario_creo` varchar(100) DEFAULT NULL,
  `estado` varchar(20) DEFAULT 'ACTIVO',
  `notas` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_cambios`
--

CREATE TABLE `historial_cambios` (
  `ID` int(11) NOT NULL,
  `Usuario_ID` varchar(55) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `Pedido_ID` int(11) DEFAULT NULL,
  `Cambio` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `Fecha_Hora` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_conductores`
--

CREATE TABLE `historial_conductores` (
  `id` int(11) NOT NULL,
  `id_vehiculo` int(11) NOT NULL,
  `id_chofer` int(11) NOT NULL,
  `fecha_inicio` datetime NOT NULL,
  `fecha_fin` datetime DEFAULT NULL,
  `creado_por` varchar(80) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_responsables`
--

CREATE TABLE `historial_responsables` (
  `id` int(11) NOT NULL,
  `id_vehiculo` int(11) NOT NULL,
  `nombre_responsable` varchar(255) NOT NULL,
  `fecha_inicio` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_fin` datetime DEFAULT NULL,
  `creado_por` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_sucursal`
--

CREATE TABLE `historial_sucursal` (
  `id` int(11) NOT NULL,
  `id_vehiculo` int(11) NOT NULL,
  `sucursal_anterior` varchar(80) NOT NULL,
  `sucursal_nueva` varchar(80) NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  `usuario` varchar(80) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventario`
--

CREATE TABLE `inventario` (
  `id` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `marca` varchar(100) DEFAULT NULL,
  `modelo` varchar(120) DEFAULT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 0,
  `stock_minimo` int(11) NOT NULL DEFAULT 0,
  `stock_maximo` int(11) NOT NULL DEFAULT 0,
  `sku` varchar(64) DEFAULT NULL,
  `costo` decimal(10,2) DEFAULT 0.00,
  `precio` decimal(10,2) DEFAULT 0.00,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `base_unidad` varchar(32) DEFAULT NULL,
  `presentacion_unidad` varchar(32) DEFAULT NULL,
  `presentacion_cantidad` decimal(10,3) NOT NULL DEFAULT 1.000,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventario_movimiento`
--

CREATE TABLE `inventario_movimiento` (
  `id` int(11) NOT NULL,
  `id_inventario` int(11) NOT NULL,
  `tipo` enum('SALIDA','ENTRADA','AJUSTE') NOT NULL,
  `cantidad` int(11) NOT NULL,
  `referencia` varchar(50) DEFAULT NULL,
  `comentario` varchar(255) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventario_vehiculo`
--

CREATE TABLE `inventario_vehiculo` (
  `id_inventario` int(11) NOT NULL,
  `id_vehiculo` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `orden_servicio`
--

CREATE TABLE `orden_servicio` (
  `id` int(11) NOT NULL,
  `id_vehiculo` int(11) NOT NULL,
  `id_servicio` int(11) DEFAULT NULL,
  `duracion_minutos` int(11) NOT NULL DEFAULT 0,
  `notas` text DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `estatus` varchar(20) DEFAULT NULL,
  `fecha_programada` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `orden_servicio_hist`
--

CREATE TABLE `orden_servicio_hist` (
  `id` int(11) NOT NULL,
  `id_orden` int(11) NOT NULL,
  `de` varchar(20) DEFAULT NULL,
  `a` varchar(20) NOT NULL,
  `hecho_en` datetime NOT NULL DEFAULT current_timestamp(),
  `usuario` varchar(64) DEFAULT NULL,
  `comentario` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `orden_servicio_material`
--

CREATE TABLE `orden_servicio_material` (
  `id_orden` int(11) NOT NULL,
  `id_inventario` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedidos`
--

CREATE TABLE `pedidos` (
  `ID` int(11) NOT NULL,
  `SUCURSAL` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `ESTADO` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `FECHA_RECEPCION_FACTURA` date DEFAULT NULL,
  `FECHA_ENTREGA_CLIENTE` date DEFAULT NULL,
  `CHOFER_ASIGNADO` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `VENDEDOR` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `FACTURA` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `DIRECCION` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `FECHA_MIN_ENTREGA` date DEFAULT NULL,
  `FECHA_MAX_ENTREGA` date DEFAULT NULL,
  `MIN_VENTANA_HORARIA_1` time DEFAULT NULL,
  `MAX_VENTANA_HORARIA_1` time DEFAULT NULL,
  `NOMBRE_CLIENTE` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `TELEFONO` varchar(55) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `CONTACTO` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `COMENTARIOS` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `Ruta` varchar(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `Coord_Origen` varchar(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Coord_Destino` varchar(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `Ruta_Fotos` varchar(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `Kilometros` decimal(15,0) DEFAULT NULL,
  `tipo_envio` varchar(50) NOT NULL DEFAULT 'Programado',
  `estado_factura_caja` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '0=En Caja, 1=Entregada a Jefe, 2=Devuelta a Caja',
  `fecha_entrega_jefe` datetime DEFAULT NULL,
  `usuario_entrega_jefe` varchar(50) DEFAULT NULL,
  `fecha_devolucion_caja` datetime DEFAULT NULL,
  `usuario_devolucion_caja` varchar(50) DEFAULT NULL,
  `precio_factura_vendedor` decimal(10,2) DEFAULT NULL COMMENT 'Precio ingresado por vendedor al crear el pedido',
  `precio_factura_real` decimal(10,2) DEFAULT NULL COMMENT 'Precio real validado/corregido por Jefe de Choferes',
  `precio_validado_jc` tinyint(1) DEFAULT 0 COMMENT '0=No validado, 1=Validado por JC',
  `fecha_validacion_precio` datetime DEFAULT NULL COMMENT 'Fecha y hora cuando JC validó el precio',
  `usuario_validacion_precio` varchar(55) DEFAULT NULL COMMENT 'Usuario (JC) que validó el precio',
  `tiene_destinatario_capturado` tinyint(1) DEFAULT 0 COMMENT '0=Sin capturar, 1=Capturado'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Disparadores `pedidos`
--
DELIMITER $$
CREATE TRIGGER `AfterInsertPedido` AFTER INSERT ON `pedidos` FOR EACH ROW BEGIN
    INSERT INTO EstadoPedido (ID_Pedido, Estado, Fecha, Hora, Coordenada)
    VALUES (NEW.ID, NEW.ESTADO, CURDATE(), CURTIME(), '');
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedidos_destinatario`
--

CREATE TABLE `pedidos_destinatario` (
  `id` int(11) NOT NULL,
  `pedido_id` int(11) NOT NULL,
  `nombre_destinatario` varchar(200) DEFAULT NULL,
  `calle` varchar(255) DEFAULT NULL,
  `no_exterior` varchar(50) DEFAULT NULL,
  `no_interior` varchar(50) DEFAULT NULL,
  `entre_calles` varchar(255) DEFAULT NULL,
  `colonia` varchar(100) DEFAULT NULL,
  `codigo_postal` varchar(10) DEFAULT NULL,
  `ciudad` varchar(100) DEFAULT NULL,
  `estado_destino` varchar(100) DEFAULT NULL,
  `contacto_destino` varchar(100) DEFAULT NULL,
  `telefono_destino` varchar(55) DEFAULT NULL,
  `lat` decimal(10,8) DEFAULT NULL,
  `lng` decimal(11,8) DEFAULT NULL,
  `nombre_paqueteria` varchar(100) DEFAULT NULL COMMENT 'Ej: D8A, Estafeta, FedEx',
  `tipo_cobro` varchar(100) DEFAULT NULL COMMENT 'Ej: OCURRE X COBRAR, PREPAGADO',
  `atn` varchar(100) DEFAULT NULL COMMENT 'Atención a',
  `num_cliente` varchar(100) DEFAULT NULL COMMENT 'Número de cliente',
  `clave_sat` varchar(100) DEFAULT NULL COMMENT 'Clave SAT',
  `fecha_captura` datetime DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `usuario_capturo` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Almacena información detallada del destinatario para pedidos de paquetería';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedidos_grupos`
--

CREATE TABLE `pedidos_grupos` (
  `id` int(11) NOT NULL,
  `pedido_id` int(11) NOT NULL,
  `grupo_id` int(11) NOT NULL,
  `orden_entrega` int(11) DEFAULT NULL,
  `fecha_asignacion` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `registro_gasolina`
--

CREATE TABLE `registro_gasolina` (
  `id_registro` int(11) NOT NULL,
  `id_vehiculo` int(11) NOT NULL,
  `id_chofer` int(11) NOT NULL,
  `fecha_registro` date NOT NULL,
  `litros` decimal(10,2) NOT NULL,
  `costo` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `registro_kilometraje`
--

CREATE TABLE `registro_kilometraje` (
  `id_registro` int(11) NOT NULL,
  `id_vehiculo` int(11) NOT NULL,
  `id_chofer` int(11) NOT NULL,
  `Tipo_Registro` enum('Registro','Servicio') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Registro',
  `fecha_registro` date DEFAULT NULL,
  `kilometraje_inicial` int(11) NOT NULL,
  `kilometraje_final` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `servicios`
--

CREATE TABLE `servicios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `duracion_minutos` int(11) NOT NULL DEFAULT 0,
  `costo_mano_obra` decimal(10,2) DEFAULT 0.00,
  `precio` decimal(10,2) DEFAULT 0.00,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `servicios_config`
--

CREATE TABLE `servicios_config` (
  `clave` varchar(64) NOT NULL,
  `valor` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `servicios_detalle`
--

CREATE TABLE `servicios_detalle` (
  `id` int(11) NOT NULL,
  `id_servicio` int(11) NOT NULL,
  `tipo_servicio` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `detalles` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `reiniciar_km` tinyint(1) NOT NULL DEFAULT 0,
  `observaciones` text CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `servicio_insumo`
--

CREATE TABLE `servicio_insumo` (
  `id_servicio` int(11) NOT NULL,
  `id_inventario` int(11) NOT NULL,
  `cantidad` decimal(10,3) NOT NULL DEFAULT 1.000
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `servicio_vehiculo`
--

CREATE TABLE `servicio_vehiculo` (
  `id_servicio` int(11) NOT NULL,
  `id_vehiculo` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ubicaciones`
--

CREATE TABLE `ubicaciones` (
  `ID` int(15) NOT NULL,
  `Ubicacion` varchar(100) NOT NULL,
  `NombreCompleto` varchar(255) DEFAULT NULL,
  `Direccion` varchar(255) NOT NULL,
  `coordenadas` varchar(150) NOT NULL,
  `Telefono` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `ID` int(11) NOT NULL,
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Rol` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `Sucursal` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Numero` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vehiculos`
--

CREATE TABLE `vehiculos` (
  `id_vehiculo` int(11) NOT NULL,
  `id_chofer_asignado` int(11) DEFAULT NULL,
  `numero_serie` varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `placa` varchar(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `tipo` varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `foto_path` varchar(255) DEFAULT NULL,
  `Sucursal` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `razon_social` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Razón social bajo la cual se compró el vehículo',
  `Km_de_Servicio` int(11) NOT NULL DEFAULT 5000,
  `Km_Total` int(11) NOT NULL DEFAULT 0,
  `Km_Actual` int(11) NOT NULL DEFAULT 0,
  `Fecha_Ultimo_Servicio` date DEFAULT NULL,
  `es_particular` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=Servicio (puede tener chofer asignado), 1=Particular (no puede tener chofer)',
  `responsable` varchar(100) DEFAULT NULL COMMENT 'Nombre del responsable (texto libre o desde usuarios)'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `checklist_vehicular`
--
ALTER TABLE `checklist_vehicular`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_checklist_vehiculo` (`id_vehiculo`,`fecha_inspeccion`),
  ADD KEY `idx_checklist_chofer` (`id_chofer`,`fecha_inspeccion`);

--
-- Indices de la tabla `choferes`
--
ALTER TABLE `choferes`
  ADD PRIMARY KEY (`ID`);

--
-- Indices de la tabla `estadopedido`
--
ALTER TABLE `estadopedido`
  ADD PRIMARY KEY (`ID`);

--
-- Indices de la tabla `gasolina_import_log`
--
ALTER TABLE `gasolina_import_log`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `gasolina_import_pending`
--
ALTER TABLE `gasolina_import_pending`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_placa_fecha_sucursal` (`placa`,`fecha`,`sucursal`);

--
-- Indices de la tabla `gasolina_semanal`
--
ALTER TABLE `gasolina_semanal`
  ADD PRIMARY KEY (`id_registro`),
  ADD UNIQUE KEY `uk_gasolina_vehiculo_anio_semana` (`id_vehiculo`,`anio`,`semana`);

--
-- Indices de la tabla `gasolina_semanal_pendiente`
--
ALTER TABLE `gasolina_semanal_pendiente`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_gas_pending_placa_anio_semana` (`placa`,`anio`,`semana`);

--
-- Indices de la tabla `grupos_rutas`
--
ALTER TABLE `grupos_rutas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_chofer` (`chofer_asignado`),
  ADD KEY `idx_sucursal` (`sucursal`),
  ADD KEY `idx_estado` (`estado`);

--
-- Indices de la tabla `historial_cambios`
--
ALTER TABLE `historial_cambios`
  ADD PRIMARY KEY (`ID`);

--
-- Indices de la tabla `historial_conductores`
--
ALTER TABLE `historial_conductores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_vehiculo` (`id_vehiculo`),
  ADD KEY `id_chofer` (`id_chofer`);

--
-- Indices de la tabla `historial_responsables`
--
ALTER TABLE `historial_responsables`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_vehiculo` (`id_vehiculo`),
  ADD KEY `idx_fecha_inicio` (`fecha_inicio`),
  ADD KEY `idx_fecha_fin` (`fecha_fin`);

--
-- Indices de la tabla `historial_sucursal`
--
ALTER TABLE `historial_sucursal`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_vehiculo` (`id_vehiculo`);

--
-- Indices de la tabla `inventario`
--
ALTER TABLE `inventario`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `inventario_movimiento`
--
ALTER TABLE `inventario_movimiento`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_mov_inv` (`id_inventario`);

--
-- Indices de la tabla `inventario_vehiculo`
--
ALTER TABLE `inventario_vehiculo`
  ADD PRIMARY KEY (`id_inventario`,`id_vehiculo`),
  ADD KEY `fk_invveh_veh` (`id_vehiculo`);

--
-- Indices de la tabla `orden_servicio`
--
ALTER TABLE `orden_servicio`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_os_veh` (`id_vehiculo`),
  ADD KEY `idx_os_srv` (`id_servicio`);

--
-- Indices de la tabla `orden_servicio_hist`
--
ALTER TABLE `orden_servicio_hist`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_hist_orden` (`id_orden`);

--
-- Indices de la tabla `orden_servicio_material`
--
ALTER TABLE `orden_servicio_material`
  ADD PRIMARY KEY (`id_orden`,`id_inventario`),
  ADD KEY `fk_osm_inv` (`id_inventario`);

--
-- Indices de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `idx_pedidos_estado_factura_caja` (`estado_factura_caja`),
  ADD KEY `idx_tiene_destinatario` (`tiene_destinatario_capturado`);

--
-- Indices de la tabla `pedidos_destinatario`
--
ALTER TABLE `pedidos_destinatario`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_pedido` (`pedido_id`),
  ADD KEY `idx_pedido_id` (`pedido_id`),
  ADD KEY `idx_coordenadas` (`lat`,`lng`);

--
-- Indices de la tabla `pedidos_grupos`
--
ALTER TABLE `pedidos_grupos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_pedido_grupo` (`pedido_id`),
  ADD KEY `idx_pedido` (`pedido_id`),
  ADD KEY `idx_grupo` (`grupo_id`);

--
-- Indices de la tabla `registro_gasolina`
--
ALTER TABLE `registro_gasolina`
  ADD PRIMARY KEY (`id_registro`),
  ADD KEY `id_vehiculo` (`id_vehiculo`),
  ADD KEY `id_chofer` (`id_chofer`);

--
-- Indices de la tabla `registro_kilometraje`
--
ALTER TABLE `registro_kilometraje`
  ADD PRIMARY KEY (`id_registro`);

--
-- Indices de la tabla `servicios`
--
ALTER TABLE `servicios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_servicios_nombre` (`nombre`);

--
-- Indices de la tabla `servicios_config`
--
ALTER TABLE `servicios_config`
  ADD PRIMARY KEY (`clave`);

--
-- Indices de la tabla `servicios_detalle`
--
ALTER TABLE `servicios_detalle`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `servicio_insumo`
--
ALTER TABLE `servicio_insumo`
  ADD PRIMARY KEY (`id_servicio`,`id_inventario`),
  ADD KEY `idx_servins_inventario` (`id_inventario`);

--
-- Indices de la tabla `servicio_vehiculo`
--
ALTER TABLE `servicio_vehiculo`
  ADD PRIMARY KEY (`id_servicio`,`id_vehiculo`),
  ADD KEY `idx_servveh_vehiculo` (`id_vehiculo`);

--
-- Indices de la tabla `ubicaciones`
--
ALTER TABLE `ubicaciones`
  ADD PRIMARY KEY (`ID`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`ID`);

--
-- Indices de la tabla `vehiculos`
--
ALTER TABLE `vehiculos`
  ADD PRIMARY KEY (`id_vehiculo`),
  ADD UNIQUE KEY `numero_serie` (`numero_serie`),
  ADD UNIQUE KEY `placa` (`placa`),
  ADD KEY `idx_veh_chof` (`id_chofer_asignado`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `checklist_vehicular`
--
ALTER TABLE `checklist_vehicular`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `choferes`
--
ALTER TABLE `choferes`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `estadopedido`
--
ALTER TABLE `estadopedido`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `gasolina_import_log`
--
ALTER TABLE `gasolina_import_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `gasolina_import_pending`
--
ALTER TABLE `gasolina_import_pending`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `gasolina_semanal`
--
ALTER TABLE `gasolina_semanal`
  MODIFY `id_registro` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `gasolina_semanal_pendiente`
--
ALTER TABLE `gasolina_semanal_pendiente`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `grupos_rutas`
--
ALTER TABLE `grupos_rutas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `historial_cambios`
--
ALTER TABLE `historial_cambios`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `historial_conductores`
--
ALTER TABLE `historial_conductores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `historial_responsables`
--
ALTER TABLE `historial_responsables`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `historial_sucursal`
--
ALTER TABLE `historial_sucursal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `inventario`
--
ALTER TABLE `inventario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `inventario_movimiento`
--
ALTER TABLE `inventario_movimiento`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `orden_servicio`
--
ALTER TABLE `orden_servicio`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `orden_servicio_hist`
--
ALTER TABLE `orden_servicio_hist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pedidos_destinatario`
--
ALTER TABLE `pedidos_destinatario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pedidos_grupos`
--
ALTER TABLE `pedidos_grupos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `registro_gasolina`
--
ALTER TABLE `registro_gasolina`
  MODIFY `id_registro` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `registro_kilometraje`
--
ALTER TABLE `registro_kilometraje`
  MODIFY `id_registro` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `servicios`
--
ALTER TABLE `servicios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `servicios_detalle`
--
ALTER TABLE `servicios_detalle`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ubicaciones`
--
ALTER TABLE `ubicaciones`
  MODIFY `ID` int(15) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `vehiculos`
--
ALTER TABLE `vehiculos`
  MODIFY `id_vehiculo` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `gasolina_semanal`
--
ALTER TABLE `gasolina_semanal`
  ADD CONSTRAINT `fk_gasolina_semanal_vehiculo` FOREIGN KEY (`id_vehiculo`) REFERENCES `vehiculos` (`id_vehiculo`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `historial_responsables`
--
ALTER TABLE `historial_responsables`
  ADD CONSTRAINT `historial_responsables_ibfk_1` FOREIGN KEY (`id_vehiculo`) REFERENCES `vehiculos` (`id_vehiculo`) ON DELETE CASCADE;

--
-- Filtros para la tabla `inventario_movimiento`
--
ALTER TABLE `inventario_movimiento`
  ADD CONSTRAINT `fk_mov_inv` FOREIGN KEY (`id_inventario`) REFERENCES `inventario` (`id`);

--
-- Filtros para la tabla `inventario_vehiculo`
--
ALTER TABLE `inventario_vehiculo`
  ADD CONSTRAINT `fk_invveh_inv` FOREIGN KEY (`id_inventario`) REFERENCES `inventario` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_invveh_veh` FOREIGN KEY (`id_vehiculo`) REFERENCES `vehiculos` (`id_vehiculo`) ON DELETE CASCADE;

--
-- Filtros para la tabla `orden_servicio`
--
ALTER TABLE `orden_servicio`
  ADD CONSTRAINT `fk_os_srv` FOREIGN KEY (`id_servicio`) REFERENCES `servicios` (`id`),
  ADD CONSTRAINT `fk_os_veh` FOREIGN KEY (`id_vehiculo`) REFERENCES `vehiculos` (`id_vehiculo`);

--
-- Filtros para la tabla `orden_servicio_material`
--
ALTER TABLE `orden_servicio_material`
  ADD CONSTRAINT `fk_osm_inv` FOREIGN KEY (`id_inventario`) REFERENCES `inventario` (`id`),
  ADD CONSTRAINT `fk_osm_os` FOREIGN KEY (`id_orden`) REFERENCES `orden_servicio` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `pedidos_destinatario`
--
ALTER TABLE `pedidos_destinatario`
  ADD CONSTRAINT `fk_destinatario_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `registro_gasolina`
--
ALTER TABLE `registro_gasolina`
  ADD CONSTRAINT `registro_gasolina_ibfk_1` FOREIGN KEY (`id_vehiculo`) REFERENCES `vehiculos` (`id_vehiculo`),
  ADD CONSTRAINT `registro_gasolina_ibfk_2` FOREIGN KEY (`id_chofer`) REFERENCES `choferes` (`ID`);

--
-- Filtros para la tabla `servicio_insumo`
--
ALTER TABLE `servicio_insumo`
  ADD CONSTRAINT `fk_servins_inv` FOREIGN KEY (`id_inventario`) REFERENCES `inventario` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_servins_srv` FOREIGN KEY (`id_servicio`) REFERENCES `servicios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `servicio_vehiculo`
--
ALTER TABLE `servicio_vehiculo`
  ADD CONSTRAINT `fk_servveh_srv` FOREIGN KEY (`id_servicio`) REFERENCES `servicios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_servveh_veh` FOREIGN KEY (`id_vehiculo`) REFERENCES `vehiculos` (`id_vehiculo`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
