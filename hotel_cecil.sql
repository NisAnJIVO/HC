-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 08-03-2026 a las 01:53:48
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
-- Base de datos: `hotel_cecil`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cierres_caja`
--

CREATE TABLE `cierres_caja` (
  `id` int(11) NOT NULL,
  `fecha_apertura` datetime NOT NULL COMMENT 'Fecha desde la que se acumulan los movimientos',
  `fecha_cierre` datetime NOT NULL COMMENT 'Fecha y hora del cierre',
  `usuario_id` int(11) DEFAULT NULL COMMENT 'ID del usuario que cerró',
  `usuario_nombre` varchar(100) DEFAULT NULL COMMENT 'Nombre del usuario que cerró',
  `recepcionista` varchar(100) DEFAULT NULL,
  `total_efectivo` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Total de ingresos en efectivo',
  `total_qr` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Total de ingresos por QR',
  `total_egresos` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Total de egresos (gastos)',
  `balance_efectivo` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Balance del recepcionista (efectivo - egresos)',
  `balance_total` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Balance total (efectivo + qr - egresos)',
  `observaciones` text DEFAULT NULL COMMENT 'Notas u observaciones del cierre',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `cierres_caja`
--

INSERT INTO `cierres_caja` (`id`, `fecha_apertura`, `fecha_cierre`, `usuario_id`, `usuario_nombre`, `recepcionista`, `total_efectivo`, `total_qr`, `total_egresos`, `balance_efectivo`, `balance_total`, `observaciones`, `created_at`) VALUES
(1, '2025-12-01 00:00:00', '2026-02-15 21:12:50', NULL, 'Hotel Cecil', 'Isaac Vargas', 14920.00, 1600.00, 1310.50, 13609.50, 15209.50, 'Antes de la primera apertura de caja en fecha 15/2/2026 a hrs 21:12', '2026-02-16 01:12:50'),
(2, '2026-02-15 21:12:50', '2026-02-18 00:00:15', NULL, 'Hotel Cecil', 'Isaac Vargas', 700.00, 0.00, 35.50, 664.50, 664.50, 'Cierre de Caja de Isaac Vargas', '2026-02-18 04:00:15'),
(3, '2026-02-18 00:00:15', '2026-02-23 07:41:48', NULL, 'Hotel Cecil', 'Isaac Vargas', 1240.00, 1040.00, 1038.00, 202.00, 1242.00, 'no sirve cierre', '2026-02-23 11:41:48'),
(4, '2026-02-23 07:41:48', '2026-02-23 07:42:11', NULL, 'Hotel Cecil', 'Isaac Vargas', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, '2026-02-23 11:42:11'),
(5, '2026-02-23 07:42:11', '2026-03-01 11:36:51', NULL, 'Hotel Cecil', 'Isaac Vargas', 2120.00, 340.00, 256.50, 1863.50, 2203.50, NULL, '2026-03-01 15:36:51'),
(6, '2026-03-01 11:36:51', '2026-03-01 11:37:11', NULL, 'Hotel Cecil', 'Gabriel Duran', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, '2026-03-01 15:37:11');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `egresos`
--

CREATE TABLE `egresos` (
  `id` int(11) NOT NULL,
  `concepto` varchar(255) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `categoria` varchar(100) DEFAULT NULL,
  `fecha` date NOT NULL,
  `hora` time DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `recepcionista` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `egresos`
--

INSERT INTO `egresos` (`id`, `concepto`, `monto`, `categoria`, `fecha`, `hora`, `observaciones`, `recepcionista`) VALUES
(12, 'Se compro bolsitas de bicarbonato de farmacia', 20.00, 'Externo', '2026-02-08', '21:32:38', NULL, 'Isaac Vargas'),
(13, 'Se compro pan 5bs, jugo 20bs y mortadela 17', 42.00, 'Cafetería', '2026-02-09', '22:40:59', NULL, 'Isaac Vargas'),
(14, 'Se compro 5bs de pan, 10bs de rollos y queque 15bs', 30.00, 'Cafetería', '2026-02-10', '22:32:47', NULL, 'Isaac Vargas'),
(15, 'Se compro 1 mortadela 17bs, pan 8bs, queque 15bs y rollo 20 bs', 60.00, 'Cafetería', '2026-02-12', '07:37:51', NULL, 'Isaac Vargas'),
(16, 'Se le iso un descuento de 80bs y sin desayuno al grupo musical que ingreso el dia de hoy de las habitaciones 203,206,207y 209', 80.00, 'Externo', '2026-02-12', '19:16:48', NULL, 'Isaac Vargas'),
(17, 'El grupo musical pago por qr el monto de 880 por las habitaciones 203,206,207 y 209', 880.00, 'Externo', '2026-02-12', '19:17:56', NULL, 'Isaac Vargas'),
(18, 'Se compro pan Galleta 4bs y pan queso 4bs', 8.00, 'Cafetería', '2026-02-13', '22:53:51', NULL, 'Isaac Vargas'),
(19, 'Se compro pan 12bs, queque 20bs y mortadela 17,5', 49.50, 'Cafetería', '2026-02-14', '08:00:24', NULL, 'Isaac Vargas'),
(20, 'Se compro jugo 14bs', 14.00, 'Cafetería', '2026-02-14', '08:40:43', NULL, 'Isaac Vargas'),
(21, 'Se compro pan 20bs y mortadela 34', 54.00, 'Cafetería', '2026-02-14', '23:39:26', NULL, 'Isaac Vargas'),
(22, 'Se compro 13bs de pan, 20bs de rollos y queque 30bs', 63.00, 'Cafetería', '2026-02-14', '07:57:32', NULL, 'Isaac Vargas'),
(23, 'Se presto a doña Adela 10 bs para taxi', 10.00, 'Externo', '2026-02-14', '07:58:33', NULL, 'Isaac Vargas'),
(24, 'Se compro Pan', 4.00, 'Cafetería', '2026-02-17', '08:44:35', NULL, 'Isaac Vargas'),
(25, 'Pan 5bs, Mortadela 17,50bs, masitas 9bs', 31.50, 'Cafetería', '2026-02-17', '21:02:23', NULL, 'Isaac Vargas'),
(26, 'Se compro jugo 20bs y queque 15', 35.00, 'Cafetería', '2026-02-18', '22:01:52', NULL, 'Isaac Vargas'),
(27, 'Se compro pan queso 5bs y pan galleta 3bs', 8.00, 'Cafetería', '2026-02-19', '19:29:46', NULL, 'Isaac Vargas'),
(28, 'Se compro pan 3bs y rollos 10bs', 13.00, 'Cafetería', '2026-02-20', '07:47:30', NULL, 'Isaac Vargas'),
(29, 'Se compro pan 3bs, queque 15bs y rollos 10bs', 28.00, 'Cafetería', '2026-02-21', '07:26:47', NULL, 'Isaac Vargas'),
(30, 'Pan 7bs, Queque 15bs, Rollo 15Bs, Mortadela 17Bs', 54.00, 'Cafetería', '2026-02-22', '09:22:32', NULL, 'Isaac Vargas'),
(31, 'Cambio de efectivo a QR', 900.00, 'Externo', '2026-02-22', '21:56:29', NULL, 'Isaac Vargas'),
(32, 'Se compro 1 mortadela 17bs Y 1 jugo 20bs', 37.00, 'Cafetería', '2026-02-23', '22:06:16', NULL, 'Gabriel Duran'),
(33, 'Se compro pan 12bs, queque 30bs y rollo 25 bs', 67.00, 'Cafetería', '2026-02-24', '07:49:17', NULL, 'Hotel Cecil'),
(34, 'Se compro 1 mortadela 17bs, pan 10bs, queque 20bs y rollo 20 bs', 67.00, 'Cafetería', '2026-02-24', '21:37:37', NULL, 'Hotel Cecil'),
(35, 'Se le devolvio 10bs a la huespet bethza por que no consumira desayuno', 10.00, 'Externo', '2026-02-25', '01:42:38', NULL, 'Hotel Cecil'),
(36, 'Se compro 1 mortadela 17bs', 17.00, 'Cafetería', '2026-02-25', '21:08:18', NULL, 'Hotel Cecil'),
(37, 'Se compro rollos 10bs', 10.00, 'Cafetería', '2026-02-26', '07:16:34', NULL, 'Hotel Cecil'),
(38, 'Se compro 6bs de pan, 10bs de rollos y queque 15bs', 31.00, 'Cafetería', '2026-02-28', '07:54:05', NULL, 'Hotel Cecil'),
(39, 'Se compro 1 mortadela 17,5bs', 17.50, 'Cafetería', '2026-02-28', '07:59:08', NULL, 'Hotel Cecil'),
(40, '1 jugo del valle 20bs, mortadela a 17bs, queque a 4bs el par y 9bs en galletas y pan', 50.00, 'Cafetería', '2026-03-02', '07:32:15', NULL, 'Isaac Vargas'),
(41, 'Se compro 5bs de pan, 5bs de rollos y queque 15bs', 25.00, 'Externo', '2026-03-03', '19:02:34', NULL, 'Hotel Cecil'),
(42, 'Se compro 2papeles carbonicos para los recibos', 2.00, 'Externo', '2026-03-03', '19:26:13', NULL, 'Gabriel Duran'),
(43, 'se compro 1 jugo 20bs, se compro 4bs de pan 5bs de rollo', 29.00, 'Cafetería', '2026-03-04', '06:45:35', NULL, 'Hotel Cecil'),
(44, 'Se compro 1 bote de agua 17bs', 17.00, 'Externo', '2026-03-06', '22:20:55', NULL, 'Hotel Cecil'),
(45, 'Se compro 5bs de pan,queque de 15bs y 2 rollos 10bs', 30.00, 'Cafetería', '2026-03-06', '22:22:03', NULL, 'Hotel Cecil'),
(46, 'Se compro jugo 20bs, mortadela 17bs', 37.00, 'Cafetería', '2026-03-07', '07:55:05', NULL, 'Hotel Cecil'),
(47, 'Se compro 8bs de pan, 20bs de rollos y queque 15bs', 43.00, 'Cafetería', '2026-03-07', '07:56:21', NULL, 'Hotel Cecil');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `habitaciones`
--

CREATE TABLE `habitaciones` (
  `id` int(11) NOT NULL,
  `numero` varchar(10) NOT NULL,
  `tipo` varchar(50) DEFAULT NULL,
  `precio_dia` decimal(10,2) NOT NULL,
  `estado` enum('disponible','ocupada','limpieza','mantenimiento') DEFAULT 'disponible'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `habitaciones`
--

INSERT INTO `habitaciones` (`id`, `numero`, `tipo`, `precio_dia`, `estado`) VALUES
(1, '102', 'Doble', 220.00, 'disponible'),
(2, '103', 'Matrimonial', 220.00, 'disponible'),
(3, '104', 'Matrimonial', 220.00, 'limpieza'),
(4, '201', 'Individual', 140.00, 'limpieza'),
(5, '202', 'Individual', 140.00, 'disponible'),
(6, '203', 'Individual', 140.00, 'limpieza'),
(7, '204', 'Individual', 140.00, 'limpieza'),
(8, '205', 'Matrimonial', 220.00, 'disponible'),
(9, '206', 'Doble', 220.00, 'disponible'),
(10, '207', 'Triple', 300.00, 'disponible'),
(11, '208', 'Familiar', 320.00, 'disponible'),
(12, '209', 'Triple', 300.00, 'disponible'),
(13, '301', 'Suite', 340.00, 'limpieza'),
(14, '302', 'Doble', 220.00, 'disponible'),
(15, '303', 'Doble', 220.00, 'disponible'),
(16, '304', 'Doble', 220.00, 'disponible'),
(17, '305', 'Triple', 300.00, 'disponible'),
(18, '306', 'Matrimonial', 220.00, 'ocupada'),
(19, '307', 'Suite', 340.00, 'disponible');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `huespedes`
--

CREATE TABLE `huespedes` (
  `id` int(11) NOT NULL,
  `nombres_apellidos` varchar(255) NOT NULL,
  `genero` enum('M','F') NOT NULL,
  `edad` int(11) NOT NULL,
  `estado_civil` varchar(50) DEFAULT NULL,
  `nacionalidad` varchar(100) NOT NULL,
  `ci_pasaporte` varchar(100) NOT NULL,
  `profesion` varchar(150) DEFAULT NULL,
  `objeto` varchar(255) DEFAULT NULL,
  `procedencia` varchar(150) DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `huespedes`
--

INSERT INTO `huespedes` (`id`, `nombres_apellidos`, `genero`, `edad`, `estado_civil`, `nacionalidad`, `ci_pasaporte`, `profesion`, `objeto`, `procedencia`, `fecha_registro`) VALUES
(29, 'Alvaro Antonio Arias Antequera', 'M', 38, 'S', 'Boliviano', '6778333', 'Abogado', 'Trabajo', 'La Paz', '2026-01-16 05:44:25'),
(30, 'David Sulca Contreras', 'M', 53, 'S', 'Boliviano', '1147411', 'Chofer', 'Trabajo', 'Tarija', '2026-01-16 06:07:52'),
(31, 'Jorge Carlos Armella Jurado', 'M', 35, 'Soltero/a', 'Boliviano', '7257092', 'Ing. Agrónomo', 'Otro', 'Tarija', '2026-01-16 06:07:52'),
(32, 'Jaime Garcia Torres', 'M', 61, 'D', 'Boliviano', '3134636', 'Abogado', 'Paso', 'Cochabamba', '2026-01-16 06:16:25'),
(33, 'Dulia Elizabeth Vera Castro', 'F', 42, 'Soltero/a', 'Boliviano', '6458951', 'Ama de Casa', 'Otro', 'Cochabamba', '2026-01-16 06:16:25'),
(34, 'Juan Carlos Ortega Pinto', 'M', 49, 'S', 'Boliviano', '3818648', 'Estudiante', 'Paso', 'Cochabamba', '2026-01-16 06:31:05'),
(35, 'Marlene Gaspar Tohara', 'F', 51, 'S', 'Boliviano', '3971204', 'Comerciante', 'Familiar', 'Potosí', '2026-01-16 06:42:58'),
(36, 'Wendy Micaela Gaspar', 'F', 23, 'Soltero/a', 'Boliviano', '14716783', 'Estudiante', 'Familiar', 'Potosí', '2026-01-16 06:42:58'),
(37, 'Silvia Gaspar Tohara', 'F', 41, 'S', 'Boliviano', '6612154', 'Abogada', 'Familiar', 'Potosí', '2026-01-26 04:22:52'),
(38, 'David Ramiro Leon Paco', 'M', 42, 'Soltero/a', 'Boliviano', '6571094', 'Estudiante', 'Familiar', 'Potosí', '2026-01-26 04:22:52'),
(39, 'Andre Fabian Leon Gaspar', 'M', 5, 'Soltero/a', 'Boliviano', '16429965', 'Niño', 'Familiar', 'Potosí', '2026-01-26 04:22:52'),
(40, 'Leonel Maximliano Leon Gaspar', 'M', 11, 'Soltero/a', 'Boliviano', '14024978', 'Niño', 'Familiar', 'Potosí', '2026-01-26 04:22:52'),
(41, 'Carlos Andres Lopez Noguera', 'M', 47, 'S', 'Boliviano', '3656491', 'Ing. Civil', 'Turismo', 'Santa Curz', '2026-01-26 04:47:06'),
(42, 'Beatriz Ayda Daza Barrero', 'F', 37, 'Soltero/a', 'Boliviano', '7472918', 'Estudiante', 'Turismo', 'Santa Cruz', '2026-01-26 04:47:06'),
(43, 'Rufino Pasquito Tarumbara', 'M', 44, 'S', 'Boliviano', '5897582', 'Lic. en Ciencias De La Comunicación', 'Turismo', 'Santa Curz', '2026-01-26 04:53:33'),
(44, 'Lilian Cortez Palomo', 'F', 42, 'Soltero/a', 'Boliviano', '6239610', 'Universitaria', 'Turismo', 'Santa Cruz', '2026-01-26 04:53:33'),
(45, 'Roberto Carlos Acha Vasquez', 'M', 26, '', 'Boliviano', '6643920', 'Estudiante', 'Paso', 'Potosí', '2026-01-26 05:02:26'),
(46, 'Ernesto Alejandro Achacollo Zarraga', 'M', 46, 'S', 'Boliviano', '4094130', 'Cirujano Odontologo', 'Paso', 'Oruro', '2026-01-26 05:05:51'),
(47, 'Elizabeth Huarachi Alvarez', 'F', 26, 'S', 'Boliviano', '9434224', 'Estudiante', 'Paso', 'Cochabamba', '2026-01-26 05:22:30'),
(48, 'Katherine Susana Cordero Martinez', 'F', 20, 'S', 'Boliviano', '10663410', 'Estudiante', 'Paso', 'Tarija', '2026-01-26 05:35:54'),
(49, 'Loida Martinez Martinez', 'F', 43, 'S', 'Boliviano', '5685263', 'Servidora Publica', 'Paso', 'Tarija', '2026-01-26 05:50:17'),
(50, 'Erick Alejandro Serrano Martinez', 'M', 7, 'Soltero/a', 'Boliviano', '15541239', 'Niño', 'Otro', 'Tarija', '2026-01-26 05:50:17'),
(51, 'Jimmy Willy Serrano Perez', 'M', 46, 'Soltero/a', 'Boliviano', '4094576', 'Estudiante', 'Otro', 'Tarija', '2026-01-26 05:50:17'),
(52, 'Mijael Gomez Ramos', 'M', 19, 'S', 'Boliviano', '14025672', 'Estudiante', 'Paso', 'Potosí', '2026-02-01 14:10:22'),
(53, 'Edgar Estrada Condori', 'M', 36, 'S', 'Boliviano', '8576372', 'Estudiante', 'Paso', 'Potosí', '2026-02-01 14:12:04'),
(54, 'Javier Chumacero Barrios', 'M', 35, 'S', 'Boliviano', '7573025', 'Chofer', 'Paso', 'Potosí', '2026-02-01 14:14:01'),
(55, 'Mariela Vivian Quiroz Crespo', 'F', 46, 'S', 'Boliviano', '3818688', 'Independiente', 'Paso', 'Cochabamba', '2026-02-01 17:20:55'),
(56, 'Lucas Esteban Salamanca', 'M', 47, 'Soltero/a', 'Boliviano', '3594394', 'Independiente', 'Turismo', 'Cochabamba', '2026-02-01 17:20:55'),
(57, 'Natalia Luciana Salamanca Quiroz', 'F', 19, 'S', 'Boliviano', '8055823', 'Estudiante', 'Turismo', 'Cochabamba', '2026-02-01 18:17:51'),
(58, 'Ariel y Lucia Salamanca Salinas', 'F', 22, 'Soltero/a', 'Boliviano', '6555816', 'Estudiante', 'Turismo', 'Cochabamba', '2026-02-01 18:17:51'),
(59, 'Juan Carlos Cordova Rojas', 'M', 46, 'S', 'Boliviano', '4434268', 'Mecánico', 'Turismo', 'Santa Cruz', '2026-02-01 18:45:08'),
(60, 'Carlos Taiwa Cordova Torrez', 'M', 19, 'Soltero/a', 'Boliviano', '13164332', 'Estudiante', 'Turismo', 'Santa Cruz', '2026-02-01 18:45:08'),
(61, 'Brisa Adai Cordova Torrez', 'F', 15, 'Soltero/a', 'Boliviano', '14510838', 'Niña', 'Turismo', 'Santa Cruz', '2026-02-01 18:45:08'),
(62, 'Rocio Verastegui Berrios', 'F', 44, 'C', 'Boliviano', '4051731', 'Médico Cirujano', 'Turismo', 'Oruro', '2026-02-01 19:53:44'),
(63, 'Luz Camila Hoyos Verastegui', 'F', 20, 'Soltero/a', 'Boliviano', '12709751', 'Estudiante', 'Turismo', 'Oruro', '2026-02-01 19:53:44'),
(64, 'Alex Fernandez Valenzuela', 'M', 25, 'S', 'Boliviano', '8786365', 'Mecánico', 'Turismo', 'Tarija', '2026-02-01 20:23:57'),
(65, 'Victoria Siacara Vargas', 'F', 26, 'Soltero/a', 'Boliviano', '12430026', 'Estudiante', 'Turismo', 'Tarija', '2026-02-01 20:23:57'),
(66, 'Luis Fernando Ruiz Moreno', 'M', 47, 'S', 'Boliviano', '4606662', 'Mecánico', 'Turismo', 'Santa Cruz', '2026-02-01 20:34:22'),
(67, 'Sandra Liliana Suarez Medrano', 'F', 40, 'Soltero/a', 'Boliviano', '6305366', 'Estudiante', 'Turismo', 'Santa Cruz', '2026-02-01 20:34:22'),
(68, 'Diego Sebastian Vaca', 'M', 46, 'S', 'Argentino', 'E-11651548', 'Músico', 'Concierto', 'La Paz', '2026-02-01 21:52:13'),
(69, 'Carlos Arando Puma', 'M', 41, 'Soltero/a', 'Boliviano', '8549281', 'Músico', 'Otro', 'La Paz', '2026-02-01 21:52:13'),
(70, 'Limbert Ademar Vargas Flores', 'M', 41, 'S', 'Boliviano', '5998206', 'Cantante', 'Concierto', 'La Paz', '2026-02-01 22:06:23'),
(71, 'Roberto Marin Monte', 'M', 48, 'Soltero/a', 'Argentina', '25.932.768', 'Músico', 'Otro', 'La Paz', '2026-02-01 22:06:23'),
(72, 'Silvio  Marcelo Zapana', 'M', 48, 'S', 'Argentino', 'E-11496603', 'Músico', 'Concierto', 'La Paz', '2026-02-01 22:11:51'),
(73, 'Fernando Rodriguez Huchani', 'M', 50, 'S', 'Boliviano', '4378113', 'Chofer', 'Concierto', 'La Paz', '2026-02-01 22:22:17'),
(74, 'Liset Rocio Peña Silva', 'F', 46, 'S', 'Boliviano', '4749459', 'Estudiante', 'Concierto', 'La Paz', '2026-02-01 22:34:51'),
(75, 'Leonardo Federico Almiron', 'M', 35, 'S', 'Argentino', 'E-116122514', 'Músico', 'Concierto', 'La Paz', '2026-02-01 22:46:46'),
(76, 'Jorge Gabriel Yarvi', 'M', 45, 'S', 'Argentina', 'E-11496605', 'Músico', 'Concierto', 'La Paz', '2026-02-01 22:59:54'),
(77, 'Ariela Narda Jauregui Condori', 'F', 36, 'S', 'Boliviano', '6957515', 'Estudiante', 'Concierto', 'La Paz', '2026-02-01 23:08:04'),
(78, 'Juan Salvador Zapana', 'M', 23, 'Soltero/a', 'Argentina', '45.108.603', 'Músico', 'Otro', 'La Paz', '2026-02-01 23:08:04'),
(79, 'Margarita Marin Alvarez', 'F', 30, 'S', 'Boliviano', '10532200', 'Estudiante', 'Paso', 'Familiar', '2026-02-01 23:17:02'),
(80, 'Evelin Noemi Marin Alvarez', 'F', 20, 'Soltero/a', 'Boliviano', '10532275', 'Estudiante', 'Familiar', 'Potosí', '2026-02-01 23:17:02'),
(81, 'Julia Alejandra Montoya Lagrava', 'F', 29, 'S', 'Boliviano', '12813450', 'Médico Cirujano', 'Paso', 'Potosí', '2026-02-01 23:20:15'),
(82, 'Carlos Daniel Espinoza Medinaceli', 'M', 27, 'S', 'Boliviano', '10571942', 'Estudiante', 'Turismo', 'Potosí', '2026-02-02 12:22:22'),
(83, 'Martin Cuenca Nicacio', 'M', 55, 'C', 'Boliviano', '3696869', 'Chofer', 'Paso', 'Villazon', '2026-02-03 02:02:57'),
(84, 'Alberto Nina Coronado', 'M', 31, 'S', 'Boliviano', '13915285', 'Estudiante', 'Paso', 'Santa Cruz', '2026-02-05 04:28:51'),
(85, 'Orlando Quintana Escobar', 'M', 47, 'c', 'Boliviano', '5333928', 'Comerciante', 'Turismo', 'Santa Cruz', '2026-02-06 03:18:49'),
(86, 'Tatiana Alejandra Vargas Torres', 'F', 37, 'Soltero/a', 'Boliviano', '8983148', 'Independiente', 'Turismo', 'Santa Cruz', '2026-02-06 03:18:49'),
(87, 'Rosse Mary Torres Diaz', 'F', 52, 'Soltero/a', 'Boliviano', '3658606', 'Estudiante', 'Turismo', 'Santa Cruz', '2026-02-06 03:18:49'),
(88, 'Maria Teresa Vacaflor Hernandez', 'F', 71, 'S', 'Boliviano', '1048890', 'Ama de Casa', 'Turismo', 'Potosí', '2026-02-06 03:34:54'),
(89, 'Eynar Ernesto Ramos Patton', 'M', 49, 'C', 'Boliviano', '4578269', 'Ing. Agronomo', 'Turismo', 'Potosí', '2026-02-06 03:37:42'),
(90, 'Elsa Eva Coronado Vacaflor', 'F', 47, 'Soltero/a', 'Boliviano', '5004623', 'Bioquimico', 'Turismo', 'Potosí', '2026-02-06 03:37:42'),
(91, 'Juan Carlos Choqueticlla Santos', 'M', 37, 'C', 'Boliviano', '6651687', 'Minero', 'Turismo', 'Potosí', '2026-02-07 08:07:54'),
(92, 'Jheanet Soledad Choque Limachi', 'F', 34, 'Casado/a', 'Boliviano', '10536673', 'Ama de casa', 'Turismo', 'Potosí', '2026-02-07 08:07:54'),
(93, 'Carlos Abdiel Choqueticlla Choque', 'M', 7, 'Soltero/a', 'Boliviano', '17623652', 'Estudiante', 'Turismo', 'Potosí', '2026-02-07 08:07:54'),
(94, 'Alejandra Choqueticlla Choque', 'F', 16, 'Soltero/a', 'Boliviano', '16020824', 'Estudiante', 'Turismo', 'Potosí', '2026-02-07 08:07:54'),
(95, 'Marco Antonio Duran Loredo', 'M', 26, 'S', 'Boliviano', '8971364', 'Estudiante', 'Turismo', 'Potosí', '2026-02-07 23:40:04'),
(96, 'Eva Duran Loredo', 'F', 35, 'Soltero/a', 'Boliviano', '8116690', 'Ama de casa', 'Turismo', 'Potosí', '2026-02-07 23:40:04'),
(97, 'Jose Antonio Quiroz Salazar', 'M', 74, 'Soltero/a', 'Boliviano', '1537758', 'Agricultor', 'Turismo', 'Potosí', '2026-02-07 23:40:04'),
(98, 'Andres Nelson Condori Martinez', 'M', 38, 'S', 'Boliviano', '6663794', 'Agricultor', 'Turismo', 'Potosí', '2026-02-08 03:32:48'),
(99, 'Maria Eugenia Huiza Pinto', 'F', 35, 'Soltero/a', 'Boliviano', '8573540', 'Ama de casa', 'Turismo', 'Potosí', '2026-02-08 03:32:49'),
(100, 'Maribel Codori Huiza', 'F', 19, 'S', 'Boliviano', '12527265', 'Estudiante', 'Turismo', 'Potosí', '2026-02-08 03:35:07'),
(101, 'Jhandy Estefany Condori Huiza', 'F', 12, 'Soltero/a', 'Boliviano', '15484368', 'Estudiante', 'Turismo', 'Potosí', '2026-02-08 03:35:07'),
(102, 'Jose Carlos Guzman Montenegro', 'M', 34, 'S', 'Boliviano', '6325921', 'Conductor', 'Turismo', 'Santa Cruz', '2026-02-08 10:02:07'),
(103, 'Jessica Lorena Soliz Aranibar', 'F', 28, 'Soltero/a', 'Boliviano', '11366393', 'Odontóloga', 'Turismo', 'Santa Cruz', '2026-02-08 10:02:07'),
(104, 'Ruth Auster Soliz Aranibar', 'F', 45, 'S', 'Boliviano', '4032413', 'Conductor', 'Turismo', 'Santa Cruz', '2026-02-08 10:04:40'),
(105, 'Gerardo Denar Ribera Guzman', 'M', 33, 'S', 'Boliviano', '7713918', 'Estudiante', 'Trabajo', 'Cochabamba', '2026-02-08 10:06:49'),
(106, 'Cristina Condori Flores', 'F', 49, 'C', 'Boliviano', '6651142', 'Ama de Casa', 'Turismo', 'La Paz', '2026-02-08 18:11:47'),
(107, 'Roberto Huanca Luque', 'M', 48, 'Casado/a', 'Boliviano', '4848969', 'Costurero', 'Turismo', 'La Paz', '2026-02-08 18:11:47'),
(108, 'Sara Lais Huanca Condori', 'F', 18, 'S', 'Brazilera', '52.430.261-3', 'Estudiante', 'Turismo', 'La Paz', '2026-02-08 18:58:30'),
(109, 'Israel Cristhian Chavez Morales', 'M', 34, 'S', 'Boliviano', '8843328', 'Estudiante', 'Paso', 'Cochabamba', '2026-02-12 03:59:57'),
(110, 'Daniel Camilo Baquero Romero', 'M', 27, 'S', 'Colombiano', 'E-18025453', 'Músico', 'Negocios', 'Santa Cruz', '2026-02-12 10:26:57'),
(111, 'Roller David Peña Saurith', 'M', 31, 'S', 'Colombiano', 'E-11643956', 'Músico', 'Negocios', 'Santa Cruz', '2026-02-12 10:30:25'),
(112, 'Jhonatan Stit Ustariz Saurith', 'M', 33, 'S', 'Colombiano', 'E-18030405', 'Músico', 'Negocios', 'Santa Cruz', '2026-02-12 10:30:25'),
(113, 'Julio Cesar Daza Meneces', 'M', 24, 'S', 'Colombiano', 'E-17962706', 'Músico', 'Negocios', 'Santa Cruz', '2026-02-12 10:34:11'),
(114, 'Andres Jose Gonzales Del Valle', 'M', 22, 'S', 'Colombiano', 'E-17982949', 'Músico', 'Negocios', 'Santa Cruz', '2026-02-12 10:34:11'),
(115, 'Ronald Enrique Sanjuan Ramirez', 'M', 28, 'S', 'Colombiano', 'E-11613559', 'Músico', 'Negocios', 'Santa Cruz', '2026-02-12 10:34:11'),
(116, 'Victor Andres Fontalvo Barrera', 'M', 28, 'S', 'Colombiano', 'E-11644145', 'Músico', 'Negocios', 'Santa Cruz', '2026-02-12 10:38:49'),
(117, 'Jorge Luis Jimenez Gil', 'M', 30, 'S', 'Colombiano', 'E-11649573', 'Músico', 'Negocios', 'Santa Cruz', '2026-02-12 10:38:49'),
(118, 'Jose David Jimene Gil', 'M', 22, 'S', 'Colombiano', '1.065.812.675', 'Músico', 'Negocios', 'Santa Cruz', '2026-02-12 10:38:49'),
(119, 'Wilzon Miguel Gutierrez Carrasco', 'M', 59, 'S', 'Boliviano', '3695699', 'Independiente', 'Paso', 'Potosí', '2026-02-14 02:49:26'),
(120, 'Marina Anibarro Marin', 'F', 58, 'S', 'Boliviano', '3690814', 'Guia de Turismo', 'Paso', 'Potosí', '2026-02-14 02:49:26'),
(121, 'Victoria Gladys Vargas Iriarte', 'F', 71, 'S', 'Boliviano', '855836', 'Independiente', 'Turismo', 'Cochabamba', '2026-02-14 04:06:40'),
(122, 'Andrea Nathalia Gonzales Vargas', 'F', 67, 'S', 'Boliviano', '6400699', 'Médico', 'Turismo', 'Cochabamba', '2026-02-14 04:06:40'),
(123, 'Franz Victor Hugo Gonzales Zurita', 'M', 68, 'S', 'Boliviano', '814259', 'Arquitecto', 'Turismo', 'Cochabamba', '2026-02-14 04:06:40'),
(124, 'Luis Fernando Revollo Larrain', 'M', 41, 'C', 'Boliviano', '3596281', 'Arquitecto', 'Turismo', 'Cochabamba', '2026-02-14 04:11:21'),
(125, 'Daniela Martha Gonzales Vargas', 'F', 40, 'S', 'Boliviano', '6400702', 'Arquitecto', 'Turismo', 'Cochabamba', '2026-02-14 04:11:21'),
(126, 'Daniel Fernando Revollo Gonzales', 'M', 11, 'S', 'Boliviano', '13589118', 'Estudiante', 'Turismo', 'Cochabamba', '2026-02-14 04:11:21'),
(127, 'Luis Alberto Belaunde Soliz', 'M', 53, 'D', 'Boliviano', '3294652', 'Abogado', 'Turismo', 'Santa Cruz', '2026-02-14 23:21:37'),
(128, 'Danny Ariel Flores Taboada', 'M', 35, 'S', 'Boliviano', '6680738-1P', 'Ing. Matematico', 'Paso', 'Potosí', '2026-02-14 23:25:59'),
(129, 'Noe Pol Cotrina Mamani', 'M', 24, 'S', 'Boliviano', '14091323', 'Estudiante', 'Paso', 'Potosí', '2026-02-14 23:25:59'),
(130, 'Boris Daniel Soleto Antezana', 'M', 34, 'S', 'Boliviano', '7625821', 'Ing. Electronico Y sistemas', 'Paso', 'Potosí', '2026-02-15 03:28:55'),
(131, 'Cinthia Danitza Choque Cala', 'F', 34, 'S', 'Boliviano', '9639896', 'Administradora General', 'Paso', 'Potosí', '2026-02-15 03:28:55'),
(132, 'Amilcar Luis Chavez Herbas', 'M', 48, 'S', 'Boliviano', '4324822', 'Independiente', 'Paso', 'La Paz', '2026-02-15 03:33:30'),
(133, 'Griselda Encinas Peñafiel', 'F', 47, 'S', 'Boliviano', '3420126-1K', 'Independiente', 'Paso', 'La Paz', '2026-02-15 03:33:30'),
(134, 'Victoria Peñafiel Vda', 'F', 79, 'V', 'Boliviano', '089887', 'Ama de Casa', 'Paso', 'La Paz', '2026-02-15 03:37:38'),
(135, 'Lucas Tadeo Chavez Encinas', 'M', 13, 'S', 'Boliviano', '14476935', 'Estudiante', 'Paso', 'La Paz', '2026-02-15 03:37:38'),
(136, 'Armando Nicolas Chavez Encinas', 'M', 11, 'S', 'Boliviano', '15008972', 'Estudiante', 'Paso', 'La Paz', '2026-02-15 03:37:38'),
(137, 'Boris Oscar Montero Portugal', 'M', 49, 'S', 'Boliviano', '11344610', 'Estudiante', 'Paso', 'Potosí', '2026-02-15 10:37:25'),
(138, 'Elmer Raul Acho Lucas', 'M', 32, 'S', 'Boliviano', '8510926', 'Auditor', 'Paso', 'Potosí', '2026-02-16 17:40:08'),
(139, 'Vivian del Rosario Mendieta Acuña', 'F', 35, 'S', 'Boliviano', '8579831', 'Medico Cirujano', 'Paso', 'Potosí', '2026-02-16 17:40:08'),
(140, 'Rocio Viviana Caihuara', 'F', 31, 'S', 'Boliviano', '7195670', 'Estudiante', 'Turismo', 'Tarija', '2026-02-16 19:37:12'),
(141, 'Oscar Zambrana Becerra', 'M', 36, 'S', 'Boliviano', '7251460', 'Estudiante', 'Turismo', 'Tarija', '2026-02-16 19:37:12'),
(142, 'Damaris Rocio Franco Gomes', 'F', 29, 'S', 'Boliviano', '8943948', 'Estudiante', 'Paso', 'Potosí', '2026-02-17 12:41:17'),
(143, 'Walker Stroebel Pedraza', 'M', 27, 'S', 'Boliviano', '10834765', 'Estudiante', 'Paso', 'Potosí', '2026-02-17 12:41:17'),
(144, 'Luis Jesus Cruz Vargas', 'M', 24, 'S', 'Boliviano', '9785100', 'Estudiante', 'Turismo', 'Santa Cruz', '2026-02-19 10:27:50'),
(145, 'Mariana Ariana Escobar Serrudo', 'F', 21, 'S', 'Boliviano', '13832886', 'Estudiante', 'Turismo', 'Santa Cruz', '2026-02-19 10:27:50'),
(146, 'Mueses Calcina Berna', 'M', 63, 'S', 'Boliviano', '6825806', 'Empresario', 'Paso', 'Potosí', '2026-02-20 10:31:45'),
(147, 'Ninfa Lidia Romero Rojas', 'F', 57, 'S', 'Boliviano', '1109569', 'Agente de viajes', 'Paso', 'Potosí', '2026-02-20 10:31:45'),
(148, 'Pablo Padilla Micordia', 'M', 44, 'S', 'Boliviano', '5486158', 'Contador', 'Paso', 'Monteagudo', '2026-02-20 10:35:20'),
(149, 'Maximo Rodriguez Apaza', 'M', 42, 'S', 'Boliviano', '5500483', 'Estudiante', 'Paso', 'Potosí', '2026-02-21 01:20:11'),
(150, 'Maria Elisa Sandoval Colque', 'F', 38, 'C', 'Boliviano', '6681048-1O', 'Estudiante', 'Paso', 'Potosí', '2026-02-21 01:20:11'),
(151, 'Maximo Rodriguez Ceballos', 'M', 40, 'S', 'Boliviano', '6514560', 'Estudiante', 'Turismo', 'Cochabamba', '2026-02-22 02:13:04'),
(152, 'Estela Maribe Chacon Robles', 'F', 31, 'S', 'Peruana', '24.290.605-5', 'Independiente', 'Turismo', 'Cochabamba', '2026-02-22 02:13:04'),
(153, 'Ivonne Rueda Torrico', 'F', 61, 'S', 'Boliviano', '3694645', 'Comerciante', 'Paso', 'La Paz', '2026-02-23 22:57:13'),
(154, 'Junnior Erick Diaz Olguin', 'M', 33, 'S', 'Boliviano', '77993876', 'Abogado', 'Negocios', 'Cochabamba', '2026-02-23 23:37:44'),
(155, 'Noelia Martha Sejas Cuba', 'F', 26, 'S', 'Boliviano', '9326306', 'Estudiante', 'Negocios', 'Cochabamba', '2026-02-23 23:37:44'),
(156, 'Rolando Mur Sullca', 'M', 37, 'S', 'Boliviano', '7128045', 'Chofer', 'Negocios', 'Tarija', '2026-02-24 00:54:24'),
(157, 'Yvan Garcia Caveros', 'M', 29, 'S', 'Boliviano', '8696809', 'Estudiante', 'Negocios', 'Cochabamba', '2026-02-24 02:03:41'),
(158, 'Carla Yauri Zelada', 'F', 29, 'S', 'Boliviano', '7999350', 'Estudiante', 'Negocios', 'Cochabamba', '2026-02-24 02:03:41'),
(159, 'Wigmar Hugo Flores Carmona', 'M', 29, 'S', 'Boliviano', '13153653', 'Estudiante', 'Paso', 'Camargo', '2026-02-24 10:30:26'),
(160, 'Francisca Taqui Janayo', 'M', 43, 'S', 'Boliviano', '5127442', 'Abogado', 'Negocios', 'Potosí', '2026-02-24 23:13:14'),
(161, 'Jose Ricardo Bolivar Condori', 'M', 41, 'S', 'Boliviano', '6639399', 'Chofer', 'Familiar', 'Potosí', '2026-02-25 01:35:27'),
(162, 'Lisbeth Marlene Cayo Ali', 'F', 38, 'S', 'Boliviano', '6603396', 'Comerciante', 'Familiar', 'Potosí', '2026-02-25 01:35:27'),
(163, 'Bethza Maribel Villca Mamani', 'F', 34, 'S', 'Boliviano', '7060945', 'Estudiante', 'Negocios', 'La Paz', '2026-02-25 05:41:29'),
(164, 'Ninfa Isabel Pinto Torrico', 'F', 54, 'S', 'Boliviano', '3517389', 'Comerciante', 'Paso', 'Oruro', '2026-02-25 23:42:34'),
(165, 'Andres Marcelo Otorola', 'M', 54, 'S', 'Argentina', '22.232.672', 'Estudiante', 'Paso', 'Oruro', '2026-02-25 23:42:34'),
(166, 'Roberth Andres Santa Cruz Quiroz', 'M', 28, 'S', 'Boliviano', '11336514', 'Estudiante', 'Negocios', 'Santa Cruz', '2026-02-26 01:01:01'),
(167, 'Miguel Angel Mamani Flores', 'M', 27, 'S', 'Boliviano', '6755222', 'Estudiante', 'Familiar', 'La Paz', '2026-03-01 01:30:11'),
(168, 'Lucy Quispe Huallpa', 'F', 37, 'S', 'Boliviano', '6853148', 'Estudiante', 'Familiar', 'La Paz', '2026-03-01 01:30:11'),
(169, 'Yovana Quispe Huallpa', 'F', 32, 'V', 'Boliviano', '9206568', 'Estudiante', 'Familiar', 'La Paz', '2026-03-01 01:30:11'),
(170, 'Yeicob Alexander Chuquimia Quispe', 'M', 8, 'S', 'Boliviano', '15752024', 'Estudiante', 'Familiar', 'La Paz', '2026-03-01 01:30:11'),
(171, 'Daraly Yolanda Murillo Garcilazo', 'F', 21, 'S', 'Boliviano', '10580418', 'Estudiante', 'Paso', 'Potosí', '2026-03-02 11:27:24'),
(172, 'Jose Reynaldo Salinas Zapata', 'M', 65, 'S', 'Boliviano', '2364307', 'Estudiante', 'Paso', 'Santa Cruz', '2026-03-03 11:31:00'),
(173, 'Diana Raquel Rivera Barba', 'F', 23, 'S', 'Boliviano', '8183059', 'Estudiante', 'Paso', 'Santa Cruz', '2026-03-03 11:33:01'),
(174, 'Jorge Luis Lopez Ribera', 'M', 38, 'S', 'Boliviano', '7700310', 'Empresario', 'Paso', 'Santa Cruz', '2026-03-06 02:41:07'),
(175, 'Wilfredo Casiano Algarañaz Cesari', 'M', 61, '', 'Boliviano', '4734896', 'Soldador', 'Paso', 'Santa Cruz', '2026-03-06 02:44:05'),
(176, 'Paola Karen Lizarazu Araca', 'F', 32, 'S', 'Boliviano', '7257430', 'Estudiante', 'Paso', 'Potosí', '2026-03-06 02:49:08'),
(177, 'Manuel Alejandro Flores Zabala', 'M', 47, 'S', 'Boliviano', '4880488', 'Estudiante', 'Educación', 'Tarija', '2026-03-06 12:02:17'),
(178, 'Nohelia Yosendi Condoro Arroyo', 'F', 18, 'S', 'Boliviano', '12946799', 'Estudiante', 'Educación', 'Tarija', '2026-03-06 12:02:17'),
(179, 'Alexander Romero Chavarria', 'M', 34, 'S', 'Boliviano', '4210375', 'Estudiante', 'Paso', 'Santa Cruz', '2026-03-07 00:41:16'),
(180, 'Roannie Maradey Vaca', 'F', 32, 'S', 'Boliviano', '4210641', 'Ing. Industrial', 'Paso', 'Santa Cruz', '2026-03-07 00:41:16');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ingresos`
--

