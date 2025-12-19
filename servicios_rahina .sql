-- phpMyAdmin SQL Dump
-- version 5.0.4
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 19-12-2025 a las 02:10:30
-- Versión del servidor: 10.4.16-MariaDB
-- Versión de PHP: 7.4.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `servicios_rahina`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `bancos`
--

CREATE TABLE `bancos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `numero_cuenta` varchar(50) NOT NULL,
  `tipo_cuenta` enum('CAJA_AHORRO','CUENTA_CORRIENTE') DEFAULT 'CAJA_AHORRO',
  `moneda` varchar(10) DEFAULT 'BOB',
  `saldo_inicial` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `banco_movimientos`
--

CREATE TABLE `banco_movimientos` (
  `id` int(11) NOT NULL,
  `id_banco` int(11) NOT NULL,
  `fecha` datetime NOT NULL,
  `tipo` enum('INGRESO','EGRESO') NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `concepto` varchar(255) NOT NULL,
  `id_venta` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `caja`
--

CREATE TABLE `caja` (
  `id` int(11) NOT NULL,
  `monto` varchar(9) DEFAULT NULL,
  `fecha` varchar(12) DEFAULT NULL,
  `hora` varchar(12) DEFAULT NULL,
  `estado` tinyint(1) DEFAULT 1,
  `habilitado` tinyint(1) DEFAULT 1,
  `tipo_caja` enum('CHICA','GENERAL') DEFAULT 'CHICA',
  `responsable` int(11) DEFAULT NULL,
  `observacion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `caja`
--

INSERT INTO `caja` (`id`, `monto`, `fecha`, `hora`, `estado`, `habilitado`, `tipo_caja`, `responsable`, `observacion`) VALUES
(1, '30115.51', '2025-11-18', '21:28:42', 0, 1, 'GENERAL', NULL, 'Cierre de caja general'),
(2, '2650', '2025-11-28', '04:39:48', 0, 1, 'GENERAL', 1, 'Cierre de caja general'),
(3, '1720', '2025-11-28', '04:42:04', 0, 1, 'GENERAL', 1, 'Cierre de caja general'),
(4, '4250', '2025-11-28', '05:23:55', 0, 1, 'GENERAL', 1, 'Cierre de caja general'),
(5, '20000', '2025-11-30', '20:55:42', 1, 1, 'GENERAL', 1, 'Apertura de caja general');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cajachica`
--

CREATE TABLE `cajachica` (
  `id` int(11) NOT NULL,
  `monto` varchar(9) DEFAULT NULL,
  `fecha` varchar(12) DEFAULT NULL,
  `hora` varchar(12) DEFAULT NULL,
  `tipo` tinyint(1) DEFAULT NULL,
  `responsable` int(11) DEFAULT NULL,
  `observacion` varchar(255) DEFAULT NULL,
  `habilitado` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `cajachica`
--

INSERT INTO `cajachica` (`id`, `monto`, `fecha`, `hora`, `tipo`, `responsable`, `observacion`, `habilitado`) VALUES
(1, '0.03', '2025-11-27', '04:05:55', 1, 1, 'Cierre de caja chica', 0),
(2, '1000', '2025-11-28', '04:39:58', 1, 1, 'Apertura de caja chica', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cajachicaregistros`
--

