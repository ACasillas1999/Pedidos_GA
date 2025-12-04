-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 04-12-2025 a las 08:59:21
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

--
-- Volcado de datos para la tabla `inventario`
--

INSERT INTO `inventario` (`id`, `nombre`, `marca`, `modelo`, `cantidad`, `stock_minimo`, `stock_maximo`, `sku`, `costo`, `precio`, `activo`, `base_unidad`, `presentacion_unidad`, `presentacion_cantidad`, `creado_en`, `actualizado_en`) VALUES
(1, 'freno', 'Marca producto', 'freno de tambor', 0, 1, 1, 'sku del producto', 50.00, NULL, 1, '', 'pieza', 1.000, '2025-11-11 22:40:32', '2025-12-03 19:24:01'),
(2, 'freno', 'Marca producto', 'freno de disco', 1, 1, 1, 'sku del producto', 60.00, NULL, 1, '', 'pieza', 1.000, '2025-11-11 22:41:20', '2025-11-11 22:41:20');

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

--
-- Volcado de datos para la tabla `inventario_movimiento`
--

INSERT INTO `inventario_movimiento` (`id`, `id_inventario`, `tipo`, `cantidad`, `referencia`, `comentario`, `creado_en`) VALUES
(1, 1, 'AJUSTE', 1, 'OS:1', 'Reserva Programado', '2025-12-03 19:24:01');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventario_vehiculo`
--

CREATE TABLE `inventario_vehiculo` (
  `id_inventario` int(11) NOT NULL,
  `id_vehiculo` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `inventario_vehiculo`
--

INSERT INTO `inventario_vehiculo` (`id_inventario`, `id_vehiculo`) VALUES
(2, 19),
(2, 20),
(2, 21),
(2, 25),
(2, 26);

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

--
-- Volcado de datos para la tabla `orden_servicio`
--

INSERT INTO `orden_servicio` (`id`, `id_vehiculo`, `id_servicio`, `duracion_minutos`, `notas`, `creado_en`, `estatus`, `fecha_programada`) VALUES
(1, 25, 2, 0, NULL, '2025-12-03 19:23:38', 'Completado', '2025-12-03'),
(2, 25, 2, 0, NULL, '2025-12-03 19:24:31', NULL, NULL);

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

--
-- Volcado de datos para la tabla `orden_servicio_hist`
--

INSERT INTO `orden_servicio_hist` (`id`, `id_orden`, `de`, `a`, `hecho_en`, `usuario`, `comentario`) VALUES
(1, 1, 'Pendiente', 'Programado', '2025-12-03 13:24:01', NULL, 'Prog: 2025-12-03'),
(2, 1, 'Programado', 'EnTaller', '2025-12-03 13:25:57', NULL, NULL),
(3, 1, 'EnTaller', 'Completado', '2025-12-03 13:27:53', NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `orden_servicio_material`
--

CREATE TABLE `orden_servicio_material` (
  `id_orden` int(11) NOT NULL,
  `id_inventario` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `orden_servicio_material`
--

INSERT INTO `orden_servicio_material` (`id_orden`, `id_inventario`, `cantidad`) VALUES
(1, 1, 1),
(2, 1, 1);

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

--
-- Volcado de datos para la tabla `servicios`
--

INSERT INTO `servicios` (`id`, `nombre`, `duracion_minutos`, `costo_mano_obra`, `precio`, `creado_en`, `actualizado_en`) VALUES
(1, 'cambio de frenos tambor', 60, 68.75, 0.00, '2025-11-11 22:52:52', '2025-11-11 22:52:52'),
(2, 'Prueba', 40, 45.83, 0.00, '2025-12-03 19:22:00', '2025-12-03 19:22:00');

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

--
-- Volcado de datos para la tabla `servicio_insumo`
--

INSERT INTO `servicio_insumo` (`id_servicio`, `id_inventario`, `cantidad`) VALUES
(1, 1, 1.000),
(2, 1, 1.000);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `servicio_vehiculo`
--

CREATE TABLE `servicio_vehiculo` (
  `id_servicio` int(11) NOT NULL,
  `id_vehiculo` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `servicio_vehiculo`
--

INSERT INTO `servicio_vehiculo` (`id_servicio`, `id_vehiculo`) VALUES
(1, 1),
(2, 1),
(2, 2),
(2, 3),
(2, 4),
(2, 5),
(2, 6),
(2, 7),
(2, 8),
(2, 9),
(2, 10),
(2, 11),
(2, 12),
(2, 13),
(2, 14),
(2, 15),
(2, 16),
(2, 17),
(2, 18),
(2, 19),
(2, 20),
(2, 21),
(2, 22),
(2, 23),
(2, 24),
(2, 25),
(2, 26),
(2, 27),
(2, 28),
(2, 29);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ubicaciones`
--