CREATE TABLE `ingresos` (
  `id` int(11) NOT NULL,
  `ocupacion_id` int(11) DEFAULT NULL,
  `concepto` varchar(255) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `metodo_pago` enum('efectivo','qr','tarjeta','pendiente','otro') DEFAULT 'efectivo',
  `fecha` date NOT NULL,
  `hora` time DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `recepcionista` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `ingresos`
--

INSERT INTO `ingresos` (`id`, `ocupacion_id`, `concepto`, `monto`, `metodo_pago`, `fecha`, `hora`, `observaciones`, `recepcionista`) VALUES
(25, 39, 'Pago habitación 201 - 1 día(s)', 140.00, 'qr', '2025-12-01', '01:44:25', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(26, 40, 'Pago habitación 303 - 2 día(s)', 440.00, 'efectivo', '2025-12-01', '02:07:52', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(27, 42, 'Pago habitación 301 - 1 día(s) (Descuento: Bs. 120.00 - Se dió a precio de Matrimonial)', 220.00, 'efectivo', '2025-12-03', '02:16:25', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(28, 44, 'Pago habitación 104 - 2 día(s)', 440.00, 'efectivo', '2025-12-01', '02:31:05', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(29, 45, 'Pago habitación 104 - 2 día(s)', 440.00, 'efectivo', '2025-12-01', '02:32:04', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(30, 46, 'Pago habitación 102 - 1 día(s) (Descuento: Bs. 20.00 - Sin Desayuno)', 200.00, 'efectivo', '2025-12-06', '02:42:58', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(31, 48, 'Pago habitación 208 - 1 día(s)', 320.00, 'efectivo', '2025-12-06', '00:22:52', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(32, 52, 'Pago habitación 205 - 1 día(s)', 220.00, 'efectivo', '2025-12-17', '00:47:06', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(33, 54, 'Pago habitación 306 - 1 día(s)', 220.00, 'efectivo', '2025-12-18', '00:53:33', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(34, 56, 'Pago habitación 204 - 1 día(s)', 140.00, 'efectivo', '2025-12-19', '01:02:26', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(35, 57, 'Pago habitación 203 - 1 día(s)', 140.00, 'efectivo', '2025-12-19', '01:05:51', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(36, 58, 'Pago habitación 302 - 1 día(s) (Descuento: Bs. 80.00 - Uso como Individual)', 140.00, 'efectivo', '2025-12-20', '01:22:30', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(37, 59, 'Pago habitación 204 - 1 día(s) (Descuento: Bs. 20.00 - Sin Desayuno)', 120.00, 'efectivo', '2025-12-26', '01:35:54', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(38, 60, 'Pago habitación 208 - 1 día(s) (Descuento: Bs. 20.00 - Sin Desayuno)', 300.00, 'efectivo', '2025-12-26', '01:50:17', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(39, 63, 'Pago habitación 201 - 1 día(s)', 140.00, 'efectivo', '2026-01-31', '10:10:22', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(40, 64, 'Pago habitación 203 - 1 día(s)', 140.00, 'efectivo', '2026-01-31', '10:12:04', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(41, 65, 'Pago habitación 204 - 1 día(s)', 140.00, 'efectivo', '2026-01-31', '10:14:01', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(42, 66, 'Pago habitación 304 - 1 día(s) (Descuento: Bs. 20.00 - Sin desayuno)', 200.00, 'efectivo', '2025-12-26', '13:20:55', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(43, 68, 'Pago habitación 302 - 1 día(s) (Descuento: Bs. 20.00 - Sin desayuno)', 200.00, 'efectivo', '2025-12-26', '14:17:51', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(44, 70, 'Pago habitación 207 - 1 día(s)', 300.00, 'efectivo', '2025-12-27', '14:45:08', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(45, 73, 'Pago habitación 104 - 1 día(s)', 220.00, 'qr', '2025-12-28', '15:53:44', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(46, 75, 'Pago habitación 306 - 1 día(s)', 220.00, 'qr', '2025-12-28', '16:23:57', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(47, 77, 'Pago habitación 206 - 1 día(s)', 220.00, 'efectivo', '2025-12-30', '16:34:22', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(48, 79, 'Pago habitación 304 - 1 día(s)', 220.00, 'efectivo', '2025-12-31', '17:52:13', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(49, 81, 'Pago habitación 303 - 1 día(s)', 220.00, 'efectivo', '2025-12-31', '18:06:23', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(50, 83, 'Pago habitación 306 - 1 día(s)', 220.00, 'efectivo', '2025-12-31', '18:11:51', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(51, 84, 'Pago habitación 202 - 1 día(s)', 140.00, 'efectivo', '2025-12-31', '18:22:17', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(52, 85, 'Pago habitación 203 - 1 día(s)', 140.00, 'efectivo', '2025-12-31', '18:34:51', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(53, 86, 'Pago habitación 204 - 1 día(s)', 140.00, 'efectivo', '2025-12-31', '18:46:46', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(54, 87, 'Pago habitación 302 - 1 día(s)', 220.00, 'efectivo', '2025-12-31', '18:59:54', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(55, 88, 'Pago habitación 205 - 1 día(s)', 220.00, 'efectivo', '2025-12-31', '19:08:04', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(56, 90, 'Pago habitación 102 - 1 día(s)', 220.00, 'efectivo', '2025-12-31', '19:17:02', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(57, 92, 'Pago habitación 104 - 1 día(s)', 220.00, 'efectivo', '2025-12-31', '19:20:15', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(58, 93, 'Pago habitación 205 - 1 día(s)', 220.00, 'efectivo', '2026-02-01', '08:22:23', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(59, 94, 'Pago habitación 203 - 1 día(s)', 140.00, 'efectivo', '2026-02-02', '22:02:57', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(60, 95, 'Pago habitación 201 - 1 día(s)', 140.00, 'qr', '2026-02-04', '21:59:04', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(61, 96, 'Pago habitación 202 - 1 día(s)', 140.00, 'efectivo', '2026-02-04', '00:28:51', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(62, 97, 'Pago habitación 207 - 1 día(s)', 300.00, 'efectivo', '2026-02-05', '23:18:49', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(63, 100, 'Pago habitación 203 - 1 día(s)', 140.00, 'efectivo', '2026-02-05', '23:34:54', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(64, 101, 'Pago habitación 206 - 1 día(s)', 220.00, 'efectivo', '2026-02-05', '23:37:42', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(65, NULL, 'La habitacion 202 pago por medio dia mas 70bs', 70.00, 'efectivo', '2026-02-06', '07:56:11', 'Ingreso extra', 'Isaac Vargas'),
(66, 103, 'Pago habitación 208 - 1 día(s)', 320.00, 'efectivo', '2026-02-06', '04:07:54', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(67, 107, 'Pago habitación 209 - 1 día(s)', 300.00, 'efectivo', '2026-02-07', '19:40:04', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(68, 110, 'Pago habitación 306 - 1 día(s) (Descuento: Bs. 10.00 - Descuento)', 210.00, 'efectivo', '2026-02-07', '23:32:48', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(69, 112, 'Pago habitación 303 - 1 día(s)', 220.00, 'efectivo', '2026-02-07', '23:35:07', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(70, 114, 'Pago habitación 205 - 1 día(s)', 220.00, 'qr', '2026-02-07', '06:02:07', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(71, 116, 'Pago habitación 203 - 1 día(s)', 140.00, 'qr', '2026-02-07', '06:04:40', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(72, 117, 'Pago habitación 204 - 1 día(s)', 140.00, 'efectivo', '2026-02-07', '06:06:49', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(73, 118, 'Pago habitación 104 - 4 día(s) (Descuento: Bs. 80.00 - 20 bs descuento por día)', 800.00, 'efectivo', '2026-02-08', '14:11:47', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(74, 120, 'Pago habitación 201 - 4 día(s) (Descuento: Bs. 40.00 - 10 Bs de descuento por día)', 520.00, 'efectivo', '2026-02-08', '14:58:30', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(75, 121, 'Pago habitación 205 - 1 día(s)', 220.00, 'efectivo', '2026-02-09', '08:04:48', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(76, 123, 'Pago habitación 203 - 1 día(s)', 140.00, 'efectivo', '2026-02-09', '08:06:15', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(77, 124, 'Pago habitación 204 - 1 día(s)', 140.00, 'efectivo', '2026-02-11', '23:59:57', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(78, 125, 'Pago habitación 202 - 1 día(s)', 140.00, 'efectivo', '2026-02-11', '00:02:36', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(79, 126, 'Pago habitación 203 - 1 día(s)', 140.00, 'efectivo', '2026-02-12', '06:26:57', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(80, 127, 'Pago habitación 206 - 1 día(s)', 220.00, 'efectivo', '2026-02-12', '06:30:25', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(81, 129, 'Pago habitación 207 - 1 día(s)', 300.00, 'efectivo', '2026-02-12', '06:34:11', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(82, 132, 'Pago habitación 209 - 1 día(s)', 300.00, 'efectivo', '2026-02-12', '06:38:49', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(83, 135, 'Pago habitación 104 - 4 día(s) (Descuento: Bs. 40.00 - Descuento)', 840.00, 'efectivo', '2026-02-12', '21:57:09', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(84, 137, 'Pago habitación 103 - 1 día(s) (Descuento: Bs. 20.00 - Sin desayuno)', 200.00, 'efectivo', '2026-02-13', '22:49:26', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(85, 139, 'Pago habitación 305 - 1 día(s)', 300.00, 'qr', '2026-02-13', '00:06:40', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(86, 142, 'Pago habitación 208 - 1 día(s)', 320.00, 'efectivo', '2026-02-14', '00:11:21', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(87, 145, 'Pago habitación 203 - 1 día(s)', 140.00, 'efectivo', '2026-02-14', '19:21:37', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(88, 146, 'Pago habitación 206 - 1 día(s)', 220.00, 'qr', '2026-02-14', '19:25:59', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(89, 148, 'Pago habitación 205 - 1 día(s) (Descuento: Bs. 10.00 - Sin desayuno)', 210.00, 'efectivo', '2026-02-14', '23:28:55', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(90, 150, 'Pago habitación 306 - 1 día(s) (Descuento: Bs. 10.00 - Descuento)', 210.00, 'efectivo', '2026-02-14', '23:33:30', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(91, 152, 'Pago habitación 207 - 1 día(s)', 300.00, 'efectivo', '2026-02-14', '23:37:38', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(92, 155, 'Pago habitación 204 - 1 día(s)', 140.00, 'efectivo', '2026-02-14', '06:37:25', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(93, NULL, 'Extensión de estadía - Hab. 203 - Luis Alberto Belaunde Soliz (1 día)', 140.00, 'efectivo', '2026-02-15', '20:28:06', NULL, 'Isaac Vargas'),
(94, NULL, 'Extensión de estadía - Hab. 104 - Cristina Condori Flores (1 día) (Descuento: Bs. 20.00 - Estadía larga)', 200.00, 'efectivo', '2026-02-15', '20:33:16', NULL, 'Isaac Vargas'),
(95, 156, 'Pago habitación 201 - 2 día(s) (Descuento: Bs. 40.00 - Familia con estadía larga de 1 semana)', 240.00, 'efectivo', '2026-02-15', '20:35:44', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(96, NULL, 'Extensión de estadía - Hab. 203 - Luis Alberto Belaunde Soliz (1 día)', 140.00, 'efectivo', '2026-02-16', '08:44:57', NULL, 'Isaac Vargas'),
(97, 157, 'Pago habitación 205 - 1 día(s)', 220.00, 'efectivo', '2026-02-16', '13:40:08', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(98, 159, 'Pago habitación 102 - 1 día(s) (Descuento: Bs. 20.00 - Sin desayuno)', 200.00, 'qr', '2026-02-15', '15:37:12', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(99, NULL, 'Extensión de estadía - Hab. 205 - Elmer Raul Acho Lucas (1 día)', 220.00, 'qr', '2026-02-17', '06:23:08', NULL, 'Isaac Vargas'),
(100, 161, 'Pago habitación 103 - 1 día(s) (Descuento: Bs. 20.00 - Descuento por estancia de solo unas cuantas horas)', 200.00, 'efectivo', '2026-02-17', '08:41:17', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(101, NULL, 'Extensión de estadía - Hab. 203 - Luis Alberto Belaunde Soliz (1 día)', 140.00, 'efectivo', '2026-02-17', '22:18:25', NULL, 'Isaac Vargas'),
(102, 163, 'Pago habitación 304 - 1 día(s)', 220.00, 'efectivo', '2026-02-18', '06:27:50', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(103, NULL, 'Extensión de estadía - Hab. 304 - Luis Jesus Cruz Vargas (1 día)', 220.00, 'efectivo', '2026-02-19', '19:18:16', NULL, 'Isaac Vargas'),
(105, 165, 'Pago habitación 205 - 1 día(s)', 220.00, 'efectivo', '2026-02-19', '06:31:45', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(106, 167, 'Pago habitación 204 - 1 día(s)', 140.00, 'efectivo', '2026-02-20', '06:35:20', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(107, 168, 'Pago habitación 306 - 1 día(s)', 220.00, 'efectivo', '2026-02-20', '21:20:11', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(108, 170, 'Pago habitación 206 - 1 día(s)', 220.00, 'qr', '2026-02-22', '00:13:04', 'Ingreso automático por registro de huésped', 'Isaac Vargas'),
(112, NULL, 'Cobro QR externo: Se cobro en QR el cambio de efectivo', 900.00, 'qr', '2026-02-23', '00:33:25', NULL, 'Isaac Vargas'),
(113, 172, 'Pago habitación 202 - 1 día(s)', 140.00, 'efectivo', '2026-02-23', '18:57:13', 'Ingreso automático por registro de huésped', 'Hotel Cecil'),
(114, 173, 'Pago habitación 205 - 1 día(s) (Descuento: Bs. 120.00 - Pago con qr 120bs y efectivo 100bs)', 100.00, 'efectivo', '2026-02-23', '19:37:44', 'Ingreso automático por registro de huésped', 'Hotel Cecil'),
(115, 175, 'Pago habitación 304 - 1 día(s)', 220.00, 'efectivo', '2026-02-23', '20:54:24', 'Ingreso automático por registro de huésped', 'Hotel Cecil'),
(116, 177, 'Pago habitación 104 - 1 día(s)', 220.00, 'efectivo', '2026-02-23', '22:03:41', 'Ingreso automático por registro de huésped', 'Hotel Cecil'),
(117, 179, 'Pago habitación 306 - 1 día(s)', 220.00, 'efectivo', '2026-02-23', '06:30:27', 'Ingreso automático por registro de huésped', 'Hotel Cecil'),
(118, NULL, 'Extensión de estadía - Hab. 104 - Yvan Garcia Caveros, Carla Yauri Zelada (2 huéspedes, 1 día)', 220.00, 'efectivo', '2026-02-24', '19:10:34', NULL, 'Hotel Cecil'),
(119, 180, 'Pago habitación 204 - 1 día(s)', 140.00, 'efectivo', '2026-02-24', '19:13:14', 'Ingreso automático por registro de huésped', 'Hotel Cecil'),
(120, 181, 'Pago habitación 103 - 1 día(s)', 220.00, 'efectivo', '2026-02-24', '21:35:27', 'Ingreso automático por registro de huésped', 'Hotel Cecil'),
(121, NULL, 'Extensión de estadía - Hab. 304 - Jorge Carlos Armella Jurado, Rolando Mur Sullca (2 huéspedes, 1 día)', 220.00, 'efectivo', '2026-02-24', '22:36:15', NULL, 'Hotel Cecil'),
(122, 183, 'Pago habitación 203 - 1 día(s) (Descuento: Bs. 10.00 - Sin desayuno)', 130.00, 'qr', '2026-02-24', '01:41:29', 'Ingreso automático por registro de huésped', 'Hotel Cecil'),
(123, 184, 'Pago habitación 306 - 1 día(s) (Descuento: Bs. 20.00 - Descuento)', 200.00, 'efectivo', '2026-02-25', '19:42:34', 'Ingreso automático por registro de huésped', 'Hotel Cecil'),
(124, 186, 'Pago habitación 102 - 1 día(s) (Descuento: Bs. 10.00 - Descuento)', 210.00, 'qr', '2026-02-25', '20:35:00', 'Ingreso automático por registro de huésped', 'Hotel Cecil'),
(125, 188, 'Pago habitación 202 - 1 día(s)', 140.00, 'efectivo', '2026-02-25', '21:01:01', 'Ingreso automático por registro de huésped', 'Hotel Cecil'),
(126, 189, 'Pago habitación 208 - 1 día(s) (Descuento: Bs. 20.00 - Descuento)', 300.00, 'efectivo', '2026-02-28', '21:30:11', 'Ingreso automático por registro de huésped', 'Hotel Cecil'),
(127, 193, 'Pago habitación 205 - 1 día(s) (Descuento: Bs. 20.00 - Descuento)', 200.00, 'efectivo', '2026-03-01', '07:27:24', 'Ingreso automático por registro de huésped', 'Hotel Cecil'),
(128, 194, 'Pago habitación 202 - 1 día(s) (Descuento: Bs. 10.00 - Descuento)', 130.00, 'efectivo', '2026-03-02', '07:31:00', 'Ingreso automático por registro de huésped', 'Hotel Cecil'),
(129, 195, 'Pago habitación 201 - 1 día(s) (Descuento: Bs. 10.00 - Descuento)', 130.00, 'efectivo', '2026-03-01', '07:33:01', 'Ingreso automático por registro de huésped', 'Hotel Cecil'),
(130, NULL, 'Extensión de estadía - Hab. 201 - Diana Raquel Rivera Barba (1 huésped, 1 día) (Descuento: Bs. 10.00 - Descuento)', 130.00, 'pendiente', '2026-03-03', '07:38:51', NULL, 'Hotel Cecil'),
(131, 196, 'Pago habitación 204 - 1 día(s) (Descuento: Bs. 10.00 - Descuento)', 130.00, 'qr', '2026-03-05', '22:41:07', 'Ingreso automático por registro de huésped', 'Hotel Cecil'),
(132, 197, 'Pago habitación 203 - 1 día(s)', 140.00, 'qr', '2026-03-05', '22:44:05', 'Ingreso automático por registro de huésped', 'Hotel Cecil'),
(133, 198, 'Pago habitación 201 - 1 día(s)', 140.00, 'qr', '2026-03-05', '22:49:08', 'Ingreso automático por registro de huésped', 'Hotel Cecil'),
(134, 199, 'Pago habitación 306 - 3 día(s) (Descuento: Bs. 60.00 - Solo estara 2 dias y medio)', 600.00, 'qr', '2026-03-06', '08:02:17', 'Ingreso automático por registro de huésped', 'Hotel Cecil'),
(135, 201, 'Pago habitación 104 - 1 día(s)', 220.00, 'qr', '2026-03-06', '20:41:16', 'Ingreso automático por registro de huésped', 'Hotel Cecil'),
(136, NULL, 'Extensión de estadía - Hab. 201 - Paola Karen Lizarazu Araca (1 huésped, 1 día)', 140.00, 'qr', '2026-03-07', '07:53:25', NULL, 'Hotel Cecil');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventario_habitaciones`
--

CREATE TABLE `inventario_habitaciones` (
  `id` int(11) NOT NULL,
  `habitacion_numero` varchar(10) NOT NULL,
  `tipo` varchar(20) DEFAULT 'habitacion',
  `cortinas` int(11) DEFAULT 0,
  `veladores` int(11) DEFAULT 0,
  `roperos` int(11) DEFAULT 0,
  `colgadores` int(11) DEFAULT 0,
  `basureros` int(11) DEFAULT 0,
  `shampoo` int(11) DEFAULT 0,
  `jabon_liquido` int(11) DEFAULT 0,
  `sillas` int(11) DEFAULT 0,
  `sillones` int(11) DEFAULT 0,
  `alfombras` int(11) DEFAULT 0,
  `camas` int(11) DEFAULT 0,
  `television` int(11) DEFAULT 0,
  `lamparas` int(11) DEFAULT 0,
  `manteles` int(11) DEFAULT 0,
  `cubrecamas` int(11) DEFAULT 0,
  `sabanas_media_plaza` int(11) DEFAULT 0,
  `sabanas_doble_plaza` int(11) DEFAULT 0,
  `almohadas` int(11) DEFAULT 0,
  `fundas` int(11) DEFAULT 0,
  `frazadas` int(11) DEFAULT 0,
  `toallas` int(11) DEFAULT 0,
  `cortinas_almacen` int(11) DEFAULT 0,
  `alfombras_almacen` int(11) DEFAULT 0,
  `ultima_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `inventario_habitaciones`
--

INSERT INTO `inventario_habitaciones` (`id`, `habitacion_numero`, `tipo`, `cortinas`, `veladores`, `roperos`, `colgadores`, `basureros`, `shampoo`, `jabon_liquido`, `sillas`, `sillones`, `alfombras`, `camas`, `television`, `lamparas`, `manteles`, `cubrecamas`, `sabanas_media_plaza`, `sabanas_doble_plaza`, `almohadas`, `fundas`, `frazadas`, `toallas`, `cortinas_almacen`, `alfombras_almacen`, `ultima_actualizacion`) VALUES
(1, 'ALMACEN', 'almacen', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '2026-03-01 13:52:11'),
(2, '102', 'habitacion', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '2025-12-28 00:38:37'),
(3, '103', 'habitacion', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '2025-12-24 05:42:23'),
(4, '104', 'habitacion', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '2025-12-24 05:42:23'),
(5, '201', 'habitacion', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '2025-12-24 05:42:23'),
(6, '202', 'habitacion', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '2025-12-24 05:42:23'),
(7, '203', 'habitacion', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '2025-12-24 05:42:23'),
(8, '204', 'habitacion', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '2025-12-24 05:42:23'),
(9, '205', 'habitacion', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '2025-12-24 05:42:23'),
(10, '206', 'habitacion', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '2025-12-24 05:42:23'),
(11, '207', 'habitacion', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '2025-12-24 05:42:23'),
(12, '208', 'habitacion', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '2025-12-24 05:42:23'),
(13, '209', 'habitacion', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '2025-12-24 05:42:23'),
(14, '301', 'habitacion', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '2025-12-24 05:42:23'),
(15, '302', 'habitacion', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '2025-12-24 05:42:23'),
(16, '303', 'habitacion', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '2025-12-24 05:42:23'),
(17, '304', 'habitacion', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '2025-12-24 05:42:23'),
(18, '305', 'habitacion', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '2025-12-24 05:42:23'),
(19, '306', 'habitacion', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '2025-12-24 05:42:23'),
(20, '307', 'habitacion', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '2025-12-24 05:42:23');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mantenimientos`
--

CREATE TABLE `mantenimientos` (
  `id` int(11) NOT NULL,
  `habitacion_numero` varchar(10) NOT NULL,
  `titulo` varchar(200) NOT NULL,
  `descripcion` text NOT NULL,
  `prioridad` enum('baja','media','alta','urgente') NOT NULL DEFAULT 'media',
  `tipo` enum('preventivo','correctivo','emergencia') NOT NULL DEFAULT 'correctivo',
  `estado` enum('pendiente','en_proceso','completado','cancelado') NOT NULL DEFAULT 'pendiente',
  `costo_estimado` decimal(10,2) DEFAULT NULL,
  `costo_real` decimal(10,2) DEFAULT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin_estimada` date DEFAULT NULL,
  `fecha_fin_real` date DEFAULT NULL,
  `responsable` varchar(100) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `mantenimientos`
--

INSERT INTO `mantenimientos` (`id`, `habitacion_numero`, `titulo`, `descripcion`, `prioridad`, `tipo`, `estado`, `costo_estimado`, `costo_real`, `fecha_inicio`, `fecha_fin_estimada`, `fecha_fin_real`, `responsable`, `observaciones`, `imagen`, `created_at`, `updated_at`) VALUES
(28, '202', 'Supuesto colchón con resorte', 'Huéspedes informaron que el colchón tiene un resorte salido en la sección mensionada, aun que no es visible al apoyarse se siente y escucha un sonido similar al de un resorte', 'media', 'emergencia', 'pendiente', NULL, NULL, '2026-02-02', '2026-02-04', NULL, 'Nisan', 'Sin más observaciones', '202_20260202_090950.jpg', '2026-02-02 13:09:50', '2026-02-02 13:09:50');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos_qr`
--

CREATE TABLE `pagos_qr` (
  `id` int(11) NOT NULL,
  `ocupacion_id` int(11) DEFAULT NULL,
  `monto` decimal(10,2) NOT NULL,
  `fecha` date NOT NULL,
  `hora` time DEFAULT NULL,
  `numero_transaccion` varchar(100) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `recepcionista` varchar(100) DEFAULT NULL,
  `concepto` varchar(255) DEFAULT NULL,
  `tipo` enum('huesped','externo') DEFAULT 'huesped'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `pagos_qr`
--

INSERT INTO `pagos_qr` (`id`, `ocupacion_id`, `monto`, `fecha`, `hora`, `numero_transaccion`, `observaciones`, `recepcionista`, `concepto`, `tipo`) VALUES
(14, 39, 140.00, '2025-12-01', '01:44:25', '', 'Pago QR por habitación 201', 'Isaac Vargas', NULL, 'huesped'),
(15, 73, 220.00, '2025-12-28', '15:53:44', '', 'Pago QR por habitación 104', 'Isaac Vargas', NULL, 'huesped'),
(16, 75, 220.00, '2025-12-28', '16:23:57', '', 'Pago QR por habitación 306', 'Isaac Vargas', NULL, 'huesped'),
(17, 95, 140.00, '2026-02-04', '21:59:04', '', 'Pago QR por habitación 201', 'Isaac Vargas', NULL, 'huesped'),
(18, 114, 220.00, '2026-02-07', '06:02:07', '', 'Pago QR por habitación 205', 'Isaac Vargas', NULL, 'huesped'),
(19, 116, 140.00, '2026-02-07', '06:04:40', '', 'Pago QR por habitación 203', 'Isaac Vargas', NULL, 'huesped'),
(20, 139, 300.00, '2026-02-13', '00:06:40', '', 'Pago QR por habitación 305', 'Isaac Vargas', NULL, 'huesped'),
(21, 146, 220.00, '2026-02-14', '19:25:59', '', 'Pago QR por habitación 206', 'Isaac Vargas', NULL, 'huesped'),
(22, NULL, 880.00, '2026-02-12', '20:06:42', '', 'Se pago por QR el egreso en efectivo de 880, habitaciones 203,206,207 y 209', 'Isaac Vargas', NULL, 'huesped'),
(23, 159, 200.00, '2026-02-15', '15:37:12', '', 'Pago QR por habitación 102', 'Isaac Vargas', NULL, 'huesped'),
(24, NULL, 220.00, '2026-02-17', '06:23:08', NULL, 'Cambio de método de pago desde PENDIENTE', 'Isaac Vargas', NULL, 'huesped'),
(25, NULL, 140.00, '2026-02-22', '09:05:30', NULL, 'Cambio de método de pago desde PENDIENTE', 'Isaac Vargas', NULL, 'huesped'),
(26, NULL, 900.00, '2026-02-22', '21:58:58', '', 'Egreso de 900bs reestablecido en QR', 'Isaac Vargas', NULL, 'huesped'),
(27, NULL, 900.00, '2026-02-23', '00:33:25', '', 'Comprobante enviado a don Rodolfo', 'Isaac Vargas', 'Se cobro en QR el cambio de efectivo', 'externo'),
(28, NULL, 220.00, '2026-02-22', '13:20:12', NULL, 'Cambio de método de pago desde PENDIENTE', 'Gabriel Duran', NULL, 'externo'),
(29, 170, 220.00, '2026-02-22', '00:13:04', NULL, 'Cambio de método de pago desde EFECTIVO', 'Gabriel Duran', NULL, 'huesped'),
(30, 183, 130.00, '2026-02-24', '01:41:29', '', 'Pago QR por habitación 203', 'Hotel Cecil', NULL, 'huesped'),
(31, 186, 210.00, '2026-02-25', '20:35:00', '', 'Pago QR por habitación 102', 'Hotel Cecil', NULL, 'huesped'),
(32, 196, 130.00, '2026-03-05', '22:41:07', '', 'Pago QR por habitación 204', 'Hotel Cecil', NULL, 'huesped'),
(33, 197, 140.00, '2026-03-05', '22:44:05', '', 'Pago QR por habitación 203', 'Hotel Cecil', NULL, 'huesped'),
(34, 198, 140.00, '2026-03-05', '22:49:08', '', 'Pago QR por habitación 201', 'Hotel Cecil', NULL, 'huesped'),
(35, 199, 600.00, '2026-03-06', '08:02:17', '', 'Pago QR por habitación 306', 'Hotel Cecil', NULL, 'huesped'),
(36, 201, 220.00, '2026-03-06', '20:41:16', '', 'Pago QR por habitación 104', 'Hotel Cecil', NULL, 'huesped'),
(37, NULL, 140.00, '2026-03-07', '07:53:25', NULL, 'Cambio de método de pago desde PENDIENTE', 'Hotel Cecil', NULL, 'externo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `registro_garaje`
--

CREATE TABLE `registro_garaje` (
  `id` int(11) NOT NULL,
  `ocupacion_id` int(11) NOT NULL,
  `huesped_nombre` varchar(255) NOT NULL,
  `placa` varchar(20) DEFAULT NULL COMMENT 'Número de placa del vehículo',
  `tipo_vehiculo` varchar(50) DEFAULT NULL COMMENT 'Tipo de vehículo',
  `fecha` date NOT NULL,
  `costo` decimal(10,2) NOT NULL DEFAULT 10.00,
  `observaciones` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `registro_garaje`
--

INSERT INTO `registro_garaje` (`id`, `ocupacion_id`, `huesped_nombre`, `placa`, `tipo_vehiculo`, `fecha`, `costo`, `observaciones`, `created_at`) VALUES
(5, 65, 'Javier Chumacero Barrios', NULL, NULL, '2026-01-31', 10.00, 'Habitación 204', '2026-02-01 14:14:01'),
(6, 94, 'Martin Cuenca Nicacio', NULL, NULL, '2026-02-02', 10.00, 'Habitación 203', '2026-02-03 02:02:57'),
(7, 101, 'Eynar Ernesto Ramos Patton', NULL, NULL, '2026-02-05', 10.00, 'Habitación 206', '2026-02-06 03:37:42'),
(8, 103, 'Juan Carlos Choqueticlla Santos', NULL, NULL, '2026-02-06', 10.00, 'Habitación 208', '2026-02-07 08:07:54'),
(9, 107, 'Marco Antonio Duran Loredo', NULL, NULL, '2026-02-07', 10.00, 'Habitación 209', '2026-02-07 23:40:04'),
(10, 110, 'Andres Nelson Condori Martinez', NULL, NULL, '2026-02-07', 10.00, 'Habitación 306', '2026-02-08 03:32:49'),
(11, 118, 'Cristina Condori Flores', NULL, NULL, '2026-02-08', 10.00, 'Habitación 104', '2026-02-08 18:11:47'),
(12, 125, 'Jaime Garcia Torres', '4407ZIR', 'Minibús', '2026-02-11', 10.00, 'Habitación 202', '2026-02-12 04:02:36'),
(13, 135, 'Cristina Condori Flores', 'CRK 577', 'Vagoneta', '2026-02-12', 10.00, 'Habitación 104', '2026-02-13 01:57:09'),
(14, 137, 'Wilzon Miguel Gutierrez Carrasco', '3439 LTI', 'Vagoneta', '2026-02-13', 10.00, 'Habitación 103', '2026-02-14 02:49:26'),
(15, 139, 'Victoria Gladys Vargas Iriarte', '5619PPL', 'Vagoneta', '2026-02-13', 10.00, 'Habitación 305', '2026-02-14 04:06:40'),
(16, 142, 'Luis Fernando Revollo Larrain', '5619PPL', 'Vagoneta', '2026-02-14', 10.00, 'Habitación 208', '2026-02-14 04:11:21'),
(17, 148, 'Boris Daniel Soleto Antezana', '6275LPE', 'Camioneta', '2026-02-14', 10.00, 'Habitación 205', '2026-02-15 03:28:55'),
(18, 150, 'Amilcar Luis Chavez Herbas', '-', 'Vagoneta', '2026-02-14', 10.00, 'Habitación 306', '2026-02-15 03:33:30'),
(19, 157, 'Elmer Raul Acho Lucas', '246XDT', 'Automóvil', '2026-02-16', 10.00, 'Habitación 205', '2026-02-16 17:40:08'),
(20, 168, 'Maximo Rodriguez Apaza', '3098 FXE', 'Camioneta', '2026-02-20', 10.00, 'Habitación 306', '2026-02-21 01:20:11'),
(21, 170, 'Maximo Rodriguez Ceballos', 'TCPW14', 'Vagoneta', '2026-02-21', 10.00, 'Habitación 206', '2026-02-22 02:13:04'),
(22, 175, 'Jorge Carlos Armella Jurado', 'CAMION', 'Otro', '2026-02-23', 10.00, 'Habitación 304', '2026-02-24 00:54:24'),
(23, 177, 'Yvan Garcia Caveros', '6440SDB', 'Camioneta', '2026-02-23', 10.00, 'Habitación 104', '2026-02-24 02:03:41'),
(24, 179, 'Wigmar Hugo Flores Carmona', '5648RTC', 'Minibús', '2026-02-23', 10.00, 'Habitación 306', '2026-02-24 10:30:27'),
(25, 181, 'Jose Ricardo Bolivar Condori', '3004HPX', 'Camioneta', '2026-02-24', 10.00, 'Habitación 103', '2026-02-25 01:35:27'),
(26, 186, 'Jose Ricardo Bolivar Condori', '3004HPX', 'Camioneta', '2026-02-25', 10.00, 'Habitación 102', '2026-02-26 00:35:00'),
(27, 189, 'Miguel Angel Mamani Flores', '1634GUS', 'Automóvil', '2026-02-28', 10.00, 'Habitación 208', '2026-03-01 01:30:11'),
(28, 194, 'Jose Reynaldo Salinas Zapata', '3001UUC', 'Camioneta', '2026-03-02', 10.00, 'Habitación 202', '2026-03-03 11:31:00'),
(29, 201, 'Alexander Romero Chavarria', '6234NCI', 'Camioneta', '2026-03-06', 10.00, 'Habitación 104', '2026-03-07 00:41:16');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `registro_ocupacion`
--

CREATE TABLE `registro_ocupacion` (
  `id` int(11) NOT NULL,
  `huesped_id` int(11) NOT NULL,
  `habitacion_id` int(11) NOT NULL,
  `nro_pieza` varchar(10) NOT NULL,
  `prox_destino` varchar(150) DEFAULT NULL,
  `via_ingreso` varchar(50) DEFAULT NULL,
  `fecha_ingreso` date NOT NULL,
  `nro_dias` int(11) NOT NULL,
  `fecha_salida_estimada` date DEFAULT NULL,
  `fecha_salida_real` date DEFAULT NULL,
  `estado` enum('activo','finalizado') DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `registro_ocupacion`
--

INSERT INTO `registro_ocupacion` (`id`, `huesped_id`, `habitacion_id`, `nro_pieza`, `prox_destino`, `via_ingreso`, `fecha_ingreso`, `nro_dias`, `fecha_salida_estimada`, `fecha_salida_real`, `estado`) VALUES
(39, 29, 4, '201', 'La Paz', 'T', '2025-12-01', 1, '2025-12-02', '2026-01-16', 'finalizado'),
(40, 30, 15, '303', 'Tarija', 'T', '2025-12-01', 2, '2025-12-03', '2026-01-16', 'finalizado'),
(41, 31, 15, '303', 'Tarija', 'T', '2025-12-01', 2, '2025-12-03', '2026-01-16', 'finalizado'),
(42, 32, 13, '301', 'Cochabamba', 'T', '2025-12-03', 1, '2025-12-04', '2026-01-16', 'finalizado'),
(43, 33, 13, '301', 'Cochabamba', 'T', '2025-12-03', 1, '2025-12-04', '2026-01-16', 'finalizado'),
(44, 34, 3, '104', 'Cochabamba', 'T', '2025-12-01', 2, '2025-12-03', '2026-01-16', 'finalizado'),
(45, 34, 3, '104', 'Cochabamba', 'T', '2025-12-01', 2, '2025-12-03', '2026-01-16', 'finalizado'),
(46, 35, 1, '102', 'Potosí', 'T', '2025-12-06', 1, '2025-12-07', '2026-01-16', 'finalizado'),
(47, 36, 1, '102', 'Potosí', 'T', '2025-12-06', 1, '2025-12-07', '2026-01-16', 'finalizado'),
(48, 37, 11, '208', 'Potosí', 'T', '2025-12-06', 1, '2025-12-07', '2026-01-26', 'finalizado'),
(49, 38, 11, '208', 'Potosí', 'T', '2025-12-06', 1, '2025-12-07', '2026-01-26', 'finalizado'),
(50, 39, 11, '208', 'Potosí', 'T', '2025-12-06', 1, '2025-12-07', '2026-01-26', 'finalizado'),
(51, 40, 11, '208', 'Potosí', 'T', '2025-12-06', 1, '2025-12-07', '2026-01-26', 'finalizado'),
(52, 41, 8, '205', 'Santa Cruz', 'T', '2025-12-17', 1, '2025-12-18', '2026-01-26', 'finalizado'),
(53, 42, 8, '205', 'Santa Cruz', 'T', '2025-12-17', 1, '2025-12-18', '2026-01-26', 'finalizado'),
(54, 43, 18, '306', 'Santa Cruz', 'T', '2025-12-18', 1, '2025-12-19', '2026-01-26', 'finalizado'),
(55, 44, 18, '306', 'Santa Cruz', 'T', '2025-12-18', 1, '2025-12-19', '2026-01-26', 'finalizado'),
(56, 45, 7, '204', 'Potosí', 'T', '2025-12-19', 1, '2025-12-20', '2026-01-26', 'finalizado'),
(57, 46, 6, '203', 'Oruro', 'T', '2025-12-19', 1, '2025-12-20', '2026-01-26', 'finalizado'),
(58, 47, 14, '302', 'Cochabamba', 'T', '2025-12-20', 1, '2025-12-21', '2026-01-26', 'finalizado'),
(59, 48, 7, '204', 'Tarija', 'T', '2025-12-26', 1, '2025-12-27', '2026-01-26', 'finalizado'),
(60, 49, 11, '208', 'Tarija', 'T', '2025-12-26', 1, '2025-12-27', '2026-01-26', 'finalizado'),
(61, 50, 11, '208', 'Tarija', 'T', '2025-12-26', 1, '2025-12-27', '2026-01-26', 'finalizado'),
(62, 51, 11, '208', 'Tarija', 'T', '2025-12-26', 1, '2025-12-27', '2026-01-26', 'finalizado'),
(63, 52, 4, '201', 'Potosí', 'T', '2026-01-31', 1, '2026-02-01', '2026-02-01', 'finalizado'),
(64, 53, 6, '203', 'Potosí', 'T', '2026-01-31', 1, '2026-02-01', '2026-02-01', 'finalizado'),
(65, 54, 7, '204', 'Potosí', 'T', '2026-01-31', 1, '2026-02-01', '2026-02-01', 'finalizado'),
(66, 55, 16, '304', 'Cochabamba', 'T', '2025-12-26', 1, '2025-12-27', '2026-02-01', 'finalizado'),
(67, 56, 16, '304', 'Cochabamba', 'T', '2025-12-26', 1, '2025-12-27', '2026-02-01', 'finalizado'),
(68, 57, 14, '302', 'Cochabamba', 'T', '2025-12-26', 1, '2025-12-27', '2026-02-01', 'finalizado'),
(69, 58, 14, '302', 'Cochabamba', 'T', '2025-12-26', 1, '2025-12-27', '2026-02-01', 'finalizado'),
(70, 59, 10, '207', 'Santa Cruz', 'T', '2025-12-27', 1, '2025-12-28', '2026-02-01', 'finalizado'),
(71, 60, 10, '207', 'Santa Cruz', 'T', '2025-12-27', 1, '2025-12-28', '2026-02-01', 'finalizado'),
(72, 61, 10, '207', 'Santa Cruz', 'T', '2025-12-27', 1, '2025-12-28', '2026-02-01', 'finalizado'),
(73, 62, 3, '104', 'Oruro', 'T', '2025-12-28', 1, '2025-12-29', '2026-02-01', 'finalizado'),
(74, 63, 3, '104', 'Oruro', 'T', '2025-12-28', 1, '2025-12-29', '2026-02-01', 'finalizado'),
(75, 64, 18, '306', 'Tarija', 'T', '2025-12-28', 1, '2025-12-29', '2026-02-01', 'finalizado'),
(76, 65, 18, '306', 'Tarija', 'T', '2025-12-28', 1, '2025-12-29', '2026-02-01', 'finalizado'),
(77, 66, 9, '206', 'Santa Cruz', 'T', '2025-12-30', 1, '2025-12-31', '2026-02-01', 'finalizado'),
(78, 67, 9, '206', 'Santa Cruz', 'T', '2025-12-30', 1, '2025-12-31', '2026-02-01', 'finalizado'),
(79, 68, 16, '304', 'La Paz', 'T', '2025-12-31', 1, '2026-01-01', '2026-02-01', 'finalizado'),
(80, 69, 16, '304', 'La Paz', 'T', '2025-12-31', 1, '2026-01-01', '2026-02-01', 'finalizado'),
(81, 70, 15, '303', 'La Paz', 'T', '2025-12-31', 1, '2026-01-01', '2026-02-01', 'finalizado'),
(82, 71, 15, '303', 'La Paz', 'T', '2025-12-31', 1, '2026-01-01', '2026-02-01', 'finalizado'),
(83, 72, 18, '306', 'La Paz', 'T', '2025-12-31', 1, '2026-01-01', '2026-02-01', 'finalizado'),
(84, 73, 5, '202', 'La Paz', 'T', '2025-12-31', 1, '2026-01-01', '2026-02-01', 'finalizado'),
(85, 74, 6, '203', 'La Paz', 'T', '2025-12-31', 1, '2026-01-01', '2026-02-01', 'finalizado'),
(86, 75, 7, '204', 'La Paz', 'T', '2025-12-31', 1, '2026-01-01', '2026-02-01', 'finalizado'),
(87, 76, 14, '302', 'La Paz', 'T', '2025-12-31', 1, '2026-01-01', '2026-02-01', 'finalizado'),
(88, 77, 8, '205', 'La Paz', 'T', '2025-12-31', 1, '2026-01-01', '2026-02-01', 'finalizado'),
(89, 78, 8, '205', 'La Paz', 'T', '2025-12-31', 1, '2026-01-01', '2026-02-01', 'finalizado'),
(90, 79, 1, '102', 'Potosí', 'T', '2025-12-31', 1, '2026-01-01', '2026-02-01', 'finalizado'),
(91, 80, 1, '102', 'Potosí', 'T', '2025-12-31', 1, '2026-01-01', '2026-02-01', 'finalizado'),
(92, 81, 3, '104', 'Potosí', 'T', '2025-12-31', 1, '2026-01-01', '2026-02-01', 'finalizado'),
(93, 82, 8, '205', 'Potosí', 'T', '2026-02-01', 1, '2026-02-02', '2026-02-02', 'finalizado'),
(94, 83, 6, '203', 'Cochabamba', 'T', '2026-02-02', 1, '2026-02-03', '2026-02-04', 'finalizado'),
(95, 29, 4, '201', 'La paz', 'T', '2026-02-04', 1, '2026-02-05', '2026-02-05', 'finalizado'),
(96, 84, 5, '202', 'Potosí', 'T', '2026-02-04', 1, '2026-02-05', '2026-02-05', 'finalizado'),
(97, 85, 10, '207', 'Cochabamba', 'T', '2026-02-05', 1, '2026-02-06', '2026-02-07', 'finalizado'),
(98, 86, 10, '207', 'Cochabamba', 'T', '2026-02-05', 1, '2026-02-06', '2026-02-07', 'finalizado'),
(99, 87, 10, '207', 'Cochabamba', 'T', '2026-02-05', 1, '2026-02-06', '2026-02-07', 'finalizado'),
(100, 88, 6, '203', 'Santa Cruz', 'T', '2026-02-05', 1, '2026-02-06', '2026-02-07', 'finalizado'),
(101, 89, 9, '206', 'Santa Cruz', 'T', '2026-02-05', 1, '2026-02-06', '2026-02-07', 'finalizado'),
(102, 90, 9, '206', 'Santa Cruz', 'T', '2026-02-05', 1, '2026-02-06', '2026-02-07', 'finalizado'),
(103, 91, 11, '208', 'Potosí', 'T', '2026-02-06', 1, '2026-02-07', '2026-02-07', 'finalizado'),
(104, 92, 11, '208', 'Potosí', 'T', '2026-02-06', 1, '2026-02-07', '2026-02-07', 'finalizado'),
(105, 93, 11, '208', 'Potosí', 'T', '2026-02-06', 1, '2026-02-07', '2026-02-07', 'finalizado'),
(106, 94, 11, '208', 'Potosí', 'T', '2026-02-06', 1, '2026-02-07', '2026-02-07', 'finalizado'),
(107, 95, 12, '209', 'Cochabamba', 'T', '2026-02-07', 1, '2026-02-08', '2026-02-08', 'finalizado'),
(108, 96, 12, '209', 'Cochabamba', 'T', '2026-02-07', 1, '2026-02-08', '2026-02-08', 'finalizado'),
(109, 97, 12, '209', 'Cochabamba', 'T', '2026-02-07', 1, '2026-02-08', '2026-02-08', 'finalizado'),
(110, 98, 18, '306', 'Potosí', 'T', '2026-02-07', 1, '2026-02-08', '2026-02-08', 'finalizado'),
(111, 99, 18, '306', 'Potosí', 'T', '2026-02-07', 1, '2026-02-08', '2026-02-08', 'finalizado'),
(112, 100, 15, '303', 'Potosí', 'T', '2026-02-07', 1, '2026-02-08', '2026-02-08', 'finalizado'),
(113, 101, 15, '303', 'Potosí', 'T', '2026-02-07', 1, '2026-02-08', '2026-02-08', 'finalizado'),
(114, 102, 8, '205', 'Santa Cruz', 'T', '2026-02-07', 2, '2026-02-08', '2026-02-08', 'finalizado'),
(115, 103, 8, '205', 'Santa Cruz', 'T', '2026-02-07', 2, '2026-02-08', '2026-02-08', 'finalizado'),
(116, 104, 6, '203', 'Santa Cruz', 'T', '2026-02-07', 2, '2026-02-08', '2026-02-08', 'finalizado'),
(117, 105, 7, '204', 'Santa Cruz', 'T', '2026-02-07', 1, '2026-02-09', '2026-02-09', 'finalizado'),
(118, 106, 3, '104', 'La paz', 'T', '2026-02-08', 4, '2026-02-12', '2026-02-12', 'finalizado'),
(119, 107, 3, '104', 'La paz', 'T', '2026-02-08', 4, '2026-02-12', '2026-02-12', 'finalizado'),
(120, 108, 4, '201', 'La paz', 'T', '2026-02-08', 4, '2026-02-12', '2026-02-12', 'finalizado'),
(121, 102, 8, '205', NULL, 'T', '2026-02-09', 1, '2026-02-10', '2026-02-09', 'finalizado'),
(122, 103, 8, '205', NULL, 'T', '2026-02-09', 1, '2026-02-10', '2026-02-09', 'finalizado'),
(123, 104, 6, '203', 'Santa Cruz', 'T', '2026-02-09', 1, '2026-02-10', '2026-02-09', 'finalizado'),
(124, 109, 7, '204', 'Potosí', 'T', '2026-02-11', 1, '2026-02-12', '2026-02-12', 'finalizado'),
(125, 32, 5, '202', 'Potosí', 'T', '2026-02-11', 1, '2026-02-12', '2026-02-12', 'finalizado'),
(126, 110, 6, '203', 'Santa Cruz', 'T', '2026-02-12', 1, '2026-02-13', '2026-02-13', 'finalizado'),
(127, 111, 9, '206', 'Santa Cruz', 'T', '2026-02-12', 1, '2026-02-13', '2026-02-13', 'finalizado'),
(128, 112, 9, '206', 'Santa Cruz', 'T', '2026-02-12', 1, '2026-02-13', '2026-02-13', 'finalizado'),
(129, 113, 10, '207', 'Santa Cruz', 'T', '2026-02-12', 1, '2026-02-13', '2026-02-13', 'finalizado'),
(130, 114, 10, '207', 'Santa Cruz', 'T', '2026-02-12', 1, '2026-02-13', '2026-02-13', 'finalizado'),
(131, 115, 10, '207', 'Santa Cruz', 'T', '2026-02-12', 1, '2026-02-13', '2026-02-13', 'finalizado'),
(132, 116, 12, '209', 'Santa Cruz', 'T', '2026-02-12', 1, '2026-02-13', '2026-02-13', 'finalizado'),
(133, 117, 12, '209', 'Santa Cruz', 'T', '2026-02-12', 1, '2026-02-13', '2026-02-13', 'finalizado'),
(134, 118, 12, '209', 'Santa Cruz', 'T', '2026-02-12', 1, '2026-02-13', '2026-02-13', 'finalizado'),
(135, 106, 3, '104', 'Santa Cruz', 'T', '2026-02-12', 5, '2026-02-17', '2026-02-17', 'finalizado'),
(136, 108, 3, '104', 'Santa Cruz', 'T', '2026-02-12', 4, '2026-02-16', '2026-02-15', 'finalizado'),
(137, 119, 2, '103', 'Santa Cruz', 'T', '2026-02-13', 1, '2026-02-14', '2026-02-14', 'finalizado'),
(138, 120, 2, '103', 'Santa Cruz', 'T', '2026-02-13', 1, '2026-02-14', '2026-02-14', 'finalizado'),
(139, 121, 17, '305', 'Cochabamba', 'T', '2026-02-13', 1, '2026-02-14', '2026-02-14', 'finalizado'),
(140, 122, 17, '305', 'Cochabamba', 'T', '2026-02-13', 1, '2026-02-14', '2026-02-14', 'finalizado'),
(141, 123, 17, '305', 'Cochabamba', 'T', '2026-02-13', 1, '2026-02-14', '2026-02-14', 'finalizado'),
(142, 124, 11, '208', 'Cochabamba', 'T', '2026-02-14', 1, '2026-02-15', '2026-02-14', 'finalizado'),
(143, 125, 11, '208', 'Cochabamba', 'T', '2026-02-14', 1, '2026-02-15', '2026-02-14', 'finalizado'),
(144, 126, 11, '208', 'Cochabamba', 'T', '2026-02-14', 1, '2026-02-15', '2026-02-14', 'finalizado'),
(145, 127, 6, '203', 'Santa Cruz', 'T', '2026-02-14', 4, '2026-02-18', '2026-02-18', 'finalizado'),
(146, 128, 9, '206', 'Potosí', 'T', '2026-02-14', 1, '2026-02-15', '2026-02-15', 'finalizado'),
(147, 129, 9, '206', 'Potosí', 'T', '2026-02-14', 1, '2026-02-15', '2026-02-15', 'finalizado'),
(148, 130, 8, '205', 'Santa Cruz', 'T', '2026-02-14', 1, '2026-02-15', '2026-02-15', 'finalizado'),
(149, 131, 8, '205', 'Santa Cruz', 'T', '2026-02-14', 1, '2026-02-15', '2026-02-15', 'finalizado'),
(150, 132, 18, '306', 'Cochabamba', 'T', '2026-02-14', 1, '2026-02-15', '2026-02-15', 'finalizado'),
(151, 133, 18, '306', 'Cochabamba', 'T', '2026-02-14', 1, '2026-02-15', '2026-02-15', 'finalizado'),
(152, 134, 10, '207', 'Cochabamba', 'T', '2026-02-14', 1, '2026-02-15', '2026-02-15', 'finalizado'),
(153, 135, 10, '207', 'Cochabamba', 'T', '2026-02-14', 1, '2026-02-15', '2026-02-15', 'finalizado'),
(154, 136, 10, '207', 'Cochabamba', 'T', '2026-02-14', 1, '2026-02-15', '2026-02-15', 'finalizado'),
(155, 137, 7, '204', 'Santa Cruz', 'T', '2026-02-15', 1, '2026-02-16', '2026-02-15', 'finalizado'),
(156, 108, 4, '201', 'La Paz', 'T', '2026-02-15', 2, '2026-02-17', '2026-02-17', 'finalizado'),
(157, 138, 8, '205', 'Potosí', 'T', '2026-02-16', 2, '2026-02-18', '2026-02-18', 'finalizado'),
(158, 139, 8, '205', 'Potosí', 'T', '2026-02-16', 2, '2026-02-18', '2026-02-18', 'finalizado'),
(159, 140, 1, '102', 'Tarija', 'T', '2026-02-15', 1, '2026-02-16', '2026-02-16', 'finalizado'),
(160, 141, 1, '102', 'Tarija', 'T', '2026-02-15', 1, '2026-02-16', '2026-02-16', 'finalizado'),
(161, 142, 2, '103', NULL, 'T', '2026-02-17', 1, '2026-02-18', '2026-02-17', 'finalizado'),
(162, 143, 2, '103', NULL, 'T', '2026-02-17', 1, '2026-02-18', '2026-02-17', 'finalizado'),
(163, 144, 16, '304', 'Santa Cruz', 'T', '2026-02-18', 2, '2026-02-20', '2026-02-20', 'finalizado'),
(164, 145, 16, '304', 'Santa Cruz', 'T', '2026-02-18', 2, '2026-02-20', '2026-02-20', 'finalizado'),
(165, 146, 8, '205', 'Potosí', 'T', '2026-02-19', 1, '2026-02-20', '2026-02-20', 'finalizado'),
(166, 147, 8, '205', 'Potosí', 'T', '2026-02-19', 1, '2026-02-20', '2026-02-20', 'finalizado'),
(167, 148, 7, '204', 'Monteagudo', 'T', '2026-02-20', 2, '2026-02-24', '2026-02-22', 'finalizado'),
(168, 149, 18, '306', 'Potosí', 'T', '2026-02-20', 1, '2026-02-21', '2026-02-21', 'finalizado'),
(169, 150, 18, '306', 'Potosí', 'T', '2026-02-20', 1, '2026-02-21', '2026-02-21', 'finalizado'),
(170, 151, 9, '206', 'Chile', 'T', '2026-02-22', 1, '2026-02-23', '2026-02-23', 'finalizado'),
(171, 152, 9, '206', 'Chile', 'T', '2026-02-22', 1, '2026-02-23', '2026-02-23', 'finalizado'),
(172, 153, 5, '202', 'La Paz', 'T', '2026-02-23', 1, '2026-02-24', '2026-02-24', 'finalizado'),
(173, 154, 8, '205', 'Cochabamba', 'T', '2026-02-23', 1, '2026-02-24', '2026-02-24', 'finalizado'),
(174, 155, 8, '205', 'Cochabamba', 'T', '2026-02-23', 1, '2026-02-24', '2026-02-24', 'finalizado'),
(175, 31, 16, '304', 'Tarija', 'T', '2026-02-23', 2, '2026-02-25', '2026-02-25', 'finalizado'),
(176, 156, 16, '304', 'Tarija', 'T', '2026-02-23', 2, '2026-02-25', '2026-02-25', 'finalizado'),
(177, 157, 3, '104', 'Cochabamba', 'T', '2026-02-23', 2, '2026-02-25', '2026-02-25', 'finalizado'),
(178, 158, 3, '104', 'Cochabamba', 'T', '2026-02-23', 2, '2026-02-25', '2026-02-25', 'finalizado'),
(179, 159, 18, '306', 'Camargo', 'T', '2026-02-23', 1, '2026-02-24', '2026-02-24', 'finalizado'),
(180, 160, 7, '204', 'Potosí', 'T', '2026-02-24', 1, '2026-02-25', '2026-02-25', 'finalizado'),
(181, 161, 2, '103', 'Potosí', 'T', '2026-02-24', 1, '2026-02-25', '2026-02-25', 'finalizado'),
(182, 162, 2, '103', 'Potosí', 'T', '2026-02-24', 1, '2026-02-25', '2026-02-25', 'finalizado'),
(183, 163, 6, '203', 'La Paz', 'T', '2026-02-24', 1, '2026-02-25', '2026-02-25', 'finalizado'),
(184, 164, 18, '306', 'Oruro', 'T', '2026-02-25', 1, '2026-02-26', '2026-02-25', 'finalizado'),
(185, 165, 18, '306', 'Oruro', 'T', '2026-02-25', 1, '2026-02-26', '2026-02-25', 'finalizado'),
(186, 161, 1, '102', 'Potosí', 'T', '2026-02-25', 1, '2026-02-26', '2026-02-26', 'finalizado'),
(187, 162, 1, '102', 'Potosí', 'T', '2026-02-25', 1, '2026-02-26', '2026-02-26', 'finalizado'),
(188, 166, 5, '202', 'Santa Cruz', 'T', '2026-02-25', 1, '2026-02-26', '2026-02-26', 'finalizado'),
(189, 167, 11, '208', 'La Paz', 'T', '2026-02-28', 1, '2026-03-01', '2026-03-01', 'finalizado'),
(190, 168, 11, '208', 'La Paz', 'T', '2026-02-28', 1, '2026-03-01', '2026-03-01', 'finalizado'),
(191, 169, 11, '208', 'La Paz', 'T', '2026-02-28', 1, '2026-03-01', '2026-03-01', 'finalizado'),
(192, 170, 11, '208', 'La Paz', 'T', '2026-02-28', 1, '2026-03-01', '2026-03-01', 'finalizado'),
(193, 171, 8, '205', 'Potosí', 'T', '2026-03-01', 1, '2026-03-02', '2026-03-02', 'finalizado'),
(194, 172, 5, '202', 'Potosí', 'T', '2026-03-02', 1, '2026-03-03', '2026-03-03', 'finalizado'),
(195, 173, 4, '201', 'Potosí', 'T', '2026-03-01', 2, '2026-03-03', '2026-03-03', 'finalizado'),
(196, 174, 7, '204', 'Potosí', 'T', '2026-03-05', 1, '2026-03-06', '2026-03-06', 'finalizado'),
(197, 175, 6, '203', 'Potosí', 'T', '2026-03-05', 1, '2026-03-06', '2026-03-06', 'finalizado'),
(198, 176, 4, '201', 'Potosí', 'T', '2026-03-05', 2, '2026-03-07', '2026-03-07', 'finalizado'),
(199, 177, 18, '306', 'Tarija', 'T', '2026-03-06', 3, '2026-03-09', NULL, 'activo'),
(200, 178, 18, '306', 'Tarija', 'T', '2026-03-06', 3, '2026-03-09', NULL, 'activo'),
(201, 179, 3, '104', 'Santa Cruz', 'T', '2026-03-06', 1, '2026-03-07', '2026-03-07', 'finalizado'),
(202, 180, 3, '104', 'Santa Cruz', 'T', '2026-03-06', 1, '2026-03-07', '2026-03-07', 'finalizado');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `turno_recepcionista`
--

CREATE TABLE `turno_recepcionista` (
  `id` int(11) NOT NULL,
  `recepcionista_nombre` varchar(100) NOT NULL,
  `fecha_inicio` datetime NOT NULL,
  `fecha_fin` datetime DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `observaciones` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `turno_recepcionista`
--

INSERT INTO `turno_recepcionista` (`id`, `recepcionista_nombre`, `fecha_inicio`, `fecha_fin`, `activo`, `observaciones`, `created_at`) VALUES
(1, 'Isaac Vargas', '2026-02-23 00:25:01', NULL, 1, 'Turno inicial del sistema', '2026-02-23 04:25:01');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `usuario` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nombre_completo` varchar(255) DEFAULT NULL,
  `rol` enum('administrador','usuario') NOT NULL DEFAULT 'usuario',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `ultimo_acceso` datetime DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `usuario`, `password`, `nombre_completo`, `rol`, `fecha_creacion`, `ultimo_acceso`, `activo`) VALUES
(1, 'Hotel Cecil', '$2y$10$KtVhWU3au1rkpbMUIX/UUu7QUYn0OviukezCp3EHyANfJB2Ykz6B2', 'Hotel Cecil', 'administrador', '2025-12-24 00:21:00', '2026-03-07 19:31:40', 1),
(2, 'Isaac Vargas', '$2y$10$f/82KXVB1uK00k.vMq0pIO0YI/pAJnAKSyU9k24AdtzdSk7APS9J6', 'Isaac Vargas', 'administrador', '2025-12-24 23:29:05', NULL, 1),
(7, 'Rodrigo Moscoso', '$2b$10$4VBoH/33EniTZ5dxJtodaOMd8SQcSU3K9l/o6otPM0q3PvZJW1I2O', 'Rodrigo Moscoso', 'administrador', '2025-12-29 02:18:36', NULL, 1),
(8, 'Usuario Hotel', '$2b$10$h8ERlsj5VTRQLHqhl46qre/zlje25g6mTRzyiXMCBmI8MX9JaFpZK', 'Usuario Hotel', 'usuario', '2025-12-29 02:18:36', '2025-12-28 22:19:37', 1),
(9, 'Gabriel Durán', '$2y$10$4FvtQuRoVEE35gmr/gFTCupigV.r0MN/ICSB6TuYeziSkHz1L4uWG', 'Gabriel Durán', 'administrador', '2026-03-08 00:00:00', NULL, 1);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `cierres_caja`
--
ALTER TABLE `cierres_caja`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_fecha_cierre` (`fecha_cierre`),
  ADD KEY `idx_fecha_apertura` (`fecha_apertura`);

--
-- Indices de la tabla `egresos`
--
ALTER TABLE `egresos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_fecha` (`fecha`),
  ADD KEY `idx_categoria` (`categoria`);

--
-- Indices de la tabla `habitaciones`
--
ALTER TABLE `habitaciones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero` (`numero`),
  ADD KEY `idx_estado` (`estado`);

--
-- Indices de la tabla `huespedes`
--
ALTER TABLE `huespedes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ci_pasaporte` (`ci_pasaporte`),
  ADD KEY `idx_ci` (`ci_pasaporte`),
  ADD KEY `idx_nombres` (`nombres_apellidos`);

--
-- Indices de la tabla `ingresos`
--
ALTER TABLE `ingresos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ocupacion_id` (`ocupacion_id`),
  ADD KEY `idx_fecha` (`fecha`),
  ADD KEY `idx_metodo_pago` (`metodo_pago`);

--
-- Indices de la tabla `inventario_habitaciones`
--
ALTER TABLE `inventario_habitaciones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `habitacion_numero` (`habitacion_numero`),
  ADD KEY `idx_habitacion` (`habitacion_numero`),
  ADD KEY `idx_tipo` (`tipo`);

--
-- Indices de la tabla `mantenimientos`
--
ALTER TABLE `mantenimientos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_habitacion` (`habitacion_numero`),
  ADD KEY `idx_estado` (`estado`),
  ADD KEY `idx_prioridad` (`prioridad`);

--
-- Indices de la tabla `pagos_qr`
--
ALTER TABLE `pagos_qr`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ocupacion_id` (`ocupacion_id`),
  ADD KEY `idx_fecha` (`fecha`);

--
-- Indices de la tabla `registro_garaje`
--
ALTER TABLE `registro_garaje`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_fecha` (`fecha`),
  ADD KEY `idx_ocupacion` (`ocupacion_id`);

--
-- Indices de la tabla `registro_ocupacion`
--
ALTER TABLE `registro_ocupacion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `huesped_id` (`huesped_id`),
  ADD KEY `habitacion_id` (`habitacion_id`),
  ADD KEY `idx_fecha_ingreso` (`fecha_ingreso`),
  ADD KEY `idx_estado` (`estado`);

--
-- Indices de la tabla `turno_recepcionista`
--
ALTER TABLE `turno_recepcionista`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_activo` (`activo`),
  ADD KEY `idx_fecha_inicio` (`fecha_inicio`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario` (`usuario`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `cierres_caja`
--
ALTER TABLE `cierres_caja`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `egresos`
--
ALTER TABLE `egresos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT de la tabla `habitaciones`
--
ALTER TABLE `habitaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT de la tabla `huespedes`
--
ALTER TABLE `huespedes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=181;

--
-- AUTO_INCREMENT de la tabla `ingresos`
--
ALTER TABLE `ingresos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=137;

--
-- AUTO_INCREMENT de la tabla `inventario_habitaciones`
--
ALTER TABLE `inventario_habitaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de la tabla `mantenimientos`
--
ALTER TABLE `mantenimientos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT de la tabla `pagos_qr`
--
ALTER TABLE `pagos_qr`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT de la tabla `registro_garaje`
--
ALTER TABLE `registro_garaje`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT de la tabla `registro_ocupacion`
--
ALTER TABLE `registro_ocupacion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=203;

--
-- AUTO_INCREMENT de la tabla `turno_recepcionista`
--
ALTER TABLE `turno_recepcionista`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `ingresos`
--
ALTER TABLE `ingresos`
  ADD CONSTRAINT `ingresos_ibfk_1` FOREIGN KEY (`ocupacion_id`) REFERENCES `registro_ocupacion` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `pagos_qr`
--
ALTER TABLE `pagos_qr`
  ADD CONSTRAINT `pagos_qr_ibfk_1` FOREIGN KEY (`ocupacion_id`) REFERENCES `registro_ocupacion` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `registro_garaje`
--
ALTER TABLE `registro_garaje`
  ADD CONSTRAINT `registro_garaje_ibfk_1` FOREIGN KEY (`ocupacion_id`) REFERENCES `registro_ocupacion` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `registro_ocupacion`
--
ALTER TABLE `registro_ocupacion`
  ADD CONSTRAINT `registro_ocupacion_ibfk_1` FOREIGN KEY (`huesped_id`) REFERENCES `huespedes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `registro_ocupacion_ibfk_2` FOREIGN KEY (`habitacion_id`) REFERENCES `habitaciones` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