CREATE TABLE `cajachicaregistros` (
  `id` int(15) NOT NULL,
  `monto` varchar(9) DEFAULT NULL,
  `tipo` tinyint(1) DEFAULT NULL,
  `fecha` varchar(12) DEFAULT NULL,
  `hora` varchar(12) DEFAULT NULL,
  `Detalle` text DEFAULT NULL,
  `habilitado` tinyint(1) DEFAULT 1,
  `responsable` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cajamovimientos`
--

CREATE TABLE `cajamovimientos` (
  `id` int(11) NOT NULL,
  `id_caja` int(11) NOT NULL,
  `tipo_mov` enum('INGRESO','EGRESO','VENTA','AJUSTE','TRANSFERENCIA') NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `monto` decimal(12,2) NOT NULL,
  `metodo_pago` enum('EFECTIVO','TRANSFERENCIA','DEPOSITO','TARJETA') DEFAULT NULL,
  `id_banco` int(11) DEFAULT NULL,
  `usuario` int(11) DEFAULT NULL,
  `fecha` date DEFAULT NULL,
  `hora` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cajaregistros`
--

CREATE TABLE `cajaregistros` (
  `id` int(15) NOT NULL,
  `monto` varchar(9) DEFAULT NULL,
  `tipo` tinyint(1) DEFAULT NULL,
  `fecha` varchar(12) DEFAULT NULL,
  `hora` varchar(12) DEFAULT NULL,
  `detalle` varchar(75) DEFAULT NULL,
  `habilitado` tinyint(1) DEFAULT 1,
  `responsable` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `cajaregistros`
--

INSERT INTO `cajaregistros` (`id`, `monto`, `tipo`, `fecha`, `hora`, `detalle`, `habilitado`, `responsable`) VALUES
(1, '720', 3, '28-11-2025', '06:04:07 am', 'Venta servicios factura #35', 1, 1),
(2, '880', 3, '28-11-2025', '06:46:43 am', 'Venta servicios factura #36', 1, 1),
(3, '1650', 3, '28-11-2025', '06:50:21 am', 'Venta servicios factura #37', 1, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cajatmp`
--

CREATE TABLE `cajatmp` (
  `id` int(25) NOT NULL,
  `idfactura` int(25) DEFAULT NULL,
  `producto` int(2) DEFAULT NULL,
  `cantidad` int(5) DEFAULT 1,
  `precio` float DEFAULT NULL,
  `totalprecio` float DEFAULT NULL,
  `iva_porcentaje` decimal(5,2) NOT NULL DEFAULT 0.00,
  `impuesto_monto` decimal(10,2) NOT NULL DEFAULT 0.00,
  `comision_porcentaje` decimal(5,2) NOT NULL DEFAULT 0.00,
  `comision_monto` decimal(10,2) NOT NULL DEFAULT 0.00,
  `comision` float DEFAULT 0,
  `vendedor` int(9) DEFAULT NULL,
  `cliente` int(9) DEFAULT 1,
  `stockTmp` int(9) DEFAULT 0,
  `stock` int(9) DEFAULT NULL,
  `fecha` varchar(10) DEFAULT NULL,
  `hora` varchar(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `cajatmp`
--

INSERT INTO `cajatmp` (`id`, `idfactura`, `producto`, `cantidad`, `precio`, `totalprecio`, `iva_porcentaje`, `impuesto_monto`, `comision_porcentaje`, `comision_monto`, `comision`, `vendedor`, `cliente`, `stockTmp`, `stock`, `fecha`, `hora`) VALUES
(77, NULL, 102, 1, 250, 250, '0.00', '0.00', '0.00', '0.00', 0, 1, 12, 0, 0, '14-12-2025', '02:51:58 pm');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `caja_chica_movimientos`
--

CREATE TABLE `caja_chica_movimientos` (
  `id` int(11) NOT NULL,
  `fecha` varchar(10) NOT NULL,
  `hora` varchar(11) NOT NULL,
  `tipo` enum('INGRESO','EGRESO') NOT NULL,
  `monto` float NOT NULL,
  `concepto` varchar(255) NOT NULL,
  `responsable` int(9) NOT NULL,
  `saldo_resultante` float NOT NULL,
  `referencia` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `caja_chica_movimientos`
--

INSERT INTO `caja_chica_movimientos` (`id`, `fecha`, `hora`, `tipo`, `monto`, `concepto`, `responsable`, `saldo_resultante`, `referencia`) VALUES
(1, '2025-11-27', '04:05:55', 'INGRESO', 0.03, 'Apertura de caja chica', 1, 0.03, 'APERTURA'),
(2, '2025-11-28', '04:39:41', 'EGRESO', 0.03, 'Cierre de caja chica', 1, 0, 'CIERRE'),
(3, '2025-11-28', '04:39:58', 'INGRESO', 1000, 'Apertura de caja chica', 1, 1000, 'APERTURA');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `caja_general_movimientos`
--

CREATE TABLE `caja_general_movimientos` (
  `id` int(11) NOT NULL,
  `fecha` varchar(10) NOT NULL,
  `hora` varchar(11) NOT NULL,
  `tipo` enum('INGRESO','EGRESO') NOT NULL,
  `monto` float NOT NULL,
  `concepto` varchar(255) NOT NULL,
  `metodo_pago` enum('EFECTIVO','TRANSFERENCIA','DEPOSITO','TARJETA') NOT NULL,
  `id_banco` int(11) DEFAULT NULL,
  `referencia` varchar(100) DEFAULT NULL,
  `responsable` int(9) NOT NULL,
  `saldo_caja` float NOT NULL,
  `saldo_banco` float DEFAULT NULL,
  `id_asiento` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `caja_general_movimientos`
--

INSERT INTO `caja_general_movimientos` (`id`, `fecha`, `hora`, `tipo`, `monto`, `concepto`, `metodo_pago`, `id_banco`, `referencia`, `responsable`, `saldo_caja`, `saldo_banco`, `id_asiento`) VALUES
(1, '27-11-2025', '04:07:06 am', 'INGRESO', 878798, '68i', 'EFECTIVO', NULL, NULL, 1, 878798, NULL, NULL),
(2, '2025-11-27', '04:10:28', 'INGRESO', 4646, '09098', 'EFECTIVO', NULL, NULL, 1, 883444, NULL, NULL),
(3, '2025-11-28', '04:39:33', 'EGRESO', 1000, 'Cierre de caja general', 'EFECTIVO', NULL, 'CIERRE', 1, 0, NULL, NULL),
(4, '2025-11-28', '04:39:48', 'INGRESO', 1000, 'Apertura de caja general', 'EFECTIVO', NULL, 'APERTURA', 1, 1000, NULL, NULL),
(5, '2025-11-28', '04:41:54', 'EGRESO', 883444, 'Cierre de caja general', 'EFECTIVO', NULL, 'CIERRE', 1, 0, NULL, NULL),
(6, '2025-11-28', '04:42:04', 'INGRESO', 1000, 'Apertura de caja general', 'EFECTIVO', NULL, 'APERTURA', 1, 1000, NULL, NULL),
(7, '2025-11-28', '05:23:50', 'EGRESO', 1000, 'Cierre de caja general', 'EFECTIVO', NULL, 'CIERRE', 1, 0, NULL, NULL),
(8, '2025-11-28', '05:23:55', 'INGRESO', 1000, 'Apertura de caja general', 'EFECTIVO', NULL, 'APERTURA', 1, 1000, NULL, NULL),
(9, '28-11-2025', '05:41:06 am', 'INGRESO', 2530, 'Venta de servicios factura #32', 'EFECTIVO', NULL, 'VENTA-32', 1, 3530, NULL, NULL),
(10, '28-11-2025', '05:41:29 am', 'INGRESO', 1650, 'Venta de servicios factura #33', 'EFECTIVO', NULL, 'VENTA-33', 1, 5180, NULL, NULL),
(11, '28-11-2025', '05:47:18 am', 'INGRESO', 720, 'Venta de servicios factura #34', 'EFECTIVO', NULL, 'VENTA-34', 1, 5900, NULL, NULL),
(12, '28-11-2025', '06:04:07 am', 'INGRESO', 720, 'Venta servicios factura #35', 'EFECTIVO', NULL, NULL, 1, 6620, NULL, NULL),
(13, '28-11-2025', '06:46:43 am', 'INGRESO', 880, 'Venta servicios factura #36', 'EFECTIVO', NULL, NULL, 1, 7500, NULL, NULL),
(14, '28-11-2025', '06:50:21 am', 'INGRESO', 1650, 'Venta servicios factura #37', 'EFECTIVO', NULL, NULL, 1, 9150, NULL, NULL),
(15, '2025-11-28', '08:57:29', 'INGRESO', 1650, 'Venta de servicios - Factura #38', 'EFECTIVO', NULL, NULL, 1, 10800, NULL, NULL),
(16, '2025-11-29', '17:06:51', 'INGRESO', 19800, 'Venta de servicios - Factura #39', 'EFECTIVO', NULL, NULL, 1, 30600, NULL, NULL),
(17, '2025-11-29', '19:14:25', 'INGRESO', 880, 'Venta de servicios - Factura #40', 'EFECTIVO', NULL, NULL, 1, 31480, NULL, NULL),
(18, '2025-11-29', '22:39:33', 'INGRESO', 1650, 'Venta de servicios - Factura #41', 'EFECTIVO', NULL, NULL, 1, 33130, NULL, NULL),
(19, '2025-11-30', '01:01:18', 'INGRESO', 3300, 'Venta de servicios - Factura #42', 'EFECTIVO', NULL, NULL, 1, 36430, NULL, NULL),
(20, '2025-11-30', '01:09:22', 'INGRESO', 2530, 'Venta de servicios - Factura #43', 'EFECTIVO', NULL, NULL, 1, 38960, NULL, NULL),
(21, '2025-11-30', '02:21:33', 'INGRESO', 6730, 'Venta de servicios - Factura #44', 'EFECTIVO', NULL, NULL, 1, 45690, NULL, NULL),
(22, '2025-11-30', '03:59:05', 'INGRESO', 880, 'Venta de servicios - Factura #45', 'EFECTIVO', NULL, NULL, 1, 46570, NULL, NULL),
(23, '2025-11-30', '05:50:49', 'INGRESO', 570, 'Venta de servicios - Factura #46', 'EFECTIVO', NULL, NULL, 1, 47140, NULL, NULL),
(24, '2025-11-30', '17:44:32', 'INGRESO', 1650, 'Venta de servicios - Factura #47', 'EFECTIVO', NULL, NULL, 1, 48790, NULL, NULL),
(25, '2025-11-30', '18:20:08', 'INGRESO', 2530, 'Venta de servicios - Factura #48', 'EFECTIVO', NULL, NULL, 1, 51320, NULL, NULL),
(26, '2025-11-30', '20:38:59', 'INGRESO', 880, 'Venta de servicios - Factura #49', 'EFECTIVO', NULL, NULL, 1, 52200, NULL, NULL),
(27, '2025-11-30', '20:55:19', 'INGRESO', 1650, 'Venta de servicios - Factura #50', 'EFECTIVO', NULL, NULL, 1, 53850, NULL, NULL),
(28, '2025-11-30', '20:55:36', 'EGRESO', 53850, 'Cierre de caja general', 'EFECTIVO', NULL, 'CIERRE', 1, 0, NULL, NULL),
(29, '2025-11-30', '20:55:42', 'INGRESO', 20000, 'Apertura de caja general', 'EFECTIVO', NULL, 'APERTURA', 1, 20000, NULL, NULL),
(30, '2025-11-30', '21:51:12', 'INGRESO', 3650, 'Venta de servicios - Factura #51', 'EFECTIVO', NULL, NULL, 1, 23650, NULL, NULL),
(31, '2025-12-02', '18:02:44', 'INGRESO', 1200, 'Venta de servicios - Factura #52', 'EFECTIVO', NULL, NULL, 1, 24850, NULL, NULL),
(32, '2025-12-02', '19:15:36', 'INGRESO', 250, 'Venta de servicios - Factura #53', 'EFECTIVO', NULL, NULL, 1, 25100, NULL, NULL),
(33, '2025-12-03', '04:30:03', 'INGRESO', 250, 'Venta de servicios - Factura #54', 'EFECTIVO', NULL, NULL, 1, 25350, NULL, NULL),
(34, '2025-12-03', '16:29:06', 'INGRESO', 500, 'Venta de servicios - Factura #55', 'EFECTIVO', NULL, NULL, 1, 25850, NULL, NULL),
(35, '2025-12-05', '17:22:24', 'INGRESO', 1200, 'Venta de servicios - Factura #56', 'EFECTIVO', NULL, NULL, 1, 27050, NULL, NULL),
(36, '2025-12-05', '20:43:36', 'INGRESO', 1000, 'Venta de servicios - Factura #57', 'EFECTIVO', NULL, NULL, 1, 28050, NULL, NULL),
(37, '2025-12-05', '20:44:04', 'INGRESO', 250, 'Venta de servicios - Factura #58', 'EFECTIVO', NULL, NULL, 1, 28300, NULL, NULL),
(38, '2025-12-05', '20:46:27', 'INGRESO', 2200, 'Venta de servicios - Factura #59', 'EFECTIVO', NULL, NULL, 1, 30500, NULL, NULL),
(39, '2025-12-05', '23:16:17', 'INGRESO', 250, 'Venta de servicios - Factura #60', 'EFECTIVO', NULL, NULL, 1, 30750, NULL, NULL),
(40, '2025-12-09', '20:44:57', 'INGRESO', 250, 'Venta de servicios - Factura #61', 'EFECTIVO', NULL, NULL, 1, 31000, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `canton`
--

CREATE TABLE `canton` (
  `id` smallint(5) UNSIGNED NOT NULL,
  `id_provincia` smallint(5) UNSIGNED NOT NULL,
  `canton` varchar(45) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `canton`
--

INSERT INTO `canton` (`id`, `id_provincia`, `canton`) VALUES
(101, 1, 'San José'),
(102, 1, 'Escazú'),
(103, 1, 'Desamparados'),
(104, 1, 'Puriscal'),
(105, 1, 'Tarrazú'),
(106, 1, 'Aserrí'),
(107, 1, 'Mora'),
(108, 1, 'Goicoechea'),
(109, 1, 'Santa Ana'),
(110, 1, 'Alajuelita'),
(111, 1, 'Vasquez de Coronado'),
(112, 1, 'Acosta'),
(113, 1, 'Tibás'),
(114, 1, 'Moravia'),
(115, 1, 'Montes de Oca'),
(116, 1, 'Turrubares'),
(117, 1, 'Dota'),
(118, 1, 'Curridabat'),
(119, 1, 'Pérez Zeledón'),
(120, 1, 'León Cortés'),
(201, 2, 'Alajuela'),
(202, 2, 'San Ramón'),
(203, 2, 'Grecia'),
(204, 2, 'San Mateo'),
(205, 2, 'Atenas'),
(206, 2, 'Naranjo'),
(207, 2, 'Palmares'),
(208, 2, 'Poás'),
(209, 2, 'Orotina'),
(210, 2, 'San Carlos'),
(211, 2, 'Alfaro Ruiz'),
(212, 2, 'Valverde Vega'),
(213, 2, 'Upala'),
(214, 2, 'Los Chiles'),
(215, 2, 'Guatuso'),
(301, 3, 'Cartago'),
(302, 3, 'Paraíso'),
(303, 3, 'La Unión'),
(304, 3, 'Jiménez'),
(305, 3, 'Turrialba'),
(306, 3, 'Alvarado'),
(307, 3, 'Oreamuno'),
(308, 3, 'El Guarco'),
(401, 4, 'Heredia'),
(402, 4, 'Barva'),
(403, 4, 'Santo Domingo'),
(404, 4, 'Santa Bárbara'),
(405, 4, 'San Rafael'),
(406, 4, 'San Isidro'),
(407, 4, 'Belén'),
(408, 4, 'Flores'),
(409, 4, 'San Pablo'),
(410, 4, 'Sarapiquí '),
(501, 5, 'Liberia'),
(502, 5, 'Nicoya'),
(503, 5, 'Santa Cruz'),
(504, 5, 'Bagaces'),
(505, 5, 'Carrillo'),
(506, 5, 'Cañas'),
(507, 5, 'Abangares'),
(508, 5, 'Tilarán'),
(509, 5, 'Nandayure'),
(510, 5, 'La Cruz'),
(511, 5, 'Hojancha'),
(601, 6, 'Puntarenas'),
(602, 6, 'Esparza'),
(603, 6, 'Buenos Aires'),
(604, 6, 'Montes de Oro'),
(605, 6, 'Osa'),
(606, 6, 'Aguirre'),
(607, 6, 'Golfito'),
(608, 6, 'Coto Brus'),
(609, 6, 'Parrita'),
(610, 6, 'Corredores'),
(611, 6, 'Garabito'),
(701, 7, 'Limón'),
(702, 7, 'Pococí'),
(703, 7, 'Siquirres '),
(704, 7, 'Talamanca'),
(705, 7, 'Matina'),
(706, 7, 'Guácimo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `carrito_cotizacion`
--

CREATE TABLE `carrito_cotizacion` (
  `id` int(11) NOT NULL,
  `id_servicio` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `precio` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `vendedor` int(11) NOT NULL,
  `cliente` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `hora` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias_servicios`
--

CREATE TABLE `categorias_servicios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `categorias_servicios`
--

INSERT INTO `categorias_servicios` (`id`, `nombre`, `descripcion`, `fecha_creacion`) VALUES
(1, 'Pasajes', NULL, '2025-11-30 21:12:29'),
(2, 'Paquetes Turísticos', NULL, '2025-11-30 21:12:29'),
(3, 'Seguros', NULL, '2025-11-30 21:12:29'),
(4, 'Trámites Migratorios', NULL, '2025-11-30 21:12:29');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cierre`
--

CREATE TABLE `cierre` (
  `id` int(25) NOT NULL,
  `numero` int(2) DEFAULT NULL,
  `valor` int(5) DEFAULT NULL,
  `tipo` varchar(35) DEFAULT NULL,
  `fecha` varchar(25) DEFAULT NULL,
  `hora` varchar(25) DEFAULT NULL,
  `vendedor` varchar(35) DEFAULT NULL,
  `cliente` varchar(35) DEFAULT NULL,
  `habilitado` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cliente`
--

CREATE TABLE `cliente` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) DEFAULT NULL,
  `ci_pasaporte` varchar(30) DEFAULT NULL,
  `tipo_documento` enum('CI','PASAPORTE','OTRO') DEFAULT 'CI',
  `nacionalidad` varchar(50) DEFAULT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(80) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `descuento` varchar(4) DEFAULT '0',
  `habilitado` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `cliente`
--

INSERT INTO `cliente` (`id`, `nombre`, `ci_pasaporte`, `tipo_documento`, `nacionalidad`, `fecha_nacimiento`, `telefono`, `email`, `direccion`, `descuento`, `habilitado`) VALUES
(10, 'Juan Pérez', NULL, '', NULL, NULL, '70000001', 'juan@example.com', NULL, '0', 0),
(11, 'María Torres', NULL, '', NULL, NULL, '70000002', 'maria@example.com', NULL, '0', 1),
(12, 'Maria', '23424', 'PASAPORTE', 'Brasileña ', '2025-12-20', '74184096', 'softw.corport@gmail.com', 'calle tumpa , Santa Cruz de la Sierra, Bolivia', '0', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comisiones_vendedores`
--

CREATE TABLE `comisiones_vendedores` (
  `id` int(11) NOT NULL,
  `id_vendedor` int(11) NOT NULL,
  `idfactura` int(11) NOT NULL,
  `comision_monto` decimal(10,2) NOT NULL DEFAULT 0.00,
  `fecha` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comision_detalle`
--

CREATE TABLE `comision_detalle` (
  `id` int(11) NOT NULL,
  `id_venta` int(11) NOT NULL,
  `id_vendedor` int(11) NOT NULL,
  `comision` decimal(12,2) NOT NULL DEFAULT 0.00,
  `fecha` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comprobante`
--

CREATE TABLE `comprobante` (
  `id` int(11) NOT NULL,
  `id_venta` int(11) NOT NULL,
  `tipo` enum('COMPROBANTE','FACTURA') NOT NULL DEFAULT 'COMPROBANTE',
  `nro_comprobante` varchar(30) NOT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `monto_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `usuario` int(11) NOT NULL,
  `pdf_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `contabilidad_cuentas`
--

CREATE TABLE `contabilidad_cuentas` (
  `id` int(11) NOT NULL,
  `codigo` varchar(20) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `tipo` enum('ACTIVO','PASIVO','PATRIMONIO','INGRESO','GASTO') NOT NULL,
  `nivel` int(11) DEFAULT 1,
  `padre_id` int(11) DEFAULT NULL,
  `habilitado` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `contabilidad_diario`
--

CREATE TABLE `contabilidad_diario` (
  `id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `descripcion` varchar(255) NOT NULL,
  `referencia` varchar(100) DEFAULT NULL,
  `creado_por` int(11) NOT NULL,
  `creado_en` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `contabilidad_diario_detalle`
--

CREATE TABLE `contabilidad_diario_detalle` (
  `id` int(11) NOT NULL,
  `id_diario` int(11) NOT NULL,
  `id_cuenta` int(11) NOT NULL,
  `debe` decimal(12,2) DEFAULT 0.00,
  `haber` decimal(12,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `contabilidad_enlace_pago`
--

CREATE TABLE `contabilidad_enlace_pago` (
  `id` int(11) NOT NULL,
  `id_pago` int(11) NOT NULL,
  `id_asiento` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `contabilidad_enlace_venta`
--

CREATE TABLE `contabilidad_enlace_venta` (
  `id` int(11) NOT NULL,
  `id_venta` int(11) NOT NULL,
  `id_asiento` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cotizacion`
--

CREATE TABLE `cotizacion` (
  `id` int(11) NOT NULL,
  `codigo` varchar(50) NOT NULL,
  `id_cliente` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `hora` time NOT NULL,
  `validez_dias` int(11) NOT NULL DEFAULT 7,
  `fecha_vencimiento` date DEFAULT NULL,
  `subtotal` decimal(10,2) DEFAULT 0.00,
  `descuento` decimal(10,2) DEFAULT 0.00,
  `iva` decimal(10,2) DEFAULT 0.00,
  `it` decimal(10,2) DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `moneda` varchar(10) NOT NULL DEFAULT 'BOB',
  `tipo_cambio` decimal(10,2) DEFAULT 1.00,
  `estado` enum('PENDIENTE','ACEPTADA','RECHAZADA','VENCIDA') NOT NULL DEFAULT 'PENDIENTE',
  `observacion` text DEFAULT NULL,
  `probabilidad` int(3) DEFAULT 0,
  `etapa` enum('NUEVO','CONTACTO','PROPUESTA ENVIADA','EN NEGOCIACIÓN','CASI CERRADO','GANADO','PERDIDO') NOT NULL DEFAULT 'NUEVO',
  `fecha_envio` datetime DEFAULT NULL,
  `fecha_seguimiento` datetime DEFAULT NULL,
  `enviado_por` varchar(100) DEFAULT NULL,
  `usuario` int(11) NOT NULL,
  `fecha_aceptada` datetime DEFAULT NULL,
  `fecha_rechazada` datetime DEFAULT NULL,
  `convertida_venta` tinyint(1) NOT NULL DEFAULT 0,
  `id_factura` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `cotizacion`
--

INSERT INTO `cotizacion` (`id`, `codigo`, `id_cliente`, `fecha`, `hora`, `validez_dias`, `fecha_vencimiento`, `subtotal`, `descuento`, `iva`, `it`, `total`, `moneda`, `tipo_cambio`, `estado`, `observacion`, `probabilidad`, `etapa`, `fecha_envio`, `fecha_seguimiento`, `enviado_por`, `usuario`, `fecha_aceptada`, `fecha_rechazada`, `convertida_venta`, `id_factura`) VALUES
(100, 'COT-100', 100, '2025-12-02', '00:24:40', 7, NULL, '850.00', '0.00', '0.00', '0.00', '850.00', 'BOB', '1.00', 'PENDIENTE', 'Pasaje SCZ-BA', 10, 'NUEVO', NULL, NULL, NULL, 1, NULL, NULL, 0, NULL),
(101, 'COT-101', 101, '2025-12-02', '00:24:40', 7, NULL, '250.00', '0.00', '0.00', '0.00', '250.00', 'BOB', '1.00', 'PENDIENTE', 'Seguro internacional', 15, 'CONTACTO', NULL, NULL, NULL, 1, NULL, NULL, 0, NULL),
(102, 'COT-102', 100, '2025-12-02', '00:24:40', 7, NULL, '1200.00', '0.00', '0.00', '0.00', '1200.00', 'BOB', '1.00', 'PENDIENTE', 'Paquete Cusco 4 días', 20, '', NULL, NULL, NULL, 1, NULL, NULL, 1, NULL),
(103, 'COT-103', 102, '2025-12-02', '00:24:40', 7, NULL, '500.00', '0.00', '0.00', '0.00', '500.00', 'BOB', '1.00', 'PENDIENTE', 'Visa Americana', 40, 'EN NEGOCIACIÓN', NULL, NULL, NULL, 1, NULL, NULL, 0, NULL),
(104, 'COT-104', 101, '2025-12-02', '00:24:40', 7, NULL, '1200.00', '0.00', '0.00', '0.00', '1200.00', 'BOB', '1.00', 'PENDIENTE', 'Paquete Cusco', 60, 'CASI CERRADO', NULL, NULL, NULL, 1, NULL, NULL, 0, NULL),
(105, 'COT-105', 100, '2025-12-02', '00:24:40', 7, NULL, '850.00', '0.00', '0.00', '0.00', '850.00', 'BOB', '1.00', 'ACEPTADA', 'Venta cerrada', 100, 'GANADO', NULL, NULL, NULL, 1, NULL, NULL, 0, NULL),
(106, 'COT-106', 102, '2025-12-02', '00:24:40', 7, NULL, '250.00', '0.00', '0.00', '0.00', '250.00', 'BOB', '1.00', 'RECHAZADA', 'Cliente no interesado', 0, 'PERDIDO', NULL, NULL, NULL, 1, NULL, NULL, 0, NULL),
(107, 'COT-20251202054027', 11, '2025-12-02', '05:40:27', 7, '2025-12-09', '250.00', '0.00', '0.00', '0.00', '250.00', 'BOB', '1.00', 'PENDIENTE', 'dasd', 0, 'NUEVO', NULL, NULL, NULL, 1, NULL, NULL, 0, NULL),
(108, 'COT-20251202055420', 10, '2025-12-02', '05:54:20', 7, '2025-12-09', '250.00', '0.00', '0.00', '0.00', '250.00', 'BOB', '1.00', 'PENDIENTE', 'dasd', 0, '', NULL, NULL, NULL, 1, NULL, NULL, 0, NULL),
(109, 'COT-20251203032217', 10, '2025-12-03', '03:22:17', 7, '2025-12-10', '850.00', '0.00', '0.00', '0.00', '850.00', 'USD', '1.00', 'PENDIENTE', 'dada', 0, 'NUEVO', NULL, NULL, NULL, 1, NULL, NULL, 0, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cotizacion_detalle`
--

CREATE TABLE `cotizacion_detalle` (
  `id` int(11) NOT NULL,
  `id_cotizacion` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `descripcion` varchar(255) NOT NULL,
  `cantidad` float NOT NULL DEFAULT 1,
  `precio` decimal(12,2) NOT NULL DEFAULT 0.00,
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `comision` decimal(12,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `cotizacion_detalle`
--

INSERT INTO `cotizacion_detalle` (`id`, `id_cotizacion`, `id_producto`, `descripcion`, `cantidad`, `precio`, `subtotal`, `comision`) VALUES
(100, 100, 100, 'Pasaje', 1, '850.00', '850.00', '0.00'),
(101, 101, 102, 'Seguro viaje', 1, '250.00', '250.00', '0.00'),
(102, 102, 101, 'Paquete Cusco', 1, '1200.00', '1200.00', '0.00'),
(103, 103, 103, 'Visa USA', 1, '500.00', '500.00', '0.00'),
(104, 104, 101, 'Paquete Cusco', 1, '1200.00', '1200.00', '0.00'),
(105, 105, 100, 'Pasaje', 1, '850.00', '850.00', '0.00'),
(106, 106, 102, 'Seguro', 1, '250.00', '250.00', '0.00'),
(107, 107, 102, 'Seguro de viaje 15 días', 1, '250.00', '250.00', '0.00'),
(108, 108, 102, 'Seguro de viaje 15 días', 1, '250.00', '250.00', '0.00'),
(109, 109, 100, 'Pasaje Santa Cruz - Buenos Aires', 1, '850.00', '850.00', '0.00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `credito`
--

CREATE TABLE `credito` (
  `id` int(25) NOT NULL,
  `id_cliente` int(25) DEFAULT NULL,
  `deuda` int(25) DEFAULT NULL,
  `deudaNeta` int(25) DEFAULT NULL,
  `saldo` int(25) DEFAULT NULL,
  `fecha` varchar(25) DEFAULT NULL,
  `interes` int(5) DEFAULT NULL,
  `cuota` varchar(25) DEFAULT NULL,
  `habilitado` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `departamento`
--

CREATE TABLE `departamento` (
  `id` int(9) NOT NULL,
  `nombre` varchar(80) DEFAULT NULL,
  `habilitada` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `departamento`
--

INSERT INTO `departamento` (`id`, `nombre`, `habilitada`) VALUES
(1, 'Pasajes Aéreos', 1),
(2, 'Trámites Migratorios', 1),
(3, 'Visas', 1),
(4, 'Seguros de Viaje', 1),
(5, 'Paquetes Turísticos', 1),
(6, 'Reservas de Hotel', 1),
(7, 'Consultas Consulares', 1),
(8, 'Servicios Administrativos', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `distrito`
--

CREATE TABLE `distrito` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_canton` smallint(5) UNSIGNED NOT NULL,
  `distrito` varchar(45) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `distrito`
--

INSERT INTO `distrito` (`id`, `id_canton`, `distrito`) VALUES
(10101, 101, 'Carmen'),
(10102, 101, 'Merced'),
(10103, 101, 'Hospital'),
(10104, 101, 'Catedral'),
(10105, 101, 'Zapote'),
(10106, 101, 'San Francisco de Dos Ríos'),
(10107, 101, 'Uruca'),
(10108, 101, 'Mata Redonda'),
(10109, 101, 'Pavas'),
(10110, 101, 'Hatillo'),
(10111, 101, 'San Sebastián'),
(10201, 102, 'Escazú'),
(10202, 102, 'San Antonio'),
(10203, 102, 'San Rafael'),
(10301, 103, 'Desamparados'),
(10302, 103, 'San Miguel'),
(10303, 103, 'San Juan de Dios'),
(10304, 103, 'San Rafael Arriba'),
(10305, 103, 'San Antonio'),
(10306, 103, 'Frailes'),
(10307, 103, 'Patarrá'),
(10308, 103, 'San Cristóbal'),
(10309, 103, 'Rosario'),
(10310, 103, 'Damas'),
(10311, 103, 'San Rafael Abajo'),
(10312, 103, 'Gravilias'),
(10313, 103, 'Los Guido'),
(10401, 104, 'Santiago'),
(10402, 104, 'Mercedes Sur'),
(10403, 104, 'Barbacoas'),
(10404, 104, 'Grifo Alto'),
(10405, 104, 'San Rafael'),
(10406, 104, 'Candelaria'),
(10407, 104, 'Desamparaditos'),
(10408, 104, 'San Antonio'),
(10409, 104, 'Chires'),
(10501, 105, 'San Marcos'),
(10502, 105, 'San Lorenzo'),
(10503, 105, 'San Carlos'),
(10601, 106, 'Aserrí'),
(10602, 106, 'Tarbaca o Praga'),
(10603, 106, 'Vuelta de Jorco'),
(10604, 106, 'San Gabriel'),
(10605, 106, 'La Legua'),
(10606, 106, 'Monterrey'),
(10607, 106, 'Salitrillos'),
(10701, 107, 'Colón'),
(10702, 107, 'Guayabo'),
(10703, 107, 'Tabarcia'),
(10704, 107, 'Piedras Negras'),
(10705, 107, 'Picagres'),
(10801, 108, 'Guadalupe'),
(10802, 108, 'San Francisco'),
(10803, 108, 'Calle Blancos'),
(10804, 108, 'Mata de Plátano'),
(10805, 108, 'Ipís'),
(10806, 108, 'Rancho Redondo'),
(10807, 108, 'Purral'),
(10901, 109, 'Santa Ana'),
(10902, 109, 'Salitral'),
(10903, 109, 'Pozos o Concepción'),
(10904, 109, 'Uruca o San Joaquín'),
(10905, 109, 'Piedades'),
(10906, 109, 'Brasil'),
(11001, 110, 'Alajuelita'),
(11002, 110, 'San Josecito'),
(11003, 110, 'San Antonio'),
(11004, 110, 'Concepción'),
(11005, 110, 'San Felipe'),
(11101, 111, 'San Isidro'),
(11102, 111, 'San Rafael'),
(11103, 111, 'Dulce Nombre de Jesús'),
(11104, 111, 'Patalillo'),
(11105, 111, 'Cascajal'),
(11201, 112, 'San Ignacio'),
(11202, 112, 'Guaitil'),
(11203, 112, 'Palmichal'),
(11204, 112, 'Cangrejal'),
(11205, 112, 'Sabanillas'),
(11301, 113, 'San Juan'),
(11302, 113, 'Cinco Esquinas'),
(11303, 113, 'Anselmo Llorente'),
(11304, 113, 'León XIII'),
(11305, 113, 'Colima'),
(11401, 114, 'San Vicente'),
(11402, 114, 'San Jerónimo'),
(11403, 114, 'Trinidad'),
(11501, 115, 'San Pedro'),
(11502, 115, 'Sabanilla'),
(11503, 115, 'Mercedes o Betania'),
(11504, 115, 'San Rafael'),
(11601, 116, 'San Pablo'),
(11602, 116, 'San Pedro'),
(11603, 116, 'San Juan de Mata'),
(11604, 116, 'San Luis'),
(11605, 116, 'Cárara'),
(11701, 117, 'Santa María'),
(11702, 117, 'Jardín'),
(11703, 117, 'Copey'),
(11801, 118, 'Curridabat'),
(11802, 118, 'Granadilla'),
(11803, 118, 'Sánchez'),
(11804, 118, 'Tirrases'),
(11901, 119, 'San Isidro de el General'),
(11902, 119, 'General'),
(11903, 119, 'Daniel Flores'),
(11904, 119, 'Rivas'),
(11905, 119, 'San Pedro'),
(11906, 119, 'Platanares'),
(11907, 119, 'Pejibaye'),
(11908, 119, 'Cajón'),
(11909, 119, 'Barú'),
(11910, 119, 'Río Nuevo'),
(11911, 119, 'Páramo'),
(12001, 120, 'San Pablo'),
(12002, 120, 'San Andrés'),
(12003, 120, 'Llano Bonito'),
(12004, 120, 'San Isidro'),
(12005, 120, 'Santa Cruz'),
(12006, 120, 'San Antonio'),
(20101, 201, 'Alajuela'),
(20102, 201, 'San José'),
(20103, 201, 'Carrizal'),
(20104, 201, 'San Antonio'),
(20105, 201, 'Guácima'),
(20106, 201, 'San Isidro'),
(20107, 201, 'Sabanilla'),
(20108, 201, 'San Rafael'),
(20109, 201, 'Río Segundo'),
(20110, 201, 'Desamparados'),
(20111, 201, 'Turrucares'),
(20112, 201, 'Tambor'),
(20113, 201, 'La Garita'),
(20114, 201, 'Sarapiquí'),
(20201, 202, 'San Ramón'),
(20202, 202, 'Santiago'),
(20203, 202, 'San Juan'),
(20204, 202, 'Piedades Norte'),
(20205, 202, 'Piedades Sur'),
(20206, 202, 'San Rafael'),
(20207, 202, 'San Isidro'),
(20208, 202, 'Angeles'),
(20209, 202, 'Alfaro'),
(20210, 202, 'Volio'),
(20211, 202, 'Concepción'),
(20212, 202, 'Zapotal'),
(20213, 202, 'San Isidro de Peñas Blancas'),
(20301, 203, 'Grecia'),
(20302, 203, 'San Isidro'),
(20303, 203, 'San José'),
(20304, 203, 'San Roque'),
(20305, 203, 'Tacares'),
(20306, 203, 'Río Cuarto'),
(20307, 203, 'Puente Piedra'),
(20308, 203, 'Bolívar'),
(20401, 204, 'San Mateo'),
(20402, 204, 'Desmonte'),
(20403, 204, 'Jesús María'),
(20501, 205, 'Atenas'),
(20502, 205, 'Jesús'),
(20503, 205, 'Mercedes'),
(20504, 205, 'San Isidro'),
(20505, 205, 'Concepción'),
(20506, 205, 'San José'),
(20507, 205, 'Santa Eulalia'),
(20508, 205, 'Escobal'),
(20601, 206, 'Naranjo'),
(20602, 206, 'San Miguel'),
(20603, 206, 'San José'),
(20604, 206, 'Cirrí Sur'),
(20605, 206, 'San Jerónimo'),
(20606, 206, 'San Juan'),
(20607, 206, 'Rosario'),
(20701, 207, 'Palmares'),
(20702, 207, 'Zaragoza'),
(20703, 207, 'Buenos Aires'),
(20704, 207, 'Santiago'),
(20705, 207, 'Candelaria'),
(20706, 207, 'Esquipulas'),
(20707, 207, 'La Granja'),
(20801, 208, 'San Pedro'),
(20802, 208, 'San Juan'),
(20803, 208, 'San Rafael'),
(20804, 208, 'Carrillos'),
(20805, 208, 'Sabana Redonda'),
(20901, 209, 'Orotina'),
(20902, 209, 'Mastate'),
(20903, 209, 'Hacienda Vieja'),
(20904, 209, 'Coyolar'),
(20905, 209, 'Ceiba'),
(21001, 210, 'Quesada'),
(21002, 210, 'Florencia'),
(21003, 210, 'Buenavista'),
(21004, 210, 'Aguas Zarcas'),
(21005, 210, 'Venecia'),
(21006, 210, 'Pital'),
(21007, 210, 'Fortuna'),
(21008, 210, 'Tigra'),
(21009, 210, 'Palmera'),
(21010, 210, 'Venado'),
(21011, 210, 'Cutris'),
(21012, 210, 'Monterrey'),
(21013, 210, 'Pocosol'),
(21101, 211, 'Zarcero'),
(21102, 211, 'Laguna'),
(21103, 211, 'Tapezco'),
(21104, 211, 'Guadalupe'),
(21105, 211, 'Palmira'),
(21106, 211, 'Zapote'),
(21107, 211, 'Las Brisas'),
(21201, 212, 'Sarchí Norte'),
(21202, 212, 'Sarchí Sur'),
(21203, 212, 'Toro Amarillo'),
(21204, 212, 'San Pedro'),
(21205, 212, 'Rodríguez'),
(21301, 213, 'Upala'),
(21302, 213, 'Aguas Claras'),
(21303, 213, 'San José o Pizote'),
(21304, 213, 'Bijagua'),
(21305, 213, 'Delicias'),
(21306, 213, 'Dos Ríos'),
(21307, 213, 'Yolillal'),
(21401, 214, 'Los Chiles'),
(21402, 214, 'Caño Negro'),
(21403, 214, 'Amparo'),
(21404, 214, 'San Jorge'),
(21501, 215, 'San Rafael'),
(21502, 215, 'Buenavista'),
(21503, 215, 'Cote'),
(30101, 301, 'Oriental'),
(30102, 301, 'Occidental'),
(30103, 301, 'Carmen'),
(30104, 301, 'San Nicolás'),
(30105, 301, 'Aguacaliente o San Francisco'),
(30106, 301, 'Guadalupe o Arenilla'),
(30107, 301, 'Corralillo'),
(30108, 301, 'Tierra Blanca'),
(30109, 301, 'Dulce Nombre'),
(30110, 301, 'Llano Grande'),
(30111, 301, 'Quebradilla'),
(30201, 302, 'Paraíso'),
(30202, 302, 'Santiago'),
(30203, 302, 'Orosi'),
(30204, 302, 'Cachí'),
(30205, 302, 'Los Llanos de Santa Lucía'),
(30301, 303, 'Tres Ríos'),
(30302, 303, 'San Diego'),
(30303, 303, 'San Juan'),
(30304, 303, 'San Rafael'),
(30305, 303, 'Concepción'),
(30306, 303, 'Dulce Nombre'),
(30307, 303, 'San Ramón'),
(30308, 303, 'Río Azul'),
(30401, 304, 'Juan Viñas'),
(30402, 304, 'Tucurrique'),
(30403, 304, 'Pejibaye'),
(30501, 305, 'Turrialba'),
(30502, 305, 'La Suiza'),
(30503, 305, 'Peralta'),
(30504, 305, 'Santa Cruz'),
(30505, 305, 'Santa Teresita'),
(30506, 305, 'Pavones'),
(30507, 305, 'Tuis'),
(30508, 305, 'Tayutic'),
(30509, 305, 'Santa Rosa'),
(30510, 305, 'Tres Equis'),
(30511, 305, 'La Isabel'),
(30512, 305, 'Chirripó'),
(30601, 306, 'Pacayas'),
(30602, 306, 'Cervantes'),
(30603, 306, 'Capellades'),
(30701, 307, 'San Rafael'),
(30702, 307, 'Cot'),
(30703, 307, 'Potrero Cerrado'),
(30704, 307, 'Cipreses'),
(30705, 307, 'Santa Rosa'),
(30801, 308, 'El Tejar'),
(30802, 308, 'San Isidro'),
(30803, 308, 'Tobosi'),
(30804, 308, 'Patio de Agua'),
(40101, 401, 'Heredia'),
(40102, 401, 'Mercedes'),
(40103, 401, 'San Francisco'),
(40104, 401, 'Ulloa'),
(40105, 401, 'Varablanca'),
(40201, 402, 'Barva'),
(40202, 402, 'San Pedro'),
(40203, 402, 'San Pablo'),
(40204, 402, 'San Roque'),
(40205, 402, 'Santa Lucía'),
(40206, 402, 'San José de la Montaña'),
(40301, 403, 'Santo Domingo'),
(40302, 403, 'San Vicente'),
(40303, 403, 'San Miguel'),
(40304, 403, 'Paracito'),
(40305, 403, 'Santo Tomás'),
(40306, 403, 'Santa Rosa'),
(40307, 403, 'Tures'),
(40308, 403, 'Pará'),
(40401, 404, 'Santa Bárbara'),
(40402, 404, 'San Pedro'),
(40403, 404, 'San Juan'),
(40404, 404, 'Jesús'),
(40405, 404, 'Santo Domingo del Roble'),
(40406, 404, 'Puraba'),
(40501, 405, 'San Rafael'),
(40502, 405, 'San Josecito'),
(40503, 405, 'Santiago'),
(40504, 405, 'Angeles'),
(40505, 405, 'Concepción'),
(40601, 406, 'San Isidro'),
(40602, 406, 'San José'),
(40603, 406, 'Concepción'),
(40604, 406, 'San Francisco'),
(40701, 407, 'San Antonio'),
(40702, 407, 'La Ribera'),
(40703, 407, 'Asunción'),
(40801, 408, 'San Joaquín'),
(40802, 408, 'Barrantes'),
(40803, 408, 'Llorente'),
(40901, 409, 'San Pablo'),
(41001, 410, 'Puerto Viejo'),
(41002, 410, 'La Virgen'),
(41003, 410, 'Horquetas'),
(41004, 410, 'Llanuras de Gaspar'),
(41005, 410, 'Cureña'),
(50101, 501, 'Liberia'),
(50102, 501, 'Cañas Dulces'),
(50103, 501, 'Mayorga'),
(50104, 501, 'Nacascolo'),
(50105, 501, 'Curubande'),
(50201, 502, 'Nicoya'),
(50202, 502, 'Mansión'),
(50203, 502, 'San Antonio'),
(50204, 502, 'Quebrada Honda'),
(50205, 502, 'Sámara'),
(50206, 502, 'Nósara'),
(50207, 502, 'Belén de Nosarita'),
(50301, 503, 'Santa Cruz'),
(50302, 503, 'Bolsón'),
(50303, 503, 'Veintisiete de Abril'),
(50304, 503, 'Tempate'),
(50305, 503, 'Cartagena'),
(50306, 503, 'Cuajiniquil'),
(50307, 503, 'Diriá'),
(50308, 503, 'Cabo Velas'),
(50309, 503, 'Tamarindo'),
(50401, 504, 'Bagaces'),
(50402, 504, 'Fortuna'),
(50403, 504, 'Mogote'),
(50404, 504, 'Río Naranjo'),
(50501, 505, 'Filadelfia'),
(50502, 505, 'Palmira'),
(50503, 505, 'Sardinal'),
(50504, 505, 'Belén'),
(50601, 506, 'Cañas'),
(50602, 506, 'Palmira'),
(50603, 506, 'San Miguel'),
(50604, 506, 'Bebedero'),
(50605, 506, 'Porozal'),
(50701, 507, 'Juntas'),
(50702, 507, 'Sierra'),
(50703, 507, 'San Juan'),
(50704, 507, 'Colorado'),
(50801, 508, 'Tilarán'),
(50802, 508, 'Quebrada Grande'),
(50803, 508, 'Tronadora'),
(50804, 508, 'Santa Rosa'),
(50805, 508, 'Líbano'),
(50806, 508, 'Tierras Morenas'),
(50807, 508, 'Arenal'),
(50901, 509, 'Carmona'),
(50902, 509, 'Santa Rita'),
(50903, 509, 'Zapotal'),
(50904, 509, 'San Pablo'),
(50905, 509, 'Porvenir'),
(50906, 509, 'Bejuco'),
(51001, 510, 'La Cruz'),
(51002, 510, 'Santa Cecilia'),
(51003, 510, 'Garita'),
(51004, 510, 'Santa Elena'),
(51101, 511, 'Hojancha'),
(51102, 511, 'Monte Romo'),
(51103, 511, 'Puerto Carrillo'),
(51104, 511, 'Huacas'),
(60101, 601, 'Puntarenas'),
(60102, 601, 'Pitahaya'),
(60103, 601, 'Chomes'),
(60104, 601, 'Lepanto'),
(60105, 601, 'Paquera'),
(60106, 601, 'Manzanillo'),
(60107, 601, 'Guacimal'),
(60108, 601, 'Barranca'),
(60109, 601, 'Monte Verde'),
(60110, 601, 'Isla del Coco'),
(60111, 601, 'Cóbano'),
(60112, 601, 'Chacarita'),
(60113, 601, 'Chira'),
(60114, 601, 'Acapulco'),
(60115, 601, 'Roble'),
(60116, 601, 'Arancibia'),
(60201, 602, 'Espíritu Santo'),
(60202, 602, 'San Juan Grande'),
(60203, 602, 'Macacona'),
(60204, 602, 'San Rafael'),
(60205, 602, 'San Jerónimo'),
(60301, 603, 'Buenos Aires'),
(60302, 603, 'Volcán'),
(60303, 603, 'Potrero Grande'),
(60304, 603, 'Boruca'),
(60305, 603, 'Pilas'),
(60306, 603, 'Colinas o Bajo de Maíz'),
(60307, 603, 'Chánguena'),
(60308, 603, 'Bioley'),
(60309, 603, 'Brunka'),
(60401, 604, 'Miramar'),
(60402, 604, 'Unión'),
(60403, 604, 'San Isidro'),
(60501, 605, 'Puerto Cortés'),
(60502, 605, 'Palmar'),
(60503, 605, 'Sierpe'),
(60504, 605, 'Bahía Ballena'),
(60505, 605, 'Piedras Blancas'),
(60601, 606, 'Quepos'),
(60602, 606, 'Savegre'),
(60603, 606, 'Naranjito'),
(60701, 607, 'Golfito'),
(60702, 607, 'Puerto Jiménez'),
(60703, 607, 'Guaycará'),
(60704, 607, 'Pavon'),
(60801, 608, 'San Vito'),
(60802, 608, 'Sabalito'),
(60803, 608, 'Agua Buena'),
(60804, 608, 'Limoncito'),
(60805, 608, 'Pittier'),
(60901, 609, 'Parrita'),
(61001, 610, 'Corredor'),
(61002, 610, 'La Cuesta'),
(61003, 610, 'Paso Canoas'),
(61004, 610, 'Laurel'),
(61101, 611, 'Jacó'),
(61102, 611, 'Tárcoles'),
(70101, 701, 'Limón'),
(70102, 701, 'Valle La Estrella'),
(70103, 701, 'Río Blanco'),
(70104, 701, 'Matama'),
(70201, 702, 'Guápiles'),
(70202, 702, 'Jiménez'),
(70203, 702, 'Rita'),
(70204, 702, 'Roxana'),
(70205, 702, 'Cariari'),
(70206, 702, 'Colorado'),
(70301, 703, 'Siquirres'),
(70302, 703, 'Pacuarito'),
(70303, 703, 'Florida'),
(70304, 703, 'Germania'),
(70305, 703, 'Cairo'),
(70306, 703, 'Alegría'),
(70401, 704, 'Bratsi'),
(70402, 704, 'Sixaola'),
(70403, 704, 'Cahuita'),
(70404, 704, 'Telire'),
(70501, 705, 'Matina'),
(70502, 705, 'Batán'),
(70503, 705, 'Carrandí'),
(70601, 706, 'Guácimo'),
(70602, 706, 'Mercedes'),
(70603, 706, 'Pocora'),
(70604, 706, 'Río Jiménez'),
(70605, 706, 'Duacarí');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `entradasalidaregistro`
--

CREATE TABLE `entradasalidaregistro` (
  `id` int(15) NOT NULL,
  `monto` varchar(9) DEFAULT NULL,
  `tipo` tinyint(1) DEFAULT NULL,
  `fecha` varchar(12) DEFAULT NULL,
  `hora` varchar(12) DEFAULT NULL,
  `Detalle` text DEFAULT NULL,
  `habilitado` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `establecimiento`
--

CREATE TABLE `establecimiento` (
  `id` int(9) NOT NULL,
  `nombre` varchar(35) DEFAULT NULL,
  `telefono` varchar(35) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `establecimiento`
--

INSERT INTO `establecimiento` (`id`, `nombre`, `telefono`) VALUES
(1, 'Souvenir #1', '26661234'),
(2, 'Souvenir #2', '26661235'),
(3, 'Souvenir #3', '26665432');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `factura`
--

CREATE TABLE `factura` (
  `id` int(20) NOT NULL,
  `subtotal` decimal(10,2) DEFAULT 0.00,
  `iva` decimal(10,2) DEFAULT 0.00,
  `it` decimal(10,2) DEFAULT 0.00,
  `tipo_comprobante` enum('FACTURA','RECIBO') DEFAULT 'RECIBO',
  `total` varchar(20) DEFAULT NULL,
  `total_comision` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_caja` decimal(10,2) NOT NULL DEFAULT 0.00,
  `fecha` varchar(25) DEFAULT NULL,
  `hora` varchar(25) DEFAULT NULL,
  `usuario` varchar(30) DEFAULT NULL,
  `cliente` int(11) DEFAULT NULL,
  `nit_cliente` varchar(30) DEFAULT NULL,
  `razon_social` varchar(255) DEFAULT NULL,
  `tipo` tinyint(1) DEFAULT 1,
  `metodo_pago` enum('EFECTIVO','TRANSFERENCIA','TARJETA','DEPOSITO','MIXTO') DEFAULT 'EFECTIVO',
  `referencia` varchar(100) DEFAULT NULL,
  `id_banco` int(11) DEFAULT NULL,
  `habilitado` int(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `factura`
--

INSERT INTO `factura` (`id`, `subtotal`, `iva`, `it`, `tipo_comprobante`, `total`, `total_comision`, `total_caja`, `fecha`, `hora`, `usuario`, `cliente`, `nit_cliente`, `razon_social`, `tipo`, `metodo_pago`, `referencia`, `id_banco`, `habilitado`) VALUES
(1, '0.00', '0.00', '0.00', 'RECIBO', '1.650,0', '0.00', '0.00', '21-11-2025', '02:40:20 am', '1', 1, NULL, NULL, 1, 'EFECTIVO', NULL, NULL, 1),
(2, '0.00', '0.00', '0.00', 'RECIBO', '720,0', '0.00', '0.00', '21-11-2025', '03:15:40 am', '1', 1, NULL, NULL, 0, 'EFECTIVO', NULL, NULL, 1),
(3, '0.00', '0.00', '0.00', 'RECIBO', '720,0', '0.00', '0.00', '21-11-2025', '04:56:39 am', '1', 1, NULL, NULL, 0, 'EFECTIVO', NULL, NULL, 1),
(4, '0.00', '0.00', '0.00', 'RECIBO', '1.440,0', '0.00', '0.00', '21-11-2025', '05:03:26 am', '1', 1, NULL, NULL, 1, 'EFECTIVO', NULL, NULL, 1),
(5, '0.00', '0.00', '0.00', 'RECIBO', '1.650,0', '0.00', '0.00', '21-11-2025', '05:04:12 am', '1', 1, NULL, NULL, 0, 'EFECTIVO', NULL, NULL, 1),
(6, '0.00', '0.00', '0.00', 'RECIBO', '2.300,0', '0.00', '0.00', '21-11-2025', '04:44:04 pm', '1', 1, NULL, NULL, 0, 'EFECTIVO', NULL, NULL, 1),
(7, '0.00', '0.00', '0.00', 'RECIBO', '880,0', '0.00', '0.00', '21-11-2025', '07:51:03 pm', '1', 1, NULL, NULL, 0, 'EFECTIVO', NULL, NULL, 1),
(8, '0.00', '0.00', '0.00', 'RECIBO', '0,0', '0.00', '0.00', '21-11-2025', '07:51:10 pm', '1', 0, NULL, NULL, 0, 'EFECTIVO', NULL, NULL, 1),
(9, '0.00', '0.00', '0.00', 'RECIBO', '1.650,0', '0.00', '0.00', '21-11-2025', '07:52:41 pm', '1', 1, NULL, NULL, 0, 'EFECTIVO', NULL, NULL, 0),
(10, '0.00', '0.00', '0.00', 'RECIBO', '3.300,0', '0.00', '0.00', '22-11-2025', '10:29:08 pm', '1', 1, NULL, NULL, 1, 'EFECTIVO', NULL, NULL, 1),
(11, '0.00', '0.00', '0.00', 'RECIBO', '880,0', '0.00', '0.00', '23-11-2025', '05:27:57 am', '1', 1, NULL, NULL, 1, 'EFECTIVO', NULL, NULL, 1),
(12, '0.00', '0.00', '0.00', 'RECIBO', '3.520,0', '0.00', '0.00', '24-11-2025', '12:19:07 am', '1', 1, NULL, NULL, 0, 'EFECTIVO', NULL, NULL, 1),
(13, '0.00', '0.00', '0.00', 'RECIBO', '720,0', '0.00', '0.00', '24-11-2025', '12:26:21 am', '1', 1, NULL, NULL, 0, 'EFECTIVO', NULL, NULL, 1),
(14, '0.00', '0.00', '0.00', '', '4020', '0.00', '4020.00', '24-11-2025', '12:32:50 am', '1', 1, NULL, NULL, 1, '', NULL, NULL, 1),
(15, '0.00', '0.00', '0.00', '', '720', '0.00', '720.00', '24-11-2025', '12:36:14 am', '1', 1, NULL, NULL, 1, '', NULL, NULL, 1),
(16, '0.00', '0.00', '0.00', '', '1650', '0.00', '1650.00', '24-11-2025', '12:44:21 am', '1', 1, NULL, NULL, 1, '', NULL, NULL, 1),
(17, '0.00', '0.00', '0.00', '', '720', '0.00', '720.00', '24-11-2025', '01:13:28 am', '2', 1, NULL, NULL, 1, '', NULL, NULL, 1),
(18, '0.00', '0.00', '0.00', '', '880', '0.00', '880.00', '24-11-2025', '01:38:03 am', '1', 1, NULL, NULL, 1, '', NULL, NULL, 1),
(19, '0.00', '0.00', '0.00', 'RECIBO', '1150', '0.00', '1150.00', '24-11-2025', '07:35:21 pm', '1', 2, NULL, NULL, 1, 'EFECTIVO', NULL, NULL, 1),
(20, '0.00', '0.00', '0.00', 'RECIBO', '720', '0.00', '720.00', '24-11-2025', '07:41:41 pm', '2', 2, NULL, NULL, 1, 'EFECTIVO', NULL, NULL, 1),
(21, '0.00', '0.00', '0.00', '', '9900', '0.00', '9900.00', '25-11-2025', '04:12:13 am', '1', 2, NULL, NULL, 1, '', NULL, NULL, 1),
(22, '0.00', '0.00', '0.00', '', '1650', '0.00', '1650.00', '25-11-2025', '04:13:55 am', '1', 2, NULL, NULL, 1, '', NULL, NULL, 1),
(23, '0.00', '0.00', '0.00', 'RECIBO', '2530', '0.00', '0.00', '25-11-2025', '06:37:57 am', '1', 2, NULL, NULL, 1, 'EFECTIVO', NULL, NULL, 1),
(24, '0.00', '0.00', '0.00', 'RECIBO', '1150', '0.00', '0.00', '25-11-2025', '06:49:55 am', '1', 2, NULL, NULL, 1, 'EFECTIVO', NULL, NULL, 1),
(25, '0.00', '0.00', '0.00', 'RECIBO', '1650', '0.00', '0.00', '25-11-2025', '05:38:48 pm', '1', 2, NULL, NULL, 1, 'EFECTIVO', NULL, NULL, 1),
(26, '0.00', '0.00', '0.00', 'RECIBO', '4950', '0.00', '0.00', '27-11-2025', '04:59:41 am', '1', 2, NULL, NULL, 1, 'EFECTIVO', NULL, NULL, 1),
(27, '0.00', '0.00', '0.00', 'RECIBO', '1650', '0.00', '0.00', '27-11-2025', '07:37:22 am', '1', 1, NULL, NULL, 1, 'EFECTIVO', NULL, NULL, 1),
(28, '0.00', '0.00', '0.00', 'RECIBO', '720', '0.00', '0.00', '27-11-2025', '08:09:36 pm', '1', 2, NULL, NULL, 1, 'EFECTIVO', NULL, NULL, 1),
(29, '0.00', '0.00', '0.00', 'RECIBO', '1760', '0.00', '0.00', '28-11-2025', '04:38:37 am', '1', 1, NULL, NULL, 1, 'EFECTIVO', NULL, NULL, 1),
(30, '0.00', '0.00', '0.00', 'RECIBO', '1650', '0.00', '0.00', '28-11-2025', '04:40:12 am', '1', 1, NULL, NULL, 1, 'EFECTIVO', NULL, NULL, 1),
(31, '0.00', '0.00', '0.00', 'RECIBO', '720', '0.00', '0.00', '28-11-2025', '04:42:17 am', '1', 1, NULL, NULL, 1, 'EFECTIVO', NULL, NULL, 1),
(32, '0.00', '0.00', '0.00', 'RECIBO', '2530', '0.00', '0.00', '28-11-2025', '05:41:06 am', '1', 1, NULL, NULL, 1, 'EFECTIVO', NULL, NULL, 1),
(33, '0.00', '0.00', '0.00', 'RECIBO', '1650', '0.00', '0.00', '28-11-2025', '05:41:29 am', '1', 1, NULL, NULL, 1, 'EFECTIVO', NULL, NULL, 1),
(34, '0.00', '0.00', '0.00', 'RECIBO', '720', '0.00', '0.00', '28-11-2025', '05:47:18 am', '1', 1, NULL, NULL, 1, 'EFECTIVO', NULL, NULL, 1),
(35, '0.00', '0.00', '0.00', 'RECIBO', '720', '0.00', '720.00', '28-11-2025', '06:04:07 am', '1', 1, NULL, NULL, 1, 'EFECTIVO', NULL, NULL, 1),
(36, '0.00', '0.00', '0.00', 'RECIBO', '880', '0.00', '880.00', '28-11-2025', '06:46:43 am', '1', 1, NULL, NULL, 1, 'EFECTIVO', NULL, NULL, 1),
(37, '0.00', '0.00', '0.00', 'RECIBO', '1650', '0.00', '1650.00', '28-11-2025', '06:50:21 am', '1', 1, NULL, NULL, 1, 'EFECTIVO', NULL, NULL, 1),
(38, '0.00', '0.00', '0.00', 'RECIBO', '1650', '0.00', '0.00', '2025-11-28', '08:57:29', '1', 1, NULL, NULL, 1, 'EFECTIVO', NULL, NULL, 1),
(39, '0.00', '0.00', '0.00', 'RECIBO', '19800', '0.00', '0.00', '2025-11-29', '17:06:51', '1', 2, NULL, NULL, 1, 'EFECTIVO', NULL, NULL, 1),
(40, '0.00', '0.00', '0.00', 'RECIBO', '880', '0.00', '0.00', '2025-11-29', '19:14:25', '1', 2, NULL, NULL, 1, 'EFECTIVO', NULL, NULL, 1),
(41, '0.00', '0.00', '0.00', 'RECIBO', '1650', '0.00', '0.00', '2025-11-29', '22:39:33', '1', 2, NULL, NULL, 1, 'EFECTIVO', NULL, NULL, 1),
(42, '0.00', '0.00', '0.00', 'RECIBO', '3300', '0.00', '0.00', '2025-11-30', '01:01:18', '1', 1, NULL, NULL, 1, 'EFECTIVO', NULL, NULL, 1),
(43, '0.00', '0.00', '0.00', 'RECIBO', '2530', '0.00', '0.00', '2025-11-30', '01:09:22', '1', 1, NULL, NULL, 1, 'EFECTIVO', NULL, NULL, 1),
(44, '0.00', '0.00', '0.00', 'RECIBO', '6730', '0.00', '0.00', '2025-11-30', '02:21:33', '1', 1, NULL, NULL, 1, 'EFECTIVO', NULL, NULL, 1),
(45, '0.00', '0.00', '0.00', 'RECIBO', '880', '0.00', '0.00', '2025-11-30', '03:59:05', '1', 2, NULL, NULL, 1, 'EFECTIVO', NULL, NULL, 1),
(46, '0.00', '0.00', '0.00', 'RECIBO', '570', '0.00', '0.00', '2025-11-30', '05:50:49', '1', 1, NULL, NULL, 1, 'EFECTIVO', NULL, NULL, 1),
(47, '0.00', '0.00', '0.00', 'RECIBO', '1650', '0.00', '0.00', '2025-11-30', '17:44:32', '1', 2, NULL, NULL, 1, 'EFECTIVO', NULL, NULL, 1),
(48, '0.00', '0.00', '0.00', 'RECIBO', '2530', '0.00', '0.00', '2025-11-30', '18:20:08', '1', 1, NULL, NULL, 1, 'EFECTIVO', NULL, NULL, 1),
(49, '0.00', '0.00', '0.00', 'RECIBO', '880', '0.00', '0.00', '2025-11-30', '20:38:59', '1', 2, NULL, NULL, 1, 'EFECTIVO', NULL, NULL, 1),
(50, '0.00', '0.00', '0.00', 'RECIBO', '1650', '0.00', '0.00', '2025-11-30', '20:55:19', '1', 2, NULL, NULL, 1, 'EFECTIVO', NULL, NULL, 1),
(51, '0.00', '0.00', '0.00', 'RECIBO', '3650', '0.00', '0.00', '2025-11-30', '21:51:12', '1', 2, NULL, NULL, 1, 'EFECTIVO', NULL, NULL, 1),
(52, '0.00', '0.00', '0.00', 'RECIBO', '1200', '0.00', '0.00', '2025-12-02', '18:02:44', '1', 100, NULL, NULL, 1, 'EFECTIVO', NULL, NULL, 1),
(53, '0.00', '0.00', '0.00', 'RECIBO', '250', '0.00', '0.00', '2025-12-02', '19:15:36', '1', 11, NULL, NULL, 1, 'EFECTIVO', NULL, NULL, 1),
(54, '0.00', '0.00', '0.00', 'RECIBO', '250', '0.00', '0.00', '2025-12-03', '04:30:03', '1', 11, NULL, NULL, 1, 'EFECTIVO', NULL, NULL, 1),
(55, '0.00', '0.00', '0.00', 'RECIBO', '500', '0.00', '0.00', '2025-12-03', '16:29:06', '1', 11, NULL, NULL, 1, 'EFECTIVO', NULL, NULL, 1),
(56, '0.00', '0.00', '0.00', 'RECIBO', '1200', '0.00', '0.00', '2025-12-05', '17:22:24', '1', 12, NULL, NULL, 1, 'EFECTIVO', NULL, NULL, 1),
(57, '0.00', '0.00', '0.00', 'RECIBO', '1000', '0.00', '0.00', '2025-12-05', '20:43:36', '1', 12, NULL, NULL, 1, 'EFECTIVO', NULL, NULL, 1),
(58, '0.00', '0.00', '0.00', 'RECIBO', '250', '0.00', '0.00', '2025-12-05', '20:44:04', '1', 12, NULL, NULL, 1, 'EFECTIVO', NULL, NULL, 1),
(59, '0.00', '0.00', '0.00', 'RECIBO', '2200', '0.00', '0.00', '2025-12-05', '20:46:27', '1', 12, NULL, NULL, 1, 'EFECTIVO', NULL, NULL, 1),
(60, '250.00', '32.50', '7.50', 'FACTURA', '250', '0.00', '250.00', '2025-12-05', '23:16:17', '1', 12, '3131312', 'wqe', 1, 'EFECTIVO', NULL, NULL, 1),
(61, '250.00', '32.50', '7.50', 'FACTURA', '250', '0.00', '250.00', '2025-12-09', '20:44:57', '1', 11, '3131312', 'ddada', 1, 'EFECTIVO', NULL, NULL, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `facturacion_siat_config`
--

CREATE TABLE `facturacion_siat_config` (
  `id` int(11) NOT NULL,
  `nit` varchar(20) NOT NULL,
  `razon_social` varchar(150) NOT NULL,
  `codigo_sistema` varchar(100) DEFAULT NULL,
  `cuis` varchar(100) DEFAULT NULL,
  `cufd` varchar(200) DEFAULT NULL,
  `token` text DEFAULT NULL,
  `ambiente` enum('PRUEBA','PRODUCCION') DEFAULT 'PRUEBA',
  `fecha_actualizacion` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `iva`
--

CREATE TABLE `iva` (
  `id` int(9) NOT NULL COMMENT 'Identificador numérico para cada uno de los registros de la tabla.(Llave Primaria)',
  `nombre` varchar(50) DEFAULT NULL COMMENT 'Nombre del impuesto de venta',
  `valor` int(4) DEFAULT NULL COMMENT 'Valor del impuesto de venta',
  `habilitado` tinyint(1) DEFAULT NULL COMMENT 'Determina si el registro es válido para utilizarse o se debe ignorar para operaciones sobre los datos.'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `iva`
--

INSERT INTO `iva` (`id`, `nombre`, `valor`, `habilitado`) VALUES
(1, 'Sin Impuesto de Venta', 0, 1),
(2, 'Impuesto de Venta', 13, 1),
(3, 'Impuesto de Servicio', 10, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `kardex`
--

CREATE TABLE `kardex` (
  `id` int(5) NOT NULL,
  `producto` varchar(255) DEFAULT NULL,
  `entrada` int(11) DEFAULT 0,
  `salida` int(11) DEFAULT 0,
  `stock` int(11) DEFAULT NULL,
  `preciounitario` varchar(15) DEFAULT NULL,
  `preciototal` varchar(15) DEFAULT NULL,
  `detalle` varchar(50) DEFAULT 'Salida de Producto',
  `fecha` varchar(10) DEFAULT NULL,
  `hora` varchar(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `kardex`
--

INSERT INTO `kardex` (`id`, `producto`, `entrada`, `salida`, `stock`, `preciounitario`, `preciototal`, `detalle`, `fecha`, `hora`) VALUES
(1, '3', 0, 1, 0, '1650', '1650', 'Salida de Producto', '21-11-2025', '02:40:10 am'),
(2, '4', 0, 1, 0, '720', '720', 'Salida de Producto', '21-11-2025', '02:43:43 am'),
(3, '4', 0, 1, 0, '720', '720', 'Salida de Producto', '21-11-2025', '04:56:27 am'),
(4, '4', 0, 2, 0, '720', '1440', 'Salida de Producto', '21-11-2025', '05:03:22 am'),
(5, '3', 0, 1, 0, '1650', '1650', 'Salida de Producto', '21-11-2025', '05:04:06 am'),
(6, '5', 0, 2, 0, '1150', '2300', 'Salida de Producto', '21-11-2025', '04:43:44 pm'),
(7, '2', 0, 1, 0, '880', '880', 'Salida de Producto', '21-11-2025', '07:51:00 pm'),
(8, '3', 0, 1, 0, '1650', '1650', 'Salida de Producto', '21-11-2025', '07:52:35 pm'),
(9, '3', 0, 2, 0, '1650', '3300', 'Salida de Producto', '22-11-2025', '10:29:00 pm'),
(10, '2', 0, 1, 0, '880', '880', 'Salida de Producto', '23-11-2025', '05:27:52 am'),
(11, '2', 0, 4, 0, '880', '3520', 'Salida de Producto', '24-11-2025', '12:19:02 am'),
(12, '4', 0, 1, 0, '720', '720', 'Salida de Producto', '24-11-2025', '12:26:14 am');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `log_usuarios`
--

CREATE TABLE `log_usuarios` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `accion` varchar(255) NOT NULL,
  `evento` varchar(50) DEFAULT NULL,
  `modulo` varchar(50) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `metodo` varchar(10) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `meta_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta_json`)),
  `fecha` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `medida`
--

CREATE TABLE `medida` (
  `id` int(9) NOT NULL,
  `nombre` varchar(50) DEFAULT NULL,
  `signo` varchar(5) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `medida`
--

INSERT INTO `medida` (`id`, `nombre`, `signo`) VALUES
(1, 'Unidad/Pza', 'U'),
(2, 'Litro', 'L'),
(3, 'Kilo', 'K');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `moneda`
--

CREATE TABLE `moneda` (
  `id` int(9) NOT NULL,
  `moneda` varchar(55) DEFAULT NULL,
  `signo` varchar(25) DEFAULT NULL,
  `valor` int(9) DEFAULT NULL,
  `rango` tinyint(1) DEFAULT 0,
  `habilitada` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `moneda`
--

INSERT INTO `moneda` (`id`, `moneda`, `signo`, `valor`, `rango`, `habilitada`) VALUES
(1, 'Colón', '¢', 528, 2, 1),
(2, 'Dolar', '$', 1, 1, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `movimientostipo`
--

CREATE TABLE `movimientostipo` (
  `id` int(2) NOT NULL,
  `nombre` varchar(35) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `movimientostipo`
--

INSERT INTO `movimientostipo` (`id`, `nombre`) VALUES
(1, 'Apertura de Caja'),
(2, 'Cierre de Caja'),
(3, 'Entrada de Dinero'),
(4, 'Salida de Dinero'),
(5, 'Entrada de Dinero Caja Chica'),
(6, 'Salida de Dinero Caja Chica');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificaciones`
--

CREATE TABLE `notificaciones` (
  `id` int(11) NOT NULL,
  `id_tramite` int(11) DEFAULT NULL,
  `notificacion` text DEFAULT NULL,
  `fecha` varchar(25) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos_proveedor`
--

CREATE TABLE `pagos_proveedor` (
  `id` int(11) NOT NULL,
  `id_proveedor` int(9) NOT NULL,
  `fecha` datetime NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `metodo_pago` enum('EFECTIVO','TRANSFERENCIA','DEPOSITO','TARJETA') DEFAULT 'EFECTIVO',
  `id_banco` int(11) DEFAULT NULL,
  `concepto` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `perfil`
--

CREATE TABLE `perfil` (
  `id` int(9) NOT NULL COMMENT 'Identificador numérico para cada uno de los registros de la tabla.(Llave Primaria)',
  `perfil` varchar(50) DEFAULT NULL COMMENT 'Nombre del perfil de usuario',
  `comentario` text DEFAULT NULL COMMENT 'aclaración o comentario explicativo del tipo de perfil',
  `habilitado` tinyint(1) DEFAULT 1 COMMENT 'Determina si el registro es válido para utilizarse o se debe ignorar para operaciones sobre los datos.'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `perfil`
--

INSERT INTO `perfil` (`id`, `perfil`, `comentario`, `habilitado`) VALUES
(1, 'Administrador', '', 1),
(2, 'Vendedor', '', 1),
(3, 'Agente de Viajes', 'Realiza ventas y cotizaciones', 1),
(4, 'Gestor de Trámites', 'Administra expedientes y documentación', 1),
(5, 'Cajero', 'Manejo de caja chica y caja general', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `perfil_permiso`
--

CREATE TABLE `perfil_permiso` (
  `id_perfil` int(11) NOT NULL,
  `id_permiso` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `perfil_permiso`
--

INSERT INTO `perfil_permiso` (`id_perfil`, `id_permiso`) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 4),
(1, 5),
(1, 6),
(1, 7),
(1, 8),
(1, 9),
(1, 10),
(1, 11),
(1, 12),
(1, 13),
(1, 14),
(1, 15),
(1, 16),
(1, 17),
(3, 10),
(3, 11),
(3, 12),
(3, 13),
(3, 14),
(3, 16),
(3, 17),
(4, 13),
(4, 14),
(4, 15),
(4, 17),
(5, 4),
(5, 5),
(5, 6),
(5, 7),
(5, 8),
(5, 11),
(5, 16),
(5, 17);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permiso`
--

CREATE TABLE `permiso` (
  `id` int(11) NOT NULL,
  `codigo` varchar(80) NOT NULL,
  `modulo` varchar(50) NOT NULL,
  `descripcion` varchar(255) NOT NULL,
  `habilitado` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `permiso`
--

INSERT INTO `permiso` (`id`, `codigo`, `modulo`, `descripcion`, `habilitado`) VALUES
(1, 'usuarios.gestionar', 'SEGURIDAD', 'Crear/editar/deshabilitar usuarios', 1),
(2, 'roles.permisos.gestionar', 'SEGURIDAD', 'Asignar permisos a roles', 1),
(3, 'auditoria.ver', 'SEGURIDAD', 'Ver registro de actividades', 1),
(4, 'caja_chica.ver', 'CAJA_CHICA', 'Ver caja chica', 1),
(5, 'caja_chica.movimientos', 'CAJA_CHICA', 'Registrar ingresos/egresos caja chica', 1),
(6, 'caja_general.ver', 'CAJA_GENERAL', 'Ver caja general', 1),
(7, 'caja_general.movimientos', 'CAJA_GENERAL', 'Registrar ingresos/egresos caja general', 1),
(8, 'caja_general.conciliar', 'CAJA_GENERAL', 'Conciliación bancaria', 1),
(9, 'servicios.catalogo.gestionar', 'SERVICIOS', 'Gestionar catálogo de servicios', 1),
(10, 'ventas.crear', 'VENTAS', 'Registrar venta de servicio', 1),
(11, 'ventas.ver', 'VENTAS', 'Ver ventas', 1),
(12, 'cotizaciones.gestionar', 'VENTAS', 'Crear/editar cotizaciones', 1),
(13, 'clientes.gestionar', 'CLIENTES', 'Crear/editar clientes', 1),
(14, 'expedientes.gestionar', 'CLIENTES', 'Gestionar expedientes', 1),
(15, 'tramites.gestionar', 'TRAMITES', 'Gestionar trámites', 1),
(16, 'facturacion.emitir', 'FACTURACION', 'Emitir factura / comprobante', 1),
(17, 'reportes.ver', 'REPORTES', 'Ver reportes', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto`
--

CREATE TABLE `producto` (
  `id` int(11) NOT NULL,
  `id_proveedor` int(9) DEFAULT NULL,
  `codigo` varchar(50) DEFAULT NULL,
  `nombre` varchar(255) DEFAULT NULL,
  `tipo_servicio` enum('PASAJE','PAQUETE','SEGURO','TRAMITE','OTRO') DEFAULT 'OTRO',
  `descripcion` text DEFAULT NULL,
  `requiere_boleto` tinyint(1) DEFAULT 0,
  `requiere_visa` tinyint(1) DEFAULT 0,
  `preciocosto` float DEFAULT NULL,
  `precioventa` float DEFAULT NULL,
  `iva` decimal(5,2) DEFAULT 0.00,
  `comision` decimal(10,2) DEFAULT 0.00,
  `es_comisionable` tinyint(1) NOT NULL DEFAULT 1,
  `proveedor` int(9) DEFAULT NULL,
  `departamento` int(6) DEFAULT NULL,
  `stock` int(9) DEFAULT NULL,
  `stockMin` int(9) DEFAULT NULL,
  `impuesto` int(3) DEFAULT 0,
  `medida` varchar(50) DEFAULT NULL,
  `especificaciones` text DEFAULT NULL,
  `habilitado` tinyint(1) DEFAULT 1,
  `categoria_id` int(11) DEFAULT NULL,
  `aerolinea` varchar(100) DEFAULT NULL,
  `destino` varchar(150) DEFAULT NULL,
  `fecha_salida` date DEFAULT NULL,
  `fecha_retorno` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `producto`
--

INSERT INTO `producto` (`id`, `id_proveedor`, `codigo`, `nombre`, `tipo_servicio`, `descripcion`, `requiere_boleto`, `requiere_visa`, `preciocosto`, `precioventa`, `iva`, `comision`, `es_comisionable`, `proveedor`, `departamento`, `stock`, `stockMin`, `impuesto`, `medida`, `especificaciones`, `habilitado`, `categoria_id`, `aerolinea`, `destino`, `fecha_salida`, `fecha_retorno`) VALUES
(100, NULL, NULL, 'Pasaje Santa Cruz - Buenos Aires', 'PASAJE', NULL, 0, 0, NULL, 850, '0.00', '0.00', 1, NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL),
(101, NULL, NULL, 'Paquete 4 días - Cusco', 'PAQUETE', NULL, 0, 0, NULL, 1200, '0.00', '0.00', 1, NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL),
(102, NULL, NULL, 'Seguro de viaje 15 días', 'SEGURO', NULL, 0, 0, NULL, 250, '0.00', '0.00', 1, NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL),
(103, NULL, NULL, 'Trámite de Visa Americana', 'TRAMITE', NULL, 0, 0, NULL, 500, '0.00', '0.00', 1, NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proveedor`
--

CREATE TABLE `proveedor` (
  `id` int(9) NOT NULL,
  `nombre` varchar(80) DEFAULT NULL,
  `telefono` varchar(35) DEFAULT NULL,
  `contacto` varchar(80) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `direccion` varchar(150) DEFAULT NULL,
  `saldo_pendiente` decimal(10,2) DEFAULT 0.00,
  `tipo_proveedor` enum('AEROLINEA','HOTEL','ASEGURADORA','CONSULADO','OTRO') DEFAULT 'OTRO',
  `habilitado` tinyint(1) DEFAULT 1,
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `proveedor`
--

INSERT INTO `proveedor` (`id`, `nombre`, `telefono`, `contacto`, `email`, `direccion`, `saldo_pendiente`, `tipo_proveedor`, `habilitado`, `fecha_registro`) VALUES
(1, 'Aerolínea BOA', NULL, NULL, NULL, NULL, '100000.00', 'OTRO', 1, '2025-12-07 01:29:21'),
(2, 'Aerolínea Amaszonas', NULL, NULL, NULL, NULL, '0.00', 'OTRO', 0, '2025-12-07 01:29:21'),
(3, 'Aerolínea Latam', NULL, NULL, NULL, NULL, '0.00', 'OTRO', 1, '2025-12-07 01:29:21'),
(4, 'Aerolínea Copa Airlines', NULL, NULL, NULL, NULL, '-16372576.00', 'OTRO', 1, '2025-12-07 01:29:21'),
(5, 'Assist Card', NULL, NULL, NULL, NULL, '231313.00', 'OTRO', 1, '2025-12-07 01:29:21'),
(6, 'Universal Assistance', NULL, NULL, NULL, NULL, '0.00', 'OTRO', 1, '2025-12-07 01:29:21'),
(7, 'Travel Ace', NULL, NULL, NULL, NULL, '0.00', 'OTRO', 1, '2025-12-07 01:29:21'),
(8, 'Hotel Radisson', NULL, NULL, NULL, NULL, '0.00', 'OTRO', 1, '2025-12-07 01:29:21'),
(9, 'Hotel Marriott', NULL, NULL, NULL, NULL, '0.00', 'OTRO', 1, '2025-12-07 01:29:21'),
(10, 'Consulado de Brasil', NULL, NULL, NULL, NULL, '0.00', 'OTRO', 1, '2025-12-07 01:29:21'),
(11, 'Consulado de Estados Unidos', NULL, NULL, NULL, NULL, '0.00', 'OTRO', 1, '2025-12-07 01:29:21'),
(12, 'CVC Operadora', NULL, NULL, NULL, NULL, '0.00', 'OTRO', 1, '2025-12-07 01:29:21'),
(13, 'Costamar Travel', NULL, NULL, NULL, NULL, '0.00', 'OTRO', 1, '2025-12-07 01:29:21'),
(14, 'Despegar Agencia', NULL, NULL, NULL, NULL, '0.00', 'OTRO', 1, '2025-12-07 01:29:21'),
(15, 'Ewqe', 'eqwewq', 'eqw', '', 'eqw', '0.00', 'OTRO', 0, '2025-12-07 01:29:21'),
(16, 'Fghfgfgfgh', '74184096', '4565465465', 'softw.corport@gmail.com', 'Sucre\r\nCalle Antofagasta #440, Zona San Matías.', '0.00', 'AEROLINEA', 1, '2025-12-07 16:07:01'),
(17, 'Micky', '74184096', '4565465465', 'softw.corport@gmail.com', 'calle tumpa , Santa Cruz de la Sierra, Bolivia\r\n7mo anillo B/Los Chacos C/B Nro.9,Santa Cruz de la Sierra,Bolivia', '0.00', 'HOTEL', 1, '2025-12-08 09:05:37');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proveedor_factura`
--

CREATE TABLE `proveedor_factura` (
  `id` int(11) NOT NULL,
  `id_proveedor` int(11) NOT NULL,
  `numero_factura` varchar(60) DEFAULT NULL,
  `fecha_emision` date DEFAULT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `monto_total` decimal(12,2) NOT NULL,
  `monto_pagado` decimal(12,2) DEFAULT 0.00,
  `estado` enum('PENDIENTE','PARCIAL','PAGADA','VENCIDA') DEFAULT 'PENDIENTE',
  `observaciones` text DEFAULT NULL,
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp(),
  `moneda` varchar(10) DEFAULT 'BOB'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `proveedor_factura`
--

INSERT INTO `proveedor_factura` (`id`, `id_proveedor`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `monto_total`, `monto_pagado`, `estado`, `observaciones`, `fecha_registro`, `moneda`) VALUES
(1, 4, '13231', '2025-12-07', '2025-12-09', '4656456.00', '67578677.00', 'PAGADA', 'dasda', '2025-12-07 17:38:29', 'BOB'),
(2, 5, '13213', '2025-12-07', '2025-12-10', '231313.00', '0.00', 'PENDIENTE', 'dada', '2025-12-07 17:38:53', 'BOB'),
(3, 4, '12', '2025-12-07', '2025-12-09', '46549645.00', '0.00', 'PENDIENTE', 'dasdas', '2025-12-07 20:41:03', 'BOB'),
(4, 1, '31313', '2025-12-13', '2025-12-12', '100000.00', '0.00', 'PENDIENTE', 'Tickets de pasajes vacacionales', '2025-12-08 09:06:36', 'BOB');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proveedor_movimiento`
--

CREATE TABLE `proveedor_movimiento` (
  `id` int(11) NOT NULL,
  `id_proveedor` int(11) NOT NULL,
  `id_factura` int(11) DEFAULT NULL,
  `id_pago` int(11) DEFAULT NULL,
  `tipo` enum('FACTURA','PAGO','AJUSTE') NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `monto` decimal(12,2) DEFAULT NULL,
  `fecha` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `proveedor_movimiento`
--

INSERT INTO `proveedor_movimiento` (`id`, `id_proveedor`, `id_factura`, `id_pago`, `tipo`, `descripcion`, `monto`, `fecha`) VALUES
(1, 4, NULL, NULL, 'FACTURA', 'Factura registrada N° 13231', '4656456.00', '2025-12-07 17:38:29'),
(2, 5, NULL, NULL, 'FACTURA', 'Factura registrada N° 13213', '231313.00', '2025-12-07 17:38:53'),
(3, 4, NULL, NULL, 'PAGO', 'Pago registrado por 79879 Bs', '-79879.00', '2025-12-07 18:18:32'),
(4, 4, NULL, NULL, 'PAGO', 'Pago registrado por 67498798 Bs', '-67498798.00', '2025-12-07 18:19:00'),
(5, 4, NULL, NULL, 'FACTURA', 'Factura registrada N° 12', '46549645.00', '2025-12-07 20:41:03'),
(6, 1, NULL, NULL, 'FACTURA', 'Factura registrada N° 31313', '100000.00', '2025-12-08 09:06:36');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proveedor_pago`
--

CREATE TABLE `proveedor_pago` (
  `id` int(11) NOT NULL,
  `id_proveedor` int(11) NOT NULL,
  `id_factura` int(11) DEFAULT NULL,
  `fecha_pago` date NOT NULL,
  `monto` decimal(12,2) NOT NULL,
  `metodo_pago` enum('EFECTIVO','TRANSFERENCIA','DEPOSITO') DEFAULT 'TRANSFERENCIA',
  `id_banco` int(11) DEFAULT NULL,
  `id_caja` int(11) DEFAULT NULL,
  `referencia` varchar(120) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `registrado_por` varchar(120) DEFAULT NULL,
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp(),
  `responsable` int(11) DEFAULT NULL,
  `id_asiento` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `proveedor_pago`
--

INSERT INTO `proveedor_pago` (`id`, `id_proveedor`, `id_factura`, `fecha_pago`, `monto`, `metodo_pago`, `id_banco`, `id_caja`, `referencia`, `observaciones`, `registrado_por`, `fecha_registro`, `responsable`, `id_asiento`) VALUES
(1, 4, 1, '2025-12-07', '67498798.00', 'TRANSFERENCIA', NULL, NULL, 'eqweqwe', 'dadasda', NULL, '2025-12-07 18:18:59', NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proveedor_saldos`
--

CREATE TABLE `proveedor_saldos` (
  `id` int(11) NOT NULL,
  `id_proveedor` int(11) NOT NULL,
  `saldo` decimal(12,2) DEFAULT NULL,
  `fecha_actualizacion` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `provincia`
--

CREATE TABLE `provincia` (
  `id` smallint(5) UNSIGNED NOT NULL,
  `provincia` varchar(45) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `provincia`
--

INSERT INTO `provincia` (`id`, `provincia`) VALUES
(1, 'San José'),
(2, 'Alajuela'),
(3, 'Cartago'),
(4, 'Heredia'),
(5, 'Guanacaste'),
(6, 'Puntarenas'),
(7, 'Limón');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `servicios`
--

CREATE TABLE `servicios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `tipo_servicio` varchar(255) NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `comision` decimal(10,2) DEFAULT 0.00,
  `categoria_id` int(11) NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `servicio_config`
--

CREATE TABLE `servicio_config` (
  `id` int(11) NOT NULL,
  `clave` varchar(100) NOT NULL,
  `valor` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sistema`
--

CREATE TABLE `sistema` (
  `id` int(1) NOT NULL,
  `logo` varchar(55) DEFAULT 'logo.jpg',
  `TipoCambio` tinyint(1) DEFAULT 1,
  `version` varchar(15) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `sistema`
--

INSERT INTO `sistema` (`id`, `logo`, `TipoCambio`, `version`) VALUES
(1, 'applogo.png', 0, 'v1.0.5 Estable');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tema`
--

CREATE TABLE `tema` (
  `id` int(5) NOT NULL,
  `nombre` varchar(35) DEFAULT NULL,
  `habilitado` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `tema`
--

INSERT INTO `tema` (`id`, `nombre`, `habilitado`) VALUES
(1, 'Amelia', 0),
(2, 'Cerulean', 0),
(3, 'Cosmo', 0),
(4, 'Cyborg', 0),
(5, 'Darkly', 0),
(6, 'Defecto', 0),
(7, 'Flatly', 0),
(8, 'Journal', 0),
(9, 'Lumen', 0),
(10, 'Paper', 0),
(11, 'Readable', 0),
(12, 'Sandstone', 0),
(13, 'Simplex', 1),
(14, 'Slate', 0),
(15, 'Spacelab', 0),
(16, 'Superhero', 0),
(17, 'United', 0),
(18, 'Yeti', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tramites`
--

CREATE TABLE `tramites` (
  `id` int(11) NOT NULL,
  `id_cliente` int(11) NOT NULL,
  `tipo_tramite` enum('VISA','RESIDENCIA','PASAPORTE','OTRO') NOT NULL,
  `pais_destino` varchar(100) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_entrega` date DEFAULT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `alerta_generada` tinyint(1) DEFAULT 0,
  `estado` enum('PENDIENTE','EN_PROCESO','FINALIZADO','RECHAZADO') DEFAULT 'PENDIENTE',
  `monto_estimado` decimal(10,2) DEFAULT 0.00,
  `observaciones` text DEFAULT NULL,
  `costo` decimal(10,2) DEFAULT 0.00,
  `nro_expediente` varchar(50) DEFAULT NULL,
  `habilitado` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `tramites`
--

INSERT INTO `tramites` (`id`, `id_cliente`, `tipo_tramite`, `pais_destino`, `fecha_inicio`, `fecha_entrega`, `fecha_vencimiento`, `alerta_generada`, `estado`, `monto_estimado`, `observaciones`, `costo`, `nro_expediente`, `habilitado`) VALUES
(1, 11, 'VISA', 'asad', '2025-12-11', '2025-01-22', '2025-12-12', 0, 'EN_PROCESO', '0.04', 'dasda', '0.00', NULL, 0),
(2, 11, 'VISA', 'asad', '2025-12-11', '2025-01-22', '2025-12-12', 0, 'EN_PROCESO', '0.04', 'dasda', '0.00', NULL, 1),
(3, 12, 'VISA', 'asad', '2025-12-05', '2025-12-27', '2025-12-27', 0, 'PENDIENTE', '1000.00', 'hijhji', '0.00', NULL, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

CREATE TABLE `usuario` (
  `id` int(11) NOT NULL,
  `usuario` varchar(50) DEFAULT NULL COMMENT 'Nombre del pseudonimo del usuario del sistema',
  `contrasena` varchar(40) DEFAULT NULL COMMENT 'Contraseña de acceso al sistema',
  `password_hash` varchar(255) DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `failed_attempts` int(11) NOT NULL DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `id_vendedor` int(9) DEFAULT NULL COMMENT 'Identificador numérico para cada uno de los registros de la tabla.(Llave Foránea-Tabla Perfil)(1:1)',
  `id_perfil` int(1) DEFAULT 2,
  `habilitado` tinyint(1) DEFAULT 1 COMMENT 'Determina si el registro es válido para utilizarse o se debe ignorar para operaciones sobre los datos.'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `usuario`
--

INSERT INTO `usuario` (`id`, `usuario`, `contrasena`, `password_hash`, `last_login_at`, `failed_attempts`, `locked_until`, `id_vendedor`, `id_perfil`, `habilitado`) VALUES
(1, 'luis', '123456', NULL, NULL, 0, NULL, 1, 1, 1),
(2, 'Admin', '123456', NULL, NULL, 0, NULL, 2, 2, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vendedores`
--

CREATE TABLE `vendedores` (
  `id` int(9) NOT NULL COMMENT 'Identificador numérico para cada uno de los registros de la tabla.(Llave Primaria)',
  `nombre` varchar(50) DEFAULT NULL COMMENT 'Nombre real de la persona que va a utilizar el sistema',
  `apellido1` varchar(50) DEFAULT NULL COMMENT 'Primer apellido de la persona que va a utilizar el sistema',
  `apellido2` varchar(50) DEFAULT NULL COMMENT 'Segundo apellido de la persona que va a utilizar el sistema',
  `establecimiento` varchar(80) DEFAULT NULL COMMENT 'Nombre del Establecimiento',
  `nota` text DEFAULT NULL COMMENT 'Dirección de la residencia de la persona',
  `provincia` int(15) DEFAULT NULL,
  `canton` int(10) DEFAULT NULL,
  `distrito` int(10) DEFAULT NULL,
  `id_usuario` int(9) DEFAULT NULL COMMENT 'Identificador numérico para cada uno de los registros de la tabla.(Llave Foránea-Tabla Usuario(1:1). Relaciona un usuario específico con un empleado en una relación uno a uno.',
  `habilitado` tinyint(1) DEFAULT 1 COMMENT 'Determina si el registro es válido para utilizarse o se debe ignorar para operaciones sobre los datos.'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `vendedores`
--

INSERT INTO `vendedores` (`id`, `nombre`, `apellido1`, `apellido2`, `establecimiento`, `nota`, `provincia`, `canton`, `distrito`, `id_usuario`, `habilitado`) VALUES
(1, 'Luis', 'Cortés', 'Juárez', 'Qualtiva WebApp', '600 metros este y 75 norte del Liceo Nocturno de Liberia', 5, 501, 50101, 1, 1),
(2, 'Maritza', 'Valdez', 'Sánchez', 'Souvenir #1', '', 1, 101, 10101, 2, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ventas`
--

CREATE TABLE `ventas` (
  `id` int(25) NOT NULL,
  `idfactura` int(25) DEFAULT NULL,
  `producto` int(2) DEFAULT NULL,
  `cantidad` int(5) DEFAULT 1,
  `precio` float DEFAULT NULL,
  `totalprecio` float DEFAULT NULL,
  `vendedor` int(9) DEFAULT NULL,
  `usuario_factura` int(11) DEFAULT NULL,
  `cliente` int(9) DEFAULT 1,
  `nit` varchar(20) DEFAULT NULL,
  `razon_social` varchar(200) DEFAULT NULL,
  `fecha` varchar(10) DEFAULT NULL,
  `hora` varchar(11) DEFAULT NULL,
  `tipo` tinyint(1) DEFAULT NULL,
  `con_factura` tinyint(1) DEFAULT 0,
  `metodo_pago` enum('EFECTIVO','TRANSFERENCIA','DEPOSITO','TARJETA') DEFAULT 'EFECTIVO',
  `id_banco` int(11) DEFAULT NULL,
  `referencia_pago` varchar(40) DEFAULT NULL,
  `nro_comprobante` varchar(30) DEFAULT NULL,
  `id_tramite` int(11) DEFAULT NULL,
  `comision` float DEFAULT 0,
  `habilitada` int(1) DEFAULT 1,
  `anulada` tinyint(1) DEFAULT 0,
  `total` decimal(12,2) DEFAULT NULL,
  `total_comision` decimal(12,2) DEFAULT NULL,
  `iva_monto` decimal(10,2) DEFAULT 0.00,
  `impuesto_monto` decimal(10,2) DEFAULT 0.00,
  `comision_monto` decimal(10,2) DEFAULT 0.00,
  `id_asiento` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `ventas`
--

INSERT INTO `ventas` (`id`, `idfactura`, `producto`, `cantidad`, `precio`, `totalprecio`, `vendedor`, `usuario_factura`, `cliente`, `nit`, `razon_social`, `fecha`, `hora`, `tipo`, `con_factura`, `metodo_pago`, `id_banco`, `referencia_pago`, `nro_comprobante`, `id_tramite`, `comision`, `habilitada`, `anulada`, `total`, `total_comision`, `iva_monto`, `impuesto_monto`, `comision_monto`, `id_asiento`) VALUES
(1, 1, 3, 1, 1650, 1650, 1, NULL, 1, NULL, NULL, '21-11-2025', '02:40:10 am', NULL, 0, 'EFECTIVO', NULL, NULL, NULL, NULL, 0, 1, 0, NULL, NULL, '0.00', '0.00', '0.00', NULL),
(2, 2, 4, 1, 720, 720, 1, NULL, 1, NULL, NULL, '21-11-2025', '02:43:43 am', NULL, 0, 'EFECTIVO', NULL, NULL, NULL, NULL, 0, 1, 0, NULL, NULL, '0.00', '0.00', '0.00', NULL),
(3, 3, 4, 1, 720, 720, 1, NULL, 1, NULL, NULL, '21-11-2025', '04:56:27 am', NULL, 0, 'EFECTIVO', NULL, NULL, NULL, NULL, 0, 1, 0, NULL, NULL, '0.00', '0.00', '0.00', NULL),
(4, 4, 4, 2, 720, 1440, 1, NULL, 1, NULL, NULL, '21-11-2025', '05:03:22 am', NULL, 0, 'EFECTIVO', NULL, NULL, NULL, NULL, 0, 1, 0, NULL, NULL, '0.00', '0.00', '0.00', NULL),
(5, 5, 3, 1, 1650, 1650, 1, NULL, 1, NULL, NULL, '21-11-2025', '05:04:06 am', NULL, 0, 'EFECTIVO', NULL, NULL, NULL, NULL, 0, 1, 0, NULL, NULL, '0.00', '0.00', '0.00', NULL),
(6, 6, 5, 2, 1150, 2300, 1, NULL, 1, NULL, NULL, '21-11-2025', '04:43:44 pm', NULL, 0, 'EFECTIVO', NULL, NULL, NULL, NULL, 0, 1, 0, NULL, NULL, '0.00', '0.00', '0.00', NULL),
(7, 7, 2, 1, 880, 880, 1, NULL, 1, NULL, NULL, '21-11-2025', '07:51:00 pm', NULL, 0, 'EFECTIVO', NULL, NULL, NULL, NULL, 0, 1, 0, NULL, NULL, '0.00', '0.00', '0.00', NULL),
(8, 9, 3, 1, 1650, 1650, 1, NULL, 1, NULL, NULL, '21-11-2025', '07:52:35 pm', NULL, 0, 'EFECTIVO', NULL, NULL, NULL, NULL, 0, 0, 0, NULL, NULL, '0.00', '0.00', '0.00', NULL),
(9, 10, 3, 2, 1650, 3300, 1, NULL, 1, NULL, NULL, '22-11-2025', '10:29:00 pm', NULL, 0, 'EFECTIVO', NULL, NULL, NULL, NULL, 0, 1, 0, NULL, NULL, '0.00', '0.00', '0.00', NULL),
(10, 11, 2, 1, 880, 880, 1, NULL, 1, NULL, NULL, '23-11-2025', '05:27:52 am', NULL, 0, 'EFECTIVO', NULL, NULL, NULL, NULL, 0, 1, 0, NULL, NULL, '0.00', '0.00', '0.00', NULL),
(11, 12, 2, 4, 880, 3520, 1, NULL, 1, NULL, NULL, '24-11-2025', '12:19:02 am', NULL, 0, 'EFECTIVO', NULL, NULL, NULL, NULL, 0, 1, 0, NULL, NULL, '0.00', '0.00', '0.00', NULL),
(12, 13, 4, 1, 720, 720, 1, NULL, 1, NULL, NULL, '24-11-2025', '12:26:14 am', NULL, 0, 'EFECTIVO', NULL, NULL, NULL, NULL, 0, 1, 0, NULL, NULL, '0.00', '0.00', '0.00', NULL),
(13, 14, 3, 2, 1650, 3300, 1, NULL, 1, NULL, NULL, '24-11-2025', '12:26:53 am', 1, 0, '', NULL, NULL, NULL, NULL, 0, 1, 0, NULL, NULL, '0.00', '0.00', '0.00', NULL),
(14, 14, 4, 1, 720, 720, 1, NULL, 1, NULL, NULL, '24-11-2025', '12:32:41 am', 1, 0, '', NULL, NULL, NULL, NULL, 0, 1, 0, NULL, NULL, '0.00', '0.00', '0.00', NULL),
(15, 15, 4, 1, 720, 720, 1, NULL, 1, NULL, NULL, '24-11-2025', '12:36:10 am', 1, 0, '', NULL, NULL, NULL, NULL, 0, 1, 0, NULL, NULL, '0.00', '0.00', '0.00', NULL),
(16, 16, 3, 1, 1650, 1650, 1, NULL, 1, NULL, NULL, '24-11-2025', '12:44:15 am', 1, 0, '', NULL, NULL, NULL, NULL, 0, 1, 0, NULL, NULL, '0.00', '0.00', '0.00', NULL),
(17, 17, 4, 1, 720, 720, 2, NULL, 1, NULL, NULL, '24-11-2025', '01:13:24 am', 1, 0, '', NULL, NULL, NULL, NULL, 0, 1, 0, NULL, NULL, '0.00', '0.00', '0.00', NULL),
(18, 18, 2, 1, 880, 880, 1, NULL, 1, NULL, NULL, '24-11-2025', '01:37:59 am', 1, 0, '', NULL, NULL, NULL, NULL, 0, 1, 0, NULL, NULL, '0.00', '0.00', '0.00', NULL),
(19, 19, 5, 1, 1150, 1150, 1, NULL, 2, NULL, NULL, '24-11-2025', '07:35:15 pm', NULL, 0, 'EFECTIVO', NULL, NULL, NULL, NULL, 0, 1, 0, NULL, NULL, '0.00', '0.00', '0.00', NULL),
(20, 20, 4, 1, 720, 720, 2, NULL, 2, NULL, NULL, '24-11-2025', '07:41:29 pm', NULL, 0, 'EFECTIVO', NULL, NULL, NULL, NULL, 0, 1, 0, NULL, NULL, '0.00', '0.00', '0.00', NULL),
(21, 21, 3, 6, 1650, 9900, 1, NULL, 2, NULL, NULL, '25-11-2025', '04:12:07 am', 1, 0, '', NULL, NULL, NULL, NULL, 0, 1, 0, NULL, NULL, '0.00', '0.00', '0.00', NULL),
(22, 23, 2, 1, 880, 880, 1, 1, 2, NULL, NULL, '25-11-2025', '04:36:08 am', 1, 0, 'EFECTIVO', NULL, NULL, '1', NULL, 0, 1, 0, NULL, NULL, '0.00', '0.00', '0.00', NULL),
(23, 23, 3, 1, 1650, 1650, 1, 1, 2, NULL, NULL, '25-11-2025', '04:39:58 am', 1, 0, 'EFECTIVO', NULL, NULL, '1', NULL, 0, 1, 0, NULL, NULL, '0.00', '0.00', '0.00', NULL),
(24, 24, 5, 1, 1150, 1150, 1, 1, 2, NULL, NULL, '25-11-2025', '06:49:47 am', 1, 0, 'EFECTIVO', NULL, NULL, '2', NULL, 0, 1, 0, NULL, NULL, '0.00', '0.00', '0.00', NULL),
(25, 25, 3, 1, 1650, 1650, 1, 1, 2, NULL, NULL, '25-11-2025', '05:38:44 pm', 1, 0, 'EFECTIVO', NULL, NULL, '3', NULL, 0, 1, 0, NULL, NULL, '0.00', '0.00', '0.00', NULL),
(26, 26, 3, 3, 1650, 4950, 1, 1, 2, NULL, NULL, '27-11-2025', '04:59:38 am', 1, 0, 'EFECTIVO', NULL, NULL, '4', NULL, 0, 1, 0, NULL, NULL, '0.00', '0.00', '0.00', NULL),
(27, 27, 3, 1, 1650, 1650, 1, 1, 1, NULL, NULL, '27-11-2025', '07:37:09 am', 1, 0, 'EFECTIVO', NULL, NULL, '5', NULL, 0, 1, 0, NULL, NULL, '0.00', '0.00', '0.00', NULL),
(28, 28, 4, 1, 720, 720, 1, 1, 2, NULL, NULL, '27-11-2025', '08:09:32 pm', 1, 0, 'EFECTIVO', NULL, NULL, '6', NULL, 0, 1, 0, NULL, NULL, '0.00', '0.00', '0.00', NULL),
(29, 29, 2, 2, 880, 1760, 1, 1, 1, NULL, NULL, '28-11-2025', '04:38:34 am', 1, 0, 'EFECTIVO', NULL, NULL, '7', NULL, 0, 1, 0, NULL, NULL, '0.00', '0.00', '0.00', NULL),
(30, 30, 3, 1, 1650, 1650, 1, 1, 1, NULL, NULL, '28-11-2025', '04:40:07 am', 1, 0, 'EFECTIVO', NULL, NULL, '8', NULL, 0, 1, 0, NULL, NULL, '0.00', '0.00', '0.00', NULL),
(31, 31, 4, 1, 720, 720, 1, 1, 1, NULL, NULL, '28-11-2025', '04:42:13 am', 1, 0, 'EFECTIVO', NULL, NULL, '9', NULL, 0, 1, 0, NULL, NULL, '0.00', '0.00', '0.00', NULL),
(32, 32, 2, 1, 880, 880, 1, 1, 1, NULL, NULL, '28-11-2025', '05:21:19 am', 1, 0, 'EFECTIVO', NULL, NULL, '10', NULL, 0, 1, 0, NULL, NULL, '0.00', '0.00', '0.00', NULL),
(33, 32, 3, 1, 1650, 1650, 1, 1, 1, NULL, NULL, '28-11-2025', '05:23:00 am', 1, 0, 'EFECTIVO', NULL, NULL, '10', NULL, 0, 1, 0, NULL, NULL, '0.00', '0.00', '0.00', NULL),
(34, 33, 3, 1, 1650, 1650, 1, 1, 1, NULL, NULL, '28-11-2025', '05:41:27 am', 1, 0, 'EFECTIVO', NULL, NULL, '11', NULL, 0, 1, 0, NULL, NULL, '0.00', '0.00', '0.00', NULL),
(35, 34, 4, 1, 720, 720, 1, 1, 1, NULL, NULL, '28-11-2025', '05:47:07 am', 1, 0, 'EFECTIVO', NULL, NULL, '12', NULL, 0, 1, 0, NULL, NULL, '0.00', '0.00', '0.00', NULL),
(36, 35, 4, 1, 720, 720, 1, 1, 1, NULL, NULL, '28-11-2025', '06:03:59 am', 1, 0, 'EFECTIVO', NULL, NULL, '13', NULL, 0, 1, 0, NULL, NULL, '0.00', '0.00', '0.00', NULL),
(37, 36, 2, 1, 880, 880, 1, 1, 1, NULL, NULL, '28-11-2025', '06:46:34 am', 1, 0, 'EFECTIVO', NULL, NULL, '14', NULL, 0, 1, 0, NULL, NULL, '0.00', '0.00', '0.00', NULL),
(38, 37, 3, 1, 1650, 1650, 1, 1, 1, NULL, NULL, '28-11-2025', '06:50:16 am', 1, 0, 'EFECTIVO', NULL, NULL, '15', NULL, 0, 1, 0, NULL, NULL, '0.00', '0.00', '0.00', NULL),
(39, 38, 3, 1, 1650, 1650, 1, 1, 1, NULL, NULL, '2025-11-28', '08:57:29', 1, 0, 'EFECTIVO', NULL, NULL, '16', NULL, 0, 1, 0, NULL, NULL, '0.00', '0.00', '0.00', NULL),
(40, 39, 3, 12, 1650, 19800, 1, 1, 2, NULL, NULL, '2025-11-29', '17:06:51', 1, 0, 'EFECTIVO', NULL, NULL, '17', NULL, 0, 1, 0, NULL, NULL, '0.00', '0.00', '0.00', NULL),
(41, 40, 2, 1, 880, 880, 1, 1, 2, NULL, NULL, '2025-11-29', '19:14:25', 1, 0, 'EFECTIVO', NULL, NULL, '18', NULL, 0, 1, 0, NULL, NULL, '0.00', '0.00', '0.00', NULL),
(42, 41, 3, 1, 1650, 1650, 1, 1, 2, NULL, NULL, '2025-11-29', '22:39:33', 1, 0, 'EFECTIVO', NULL, NULL, '19', NULL, 0, 1, 0, NULL, NULL, '0.00', '0.00', '0.00', NULL),
(43, 42, 3, 2, 1650, 3300, 1, 1, 1, NULL, NULL, '2025-11-30', '01:01:18', 1, 0, 'EFECTIVO', NULL, NULL, '20', NULL, 0, 1, 0, NULL, NULL, '0.00', '0.00', '0.00', NULL),
(44, 43, 3, 1, 1650, 1650, 1, 1, 1, NULL, NULL, '2025-11-30', '01:09:22', 1, 0, 'EFECTIVO', NULL, NULL, '21', NULL, 0, 1, 0, NULL, NULL, '0.00', '0.00', '0.00', NULL),
(45, 43, 2, 1, 880, 880, 1, 1, 1, NULL, NULL, '2025-11-30', '01:09:22', 1, 0, 'EFECTIVO', NULL, NULL, '21', NULL, 0, 1, 0, NULL, NULL, '0.00', '0.00', '0.00', NULL),
(46, 44, 2, 1, 880, 880, 1, 1, 1, NULL, NULL, '2025-11-30', '02:21:33', 1, 0, 'EFECTIVO', NULL, NULL, '22', NULL, 0, 1, 0, NULL, NULL, '0.00', '0.00', '0.00', NULL),
(47, 44, 3, 2, 1650, 3300, 1, 1, 1, NULL, NULL, '2025-11-30', '02:21:33', 1, 0, 'EFECTIVO', NULL, NULL, '22', NULL, 0, 1, 0, NULL, NULL, '0.00', '0.00', '0.00', NULL),
(48, 44, 4, 1, 720, 720, 1, 1, 1, NULL, NULL, '2025-11-30', '02:21:33', 1, 0, 'EFECTIVO', NULL, NULL, '22', NULL, 0, 1, 0, NULL, NULL, '0.00', '0.00', '0.00', NULL),
(49, 44, 8, 1, 180, 180, 1, 1, 1, NULL, NULL, '2025-11-30', '02:21:33', 1, 0, 'EFECTIVO', NULL, NULL, '22', NULL, 0, 1, 0, NULL, NULL, '0.00', '0.00', '0.00', NULL),
(50, 44, 3, 1, 1650, 1650, 1, 1, 1, NULL, NULL, '2025-11-30', '02:21:33', 1, 0, 'EFECTIVO', NULL, NULL, '22', NULL, 0, 1, 0, NULL, NULL, '0.00', '0.00', '0.00', NULL),
(51, 45, 2, 1, 880, 880, 1, 1, 2, NULL, NULL, '2025-11-30', '03:59:05', 1, 0, 'EFECTIVO', NULL, NULL, '23', NULL, 0, 1, 0, NULL, NULL, '0.00', '0.00', '0.00', NULL),
(52, 46, 6, 1, 350, 350, 1, 1, 1, NULL, NULL, '2025-11-30', '05:50:49', 1, 0, 'EFECTIVO', NULL, NULL, '24', NULL, 0, 1, 0, NULL, NULL, '0.00', '0.00', '0.00', NULL),
(53, 46, 7, 1, 220, 220, 1, 1, 1, NULL, NULL, '2025-11-30', '05:50:49', 1, 0, 'EFECTIVO', NULL, NULL, '24', NULL, 0, 1, 0, NULL, NULL, '0.00', '0.00', '0.00', NULL),
(54, 47, 3, 1, 1650, 1650, 1, 1, 2, NULL, NULL, '2025-11-30', '17:44:32', 1, 0, 'EFECTIVO', NULL, NULL, '25', NULL, 0, 1, 0, NULL, NULL, '0.00', '0.00', '0.00', NULL),
(55, 48, 3, 1, 1650, 1650, 1, 1, 1, NULL, NULL, '2025-11-30', '18:20:08', 1, 0, 'EFECTIVO', NULL, NULL, '26', NULL, 0, 1, 0, NULL, NULL, '0.00', '0.00', '0.00', NULL),
(56, 48, 2, 1, 880, 880, 1, 1, 1, NULL, NULL, '2025-11-30', '18:20:08', 1, 0, 'EFECTIVO', NULL, NULL, '26', NULL, 0, 1, 0, NULL, NULL, '0.00', '0.00', '0.00', NULL),
(57, 49, 2, 1, 880, 880, 1, 1, 2, NULL, NULL, '2025-11-30', '20:38:59', 1, 0, 'EFECTIVO', NULL, NULL, '27', NULL, 0, 1, 0, NULL, NULL, '0.00', '0.00', '0.00', NULL),
(58, 50, 3, 1, 1650, 1650, 1, 1, 2, NULL, NULL, '2025-11-30', '20:55:19', 1, 0, 'EFECTIVO', NULL, NULL, '28', NULL, 0, 1, 0, NULL, NULL, '0.00', '0.00', '0.00', NULL),
(59, 50, 0, 0, 0, 0, 1, 1, 2, NULL, NULL, '2025-11-30', '20:55:19', 1, 0, 'EFECTIVO', NULL, NULL, '28', NULL, 0, 1, 0, NULL, NULL, '0.00', '0.00', '0.00', NULL),
(60, 51, 3, 2, 1650, 3300, 1, 1, 2, NULL, NULL, '2025-11-30', '21:51:12', 1, 0, 'EFECTIVO', NULL, NULL, '29', NULL, 0, 1, 0, NULL, NULL, '0.00', '0.00', '0.00', NULL),
(61, 51, 6, 1, 350, 350, 1, 1, 2, NULL, NULL, '2025-11-30', '21:51:12', 1, 0, 'EFECTIVO', NULL, NULL, '29', NULL, 0, 1, 0, NULL, NULL, '0.00', '0.00', '0.00', NULL),
(62, 52, 101, 1, 1200, 1200, 1, 1, 100, NULL, NULL, '2025-12-02', '18:02:44', 1, 0, 'EFECTIVO', NULL, NULL, '30', NULL, 0, 1, 0, NULL, NULL, '0.00', '0.00', '0.00', NULL),
(63, 53, 102, 1, 250, 250, 1, 1, 11, NULL, NULL, '2025-12-02', '19:15:36', 1, 0, 'EFECTIVO', NULL, NULL, '31', NULL, 0, 1, 0, NULL, NULL, '0.00', '0.00', '0.00', NULL),
(64, 54, 102, 1, 250, 250, 1, 1, 11, NULL, NULL, '2025-12-03', '04:30:03', 1, 0, 'EFECTIVO', NULL, NULL, '32', NULL, 0, 1, 0, NULL, NULL, '0.00', '0.00', '0.00', NULL),
(65, 55, 103, 1, 500, 500, 1, 1, 11, NULL, NULL, '2025-12-03', '16:29:06', 1, 0, 'EFECTIVO', NULL, NULL, '33', NULL, 0, 1, 0, NULL, NULL, '0.00', '0.00', '0.00', NULL),
(66, 56, 101, 1, 1200, 1200, 1, 1, 12, NULL, NULL, '2025-12-05', '17:22:24', 1, 0, 'EFECTIVO', NULL, NULL, '34', NULL, 0, 1, 0, NULL, NULL, '0.00', '0.00', '0.00', NULL),
(67, 57, 102, 4, 250, 1000, 1, 1, 12, NULL, NULL, '2025-12-05', '20:43:36', 1, 0, 'EFECTIVO', NULL, NULL, '35', NULL, 0, 1, 0, NULL, NULL, '0.00', '0.00', '0.00', NULL),
(68, 58, 102, 1, 250, 250, 1, 1, 12, NULL, NULL, '2025-12-05', '20:44:04', 1, 0, 'EFECTIVO', NULL, NULL, '36', NULL, 0, 1, 0, NULL, NULL, '0.00', '0.00', '0.00', NULL),
(69, 59, 101, 1, 1200, 1200, 1, 1, 12, NULL, NULL, '2025-12-05', '20:46:27', 1, 0, 'EFECTIVO', NULL, NULL, '37', NULL, 0, 1, 0, NULL, NULL, '0.00', '0.00', '0.00', NULL),
(70, 59, 103, 2, 500, 1000, 1, 1, 12, NULL, NULL, '2025-12-05', '20:46:27', 1, 0, 'EFECTIVO', NULL, NULL, '37', NULL, 0, 1, 0, NULL, NULL, '0.00', '0.00', '0.00', NULL),
(71, 60, 102, 1, 250, 250, 1, 1, 12, '3131312', 'wqe', '2025-12-05', '23:16:17', 1, 1, 'EFECTIVO', NULL, NULL, '35', NULL, 0, 1, 0, NULL, NULL, '32.50', '7.50', '0.00', NULL),
(72, 61, 102, 1, 250, 250, 1, 1, 11, '3131312', 'ddada', '2025-12-09', '20:44:57', 1, 1, 'EFECTIVO', NULL, NULL, '34', NULL, 0, 1, 0, NULL, NULL, '32.50', '7.50', '0.00', NULL);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `bancos`
--
ALTER TABLE `bancos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `banco_movimientos`
--
ALTER TABLE `banco_movimientos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_bm_banco` (`id_banco`),
  ADD KEY `fk_bm_venta` (`id_venta`);

--
-- Indices de la tabla `caja`
--
ALTER TABLE `caja`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `cajachica`
--
ALTER TABLE `cajachica`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `cajachicaregistros`
--
ALTER TABLE `cajachicaregistros`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `cajamovimientos`
--
ALTER TABLE `cajamovimientos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `cajaregistros`
--
ALTER TABLE `cajaregistros`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `cajatmp`
--
ALTER TABLE `cajatmp`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `caja_chica_movimientos`
--
ALTER TABLE `caja_chica_movimientos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_fecha` (`fecha`),
  ADD KEY `idx_responsable` (`responsable`);

--
-- Indices de la tabla `caja_general_movimientos`
--
ALTER TABLE `caja_general_movimientos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_fecha` (`fecha`),
  ADD KEY `idx_metodo` (`metodo_pago`),
  ADD KEY `idx_banco` (`id_banco`);

--
-- Indices de la tabla `canton`
--
ALTER TABLE `canton`
  ADD PRIMARY KEY (`id`) USING BTREE,
  ADD KEY `FK_CANTON_PROVINCIA` (`id_provincia`);

--
-- Indices de la tabla `carrito_cotizacion`
--
ALTER TABLE `carrito_cotizacion`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `categorias_servicios`
--
ALTER TABLE `categorias_servicios`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `cierre`
--
ALTER TABLE `cierre`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `cliente`
--
ALTER TABLE `cliente`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `comisiones_vendedores`
--
ALTER TABLE `comisiones_vendedores`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `comision_detalle`
--
ALTER TABLE `comision_detalle`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_venta` (`id_venta`),
  ADD KEY `id_vendedor` (`id_vendedor`);

--
-- Indices de la tabla `comprobante`
--
ALTER TABLE `comprobante`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_venta` (`id_venta`);

--
-- Indices de la tabla `contabilidad_cuentas`
--
ALTER TABLE `contabilidad_cuentas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`);

--
-- Indices de la tabla `contabilidad_diario`
--
ALTER TABLE `contabilidad_diario`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `contabilidad_diario_detalle`
--
ALTER TABLE `contabilidad_diario_detalle`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_diario` (`id_diario`),
  ADD KEY `id_cuenta` (`id_cuenta`);

--
-- Indices de la tabla `contabilidad_enlace_pago`
--
ALTER TABLE `contabilidad_enlace_pago`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_asiento` (`id_asiento`);

--
-- Indices de la tabla `contabilidad_enlace_venta`
--
ALTER TABLE `contabilidad_enlace_venta`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_asiento` (`id_asiento`);

--
-- Indices de la tabla `cotizacion`
--
ALTER TABLE `cotizacion`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `cotizacion_detalle`
--
ALTER TABLE `cotizacion_detalle`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_cotizacion` (`id_cotizacion`);

--
-- Indices de la tabla `credito`
--
ALTER TABLE `credito`
  ADD PRIMARY KEY (`id`),
  ADD KEY `FK_credito` (`id_cliente`);

--
-- Indices de la tabla `departamento`
--
ALTER TABLE `departamento`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `distrito`
--
ALTER TABLE `distrito`
  ADD PRIMARY KEY (`id`),
  ADD KEY `FK_DISTRITO_CANTON` (`id_canton`);

--
-- Indices de la tabla `entradasalidaregistro`
--
ALTER TABLE `entradasalidaregistro`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `establecimiento`
--
ALTER TABLE `establecimiento`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `factura`
--
ALTER TABLE `factura`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `facturacion_siat_config`
--
ALTER TABLE `facturacion_siat_config`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `iva`
--
ALTER TABLE `iva`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `kardex`
--
ALTER TABLE `kardex`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `log_usuarios`
--
ALTER TABLE `log_usuarios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_log_usuario` (`id_usuario`),
  ADD KEY `idx_log_usuarios_fecha` (`fecha`),
  ADD KEY `idx_log_usuarios_usuario_fecha` (`id_usuario`,`fecha`);

--
-- Indices de la tabla `medida`
--
ALTER TABLE `medida`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `moneda`
--
ALTER TABLE `moneda`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `movimientostipo`
--
ALTER TABLE `movimientostipo`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_notif_tramite` (`id_tramite`);

--
-- Indices de la tabla `pagos_proveedor`
--
ALTER TABLE `pagos_proveedor`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pp_proveedor` (`id_proveedor`),
  ADD KEY `fk_pp_banco` (`id_banco`);

--
-- Indices de la tabla `perfil`
--
ALTER TABLE `perfil`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `perfil_permiso`
--
ALTER TABLE `perfil_permiso`
  ADD PRIMARY KEY (`id_perfil`,`id_permiso`),
  ADD KEY `fk_pp_permiso` (`id_permiso`);

--
-- Indices de la tabla `permiso`
--
ALTER TABLE `permiso`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`);

--
-- Indices de la tabla `producto`
--
ALTER TABLE `producto`
  ADD PRIMARY KEY (`id`),
  ADD KEY `FK_producto` (`departamento`),
  ADD KEY `FK_id_proveedor` (`proveedor`),
  ADD KEY `fk_producto_proveedor` (`id_proveedor`);

--
-- Indices de la tabla `proveedor`
--
ALTER TABLE `proveedor`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_proveedor_tipo` (`tipo_proveedor`),
  ADD KEY `idx_proveedor_habilitado` (`habilitado`);

--
-- Indices de la tabla `proveedor_factura`
--
ALTER TABLE `proveedor_factura`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_factura_estado` (`estado`),
  ADD KEY `idx_factura_proveedor` (`id_proveedor`);

--
-- Indices de la tabla `proveedor_movimiento`
--
ALTER TABLE `proveedor_movimiento`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_proveedor` (`id_proveedor`),
  ADD KEY `fk_mov_factura` (`id_factura`),
  ADD KEY `fk_mov_pago` (`id_pago`);

--
-- Indices de la tabla `proveedor_pago`
--
ALTER TABLE `proveedor_pago`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pago_proveedor` (`id_proveedor`),
  ADD KEY `idx_pago_factura` (`id_factura`);

--
-- Indices de la tabla `proveedor_saldos`
--
ALTER TABLE `proveedor_saldos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `provincia`
--
ALTER TABLE `provincia`
  ADD PRIMARY KEY (`id`) USING BTREE;

--
-- Indices de la tabla `servicios`
--
ALTER TABLE `servicios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `categoria_id` (`categoria_id`);

--
-- Indices de la tabla `servicio_config`
--
ALTER TABLE `servicio_config`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `sistema`
--
ALTER TABLE `sistema`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `tema`
--
ALTER TABLE `tema`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `tramites`
--
ALTER TABLE `tramites`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_tramites_cliente` (`id_cliente`);

--
-- Indices de la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`id`),
  ADD KEY `FK_usuario` (`id_vendedor`),
  ADD KEY `FK_perfil` (`id_perfil`);

--
-- Indices de la tabla `vendedores`
--
ALTER TABLE `vendedores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `FK_usuario` (`id_usuario`);

--
-- Indices de la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `bancos`
--
ALTER TABLE `bancos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `banco_movimientos`
--
ALTER TABLE `banco_movimientos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `caja`
--
ALTER TABLE `caja`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `cajachica`
--
ALTER TABLE `cajachica`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `cajachicaregistros`
--
ALTER TABLE `cajachicaregistros`
  MODIFY `id` int(15) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cajamovimientos`
--
ALTER TABLE `cajamovimientos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cajaregistros`
--
ALTER TABLE `cajaregistros`
  MODIFY `id` int(15) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `cajatmp`
--
ALTER TABLE `cajatmp`
  MODIFY `id` int(25) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT de la tabla `caja_chica_movimientos`
--
ALTER TABLE `caja_chica_movimientos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `caja_general_movimientos`
--
ALTER TABLE `caja_general_movimientos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT de la tabla `carrito_cotizacion`
--
ALTER TABLE `carrito_cotizacion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `categorias_servicios`
--
ALTER TABLE `categorias_servicios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `cierre`
--
ALTER TABLE `cierre`
  MODIFY `id` int(25) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cliente`
--
ALTER TABLE `cliente`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `comisiones_vendedores`
--
ALTER TABLE `comisiones_vendedores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `comision_detalle`
--
ALTER TABLE `comision_detalle`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `comprobante`
--
ALTER TABLE `comprobante`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `contabilidad_cuentas`
--
ALTER TABLE `contabilidad_cuentas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `contabilidad_diario`
--
ALTER TABLE `contabilidad_diario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `contabilidad_diario_detalle`
--
ALTER TABLE `contabilidad_diario_detalle`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `contabilidad_enlace_pago`
--
ALTER TABLE `contabilidad_enlace_pago`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `contabilidad_enlace_venta`
--
ALTER TABLE `contabilidad_enlace_venta`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cotizacion`
--
ALTER TABLE `cotizacion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=110;

--
-- AUTO_INCREMENT de la tabla `cotizacion_detalle`
--
ALTER TABLE `cotizacion_detalle`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=110;

--
-- AUTO_INCREMENT de la tabla `credito`
--
ALTER TABLE `credito`
  MODIFY `id` int(25) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `departamento`
--
ALTER TABLE `departamento`
  MODIFY `id` int(9) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `entradasalidaregistro`
--
ALTER TABLE `entradasalidaregistro`
  MODIFY `id` int(15) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `establecimiento`
--
ALTER TABLE `establecimiento`
  MODIFY `id` int(9) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `factura`
--
ALTER TABLE `factura`
  MODIFY `id` int(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT de la tabla `facturacion_siat_config`
--
ALTER TABLE `facturacion_siat_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `iva`
--
ALTER TABLE `iva`
  MODIFY `id` int(9) NOT NULL AUTO_INCREMENT COMMENT 'Identificador numérico para cada uno de los registros de la tabla.(Llave Primaria)', AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `kardex`
--
ALTER TABLE `kardex`
  MODIFY `id` int(5) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `log_usuarios`
--
ALTER TABLE `log_usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `medida`
--
ALTER TABLE `medida`
  MODIFY `id` int(9) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `moneda`
--
ALTER TABLE `moneda`
  MODIFY `id` int(9) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `movimientostipo`
--
ALTER TABLE `movimientostipo`
  MODIFY `id` int(2) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pagos_proveedor`
--
ALTER TABLE `pagos_proveedor`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `perfil`
--
ALTER TABLE `perfil`
  MODIFY `id` int(9) NOT NULL AUTO_INCREMENT COMMENT 'Identificador numérico para cada uno de los registros de la tabla.(Llave Primaria)', AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `permiso`
--
ALTER TABLE `permiso`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de la tabla `producto`
--
ALTER TABLE `producto`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=104;

--
-- AUTO_INCREMENT de la tabla `proveedor`
--
ALTER TABLE `proveedor`
  MODIFY `id` int(9) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de la tabla `proveedor_factura`
--
ALTER TABLE `proveedor_factura`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `proveedor_movimiento`
--
ALTER TABLE `proveedor_movimiento`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `proveedor_pago`
--
ALTER TABLE `proveedor_pago`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `proveedor_saldos`
--
ALTER TABLE `proveedor_saldos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `provincia`
--
ALTER TABLE `provincia`
  MODIFY `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `servicios`
--
ALTER TABLE `servicios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `servicio_config`
--
ALTER TABLE `servicio_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `sistema`
--
ALTER TABLE `sistema`
  MODIFY `id` int(1) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `tema`
--
ALTER TABLE `tema`
  MODIFY `id` int(5) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de la tabla `tramites`
--
ALTER TABLE `tramites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `usuario`
--
ALTER TABLE `usuario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `vendedores`
--
ALTER TABLE `vendedores`
  MODIFY `id` int(9) NOT NULL AUTO_INCREMENT COMMENT 'Identificador numérico para cada uno de los registros de la tabla.(Llave Primaria)', AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `ventas`
--
ALTER TABLE `ventas`
  MODIFY `id` int(25) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `banco_movimientos`
--
ALTER TABLE `banco_movimientos`
  ADD CONSTRAINT `fk_bm_banco` FOREIGN KEY (`id_banco`) REFERENCES `bancos` (`id`),
  ADD CONSTRAINT `fk_bm_venta` FOREIGN KEY (`id_venta`) REFERENCES `ventas` (`id`);

--
-- Filtros para la tabla `canton`
--
ALTER TABLE `canton`
  ADD CONSTRAINT `FK_CANTON_PROVINCIA` FOREIGN KEY (`id_provincia`) REFERENCES `provincia` (`id`);

--
-- Filtros para la tabla `comision_detalle`
--
ALTER TABLE `comision_detalle`
  ADD CONSTRAINT `comision_detalle_ibfk_1` FOREIGN KEY (`id_venta`) REFERENCES `ventas` (`id`),
  ADD CONSTRAINT `comision_detalle_ibfk_2` FOREIGN KEY (`id_vendedor`) REFERENCES `usuario` (`id`);

--
-- Filtros para la tabla `comprobante`
--
ALTER TABLE `comprobante`
  ADD CONSTRAINT `comprobante_ibfk_1` FOREIGN KEY (`id_venta`) REFERENCES `ventas` (`id`);

--
-- Filtros para la tabla `contabilidad_diario_detalle`
--
ALTER TABLE `contabilidad_diario_detalle`
  ADD CONSTRAINT `contabilidad_diario_detalle_ibfk_1` FOREIGN KEY (`id_diario`) REFERENCES `contabilidad_diario` (`id`),
  ADD CONSTRAINT `contabilidad_diario_detalle_ibfk_2` FOREIGN KEY (`id_cuenta`) REFERENCES `contabilidad_cuentas` (`id`);

--
-- Filtros para la tabla `contabilidad_enlace_pago`
--
ALTER TABLE `contabilidad_enlace_pago`
  ADD CONSTRAINT `contabilidad_enlace_pago_ibfk_1` FOREIGN KEY (`id_asiento`) REFERENCES `contabilidad_diario` (`id`);

--
-- Filtros para la tabla `contabilidad_enlace_venta`
--
ALTER TABLE `contabilidad_enlace_venta`
  ADD CONSTRAINT `contabilidad_enlace_venta_ibfk_1` FOREIGN KEY (`id_asiento`) REFERENCES `contabilidad_diario` (`id`);

--
-- Filtros para la tabla `distrito`
--
ALTER TABLE `distrito`
  ADD CONSTRAINT `FK_DISTRITO_CANTON` FOREIGN KEY (`id_canton`) REFERENCES `canton` (`id`);

--
-- Filtros para la tabla `log_usuarios`
--
ALTER TABLE `log_usuarios`
  ADD CONSTRAINT `fk_log_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id`);

--
-- Filtros para la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD CONSTRAINT `fk_notif_tramite` FOREIGN KEY (`id_tramite`) REFERENCES `tramites` (`id`);

--
-- Filtros para la tabla `pagos_proveedor`
--
ALTER TABLE `pagos_proveedor`
  ADD CONSTRAINT `fk_pp_banco` FOREIGN KEY (`id_banco`) REFERENCES `bancos` (`id`),
  ADD CONSTRAINT `fk_pp_proveedor` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedor` (`id`);

--
-- Filtros para la tabla `perfil_permiso`
--
ALTER TABLE `perfil_permiso`
  ADD CONSTRAINT `fk_pp_perfil` FOREIGN KEY (`id_perfil`) REFERENCES `perfil` (`id`),
  ADD CONSTRAINT `fk_pp_permiso` FOREIGN KEY (`id_permiso`) REFERENCES `permiso` (`id`);

--
-- Filtros para la tabla `producto`
--
ALTER TABLE `producto`
  ADD CONSTRAINT `FK_id_categoria` FOREIGN KEY (`departamento`) REFERENCES `departamento` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_id_proveedor` FOREIGN KEY (`proveedor`) REFERENCES `proveedor` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_producto_proveedor` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedor` (`id`);

--
-- Filtros para la tabla `proveedor_factura`
--
ALTER TABLE `proveedor_factura`
  ADD CONSTRAINT `proveedor_factura_ibfk_1` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedor` (`id`);

--
-- Filtros para la tabla `proveedor_movimiento`
--
ALTER TABLE `proveedor_movimiento`
  ADD CONSTRAINT `fk_mov_factura` FOREIGN KEY (`id_factura`) REFERENCES `proveedor_factura` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_mov_pago` FOREIGN KEY (`id_pago`) REFERENCES `proveedor_pago` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `proveedor_movimiento_ibfk_1` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedor` (`id`);

--
-- Filtros para la tabla `proveedor_pago`
--
ALTER TABLE `proveedor_pago`
  ADD CONSTRAINT `proveedor_pago_ibfk_1` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedor` (`id`),
  ADD CONSTRAINT `proveedor_pago_ibfk_2` FOREIGN KEY (`id_factura`) REFERENCES `proveedor_factura` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