CREATE TABLE `ubicaciones` (
  `ID` int(11) NOT NULL,
  `Ubicacion` varchar(100) NOT NULL,
  `NombreCompleto` varchar(255) DEFAULT NULL,
  `Direccion` varchar(255) NOT NULL,
  `Telefono` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ubicaciones`
--

INSERT INTO `ubicaciones` (`ID`, `Ubicacion`, `NombreCompleto`, `Direccion`, `Telefono`) VALUES
(1, 'AIESA', 'ASCENCIO INDUSTRIAL ELECTRICA SA DE CV', 'Av. Gobernador Curiel número 2050 en la Colonia 8 de Julio en Guadalajara, Jalisco Código Postal 44910', '+523338109101'),
(2, 'DEASA', 'DISTRIBUIDORA ELECTRICA ASCENCIO SA DE CV', 'Avenida Alemania 1255 en la Colonia Moderna con Código Postal 44190 en  Guadalajara, Jalisco.', '+523336141989'),
(3, 'DIMEGSA', 'DISTRIBUIDORA DE MATERIALES ELECTRICOS DE GUADALAJARA SA DE CV', 'Calle 8 de Julio 1031, en la Colonia Moderna con código postal 44190 en  Guadalajara, Jalisco', '+523336193060'),
(4, 'FESA', 'FERRETERIA Y SANITARIOS ASCENCIO SA DE CV', 'Calle Ramos Millán 284, en la Colonia Santa Teresita con código postal 44600 en Guadalajara, Jalisco', '+523336580313'),
(5, 'GABSA', 'GRUPO ASCENCIO DEL BAJIO SA DE CV', 'Boulevard Torres Landa con el número 2522 en la Colonia Buenos Aires en la Ciudad de León de los Aldama, Guanajuato.', '+524777136491'),
(6, 'ILUMINACION', 'ELECTRO ILUMINACION TAPATIA SA DE CV', 'Avenida 8 de Julio número 1150, Colonia Moderna, C.P. 44190 en  Guadalajara, Jalisco.', '+523336501060'),
(7, 'SEGSA', 'SURTIDOR ELECTRICO GARIBALDI SA DE CV', 'Calle Ramos Millán número 277, Col. Santa Teresita, Guadalajara, Jalisco, C.P. 44600.', '+523338252572'),
(8, 'TAPATIA', 'ELECTRO INDUSTRIAL TAPATIA SA DE CV', 'Avenida 8 de Julio número 1146, Colonia Moderna, C.P. 44190 en  Guadalajara, Jalisco.', '+523336191453'),
(9, 'VALLARTA', 'DISTRIBUIDORA DE MATERIALES ELECTRICOS DE GUADALAJARA SA DE CV', 'Avenida 8 de Julio número 1031, en la Colonia Moderna con código postal 44190 en  Guadalajara, Jalisco.', '+523292962100'),
(10, 'QUERETARO', 'ASCENCIO INDUSTRIAL ELECTRICA SA DE CV', 'Av. Gobernador Curiel número 2050 en la Colonia 8 de Julio en Guadalajara, Jalisco Código Postal 44910', '+524429269017'),
(11, 'CODI', 'ASCENCIO SA DE CV', 'Av. Circunvalación Agustín Yáñez 1332, Moderna, 44190 Guadalajara, Jal.', '+523336141989');

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

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`ID`, `username`, `password`, `Rol`, `fecha_registro`, `Sucursal`, `Numero`, `Nombre`) VALUES
(8, 'JC.DEASA', '$2y$10$Blli9xk1TvY4mjJnX7CYrO268vkqI6FBUSH0ORHu7SZEVEXO3iDjq', 'JC', '2024-07-13 02:28:25', 'DEASA', '0', 'JC.DEASA'),
(9, 'JC.DIMEGSA', '$2y$10$yk6m/gMpXV2nKdwC5oo3rOKPkEHMpuwla2gC7CjEt70FT5HXeQgJG', 'JC', '2024-07-13 02:34:50', 'DIMEGSA', '3310067437', 'JC.DIMEGSA'),
(15, 'Vendedor_DEASA', '$2y$10$RxP/QMHjMywbN9tpcgUUV.jTtqdX9OYCcEWcVCWSOY/78dryeahZa', 'VR', '2024-07-23 04:07:13', 'DEASA', '3310067437', 'Vendedor_DEASA'),
(31, 'n.ibarra', '$2y$10$VpgwBgPSBP3AoYR4moH2aex3hPl/MoBFL8/HwReeFImzxYnuPkeqq', 'VR', '2024-07-31 17:19:11', 'DIMEGSA', '3313407158', 'NICOLAS MANUEL IBARRA GOMEZ'),
(32, 'jose.rocha', '$2y$10$Kpaq0F3f6uuME29zNC3oBOucrQ8E7tpFPf8XbkVOZLt/G06EVw6R2', 'VR', '2024-07-31 17:21:40', 'DIMEGSA', '3318504324', 'JOSE MANUEL ROCHA DIAZ'),
(33, 'a.aguila', '$2y$10$1H3SKirSZ.dAslKKIwMxbOzkrXfexObt29qV1ltBBQVEAsMMOUQAa', 'VR', '2024-07-31 18:03:53', 'DIMEGSA', '333815 7119', 'ALAN DARIO AGUILAR ESCOTO'),
(34, 'm.lemus', '$2y$10$C6eZWCp0yeRU78njnRFaM.vkH9wSPZ2D71jWP4nJAqluLlhnXUDCi', 'VR', '2024-07-31 18:27:48', 'DIMEGSA', '3311154916', 'ANDREA MONSERRAT LEMUS GUERRERO'),
(36, 'd.aranda', '$2y$10$AvT3AgXY1lxEYx0tq3mmoOIixnQHVIbAut824laVAlgpjtUA5gIfi', 'VR', '2024-07-31 18:31:53', 'DIMEGSA', '3318433290 ', 'DIEGO ALEJANDRO ARANDA VALDEZ'),
(37, 'e.diaz', '$2y$10$MjozdlQodESPhcaSwMqkQOFbjGG9m.MzWANWW63NRh44hQTjOAdSS', 'VR', '2024-07-31 18:32:44', 'DIMEGSA', '3316012412', 'EDGAR IVAN DIAZ ROSAS'),
(38, 'c.gomez', '$2y$10$xz8ZBqmr1q5u0B7Oe7tGteA5dMxjQCLr5XZXeoDu0XRct3aj5DWzG', 'VR', '2024-07-31 18:33:15', 'DIMEGSA', '331894 5480', 'CRISTINA GOMEZ ELIZALDE'),
(39, 'r.gomez', '$2y$10$Aetmv7Vs911mBLEY9ZXkluRPSya6QXbPZpUaZBHcJ1//zhNEX9V7C', 'VR', '2024-07-31 18:34:21', 'DIMEGSA', '3316047020', 'RAUL GOMEZ'),
(41, 'k.carmona', '$2y$10$4OFdsZuURSQu43phBtzVJ.mxNMOZr3v0PUMoxM6rqRbx8wS4S7FMG', 'VR', '2024-07-31 18:40:31', 'DIMEGSA', '3310112099', 'KARLA ELIZABETH CARMONA SALAZAR'),
(42, 'e.velazquez', '$2y$10$rhw/tFXxo/dEzPc0JKwgNeH.A7A58gGaLPxX0AhLcBk5TSZj.ofwu', 'VR', '2024-07-31 18:41:21', 'DIMEGSA', '3318937030', 'EDUARDO VELAZQUEZ PEREZ'),
(43, 's.almaraz', '$2y$10$3pwCawB00wKrs68Fjrkn8.BP8jp5CMch1Agk436wbr2FpcKdKMdZe', 'VR', '2024-07-31 18:42:09', 'DIMEGSA', '3318958578', 'SERGIO BENJAMIN ALMARAZ ROCHA'),
(44, 'Alex', '$2y$10$H6Pv9E6H0sL5CiWYeeEP3ejVk5Au5kfRxwHw/fcddDA6ectHMH2oy', 'Admin', '2024-07-31 18:53:35', 'TODAS', '3318502809', 'Alex Casillas'),
(45, 'Admin', '$2y$10$u2RnCuHhdb9z5PPAVySzX.5AgBc.Pl6Z46uB3Nhct6EXfnDnKG5s2', 'Admin', '2024-07-31 18:58:50', 'TODAS', '3318502809', 'Administrador'),
(46, 'a.morales', '$2y$10$4gPv77jkz7O4.iU9w.J29.R1Pac7HakzE6HPokZNdeturA12/1VMa', 'VR', '2024-07-31 19:04:31', 'DIMEGSA', '0', 'ALEJANDRA MORALES'),
(49, 's.rodriguez', '$2y$10$NhBjN3aMHcTpX2qZ1/97m.gGP3q8F5UPeR/ZGC1iWJFYsqh7..cxG', 'VR', '2024-07-31 19:06:21', 'DIMEGSA', '0', 'SALVADOR RODRIGUEZ LUJANO'),
(50, 'm.guadalupe', '$2y$10$uyy20E1Ewz4EwxUpcURdhukzdCX4P8mNHPAqIQe4wc9wkffQutmoK', 'VR', '2024-07-31 19:06:52', 'DIMEGSA', '0', 'MARIA GUADALUPE GAYTAN PEREZ'),
(51, 'D.Lora', '$2y$10$eBu/I8e2k1Zi6tgi4lUyGOuT3ejDQ..SFnSutF0NDOZ.e3NW/khE6', 'Admin', '2024-07-31 19:13:59', 'TODAS', '0', 'Deinap Lora'),
(52, 'L.Perez', '$2y$10$sm7FW3NDf07Y5yXOiYFDP.8cps/IQ39UwHc4iLdaCQSkf5TJYr7a6', 'Admin', '2024-07-31 19:14:43', 'TODAS', '0', 'Layla Perez'),
(53, 'E.Dominguez', '$2y$10$R5Suim0oa.eou21I6Xcv.OnaRkDt0U6zmpQdq7qC5MHsSVg63aEPu', 'Admin', '2024-07-31 19:15:17', 'TODAS', '0', 'Emilio Dominguez'),
(54, 'Test_Diseno', '$2y$10$.Gb8rq3.ix8G4ZtmU9SYHOHG8mfcaezOdJlOT7tT7fKQUFezDrD4e', 'Admin', '2024-07-31 19:16:55', 'TODAS', '0', 'Suyevi'),
(55, 'a.pina', '$2y$10$666XqK5axK0vkAJQNh4SHO.7Hj9i07FHGK3pwWi9unWOn0CI0efAy', 'Admin', '2024-08-01 16:06:50', 'TODAS', '0', 'Areli Pina'),
(56, 'o.rodriguez', '$2y$10$svgJHeHkVmNsfdvoLjomWe4fDeks9j1opPownLhjtjkqTQqMZVJAm', 'JC', '2024-08-07 20:36:35', 'DIMEGSA', '3317551262', 'OSCAR RODRIGUEZ'),
(57, 'Edgar.g', '$2y$10$TRuBTCs6V0GnbMBSInf0NOyl/hJo9FDkUjLK5Li.lRGM/Yv065VDS', 'VR', '2024-12-03 23:25:06', 'DEASA', '3310067437', 'Garcia'),
(58, 'Sebastian', '$2y$10$Yb7l6ZpL8kb.2D/F1YgtVuxSoG1qKZ5NKtFo/pvbTrPmXONybTBFW', 'JC', '2024-12-04 04:29:04', 'DEASA', '3314341096', 'Sebastian Cano'),
(59, 'Vendedor1', '$2y$10$.9SiLCLaIQ0WFQ5lyTM6k.zPlqbwf.jTQz9lVJ4SJhBRDby7Tpc8O', 'VR', '2024-12-04 05:52:48', 'DEASA', '3314341096', 'Edgar Garcia'),
(60, 'Armando.b', '$2y$10$WOLU3RojEj3X6bHbf8xhQuoZ5BbKv55DW1VgXv2CP4zUnzSqVBrqi', 'VR', '2024-12-04 16:56:30', 'DIMEGSA', '3311973919', 'Raul Becerra'),
(61, 'a.ramirez', '$2y$10$TcjZejY4P7enif3odGOQOuSEvVZThtnyj.fe.Jkuz8TH0Q5ty4fY.', 'VR', '2024-12-07 16:59:59', 'TAPATIA', '33 1169 4171 ', 'ABRAHAM ISRAEL RAMIREZ HERNANDEZ'),
(62, 'a.ortiz', '$2y$10$4lfTUmBFTiy7jNolAQRXPuCsK2MB29Dmjqd2jEgH7POZbsOf5PaCG', 'VR', '2024-12-07 17:03:39', 'TAPATIA', '33 3474 8935', 'ARACELI ORTIZ MORALES'),
(63, 'd.ramirez', '$2y$10$15ubf6j4ovY3OjNpZu7crOPzOaM1AQfMMPkbVcKF63EGJb5/s9zPa', 'VR', '2024-12-07 17:04:47', 'TAPATIA', '33 3968 4306', 'DIGNA JUANA LARA RAMIREZ '),
(64, 'f.rodriguez', '$2y$10$w4j5eQ78g7G3u6SBYLaQx.FMIU1IV4gZwkGZnZTu/Xb7HwMvRVnNe', 'VR', '2024-12-07 17:05:40', 'TAPATIA', '33 1038 7302', 'FERNANDA RODRIGUEZ'),
(65, 'm.larios', '$2y$10$dnpeNnMmPXSuo/ajeAeMpOkyxZmJWY1Ja/r3rRJYRNae/qV/p8Qrm', 'VR', '2024-12-07 17:06:26', 'TAPATIA', '33 2158 2539', 'MANUEL JUVENAL LARIOS'),
(66, 'a.cigarroa', '$2y$10$/Bopd28Vcjmg9mROv1go5uXD32h5IVYpP/qDpGYpYRoCuMLILaiMe', 'VR', '2024-12-07 17:07:09', 'TAPATIA', '33 1862 1361', 'ALEJANDRO AGUILAR CIGARROA'),
(67, 'j.modesto', '$2y$10$BmLBxSMfyMGhmSlj64rcGOUGUBN4HxhxQxdPs54UfP37vXGc2bzfu', 'VR', '2024-12-07 17:07:48', 'TAPATIA', '33 1406 0665', 'JOSE MIGUEL MODESTO LIRA'),
(68, 'm.mendez', '$2y$10$dg1UTnDttN0LUPnRomwBsuBd0Wt9sVOBYz6J0YZ2AAFrzvJalznZu', 'VR', '2024-12-07 17:08:38', 'TAPATIA', '33 3496 9820', 'MARIA GUADALUPE GARCIA MENDEZ'),
(69, 'p.rangel', '$2y$10$gRBIfhIqPuLWkHST4wbli.EbvwLRS7Z8xIZHt1rprAr9L0LjP2zui', 'VR', '2024-12-07 17:09:22', 'TAPATIA', '33 2832 4959', 'PAULA MONTSERRAT RANGEL ANGUIANO'),
(70, 's.garcia', '$2y$10$.z1dBHEV2yRHY.3IRpCrUO/edme1Hx4qb.oWYJ4pcxpNJp38.Mmca', 'VR', '2024-12-07 17:10:00', 'TAPATIA', '33 3496 9557', 'SAUL GARCIA MARTINEZ'),
(71, 'g.hernandez', '$2y$10$v.CkpfOcjuXfa/xmbt2UB.rhTxazVfLwyKeOg1cC5vPwuS7v.lxjq', 'VR', '2024-12-07 17:10:44', 'TAPATIA', '33 1850 5782', 'GUADALUPE HERNANDEZ ACOSTA'),
(73, 'm.martinez', '$2y$10$/PrKYYqkagfpQvuUK5Veh.SW0Vz29FOJXyWV4FPdLQyq8JJC3gDUO', 'Admin', '2025-03-07 18:53:25', 'TODAS', '0', 'Mario Alberto Martínez'),
(74, 'E.Rubio', '$2y$10$KiXbxGDDBwJW9JYmCTJbeu1UJAylBI/rBgK3d58teJcc.JwWKWIeO', 'Admin', '2025-03-12 19:11:11', 'TODAS', '3339565268', 'Erick Rubio'),
(76, 'h.ramirez', '$2y$10$1/xRGnmv5yVsHChwNqlW9ejaVpQf5w2JowRsNg.Ba3/HSFWxvzNFW', 'JC', '2025-03-12 19:19:44', 'SEGSA', '3316007026', 'Héctor Ramírez'),
(78, 'J.Garcia', '$2y$10$oKPWbrY80M4cJnF9S7MBVOTKCO6gXPztUMTpv6NBBF/B..k5ANWcS', 'JC', '2025-03-12 19:23:41', 'CODI', '3316000956', 'GARCIA PADILLA JOSE CESAR'),
(79, 'D.Rodriguez', '$2y$10$jFOq3V9tthpSiud.kMmA.eHPyWrQJ95bcyzdvXeP61o9MLd7caOWy', 'JC', '2025-03-12 19:27:28', 'AIESA', '3332013842', 'Diego Humberto Rodríguez Castillo '),
(80, 'E.Becerra', '$2y$10$jx.p.1H1gEDYjxYtrGEmeeC6dJECn/4vMXyjOWp7cSfaaByDKzjdq', 'JC', '2025-03-12 19:31:38', 'VALLARTA', '3332005880', 'Alexis Emmanuel Becerra '),
(81, 'J.Ramirez', '$2y$10$2J45bdUPv/L7x3XJ1Nnm.OenxUCI0SMMzlKnhEkOVigUGMcpJTMjK', 'JC', '2025-03-12 19:33:33', 'GABSA', '3316047352', 'Jonatan Miguel Ángel Ramírez Almaguer '),
(82, 'JC.QRO', '$2y$10$B1WmbBcnBYnZR/ZrpHH7ce71yw0.KGM5AxFjn2wCwk6edRpHwNJkG', 'JC', '2025-03-12 19:36:23', 'QUERETARO', '0', 'Jefe de Choferes Queretaro'),
(83, 'jm.Guevara', '$2y$10$xlxhV5SV2mnxabzp.rEdJuCu1FywLFs4dPOxvzsi6SRwAy14m7xU2', 'VR', '2025-03-15 19:06:26', 'TAPATIA', '0', 'GUEVARA REGLA JUAN MANUEL'),
(84, 'a.hernandez', '$2y$10$5sQQb4A2i/HjxUGI2NA4UeD6WfedwASYb9h3I47RLAw9WsxEtXxTG', 'VR', '2025-03-18 23:17:13', 'DEASA', '33 3844 1171', 'ALEJANDRA HERNANDEZ RODRIGUEZ'),
(85, 'alma.hernandez', '$2y$10$x30RsGKrryAr/P1cjjqo7OBVZDo3c9w0QGbWnMWyHwR9f7ic0Pqba', 'VR', '2025-03-18 23:17:49', 'DEASA', '0', 'ALMA HERNANDEZ'),
(86, 'a.garcia', '$2y$10$JntwyW6xIfOhoH6CuhhOKObT8az9WP6.CGRAX6zDdbjKkHRhTdUnK', 'VR', '2025-03-18 23:18:33', 'DEASA', '33 3159 9894', 'BEATRIZ ADALID GARCIA VIDRIO'),
(87, 'daniel.ornelas', '$2y$10$iHKubKs6PLHopGl6mWAz3uGg16I8ShKDc2Z6qhShcwmHmCmhCFn8i', 'VR', '2025-03-18 23:19:06', 'DEASA', '33 1837 3443', 'DANIEL ORNELAS CORONADO'),
(88, 'e.monteon', '$2y$10$0ETYSm3lG.XcfWvJNzXK2O2wO42L2Q9/Mo1RSHTeQXE.HHRiaMiLa', 'VR', '2025-03-18 23:19:37', 'DEASA', '33 1865 7354', 'EDUARDO MONTEON IBARRA'),
(89, 'jorge.deasa', '$2y$10$VREdOShSVuUrdsE9aGUiwulbfEcf.6oEY3JjTjK9ezsPf.lv4dXhm', 'VR', '2025-03-18 23:20:04', 'DEASA', '33 1743 3771', 'JORGE NAVARRO GOMEZ'),
(90, 'j.perez', '$2y$10$6.vPNVfV9Zro9fO5lcO3Que.f9qAbUulrcbyHeJddmQI4LstJIqmq', 'VR', '2025-03-18 23:20:29', 'DEASA', '0', 'JOSUE JESAEL PEREZ GONZALEZ'),
(91, 'juan.deasa', '$2y$10$4RTs.DA9ffRM7UenFq7DHuhsC5gVQqkliRfKZd.Bjf6B1A1hqZ2n.', 'VR', '2025-03-18 23:20:57', 'DEASA', '33 1049 9650 ', 'JUAN JOSE RODRIGUEZ DE LA ROSA'),
(92, 'k.cardenas', '$2y$10$ApX5PSYNfhNrM0Yemfyxz.Tl6wvBZtA2bgkZqeF8Oh.WcVydGbaP2', 'VR', '2025-03-18 23:21:23', 'DEASA', '33 11 38 6545', 'KARLA JOHANA CARDENAS ARROYO'),
(93, 'ma.betancourt', '$2y$10$hZqxAiA4OtGAs6fkxlQMiOT1ityXUEMk7UtkOJQypLiW7b6QDHnhy', 'VR', '2025-03-18 23:21:49', 'DEASA', '0', 'MANUEL ALEJANDRO BETANCOURT CASTILLO'),
(94, 'mel.deasa', '$2y$10$qae7mFgyL1OonL0YMjzxieIOOsdYTonmU5Xyo2h9NZnBkXk2CdZRW', 'VR', '2025-03-18 23:22:19', 'DEASA', '33 1007 5393', 'MELESIO BERNARDO PEREZ DURAN'),
(95, 'n.escanuela', '$2y$10$rF.C7xb0nncf9dAWXyK1Jubo/LNDC2Yne77.N7CjscRBjJ2o9nm0W', 'VR', '2025-03-18 23:22:57', 'DEASA', '33 1865 9656', 'NANCY JACQUELINNE ESCAÑUELA GAMEZ'),
(96, 'r.gonzalez', '$2y$10$BGOTH1m5q5HlSxyFBVm8We77vmIHYdw3fl8QBgPHnjuFWUmOVwZfG', 'VR', '2025-03-18 23:23:25', 'DEASA', '33 1862 8271', 'RAUL GONZALEZ BARBA'),
(97, 'ruth.deasa', '$2y$10$BQS5/OkpW161lJ3TbBu.pu/t/c65H8JSWNLD8tOqnk7n6YoYMpt2a', 'VR', '2025-03-18 23:23:54', 'DEASA', '33 1605 8819', 'RUTH SOLANA SOTO'),
(98, 'v.rodriguez', '$2y$10$hcKWwmg4GZ9FvdMBC3D8kumz8WPiOua9QVTuBkc7oGQlYz1XMvVTa', 'VR', '2025-03-18 23:24:46', 'DEASA', '33 1256 7027', 'VERONICA RODRIGUEZ PEREZ'),
(99, 'a.betancourt', '$2y$10$oZrs678ibqP29yP5a.puxOvLvdHO0M2vvHycjsN0oimPETSwVr6jq', 'JC', '2025-03-18 23:25:18', 'DEASA', '33 3201 3839', 'BETANCOURT AGREDANO JOSE ALFREDO'),
(100, 'A.ORNELAS', '$2y$10$egMA6uee3fFX7zZ6nLvU3.TVlEPbC663w4ayJoHiVSuaOEE1SSJ9G', 'VR', '2025-03-20 21:38:51', 'SEGSA', '33333333', 'ALFONSO ORNELAS PEREZ'),
(101, 'B.MEDINA', '$2y$10$M9Yhk40k69xtQYaF4jGzQOkRW7ck9/KmV3lMu8oAKo4rx9G9OS7S2', 'VR', '2025-03-20 21:43:27', 'SEGSA', '33333333', 'BENJAMIN JUNIOR ZUÑIGA MEDINA'),
(102, 'C.JIMENEZ', '$2y$10$KqQsNUXo1PozO4lJ3YUZUe5O.aihH13b2e5NxCqHlLvsuu5fo5uJ.', 'VR', '2025-03-20 21:49:46', 'SEGSA', '33333333', 'CLAUDIA BERENICE JIMENEZ RAMOS'),
(103, 'E.MORALES', '$2y$10$rAEpP1149kajVVxga9JJBO8oBXxz/EfayPNCHWTjpAOUODHFXEi8e', 'VR', '2025-03-20 21:56:36', 'SEGSA', '33333333', 'EVELYN AIME MORALES CRUZ'),
(104, 'M.SEVILLA', '$2y$10$e2VE3yd8yVwEv.TpmpuFN.9GfKNG8OCZahKFTRxYQ1TAFxzIEk54S', 'VR', '2025-03-20 22:00:55', 'SEGSA', '33333333', 'MARTHA ELBA LOPEZ SEVILLA'),
(105, 'T.SAUL', '$2y$10$cQ3A/Fr2s4rsx.TsTJ3of.jk4hyrspEvPVVzg1B82AIv79.TwZboi', 'VR', '2025-03-20 22:02:17', 'SEGSA', '33333333', 'SAUL TOLENTINO OLAGUES'),
(106, 'M.HUIZAR', '$2y$10$o9YHWC0XMVcw6SixM1BFG.gd54NQkk4hnHui0/eWeVPE8ogrqEb1W', 'VR', '2025-03-21 18:45:26', 'SEGSA', '3318657696', 'MIGUEL HUIZAR REYES'),
(107, 'E.DORADO', '$2y$10$Lkf9hGmUDAjm.yrTZspWp.JLzioL/8fW2eERABCghFFXC77pX4cmy', 'VR', '2025-03-24 15:26:01', 'FESA', '33333333', 'ERICK FABIAN AGUILERA DORADO'),
(108, 'J.OROZCO', '$2y$10$n9NVZiFjquIgkpOXqGdS4ukAbvs9V2CN5DohJPQCuVc.P7nRkKya.', 'VR', '2025-03-24 15:35:10', 'FESA', '33333333', 'JOSE LUIS HDEZ OROZCO'),
(109, 'M.LAZARIN', '$2y$10$qZtSo6XGTSj7lF0vumlbrOXv.sy9Z/Hjb3l74DnZvZNwLnBb1AXN.', 'VR', '2025-03-24 15:38:57', 'FESA', '33333333', 'MARIA DE LA LUZ LAZARIN'),
(110, 'M.ESQUIVEL', '$2y$10$UaSs37VBIrwBwyDweWcwbuc/R3h4z3E3zOtjzd4AjBGYZTEeN9BVC', 'VR', '2025-03-24 15:52:14', 'FESA', '33333333', 'MONICA MICHEL GARCIA ESQUIVEL'),
(111, 'R.SOTO', '$2y$10$vpXe2J6oifjraGcdoy8ghep5NGUqq0Gy2kUfBiU2A0Giy3j/3HE4q', 'VR', '2025-03-24 15:53:52', 'FESA', '33333333', 'ROBERTO ESTRADA SOTO'),
(114, 'J.NAJAR', '$2y$10$A.WzNv4AMIqcEFnINEi6c.L/Yg2MX44sS0Fa4eybPyih4dSZeyv8.', 'JC', '2025-03-24 19:01:34', 'FESA', '3334922082', 'NAJAR ESPINOZA JORGE ADRIAN'),
(115, 'Lupita Solis', '$2y$10$nLzSHdRgn6eWJSDSCpoJnu/9li4oeiPlXrnuD8i0b.Kdv/gXvzft.', 'VR', '2025-03-24 23:05:36', 'FESA', '3310168150', 'Maria Guadalupe Solis Garcia'),
(116, 'JC.DEASA', '$2y$10$pcnnG6i8ROhsip4PfVdgYe5/KprzVUogVHROf5NjWiWF/a42Nbj0.', 'JC', '2025-03-25 16:01:01', 'ILUMINACION', '0', 'JC.DEASA'),
(117, 'JESSICA ROMERO', '$2y$10$ibJa7lkl2S1SmgpthMNVB.Sk1paN.W2hAZLfAbt4Yp96iXMi8Wihm', 'VR', '2025-03-25 19:18:17', 'SEGSA', '3318504313', 'JESSICA ROMERO AYALA'),
(118, 'b.alfredo', '$2y$10$njkw3Ya/YTj2H7tWpa3u0eHJJgtS75FTJe/St7J6y6LVHPJbBzEwW', 'JC', '2025-03-26 00:52:50', 'DEASA', '3332013839', 'BETANCOURT AGREDANO JOSE ALFREDO'),
(119, 'hweg', '$2y$10$hsW6mv5Mw5Qbwkxr08BH1.b79DfbX4RZPDeVEVmB15AgH0DAfFVki', 'Admin', '2025-03-29 15:09:49', 'TODAS', '3331679990', 'julio leonel rubio ramirez'),
(120, 'h.morales', '$2y$10$JHNeBBGdcvh.rM.HgHXsaOY7Xvh7EveIX5/sTR8L5hqSoR3Dtu/Re', 'JC', '2025-03-29 15:50:33', 'TAPATIA', '3318639654', 'hector morales villa'),
(121, 'b.zuiga', '$2y$10$UNsbQngUvHzkzd38gJs7.enLM9wnRKOf.iymimqqKl2tKvIt04lX.', 'VR', '2025-04-08 23:41:18', 'SEGSA', '0', 'BENJAMIN ZUIGA'),
(122, 'CINTHIA PEREZ', '$2y$10$ohmIVLqkfTlnpwhUIn/l0.SZNhYXETnohYVh5jcyTIbII6JV0XSVa', 'VR', '2025-04-10 15:45:45', 'DEASA', '3338156731', 'CINTHIA PEREZ'),
(123, 'Fernanda Perez', '$2y$10$IgcnHiskpsU9sYZgsbcCuuY5IAkMQvfE.UsCEEoD5P8qgGEVbSspC', 'VR', '2025-04-10 19:36:50', 'DEASA', '3316047371', 'Fernanda Perez'),
(124, 'Etzael Gonzalez ', '$2y$10$dDbu9L58.xRBMkk0.9I95eSFHJbyNBZKnneox85Crf4PkOmlymu1e', 'JC', '2025-04-10 22:08:44', 'CODI', '3338140917', 'Etzael Gonzalez '),
(125, 'm.gomez', '$2y$10$PeEGzaO0HC.e7CSWPxikTeCxjSRSZFiVS9VfxYuYDaOAuIlwC7Uw.', 'VR', '2025-05-02 22:41:51', 'CODI', '0', 'ELBA MARIBEL GOMEZ RODRIGUEZ'),
(126, 'jm.garcia', '$2y$10$tc07L8zBm/nb7XOCmiU2w.g.mQfeDoYARxt4h0zL5b2irmpeQD/RS', 'VR', '2025-05-02 22:42:30', 'CODI', '33 1893 9778', 'JUAN MANUEL GARCIA ULLOA'),
(127, 'g.zamora', '$2y$10$L.LHsLP7U81u4V2F8S0ZzOvgREKwzkVLcWD8Jb4/Vn9KfUbrYnWxe', 'VR', '2025-05-02 22:43:46', 'CODI', '0', 'GERARDO ZAMORA'),
(128, 'carmencruz', '$2y$10$FHzfwZsdaQKmk28gkOi8A.cGQR9yVY6zUNrJYCcpOOMzpkn703rVy', 'VR', '2025-05-02 22:44:16', 'CODI', '0', 'MA DEL CARMEN CRUZ ISLAS'),
(129, 'o.moreno', '$2y$10$E11hBQE93CB9vlbzCJpnje7beQ5yBiViNxidIBhvjuRIwwNIzlSOO', 'VR', '2025-05-02 22:44:54', 'CODI', '33 2184 8324', 'OSCAR GPE MORENO QUIROZ'),
(130, 'Abel.Martinez', '$2y$10$hzSjPIQyVBeMEoaF72Xr4ua7Jb4FPi7GnvcAe4Xyv20rDoD8gml82', 'JC', '2025-05-02 22:52:27', 'CODI', '33 2835 8211', 'JOSE ABELARDO MARTINEZ FLORES'),
(131, 'abel.martin', '$2y$10$VPXHsnd4YVLW1RNKcfi/QufBR/Qxw59MAVKHbtl9/jCUcLdATops.', 'JC', '2025-05-15 22:24:03', 'CODI', '3328358211', 'jose abelardo martinez'),
(132, 'gerencia.codi', '$2y$10$n95AzpeIj5VwHbhpwYSau.bGpxeHPva6mYT1UP9T6Q3FmUx54GdmW', 'VR', '2025-05-15 22:48:42', 'CODI', '3322564770', 'Alejandra Morales'),
(133, 'claudia.martinez', '$2y$10$qYg1q4N4GCNd5ZILhpq/JeAx9lGixiXYK2kF/3JgRA5fu89rNfhsm', 'VR', '2025-05-16 19:10:34', 'AIESA', '0', 'CLAUDIA MARCELA ESCOBAR MARTINEZ'),
(134, 'edgar.porras', '$2y$10$d0k.1cN0Uf4Dtf2bCLa4f.JbJJZ3PgLNPkSxgd2Fv8mHXjj5RVuGq', 'VR', '2025-05-16 19:16:29', 'AIESA', '0', 'EDGAR OMAR CHAVEZ PORRAS'),
(135, 'jose.perez', '$2y$10$tDDHwxYt.cB.1S.H3VAns.f78.1.Hf//dvSuGRpo2oZBjXF8LjZDm', 'VR', '2025-05-16 19:20:15', 'AIESA', '0', 'JOSE LUIS VELAZQUEZ PEREZ'),
(136, 'martin.huerta', '$2y$10$utwK7uATOhTF5Fk32rTZGuIfUq9FlSl3y.VONAYEfnzJ4ExaUvfBG', 'VR', '2025-05-16 19:34:16', 'AIESA', '0', 'JOSE MARTIN HUERTA CARRILLO'),
(137, 'kelly.gibson', '$2y$10$xouLaHcT4yiazmEKnRzKy.9PNHWbCu2kiERe/UcLyjlfGRj.Vqq2K', 'VR', '2025-05-16 19:35:34', 'AIESA', '0', 'KELLY CAMACHO GIBSON'),
(138, 'omar.pulido', '$2y$10$xmSSJ/YGbbcw1sg9.Qbz0.Zx7qw9ct0mDYtqLFETbZw2xb5ywPU2C', 'VR', '2025-05-16 19:43:03', 'AIESA', '0', 'OMAR ALEJANDRO VALADEZ PULIDO'),
(139, 'alberto.tellez', '$2y$10$KCwn/pPFqW0NFCmS7FGnuO0Bo8kdgWFGYNH1y1qtbM6z7miKJ7ryS', 'JC', '2025-05-22 16:09:10', 'TAPATIA', '3313631168', 'alberto tellez'),
(140, 'JHON.FESA', '$2y$10$4GRX2QrNbO.V.UGxtiMaaOKh2RPFNdiEYoI2F8Sj8YvB3QFinTRem', 'JC', '2025-06-02 16:07:27', 'FESA', '3318911746', 'JHON.FESA'),
(141, 'Julio.Rubio', '$2y$10$vgaKcganAoeuqe8oW5z6/u8H1bsEgyNdpWy4tn8cmxS6JJCtkDomm', 'Admin', '2025-06-27 04:57:19', 'TODAS', '0', 'Julio Rubio'),
(142, 'fernandor', '$2y$10$T96p5BB2/v3tH0ER9S2msucEKFZyXaNSHVJp9UTaCTUTeOYVrkZ1y', 'VR', '2025-08-05 04:39:59', 'TAPATIA', '3318621361', 'Fernando reyes pompa'),
(143, 'hweg115', '$2y$10$mHhQPyXdHGRHaAjkTHutZuEZYbjmXDFkRIQBpC/lY6ma09J/kP4c.', 'JC', '2025-09-02 18:27:03', 'CODI', '3313376944', 'julio prueba qr'),
(144, 'hwegvendedor', '$2y$10$MdWqzOZUQ8E8NMTYcZhb/.GbKwk2HWseAmFTzqCXa1E3nQppAtvLm', 'VR', '2025-09-03 20:25:10', 'AIESA', '3331679990', 'hwegvendedor'),
(145, 'Demo.Pedidos', '$2y$10$rSrQ1YmS0HZoxPGd.aS/B.T9ymEfmf9GRtLjk5OSJTYx.O6f9OZJi', 'Admin', '2025-11-11 20:17:50', 'TODAS', '0', 'Demo.Pedidos');

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
-- Volcado de datos para la tabla `vehiculos`
--

INSERT INTO `vehiculos` (`id_vehiculo`, `id_chofer_asignado`, `numero_serie`, `placa`, `tipo`, `foto_path`, `Sucursal`, `razon_social`, `Km_de_Servicio`, `Km_Total`, `Km_Actual`, `Fecha_Ultimo_Servicio`, `es_particular`, `responsable`) VALUES
(1, NULL, '3N6DD25T8CK666666', 'JS66666', 'NISSAN ESTACAS NP300 DH STD', 'uploads/vehiculos/JS66666_eb23953f18d4.jpg', 'DEASA', 'DEASA', 5000, 160000, 0, NULL, 0, NULL),
(2, NULL, '3N6AD35A3HK865218', 'JM9214A', 'NISSAN NP300 CHASIS CAB', NULL, 'DEASA', 'DEASA', 4500, 205267, 0, NULL, 0, NULL),
(3, NULL, '3N6DD25T8BK040758', 'JS37510', 'CHASIS CABINA NP300 DH STD 2P', NULL, 'TAPATIA', 'TAPATIA', 4500, 300809, 0, NULL, 0, NULL),
(4, NULL, '3N6AD35A1NK822007', 'JX47882', 'CHASIS CABINA NP300 DH STD 2P', NULL, 'DIMEGSA', 'DIMEGSA', 4500, 65258, 0, NULL, 0, NULL),
(5, NULL, '3N6DD25T4BK016358', 'JM7506A', 'CHASIS CABINA NP300 DH STD 2P', NULL, 'DIMEGSA', 'DIMEGSA', 4500, 335250, 0, NULL, 0, NULL),
(6, NULL, '3N6DD25T3BK026041', 'JZ0591A', 'CHASIS CABINA NP300 DH STD 2P', NULL, 'DIMEGSA', 'DIMEGSA', 4500, 286492, 0, NULL, 0, NULL),
(7, NULL, '3N6AD35A0MK835054', 'JW95287', 'CHASIS CABINA NP300 DH STD 2P', NULL, 'DIMEGSA', 'DIMEGSA', 4500, 96162, 0, NULL, 0, NULL),
(8, NULL, '3N6CD15S74K129205', 'JV28557', 'CHASIS CABINA NP300 DH STD 2P', NULL, 'DIMEGSA', 'DIMEGSA', 4500, 380759, 0, NULL, 0, NULL),
(9, NULL, '3N6AD35A3MK824095', 'JW94928', 'CHASIS CABINA NP300 DH STD 2P', NULL, 'DEASA', 'DEASA', 4500, 105384, 0, NULL, 0, NULL),
(10, NULL, 'VWASHTF29D1150439', 'JM7505A', 'NISSAN CHASIS CAB CABSTAR EXTEND TM CA', NULL, 'DEASA', 'DEASA', 4500, 197051, 0, NULL, 0, NULL),
(11, NULL, '3N6AD35A0PK878104', 'JY48065', 'NISSAN CHASIS CAB CABSTAR EXTEND TM CA', NULL, 'DEASA', 'DEASA', 4500, 43408, 0, NULL, 0, NULL),
(12, NULL, '3N6DD14S28K033591', 'JU62130', 'NISSAN ESTACAS LARGA DH 1.5T L4 STD 2PTAS', NULL, 'FESA', 'FESA', 4500, 347472, 0, NULL, 0, NULL),
(13, NULL, '3N6DD14SX6K040222', 'JM7542A', 'NISSAN ESTACAS LARGA DH 1.5T L4 STD 2PTAS', NULL, 'FESA', 'FESA', 4500, 382890, 0, NULL, 0, NULL),
(14, NULL, '3N6DD14S26K039601', 'JM9212A', 'NISSAN ESTACAS LARGA DH 1.5T L4 STD 2PTAS', NULL, 'FESA', 'FESA', 4500, 340102, 0, NULL, 0, NULL),
(15, NULL, '3N6AD35A6MK810904', 'JW95044', 'CHASIS CABINA NP300 DH STD 2P', NULL, 'TAPATIA', 'TAPATIA', 4500, 96165, 0, NULL, 0, NULL),
(16, NULL, '3N6AD35C3GK868443', 'JU77613', 'CHASIS CABINA NP300 DH STD 2P', NULL, 'TAPATIA', 'TAPATIA', 4500, 129958, 0, NULL, 0, NULL),
(17, NULL, '3B6MC365XYM279368', 'JF30997', 'RAM 4000 CHASIS CABINA 3.5T V8 STD', NULL, 'TAPATIA', 'TAPATIA', 4500, 258272, 0, NULL, 0, NULL),
(18, NULL, '3N6DD14S48K033592', 'JP43907', 'NISSAN ESTACAS LARGA DH 1.5T L4 STD 2PTAS', NULL, 'DEASA', 'DEASA', 4500, 420409, 0, NULL, 0, NULL),
(19, NULL, '3N6DD25T8CK012332', 'JS1767A', 'CHASIS CABINA NP300 DH STD 2P', NULL, 'AIESA', 'AIESA', 4500, 262602, 0, NULL, 0, NULL),
(20, NULL, '3N6AD35A8KK827359', 'JV98839', 'NISSAN CHASIS CAB NP300 DH PAQ SEG STD', NULL, 'AIESA', 'AIESA', 4500, 193231, 0, NULL, 0, NULL),
(21, NULL, '3N6CD15S1YK050621', 'JM7527A', 'NISSAN ESTACAS LARGA DH 1.5T L4 STD 2PTAS', NULL, 'AIESA', 'AIESA', 4500, 315344, 0, NULL, 0, NULL),
(22, NULL, '3N6AD35AXMK828001', 'JW94929', 'CHASIS CABINA NP300 DH STD 2P', NULL, 'SEGSA', 'SEGSA', 4500, 83629, 0, NULL, 0, NULL),
(23, NULL, '3N6AD35C3JK815622', 'JM7528A', 'CHASIS CABINA NP300 DH STD 2P', NULL, 'SEGSA', 'SEGSA', 4500, 188436, 0, NULL, 0, NULL),
(24, NULL, '3N6DD25TXBK005316', 'JM9216A', 'CHASIS CABINA NP300 DH STD 2P', NULL, 'SEGSA', 'SEGSA', 4500, 220459, 0, NULL, 0, NULL),
(25, NULL, '3N6AD35A6RK845921', 'HW8618A', 'CHASIS CABINA NP300 DH STD 2P', NULL, 'CODI', 'SEGSA', 4500, 25457, 0, NULL, 0, NULL),
(26, NULL, '3N6DD14S96K044083', 'JN15349', 'NISSAN ESTACAS LARGA DH 1.5T L4 STD 2PTAS', NULL, 'CODI', 'TAPATIA', 4500, 320278, 0, NULL, 0, NULL),
(27, NULL, '3N6DD14S35K023499', 'JP40112', 'NISSAN ESTACAS LARGA DH 1.5T L4 STD 2PTAS', NULL, 'CODI', 'DEASA', 4500, 5382, 0, NULL, 0, NULL),
(28, NULL, '3N6AD35A8KK825272', 'JW15000', 'CHASIS CABINA NP300 DH STD 2P', NULL, 'VALLARTA', 'TAPATIA', 4500, 230048, 0, NULL, 0, NULL),
(29, NULL, '3N6AD35C3JK813398', 'JV56298', 'CHASIS CABINA NP300 DH STD 2P', NULL, 'DEASA', 'VALLARTA', 4500, 207116, 0, NULL, 0, NULL),
(30, NULL, '3N6AD35C5JK815654', 'JV50777', 'CHASIS CABINA NP300 DH STD 2P', NULL, 'GABSA', 'TAPATIA', 4500, 214502, 0, NULL, 0, NULL),
(31, NULL, '3N6AD35A5HK840384', 'KG5115A', 'CHASIS CABINA NP300 DH STD 2P', NULL, 'DIMEGSA', 'DEASA', 4500, 225378, 0, NULL, 0, NULL),
(32, NULL, '3N6DD25T2EK062792', 'GV5498C', 'NISSAN ESTACAS LARGA DH 1.5T L4 STD 2PTAS', NULL, 'GABSA', 'DEASA', 5000, 195807, 0, NULL, 0, NULL),
(33, NULL, '3N6AD35A9PK878232', 'JY48068', 'CHASIS CABINA NP300 DH STD 2P', NULL, 'QUERETARO', 'DIMEGSA', 5000, 49381, 0, NULL, 0, NULL),
(34, NULL, '3N6AD35A2PK878198', 'JY48067', 'CHASIS CABINA NP300 DH STD 2P', NULL, 'QUERETARO', 'AIESA', 4500, 52302, 0, NULL, 0, NULL);

--
-- Índices para tablas volcadas
--

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `inventario_movimiento`
--
ALTER TABLE `inventario_movimiento`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `orden_servicio`
--
ALTER TABLE `orden_servicio`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `orden_servicio_hist`
--
ALTER TABLE `orden_servicio_hist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `servicios_detalle`
--
ALTER TABLE `servicios_detalle`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=146;

--
-- AUTO_INCREMENT de la tabla `vehiculos`
--
ALTER TABLE `vehiculos`
  MODIFY `id_vehiculo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- Restricciones para tablas volcadas
--

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
