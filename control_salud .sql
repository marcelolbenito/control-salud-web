-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Servidor: mysql:3306
-- Tiempo de generación: 27-04-2026 a las 12:18:52
-- Versión del servidor: 8.0.43
-- Versión de PHP: 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `control_salud`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Agenda Telefonica`
--

CREATE TABLE `Agenda Telefonica` (
  `id` int NOT NULL,
  `Ape` varchar(30) DEFAULT NULL,
  `Nom` varchar(30) DEFAULT NULL,
  `Te1` varchar(20) DEFAULT NULL,
  `Te2` varchar(20) DEFAULT NULL,
  `Direccion` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Agenda Turnos`
--

CREATE TABLE `Agenda Turnos` (
  `id` int NOT NULL,
  `Doctor` int DEFAULT NULL,
  `NroHC` int DEFAULT NULL,
  `Fecha` datetime DEFAULT NULL,
  `Paciente` varchar(60) DEFAULT NULL,
  `motivo` int DEFAULT NULL,
  `Atendido` tinyint(1) DEFAULT NULL,
  `pagado` tinyint(1) DEFAULT NULL,
  `llegado` tinyint(1) DEFAULT NULL,
  `idorden` int DEFAULT NULL,
  `numesesion` int DEFAULT NULL,
  `idsesion` int DEFAULT NULL,
  `fechahoraasignado` datetime DEFAULT NULL,
  `idcaja` int DEFAULT NULL,
  `observacionestur` varchar(250) DEFAULT NULL,
  `usuarioasigtur` varchar(50) DEFAULT NULL,
  `llegadohora` varchar(5) DEFAULT NULL,
  `altapaciweb` smallint DEFAULT NULL,
  `confirmado` tinyint(1) DEFAULT NULL,
  `primeravez` smallint DEFAULT NULL,
  `faltoturno` tinyint(1) DEFAULT NULL,
  `reingresar` tinyint(1) DEFAULT NULL,
  `atendidohora` varchar(5) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Agenda Turnos Horarios`
--

CREATE TABLE `Agenda Turnos Horarios` (
  `id` int NOT NULL,
  `iddoctor` int DEFAULT NULL,
  `fechadesde` datetime DEFAULT NULL,
  `fechahasta` datetime DEFAULT NULL,
  `DoMaDesde` datetime DEFAULT NULL,
  `DoMaHasta` datetime DEFAULT NULL,
  `DoTaDesde` datetime DEFAULT NULL,
  `DoTaHasta` datetime DEFAULT NULL,
  `LuMaDesde` datetime DEFAULT NULL,
  `LuMaHasta` datetime DEFAULT NULL,
  `LuTaDesde` datetime DEFAULT NULL,
  `LuTaHasta` datetime DEFAULT NULL,
  `MaMaDesde` datetime DEFAULT NULL,
  `MaMaHasta` datetime DEFAULT NULL,
  `MaTaDesde` datetime DEFAULT NULL,
  `MaTaHasta` datetime DEFAULT NULL,
  `MiMaDesde` datetime DEFAULT NULL,
  `MiMaHasta` datetime DEFAULT NULL,
  `MiTaDesde` datetime DEFAULT NULL,
  `MiTaHasta` datetime DEFAULT NULL,
  `JuMaDesde` datetime DEFAULT NULL,
  `JuMaHasta` datetime DEFAULT NULL,
  `JuTaDesde` datetime DEFAULT NULL,
  `JuTaHasta` datetime DEFAULT NULL,
  `ViMaDesde` datetime DEFAULT NULL,
  `ViMaHasta` datetime DEFAULT NULL,
  `ViTaDesde` datetime DEFAULT NULL,
  `ViTaHasta` datetime DEFAULT NULL,
  `SaMaDesde` datetime DEFAULT NULL,
  `SaMaHasta` datetime DEFAULT NULL,
  `SaTaDesde` datetime DEFAULT NULL,
  `SaTaHasta` datetime DEFAULT NULL,
  `durtur1` smallint DEFAULT NULL,
  `durtur2` smallint DEFAULT NULL,
  `durtur3` smallint DEFAULT NULL,
  `durtur4` smallint DEFAULT NULL,
  `durtur5` smallint DEFAULT NULL,
  `durtur6` smallint DEFAULT NULL,
  `durtur7` smallint DEFAULT NULL,
  `cantidadsobreturnos` smallint DEFAULT NULL,
  `cantidadsobreturnoshora` smallint DEFAULT NULL,
  `sobreturnoshoraresaltar` tinyint(1) DEFAULT NULL,
  `solosobreturnoshoraplanilla` tinyint(1) DEFAULT NULL,
  `diarojoparasobreturnoscompleto` smallint DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Agenda Turnos No Se Atiende`
--

CREATE TABLE `Agenda Turnos No Se Atiende` (
  `id` int NOT NULL,
  `dia` smallint DEFAULT NULL,
  `mes` smallint DEFAULT NULL,
  `anio` smallint DEFAULT NULL,
  `iddoctor` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `agenda_bloqueos`
--

CREATE TABLE `agenda_bloqueos` (
  `id` int NOT NULL,
  `id_clinica` int NOT NULL DEFAULT '1',
  `doctor` int NOT NULL COMMENT 'id lista_doctores',
  `fecha_desde` date NOT NULL,
  `fecha_hasta` date NOT NULL,
  `hora_desde` time DEFAULT NULL COMMENT 'NULL = bloquea todo el día en cada fecha del rango',
  `hora_hasta` time DEFAULT NULL COMMENT 'Fin exclusivo del intervalo (misma convención que slots de agenda)',
  `motivo` varchar(255) DEFAULT NULL,
  `creado_en` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `agenda_turnos`
--

CREATE TABLE `agenda_turnos` (
  `id` int NOT NULL,
  `id_clinica` int NOT NULL DEFAULT '1',
  `Fecha` date NOT NULL,
  `hora` time DEFAULT NULL,
  `NroHC` int NOT NULL COMMENT 'Nro historia clínica paciente',
  `Doctor` int NOT NULL COMMENT 'id lista_doctores',
  `idorden` int DEFAULT NULL COMMENT 'id pacientes_ordenes si aplica',
  `estado` varchar(50) DEFAULT 'pendiente' COMMENT 'pendiente, atendido, cancelado, no_asistio',
  `observaciones` text,
  `creado_en` datetime DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `paciente_nombre` varchar(60) DEFAULT NULL COMMENT 'Access: Paciente (texto denormalizado)',
  `motivo` int DEFAULT NULL COMMENT 'Access: motivo → Lista Motivos Consulta',
  `atendido` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Access: Atendido',
  `pagado` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Access: pagado',
  `llegado` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Access: llegado',
  `llegado_hora` varchar(5) DEFAULT NULL COMMENT 'Access: llegadohora',
  `confirmado` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Access: confirmado',
  `falta_turno` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Access: faltoturno',
  `reingresar` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Access: reingresar',
  `primera_vez` smallint DEFAULT NULL COMMENT 'Access: primeravez',
  `num_sesion` int DEFAULT NULL COMMENT 'Access: numesesion',
  `id_sesion` int DEFAULT NULL COMMENT 'Access: idsesion',
  `id_caja` int DEFAULT NULL COMMENT 'Access: idcaja',
  `usuario_asignado` varchar(50) DEFAULT NULL COMMENT 'Access: usuarioasigtur',
  `alta_paci_web` smallint DEFAULT NULL COMMENT 'Access: altapaciweb',
  `fechahora_asignado` datetime DEFAULT NULL COMMENT 'Access: fechahoraasignado'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Antecedentes Familiares`
--

CREATE TABLE `Antecedentes Familiares` (
  `id` int NOT NULL,
  `NroPaci` int DEFAULT NULL,
  `fami` smallint DEFAULT NULL,
  `a0` smallint DEFAULT NULL,
  `a1` smallint DEFAULT NULL,
  `a2` smallint DEFAULT NULL,
  `a3` smallint DEFAULT NULL,
  `a4` smallint DEFAULT NULL,
  `a5` smallint DEFAULT NULL,
  `a6` smallint DEFAULT NULL,
  `a7` smallint DEFAULT NULL,
  `a8` smallint DEFAULT NULL,
  `a9` smallint DEFAULT NULL,
  `a10` smallint DEFAULT NULL,
  `a11` smallint DEFAULT NULL,
  `a12` smallint DEFAULT NULL,
  `tipodiabete` smallint DEFAULT NULL,
  `detalles` varchar(250) DEFAULT NULL,
  `hipofrecu` varchar(20) DEFAULT NULL,
  `hipocoagu` varchar(20) DEFAULT NULL,
  `hipoanti` varchar(50) DEFAULT NULL,
  `hipotiroidismo` varchar(250) DEFAULT NULL,
  `coagulopatias` varchar(250) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Antecedentes Generales`
--

CREATE TABLE `Antecedentes Generales` (
  `id` int NOT NULL,
  `NroPaci` int DEFAULT NULL,
  `a0` smallint DEFAULT NULL,
  `a1` smallint DEFAULT NULL,
  `a2` smallint DEFAULT NULL,
  `a3` smallint DEFAULT NULL,
  `a4` smallint DEFAULT NULL,
  `a5` smallint DEFAULT NULL,
  `a6` smallint DEFAULT NULL,
  `a7` smallint DEFAULT NULL,
  `a8` smallint DEFAULT NULL,
  `a9` smallint DEFAULT NULL,
  `a10` smallint DEFAULT NULL,
  `a11` smallint DEFAULT NULL,
  `a12` smallint DEFAULT NULL,
  `tipodiabete` smallint DEFAULT NULL,
  `detallesanteper` varchar(250) DEFAULT NULL,
  `detallesantefam` varchar(250) DEFAULT NULL,
  `Ejercicios` smallint DEFAULT NULL,
  `ejerfrec` smallint DEFAULT NULL,
  `ejerdeta` varchar(250) DEFAULT NULL,
  `Tabaco` smallint DEFAULT NULL,
  `idtabaco` int DEFAULT NULL,
  `tabanume` smallint DEFAULT NULL,
  `tabaedad` smallint DEFAULT NULL,
  `tabaexfuma` smallint DEFAULT NULL,
  `tabaexfumadesde` smallint DEFAULT NULL,
  `tabaexfumaprome` smallint DEFAULT NULL,
  `tabaexfumadurante` smallint DEFAULT NULL,
  `tabadeta` varchar(250) DEFAULT NULL,
  `Alcohol` smallint DEFAULT NULL,
  `idalcohol` int DEFAULT NULL,
  `alcofrec` smallint DEFAULT NULL,
  `alconume` smallint DEFAULT NULL,
  `alcoedad` smallint DEFAULT NULL,
  `alcodeta` varchar(250) DEFAULT NULL,
  `Drogas` smallint DEFAULT NULL,
  `iddrogas` int DEFAULT NULL,
  `drogasdeta` varchar(250) DEFAULT NULL,
  `antesoestu` smallint DEFAULT NULL,
  `antesomayornivel` smallint DEFAULT NULL,
  `antesohijosvivos` smallint DEFAULT NULL,
  `antesovive` smallint DEFAULT NULL,
  `antesoapoyo` smallint DEFAULT NULL,
  `antesosegurocasa` smallint DEFAULT NULL,
  `antesosegurorelacion` smallint DEFAULT NULL,
  `antesovivienda` smallint DEFAULT NULL,
  `antesosanitaria` smallint DEFAULT NULL,
  `antesorecursos` smallint DEFAULT NULL,
  `antesodeta` varchar(250) DEFAULT NULL,
  `hipofrecuper` varchar(20) DEFAULT NULL,
  `hipocoaguper` varchar(20) DEFAULT NULL,
  `hipoantiper` varchar(50) DEFAULT NULL,
  `hipotiroidismoper` varchar(250) DEFAULT NULL,
  `coagulopatiasper` varchar(250) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Antecedentes Gineco - Obstetricos`
--

CREATE TABLE `Antecedentes Gineco - Obstetricos` (
  `id` int NOT NULL,
  `NroPaci` int DEFAULT NULL,
  `antegine0` smallint DEFAULT NULL,
  `antegine1` smallint DEFAULT NULL,
  `antegine2` smallint DEFAULT NULL,
  `antegine3` smallint DEFAULT NULL,
  `antegine4` smallint DEFAULT NULL,
  `antegine5` smallint DEFAULT NULL,
  `antegine6` smallint DEFAULT NULL,
  `anteginedeta0` varchar(250) DEFAULT NULL,
  `anteginedeta1` varchar(250) DEFAULT NULL,
  `anteginedeta2` varchar(250) DEFAULT NULL,
  `anteginedeta3` varchar(250) DEFAULT NULL,
  `anteginedeta4` varchar(250) DEFAULT NULL,
  `anteginedeta5` varchar(3) DEFAULT NULL,
  `anteginedeta6` varchar(3) DEFAULT NULL,
  `anteginedeta7` varchar(3) DEFAULT NULL,
  `anteginedeta8` varchar(3) DEFAULT NULL,
  `antegineobser` varchar(250) DEFAULT NULL,
  `espontaneos3` smallint DEFAULT NULL,
  `anteobste13` varchar(2) DEFAULT NULL,
  `anteobste14` varchar(2) DEFAULT NULL,
  `anteobste15` varchar(2) DEFAULT NULL,
  `anteobste16` varchar(2) DEFAULT NULL,
  `anteobste17` varchar(2) DEFAULT NULL,
  `anteobste18` varchar(2) DEFAULT NULL,
  `anteobste19` varchar(2) DEFAULT NULL,
  `anteobste20` varchar(2) DEFAULT NULL,
  `anteobste21` varchar(2) DEFAULT NULL,
  `anteobste22` varchar(2) DEFAULT NULL,
  `anteobsteobser` varchar(250) DEFAULT NULL,
  `anteginedeta9` varchar(3) DEFAULT NULL,
  `anteginedeta10` varchar(3) DEFAULT NULL,
  `estironpuberal` varchar(30) DEFAULT NULL,
  `lactancia` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Antecedentes Perinatologicos`
--

CREATE TABLE `Antecedentes Perinatologicos` (
  `id` int NOT NULL,
  `NroPaci` int DEFAULT NULL,
  `gesta` smallint DEFAULT NULL,
  `para` smallint DEFAULT NULL,
  `semgesta` smallint DEFAULT NULL,
  `parto1` smallint DEFAULT NULL,
  `parto2` smallint DEFAULT NULL,
  `apgar1` varchar(5) DEFAULT NULL,
  `apgar2` varchar(5) DEFAULT NULL,
  `peso` smallint DEFAULT NULL,
  `talla` float DEFAULT NULL,
  `pericefa` float DEFAULT NULL,
  `lugarparto` varchar(50) DEFAULT NULL,
  `controlemba` smallint DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Antecedentes Perso - Fami - Medicamentos`
--

CREATE TABLE `Antecedentes Perso - Fami - Medicamentos` (
  `id` int NOT NULL,
  `NroPaci` int DEFAULT NULL,
  `fami` smallint DEFAULT NULL,
  `iddroga` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Antecedentes Perso - Fami - Patologias`
--

CREATE TABLE `Antecedentes Perso - Fami - Patologias` (
  `id` int NOT NULL,
  `NroPaci` int DEFAULT NULL,
  `fami` smallint DEFAULT NULL,
  `cie10` tinyint(1) DEFAULT NULL,
  `idpatologia` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Anunciador`
--

CREATE TABLE `Anunciador` (
  `id` int NOT NULL,
  `idturno` int DEFAULT NULL,
  `sucursal` smallint DEFAULT NULL,
  `sonar` smallint DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `backup_legacy_Caja_20260409_131540`
--

CREATE TABLE `backup_legacy_Caja_20260409_131540` (
  `id` int NOT NULL,
  `doctor` int DEFAULT NULL,
  `fecha` datetime DEFAULT NULL,
  `detalle` varchar(100) DEFAULT NULL,
  `importe` float DEFAULT NULL,
  `modopago` smallint DEFAULT NULL,
  `detallepago` varchar(100) DEFAULT NULL,
  `codigopago` varchar(15) DEFAULT NULL,
  `usuariocobra` int DEFAULT NULL,
  `sucursal` int DEFAULT NULL,
  `turnocaja` int DEFAULT NULL,
  `deagendaturno` smallint DEFAULT NULL,
  `idcoberturacaja` int DEFAULT NULL,
  `importecobertura` float DEFAULT NULL,
  `porcenhonorcaja` float DEFAULT NULL,
  `fijohonorcaja` float DEFAULT NULL,
  `modicaja` smallint DEFAULT NULL,
  `historialmodicaja` varchar(250) DEFAULT NULL,
  `importe2` float DEFAULT NULL,
  `modopago2` smallint DEFAULT NULL,
  `reserva` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `backup_legacy_Camas_20260409_131540`
--

CREATE TABLE `backup_legacy_Camas_20260409_131540` (
  `id` int NOT NULL,
  `nombre` varchar(30) DEFAULT NULL,
  `ubicacion` varchar(100) DEFAULT NULL,
  `inhabilitada` smallint DEFAULT NULL,
  `sucursal` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `backup_legacy_Config_20260409_131540`
--

CREATE TABLE `backup_legacy_Config_20260409_131540` (
  `NomDoc` varchar(50) DEFAULT NULL,
  `Iniciales` varchar(3) DEFAULT NULL,
  `Matricula` varchar(20) DEFAULT NULL,
  `ResInscrip` tinyint(1) DEFAULT NULL,
  `Cuit` varchar(13) DEFAULT NULL,
  `TelSinUso` varchar(10) DEFAULT NULL,
  `Domi` varchar(30) DEFAULT NULL,
  `Locali` varchar(30) DEFAULT NULL,
  `UltimoPaci` int DEFAULT NULL,
  `Especialidad` varchar(100) DEFAULT NULL,
  `docactivo` int DEFAULT NULL,
  `pasarahc` tinyint(1) DEFAULT NULL,
  `Encabezado` longtext,
  `cie10` tinyint(1) DEFAULT NULL,
  `tipoespecialidadactiva` int DEFAULT NULL,
  `Tel` varchar(20) DEFAULT NULL,
  `encabezadocli` varchar(250) DEFAULT NULL,
  `licenciado` smallint DEFAULT NULL,
  `ventanainicio` smallint DEFAULT NULL,
  `agendarmotivo` tinyint(1) DEFAULT NULL,
  `anuncio` longtext,
  `encabezadogeneral` longtext,
  `encabezadopaciente` longtext,
  `hacerbackupautomatico` smallint DEFAULT NULL,
  `fondoaplicacion` int DEFAULT NULL,
  `verotrasconsultas` tinyint(1) DEFAULT NULL,
  `verfichareducida` tinyint(1) DEFAULT NULL,
  `verestadisticareducida` tinyint(1) DEFAULT NULL,
  `forzarguardarpaci` smallint DEFAULT NULL,
  `tipoformunewpaci` smallint DEFAULT NULL,
  `forzarcantidadsesiones` smallint DEFAULT NULL,
  `forzarpractica` smallint DEFAULT NULL,
  `sumfpp` smallint DEFAULT NULL,
  `bloquearcostos` smallint DEFAULT NULL,
  `versionprograma` varchar(12) DEFAULT NULL,
  `encabezadoimagen` tinyint(1) DEFAULT NULL,
  `forzarderivador` smallint DEFAULT NULL,
  `forzarmotivo` smallint DEFAULT NULL,
  `primerdiaagendaturnos` smallint DEFAULT NULL,
  `pagocaja` smallint DEFAULT NULL,
  `versaldoanioactual` smallint DEFAULT NULL,
  `forzarusuario` smallint DEFAULT NULL,
  `imprimirturnos` smallint DEFAULT NULL,
  `nuidhc` smallint DEFAULT NULL,
  `selecusuario` tinyint(1) DEFAULT NULL,
  `actualizarstock` tinyint(1) DEFAULT NULL,
  `forzarnumeorden` smallint DEFAULT NULL,
  `reemplazoid` varchar(25) DEFAULT NULL,
  `plantillaagenda` tinyint(1) DEFAULT NULL,
  `prefijohc` varchar(5) DEFAULT NULL,
  `numerohc` varchar(10) DEFAULT NULL,
  `encabezadoticket` longtext,
  `forzarguion` smallint DEFAULT NULL,
  `forzarnopuntos` smallint DEFAULT NULL,
  `forzarusuarioclave` smallint DEFAULT NULL,
  `tipohojarecibo` smallint DEFAULT NULL,
  `imprimirturnostel` smallint DEFAULT NULL,
  `unidadtalla` smallint DEFAULT NULL,
  `atenderconsesiones` smallint DEFAULT NULL,
  `nomostrarsaldos` smallint DEFAULT NULL,
  `titulomatricula` varchar(20) DEFAULT NULL,
  `llegosolosecretarias` smallint DEFAULT NULL,
  `impresoraticket` varchar(250) DEFAULT NULL,
  `nomodificarhc` tinyint(1) DEFAULT NULL,
  `ExaFisiHC` smallint DEFAULT NULL,
  `imprimirturnosdomi` smallint DEFAULT NULL,
  `forzarsucursalcaja` tinyint(1) DEFAULT NULL,
  `altapaciweb` smallint DEFAULT NULL,
  `controlausencias` smallint DEFAULT NULL,
  `controldias` smallint DEFAULT NULL,
  `autoconfirwhatsapp` smallint DEFAULT NULL,
  `valoresdefecto` smallint DEFAULT NULL,
  `honorcalcu` smallint DEFAULT NULL,
  `noborraratendidos` smallint DEFAULT NULL,
  `atenderconhc` smallint DEFAULT NULL,
  `recargocredito` float DEFAULT NULL,
  `recargodebito` float DEFAULT NULL,
  `recargomercadopago` float DEFAULT NULL,
  `calcuhonorsolocaja` smallint DEFAULT NULL,
  `indicarcobercaja` smallint DEFAULT NULL,
  `unidadpeso` smallint DEFAULT NULL,
  `dondedesconstock` smallint DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `backup_legacy_Consultas_20260409_131540`
--

CREATE TABLE `backup_legacy_Consultas_20260409_131540` (
  `id` int NOT NULL,
  `iddoctor` int DEFAULT NULL,
  `NroPaci` int DEFAULT NULL,
  `Fecha` datetime DEFAULT NULL,
  `histoenferactu` longtext,
  `control` smallint DEFAULT NULL,
  `controldias` varchar(5) DEFAULT NULL,
  `observaciones` longtext,
  `detallemotivo` longtext,
  `detallediagnos` longtext,
  `detalletrata` longtext,
  `detalleestusoli` longtext,
  `detalleestureali` longtext,
  `idordenconsulta` int DEFAULT NULL,
  `anulada` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `backup_legacy_Pacientes_20260409_131540`
--

CREATE TABLE `backup_legacy_Pacientes_20260409_131540` (
  `id` int NOT NULL,
  `NroHC` int DEFAULT NULL,
  `numehistoria` varchar(30) DEFAULT NULL,
  `Embarazo` tinyint(1) DEFAULT NULL,
  `UltiEmba` smallint DEFAULT NULL,
  `UltimaCons` datetime DEFAULT NULL,
  `pacienteinactivo` tinyint(1) DEFAULT NULL,
  `motivoinactividad` varchar(250) DEFAULT NULL,
  `cobertura` smallint DEFAULT NULL,
  `idcobertura` int DEFAULT NULL,
  `NroOS` varchar(30) DEFAULT NULL,
  `Apellido` varchar(30) DEFAULT NULL,
  `apellido2` varchar(30) DEFAULT NULL,
  `Nombres` varchar(30) DEFAULT NULL,
  `FeNac` datetime DEFAULT NULL,
  `sexo` smallint DEFAULT NULL,
  `DNISinUso` varchar(10) DEFAULT NULL,
  `idtipodoc` int DEFAULT NULL,
  `idocupacion` int DEFAULT NULL,
  `detallesocupacion` varchar(250) DEFAULT NULL,
  `Tel` varchar(30) DEFAULT NULL,
  `TelCelular` varchar(30) DEFAULT NULL,
  `TelLaboral` varchar(30) DEFAULT NULL,
  `nombrepadre` varchar(30) DEFAULT NULL,
  `nacipadre` datetime DEFAULT NULL,
  `idocupacionpadre` int DEFAULT NULL,
  `horashogarpadre` varchar(10) DEFAULT NULL,
  `nombremadre` varchar(30) DEFAULT NULL,
  `nacimadre` datetime DEFAULT NULL,
  `idocupacionmadre` int DEFAULT NULL,
  `horashogarmadre` varchar(10) DEFAULT NULL,
  `nrohermanos` varchar(2) DEFAULT NULL,
  `edadhermanos` varchar(50) DEFAULT NULL,
  `nrohermanas` varchar(2) DEFAULT NULL,
  `edadhermanas` varchar(50) DEFAULT NULL,
  `detallesfamilia` varchar(250) DEFAULT NULL,
  `ape1contacto` varchar(30) DEFAULT NULL,
  `ape2contacto` varchar(30) DEFAULT NULL,
  `nombrecontacto` varchar(30) DEFAULT NULL,
  `idrelacion` int DEFAULT NULL,
  `telparcontacto` varchar(30) DEFAULT NULL,
  `telcelcontacto` varchar(30) DEFAULT NULL,
  `tellabcontacto` varchar(30) DEFAULT NULL,
  `idestadocivil` int DEFAULT NULL,
  `idetnia` int DEFAULT NULL,
  `Domicilio` varchar(250) DEFAULT NULL,
  `idciudad` int DEFAULT NULL,
  `CP` varchar(10) DEFAULT NULL,
  `idprovincia` int DEFAULT NULL,
  `idpais` int DEFAULT NULL,
  `idestatus` int DEFAULT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `Notas` longtext,
  `alergias` longtext,
  `GrupoSanguineo` smallint DEFAULT NULL,
  `FactorSanguineo` smallint DEFAULT NULL,
  `HC` longtext,
  `referente` varchar(100) DEFAULT NULL,
  `DNI` varchar(15) DEFAULT NULL,
  `idcobertura2` int DEFAULT NULL,
  `nuafiliado2` varchar(30) DEFAULT NULL,
  `antecedenteshc` longtext,
  `idplan` int DEFAULT NULL,
  `pagaiva` smallint DEFAULT NULL,
  `convenio` smallint DEFAULT NULL,
  `altapaciweb` smallint DEFAULT NULL,
  `identidadgen` smallint DEFAULT NULL,
  `orientacionsex` smallint DEFAULT NULL,
  `fechaaltapaci` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `caja`
--

CREATE TABLE `caja` (
  `id` int NOT NULL,
  `id_clinica` int NOT NULL DEFAULT '1',
  `doctor` int NOT NULL COMMENT 'id lista_doctores',
  `fechacaja` date NOT NULL,
  `importecaja` decimal(12,2) DEFAULT '0.00',
  `idcoberturacaja` int DEFAULT NULL,
  `turnocaja` text,
  `observaciones` text,
  `creado_en` datetime DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `camas`
--

CREATE TABLE `camas` (
  `id` int NOT NULL,
  `id_clinica` int NOT NULL DEFAULT '1',
  `sucursal` varchar(50) DEFAULT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT '1',
  `creado_en` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `CamasGastos`
--

CREATE TABLE `CamasGastos` (
  `id` int NOT NULL,
  `fecha` datetime DEFAULT NULL,
  `idcamapaci` int DEFAULT NULL,
  `detalle` varchar(250) DEFAULT NULL,
  `cantidad` double DEFAULT NULL,
  `precio` double DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `CamasInsumos`
--

CREATE TABLE `CamasInsumos` (
  `id` int NOT NULL,
  `fecha` datetime DEFAULT NULL,
  `idcamapaci` int DEFAULT NULL,
  `tipoinsumo` smallint DEFAULT NULL,
  `detalle` varchar(250) DEFAULT NULL,
  `idinsumo` int DEFAULT NULL,
  `cantidad` double DEFAULT NULL,
  `precio` double DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `CamasPacientes`
--

CREATE TABLE `CamasPacientes` (
  `id` int NOT NULL,
  `idcama` int DEFAULT NULL,
  `nropaci` int DEFAULT NULL,
  `fechaingreso` datetime DEFAULT NULL,
  `fechaegreso` datetime DEFAULT NULL,
  `observaciones` longtext
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `camas_pacientes`
--

CREATE TABLE `camas_pacientes` (
  `id` int NOT NULL,
  `id_clinica` int NOT NULL DEFAULT '1',
  `idcama` int NOT NULL,
  `nropaci` int NOT NULL COMMENT 'NroHC',
  `fecha_desde` date DEFAULT NULL,
  `fecha_hasta` date DEFAULT NULL,
  `creado_en` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Certificados`
--

CREATE TABLE `Certificados` (
  `id` int NOT NULL,
  `titulo` varchar(100) DEFAULT NULL,
  `contenido` longtext,
  `tipohoja` smallint DEFAULT NULL,
  `encabezado` longtext,
  `margensup` smallint DEFAULT NULL,
  `margenizq` smallint DEFAULT NULL,
  `margender` smallint DEFAULT NULL,
  `margeninf` smallint DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Certificados Historial`
--

CREATE TABLE `Certificados Historial` (
  `id` int NOT NULL,
  `fecha` datetime DEFAULT NULL,
  `titulo` varchar(100) DEFAULT NULL,
  `encabezado` longtext,
  `contenido` longtext,
  `nropaci` int DEFAULT NULL,
  `archivo` varchar(100) DEFAULT NULL,
  `idcertifi` int DEFAULT NULL,
  `archivo2` varchar(100) DEFAULT NULL,
  `archivo3` varchar(100) DEFAULT NULL,
  `archivo4` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `CIE10`
--

CREATE TABLE `CIE10` (
  `id` int NOT NULL,
  `nivel` varchar(10) DEFAULT NULL,
  `iddiagnostico` varchar(10) DEFAULT NULL,
  `diagnostico` varchar(250) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clinicas`
--

CREATE TABLE `clinicas` (
  `id` int NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `creado_en` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `config`
--

CREATE TABLE `config` (
  `id` int NOT NULL,
  `id_clinica` int NOT NULL DEFAULT '1',
  `clave` varchar(100) NOT NULL,
  `valor` mediumtext
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Consulta - Cardiopulmonar`
--

CREATE TABLE `Consulta - Cardiopulmonar` (
  `id` int NOT NULL,
  `idconsulta` int DEFAULT NULL,
  `nropaci` int DEFAULT NULL,
  `examennormal` smallint DEFAULT NULL,
  `comentarios` longtext,
  `iddoctor` int DEFAULT NULL,
  `fecha` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Consulta - Digestivo`
--

CREATE TABLE `Consulta - Digestivo` (
  `id` int NOT NULL,
  `idconsulta` int DEFAULT NULL,
  `nropaci` int DEFAULT NULL,
  `examennormal` smallint DEFAULT NULL,
  `comentarios` longtext,
  `iddoctor` int DEFAULT NULL,
  `fecha` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Consulta - Endocrino`
--

CREATE TABLE `Consulta - Endocrino` (
  `id` int NOT NULL,
  `idconsulta` int DEFAULT NULL,
  `nropaci` int DEFAULT NULL,
  `examennormal` smallint DEFAULT NULL,
  `comentarios` longtext,
  `iddoctor` int DEFAULT NULL,
  `fecha` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Consulta - Estado General`
--

CREATE TABLE `Consulta - Estado General` (
  `id` int NOT NULL,
  `idconsulta` int DEFAULT NULL,
  `nropaci` int DEFAULT NULL,
  `examennormal` smallint DEFAULT NULL,
  `comentarios` longtext,
  `iddoctor` int DEFAULT NULL,
  `fecha` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Consulta - Ginecologico`
--

CREATE TABLE `Consulta - Ginecologico` (
  `id` int NOT NULL,
  `idconsulta` int DEFAULT NULL,
  `nropaci` int DEFAULT NULL,
  `fum` datetime DEFAULT NULL,
  `papexamen` smallint DEFAULT NULL,
  `papexamendeta` varchar(250) DEFAULT NULL,
  `papfecha` datetime DEFAULT NULL,
  `papresullis` smallint DEFAULT NULL,
  `papresuldeta` varchar(250) DEFAULT NULL,
  `papante` smallint DEFAULT NULL,
  `papantedeta` varchar(250) DEFAULT NULL,
  `ciclo` smallint DEFAULT NULL,
  `cicloduradesde` varchar(3) DEFAULT NULL,
  `ciclodurahasta` varchar(3) DEFAULT NULL,
  `ciclomenstrudesde` varchar(3) DEFAULT NULL,
  `ciclomenstruhasta` varchar(3) DEFAULT NULL,
  `cicloflujo` smallint DEFAULT NULL,
  `ciclodisme` smallint DEFAULT NULL,
  `ciclosangra` smallint DEFAULT NULL,
  `ciclosangradeta` varchar(250) DEFAULT NULL,
  `ciclosindro` smallint DEFAULT NULL,
  `ciclosindrodeta` varchar(250) DEFAULT NULL,
  `vidaactiva` smallint DEFAULT NULL,
  `vidaactivadeta` varchar(250) DEFAULT NULL,
  `vidasati` smallint DEFAULT NULL,
  `vidasatideta` varchar(250) DEFAULT NULL,
  `vidameto` smallint DEFAULT NULL,
  `vidametosilista` smallint DEFAULT NULL,
  `vidametosideta` varchar(250) DEFAULT NULL,
  `vidametonolista` smallint DEFAULT NULL,
  `vidadispa` smallint DEFAULT NULL,
  `vidadispadeta` varchar(250) DEFAULT NULL,
  `vidaflujo` smallint DEFAULT NULL,
  `vidaflujodeta` varchar(250) DEFAULT NULL,
  `vidadolor` smallint DEFAULT NULL,
  `vidadolordeta` varchar(250) DEFAULT NULL,
  `infecante` smallint DEFAULT NULL,
  `infecantedeta` varchar(250) DEFAULT NULL,
  `infecproteclista` smallint DEFAULT NULL,
  `infecprueba` smallint DEFAULT NULL,
  `infecpruebadeta` varchar(250) DEFAULT NULL,
  `infecfecha` datetime DEFAULT NULL,
  `infecresul` smallint DEFAULT NULL,
  `mamasproblelis` smallint DEFAULT NULL,
  `mamasprobledeta` varchar(250) DEFAULT NULL,
  `mamasautolis` smallint DEFAULT NULL,
  `mamasautodeta` varchar(250) DEFAULT NULL,
  `mamasmamo` smallint DEFAULT NULL,
  `mamasmamodeta` varchar(250) DEFAULT NULL,
  `mamasante` smallint DEFAULT NULL,
  `mamasantedeta` varchar(250) DEFAULT NULL,
  `mamasfecha` datetime DEFAULT NULL,
  `mamasresullis` smallint DEFAULT NULL,
  `mamasresuldeta` varchar(250) DEFAULT NULL,
  `densito` smallint DEFAULT NULL,
  `densitofecha` datetime DEFAULT NULL,
  `densitoresult` double DEFAULT NULL,
  `densitoresulz` double DEFAULT NULL,
  `densitoresullista` smallint DEFAULT NULL,
  `comentarios` longtext,
  `vidanuparesex` varchar(50) DEFAULT NULL,
  `iddoctor` int DEFAULT NULL,
  `fecha` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Consulta - Muscu Equilibrio Muscular Extre Inf`
--

CREATE TABLE `Consulta - Muscu Equilibrio Muscular Extre Inf` (
  `id` int NOT NULL,
  `idconsulta` int DEFAULT NULL,
  `NroPaci` int DEFAULT NULL,
  `diagequiinf` varchar(100) DEFAULT NULL,
  `comiequiinf` varchar(100) DEFAULT NULL,
  `exploa1` varchar(20) DEFAULT NULL,
  `exploa2` varchar(20) DEFAULT NULL,
  `exploa3` varchar(20) DEFAULT NULL,
  `exploa4` varchar(20) DEFAULT NULL,
  `exploa5` varchar(20) DEFAULT NULL,
  `exploa6` varchar(20) DEFAULT NULL,
  `exploa7` varchar(20) DEFAULT NULL,
  `exploa8` varchar(20) DEFAULT NULL,
  `exploa9` varchar(20) DEFAULT NULL,
  `exploa10` varchar(20) DEFAULT NULL,
  `exploa11` varchar(20) DEFAULT NULL,
  `exploa12` varchar(20) DEFAULT NULL,
  `exploa13` varchar(20) DEFAULT NULL,
  `exploa14` varchar(20) DEFAULT NULL,
  `exploa15` varchar(20) DEFAULT NULL,
  `exploa16` varchar(20) DEFAULT NULL,
  `exploa17` varchar(20) DEFAULT NULL,
  `exploa18` varchar(20) DEFAULT NULL,
  `exploa19` varchar(20) DEFAULT NULL,
  `exploa20` varchar(20) DEFAULT NULL,
  `exploa21` varchar(20) DEFAULT NULL,
  `explob1` varchar(20) DEFAULT NULL,
  `explob2` varchar(20) DEFAULT NULL,
  `explob3` varchar(20) DEFAULT NULL,
  `explob4` varchar(20) DEFAULT NULL,
  `explob5` varchar(20) DEFAULT NULL,
  `explob6` varchar(20) DEFAULT NULL,
  `explob7` varchar(20) DEFAULT NULL,
  `explob8` varchar(20) DEFAULT NULL,
  `explob9` varchar(20) DEFAULT NULL,
  `explob10` varchar(20) DEFAULT NULL,
  `explob11` varchar(20) DEFAULT NULL,
  `explob12` varchar(20) DEFAULT NULL,
  `explob13` varchar(20) DEFAULT NULL,
  `explob14` varchar(20) DEFAULT NULL,
  `explob15` varchar(20) DEFAULT NULL,
  `explob16` varchar(20) DEFAULT NULL,
  `explob17` varchar(20) DEFAULT NULL,
  `explob18` varchar(20) DEFAULT NULL,
  `explob19` varchar(20) DEFAULT NULL,
  `explob20` varchar(20) DEFAULT NULL,
  `explob21` varchar(20) DEFAULT NULL,
  `explob22` varchar(20) DEFAULT NULL,
  `explob23` varchar(20) DEFAULT NULL,
  `explob24` varchar(20) DEFAULT NULL,
  `explob25` varchar(20) DEFAULT NULL,
  `idmuscu` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Consulta - Muscu Equilibrio Muscular Extre Sup`
--

CREATE TABLE `Consulta - Muscu Equilibrio Muscular Extre Sup` (
  `id` int NOT NULL,
  `idconsulta` int DEFAULT NULL,
  `NroPaci` int DEFAULT NULL,
  `diagequisup` varchar(100) DEFAULT NULL,
  `comiequisup` varchar(100) DEFAULT NULL,
  `exploa1` varchar(20) DEFAULT NULL,
  `exploa2` varchar(20) DEFAULT NULL,
  `exploa3` varchar(20) DEFAULT NULL,
  `exploa4` varchar(20) DEFAULT NULL,
  `exploa5` varchar(20) DEFAULT NULL,
  `exploa6` varchar(20) DEFAULT NULL,
  `exploa7` varchar(20) DEFAULT NULL,
  `exploa8` varchar(20) DEFAULT NULL,
  `exploa9` varchar(20) DEFAULT NULL,
  `exploa10` varchar(20) DEFAULT NULL,
  `exploa11` varchar(20) DEFAULT NULL,
  `exploa12` varchar(20) DEFAULT NULL,
  `exploa13` varchar(20) DEFAULT NULL,
  `exploa14` varchar(20) DEFAULT NULL,
  `exploa15` varchar(20) DEFAULT NULL,
  `exploa16` varchar(20) DEFAULT NULL,
  `exploa17` varchar(20) DEFAULT NULL,
  `exploa18` varchar(20) DEFAULT NULL,
  `exploa19` varchar(20) DEFAULT NULL,
  `exploa20` varchar(20) DEFAULT NULL,
  `exploa21` varchar(20) DEFAULT NULL,
  `exploa22` varchar(20) DEFAULT NULL,
  `exploa23` varchar(20) DEFAULT NULL,
  `exploa24` varchar(20) DEFAULT NULL,
  `exploa25` varchar(20) DEFAULT NULL,
  `exploa26` varchar(20) DEFAULT NULL,
  `exploa27` varchar(20) DEFAULT NULL,
  `exploa28` varchar(20) DEFAULT NULL,
  `exploa29` varchar(20) DEFAULT NULL,
  `exploa30` varchar(20) DEFAULT NULL,
  `exploa31` varchar(20) DEFAULT NULL,
  `exploa32` varchar(20) DEFAULT NULL,
  `exploa33` varchar(20) DEFAULT NULL,
  `exploa34` varchar(20) DEFAULT NULL,
  `exploa35` varchar(20) DEFAULT NULL,
  `exploa36` varchar(20) DEFAULT NULL,
  `exploa37` varchar(20) DEFAULT NULL,
  `exploa38` varchar(20) DEFAULT NULL,
  `exploa39` varchar(20) DEFAULT NULL,
  `explob1` varchar(20) DEFAULT NULL,
  `explob2` varchar(20) DEFAULT NULL,
  `explob3` varchar(20) DEFAULT NULL,
  `explob4` varchar(20) DEFAULT NULL,
  `explob5` varchar(20) DEFAULT NULL,
  `explob6` varchar(20) DEFAULT NULL,
  `explob7` varchar(20) DEFAULT NULL,
  `explob8` varchar(20) DEFAULT NULL,
  `explob9` varchar(20) DEFAULT NULL,
  `explob10` varchar(20) DEFAULT NULL,
  `explob11` varchar(20) DEFAULT NULL,
  `explob12` varchar(20) DEFAULT NULL,
  `explob13` varchar(20) DEFAULT NULL,
  `explob14` varchar(20) DEFAULT NULL,
  `explob15` varchar(20) DEFAULT NULL,
  `explob16` varchar(20) DEFAULT NULL,
  `explob17` varchar(20) DEFAULT NULL,
  `explob18` varchar(20) DEFAULT NULL,
  `explob19` varchar(20) DEFAULT NULL,
  `explob20` varchar(20) DEFAULT NULL,
  `explob21` varchar(20) DEFAULT NULL,
  `explob22` varchar(20) DEFAULT NULL,
  `explob23` varchar(20) DEFAULT NULL,
  `explob24` varchar(20) DEFAULT NULL,
  `explob25` varchar(20) DEFAULT NULL,
  `explob26` varchar(20) DEFAULT NULL,
  `explob27` varchar(20) DEFAULT NULL,
  `explob28` varchar(20) DEFAULT NULL,
  `explob29` varchar(20) DEFAULT NULL,
  `explob30` varchar(20) DEFAULT NULL,
  `explob31` varchar(20) DEFAULT NULL,
  `idmuscu` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Consulta - Muscu Ficha Kinesica`
--

CREATE TABLE `Consulta - Muscu Ficha Kinesica` (
  `id` int NOT NULL,
  `idconsulta` int DEFAULT NULL,
  `NroPaci` int DEFAULT NULL,
  `sesiones` varchar(5) DEFAULT NULL,
  `evaluacion` varchar(250) DEFAULT NULL,
  `inicio` varchar(10) DEFAULT NULL,
  `kinesio1` smallint DEFAULT NULL,
  `kinesio2` smallint DEFAULT NULL,
  `kinesio3` smallint DEFAULT NULL,
  `kinesio4` smallint DEFAULT NULL,
  `kinesio5` smallint DEFAULT NULL,
  `kinesio6` smallint DEFAULT NULL,
  `kinesio7` smallint DEFAULT NULL,
  `fisio1` smallint DEFAULT NULL,
  `fisio2` smallint DEFAULT NULL,
  `fisio3` smallint DEFAULT NULL,
  `fisio4` smallint DEFAULT NULL,
  `fisio5` smallint DEFAULT NULL,
  `fisio6` smallint DEFAULT NULL,
  `fisio7` smallint DEFAULT NULL,
  `fisio8` smallint DEFAULT NULL,
  `fisio9` smallint DEFAULT NULL,
  `fisio10` smallint DEFAULT NULL,
  `fisio11` smallint DEFAULT NULL,
  `fisio12` smallint DEFAULT NULL,
  `fisio13` smallint DEFAULT NULL,
  `condicion` smallint DEFAULT NULL,
  `previo` varchar(250) DEFAULT NULL,
  `pronostico` smallint DEFAULT NULL,
  `evolucion` varchar(250) DEFAULT NULL,
  `mediprescri` varchar(50) DEFAULT NULL,
  `idmuscu` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Consulta - Musculoesqueletico`
--

CREATE TABLE `Consulta - Musculoesqueletico` (
  `id` int NOT NULL,
  `idconsulta` int DEFAULT NULL,
  `nropaci` int DEFAULT NULL,
  `examennormal` smallint DEFAULT NULL,
  `comentarios` longtext,
  `iddoctor` int DEFAULT NULL,
  `fecha` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Consulta - Muscu Medicion Articular`
--

CREATE TABLE `Consulta - Muscu Medicion Articular` (
  `id` int NOT NULL,
  `idconsulta` int DEFAULT NULL,
  `NroPaci` int DEFAULT NULL,
  `diagmedisup` varchar(100) DEFAULT NULL,
  `comimedisup` varchar(100) DEFAULT NULL,
  `extresup1` varchar(20) DEFAULT NULL,
  `extresup2` varchar(20) DEFAULT NULL,
  `extresup3` varchar(20) DEFAULT NULL,
  `extresup4` varchar(20) DEFAULT NULL,
  `extresup5` varchar(20) DEFAULT NULL,
  `extresup6` varchar(20) DEFAULT NULL,
  `extresup7` varchar(20) DEFAULT NULL,
  `extresup8` varchar(20) DEFAULT NULL,
  `extresup9` varchar(20) DEFAULT NULL,
  `extresup10` varchar(20) DEFAULT NULL,
  `extresup11` varchar(20) DEFAULT NULL,
  `extresup12` varchar(20) DEFAULT NULL,
  `extresup13` varchar(20) DEFAULT NULL,
  `extresup14` varchar(20) DEFAULT NULL,
  `extresup15` varchar(20) DEFAULT NULL,
  `extresup16` varchar(20) DEFAULT NULL,
  `extresup17` varchar(20) DEFAULT NULL,
  `extresup18` varchar(20) DEFAULT NULL,
  `extresup19` varchar(20) DEFAULT NULL,
  `extresup20` varchar(20) DEFAULT NULL,
  `extresup21` varchar(20) DEFAULT NULL,
  `extresup22` varchar(20) DEFAULT NULL,
  `extresup23` varchar(20) DEFAULT NULL,
  `notasmedisup` varchar(250) DEFAULT NULL,
  `diagmediinf` varchar(100) DEFAULT NULL,
  `comimediinf` varchar(100) DEFAULT NULL,
  `extreinf1` varchar(20) DEFAULT NULL,
  `extreinf2` varchar(20) DEFAULT NULL,
  `extreinf3` varchar(20) DEFAULT NULL,
  `extreinf4` varchar(20) DEFAULT NULL,
  `extreinf5` varchar(20) DEFAULT NULL,
  `extreinf6` varchar(20) DEFAULT NULL,
  `extreinf7` varchar(20) DEFAULT NULL,
  `extreinf8` varchar(20) DEFAULT NULL,
  `extreinf9` varchar(20) DEFAULT NULL,
  `extreinf10` varchar(20) DEFAULT NULL,
  `extreinf11` varchar(20) DEFAULT NULL,
  `extreinf12` varchar(20) DEFAULT NULL,
  `extreinf13` varchar(20) DEFAULT NULL,
  `extreinf14` varchar(20) DEFAULT NULL,
  `extreinf15` varchar(20) DEFAULT NULL,
  `extreinf16` varchar(20) DEFAULT NULL,
  `extreinf17` varchar(20) DEFAULT NULL,
  `extreinf18` varchar(20) DEFAULT NULL,
  `extreinf19` varchar(20) DEFAULT NULL,
  `extreinf20` varchar(20) DEFAULT NULL,
  `notasmediinf` varchar(250) DEFAULT NULL,
  `idmuscu` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Consulta - Neurologico`
--

CREATE TABLE `Consulta - Neurologico` (
  `id` int NOT NULL,
  `idconsulta` int DEFAULT NULL,
  `nropaci` int DEFAULT NULL,
  `examennormal` smallint DEFAULT NULL,
  `comentarios` longtext,
  `iddoctor` int DEFAULT NULL,
  `fecha` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Consulta - ORL`
--

CREATE TABLE `Consulta - ORL` (
  `id` int NOT NULL,
  `idconsulta` int DEFAULT NULL,
  `nropaci` int DEFAULT NULL,
  `examennormal` smallint DEFAULT NULL,
  `comentarios` longtext,
  `iddoctor` int DEFAULT NULL,
  `fecha` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Consulta - Salud Mental`
--

CREATE TABLE `Consulta - Salud Mental` (
  `id` int NOT NULL,
  `idconsulta` int DEFAULT NULL,
  `nropaci` int DEFAULT NULL,
  `examennormal` smallint DEFAULT NULL,
  `comentarios` longtext,
  `iddoctor` int DEFAULT NULL,
  `fecha` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Consulta - Salud Sexual`
--

CREATE TABLE `Consulta - Salud Sexual` (
  `id` int NOT NULL,
  `idconsulta` int DEFAULT NULL,
  `nropaci` int DEFAULT NULL,
  `examennormal` smallint DEFAULT NULL,
  `comentarios` longtext,
  `iddoctor` int DEFAULT NULL,
  `fecha` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Consulta - Urinario`
--

CREATE TABLE `Consulta - Urinario` (
  `id` int NOT NULL,
  `idconsulta` int DEFAULT NULL,
  `nropaci` int DEFAULT NULL,
  `examennormal` smallint DEFAULT NULL,
  `comentarios` longtext,
  `iddoctor` int DEFAULT NULL,
  `fecha` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Consulta Exa - Abdominal`
--

CREATE TABLE `Consulta Exa - Abdominal` (
  `id` int NOT NULL,
  `idconsulta` int DEFAULT NULL,
  `nropaci` int DEFAULT NULL,
  `examennormal` smallint DEFAULT NULL,
  `comentarios` longtext,
  `iddoctor` int DEFAULT NULL,
  `fecha` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Consulta Exa - Cardio Vascular`
--

CREATE TABLE `Consulta Exa - Cardio Vascular` (
  `id` int NOT NULL,
  `idconsulta` int DEFAULT NULL,
  `nropaci` int DEFAULT NULL,
  `examennormal` smallint DEFAULT NULL,
  `comentarios` longtext,
  `iddoctor` int DEFAULT NULL,
  `fecha` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Consulta Exa - Dermatologico`
--

CREATE TABLE `Consulta Exa - Dermatologico` (
  `id` int NOT NULL,
  `idconsulta` int DEFAULT NULL,
  `nropaci` int DEFAULT NULL,
  `examennormal` smallint DEFAULT NULL,
  `comentarios` longtext,
  `iddoctor` int DEFAULT NULL,
  `fecha` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Consulta Exa - Fisico General`
--

CREATE TABLE `Consulta Exa - Fisico General` (
  `id` int NOT NULL,
  `idconsulta` int DEFAULT NULL,
  `nropaci` int DEFAULT NULL,
  `examennormal` smallint DEFAULT NULL,
  `comentarios` longtext,
  `peso` float DEFAULT NULL,
  `talla` float DEFAULT NULL,
  `imc` float DEFAULT NULL,
  `sistolica` float DEFAULT NULL,
  `diastolica` float DEFAULT NULL,
  `fr` float DEFAULT NULL,
  `pulso` float DEFAULT NULL,
  `temperatura` float DEFAULT NULL,
  `glucosa` float DEFAULT NULL,
  `pericefa` float DEFAULT NULL,
  `pericintu` float DEFAULT NULL,
  `porcengrasa` float DEFAULT NULL,
  `porcensaturacion` float DEFAULT NULL,
  `iddoctor` int DEFAULT NULL,
  `fecha` datetime DEFAULT NULL,
  `perimetrocadera` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Consulta Exa - Genitourinario`
--

CREATE TABLE `Consulta Exa - Genitourinario` (
  `id` int NOT NULL,
  `idconsulta` int DEFAULT NULL,
  `nropaci` int DEFAULT NULL,
  `examennormal` smallint DEFAULT NULL,
  `comentarios` longtext,
  `iddoctor` int DEFAULT NULL,
  `fecha` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Consulta Exa - Ginecologico`
--

CREATE TABLE `Consulta Exa - Ginecologico` (
  `id` int NOT NULL,
  `idconsulta` int DEFAULT NULL,
  `nropaci` int DEFAULT NULL,
  `pielmuco` varchar(50) DEFAULT NULL,
  `distripelo` varchar(50) DEFAULT NULL,
  `contor` varchar(50) DEFAULT NULL,
  `parevagi` varchar(50) DEFAULT NULL,
  `uretra` varchar(50) DEFAULT NULL,
  `vagi` varchar(50) DEFAULT NULL,
  `cuello` varchar(50) DEFAULT NULL,
  `uteposi` varchar(50) DEFAULT NULL,
  `utetama` varchar(50) DEFAULT NULL,
  `utemovi` varchar(50) DEFAULT NULL,
  `uteversion` varchar(50) DEFAULT NULL,
  `uteconsis` varchar(50) DEFAULT NULL,
  `utesensi` varchar(50) DEFAULT NULL,
  `cueaper` varchar(50) DEFAULT NULL,
  `cuetama` varchar(50) DEFAULT NULL,
  `cuemovi` varchar(50) DEFAULT NULL,
  `cuelongi` varchar(50) DEFAULT NULL,
  `cueconsis` varchar(50) DEFAULT NULL,
  `cuesensi` varchar(50) DEFAULT NULL,
  `cueposi` varchar(50) DEFAULT NULL,
  `fondosaco` varchar(50) DEFAULT NULL,
  `dereloca` varchar(50) DEFAULT NULL,
  `deretama` varchar(50) DEFAULT NULL,
  `dereconsis` varchar(50) DEFAULT NULL,
  `deremovi` varchar(50) DEFAULT NULL,
  `deresensi` varchar(50) DEFAULT NULL,
  `izquiloca` varchar(50) DEFAULT NULL,
  `izquitama` varchar(50) DEFAULT NULL,
  `izquiconsis` varchar(50) DEFAULT NULL,
  `izquimovi` varchar(50) DEFAULT NULL,
  `izquisensi` varchar(50) DEFAULT NULL,
  `tactoaper` varchar(50) DEFAULT NULL,
  `tactoparedante` varchar(50) DEFAULT NULL,
  `tactotonoesfin` varchar(50) DEFAULT NULL,
  `tactootraspare` varchar(50) DEFAULT NULL,
  `tactosensi` varchar(50) DEFAULT NULL,
  `tactoprueba` varchar(50) DEFAULT NULL,
  `examennormal` smallint DEFAULT NULL,
  `conclusion` longtext,
  `iddoctor` int DEFAULT NULL,
  `fecha` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Consulta Exa - Mamario`
--

CREATE TABLE `Consulta Exa - Mamario` (
  `id` int NOT NULL,
  `NroPaci` int DEFAULT NULL,
  `Fecha` datetime DEFAULT NULL,
  `Esquema` varchar(240) DEFAULT NULL,
  `simede` varchar(50) DEFAULT NULL,
  `contorde` varchar(50) DEFAULT NULL,
  `aspecde` varchar(50) DEFAULT NULL,
  `tempede` varchar(50) DEFAULT NULL,
  `pielde` varchar(50) DEFAULT NULL,
  `denside` varchar(50) DEFAULT NULL,
  `nodude` varchar(50) DEFAULT NULL,
  `hiperde` varchar(50) DEFAULT NULL,
  `simeiz` varchar(50) DEFAULT NULL,
  `contoriz` varchar(50) DEFAULT NULL,
  `aspeciz` varchar(50) DEFAULT NULL,
  `tempeiz` varchar(50) DEFAULT NULL,
  `pieliz` varchar(50) DEFAULT NULL,
  `densiiz` varchar(50) DEFAULT NULL,
  `noduiz` varchar(50) DEFAULT NULL,
  `hiperiz` varchar(50) DEFAULT NULL,
  `examennormal` smallint DEFAULT NULL,
  `Observaciones` varchar(250) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Consulta Exa - Mental`
--

CREATE TABLE `Consulta Exa - Mental` (
  `id` int NOT NULL,
  `idconsulta` int DEFAULT NULL,
  `nropaci` int DEFAULT NULL,
  `examennormal` smallint DEFAULT NULL,
  `comentarios` longtext,
  `iddoctor` int DEFAULT NULL,
  `fecha` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Consulta Exa - Neurologico`
--

CREATE TABLE `Consulta Exa - Neurologico` (
  `id` int NOT NULL,
  `idconsulta` int DEFAULT NULL,
  `nropaci` int DEFAULT NULL,
  `examennormal` smallint DEFAULT NULL,
  `comentarios` longtext,
  `iddoctor` int DEFAULT NULL,
  `fecha` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Consulta Exa - Oftalmologico`
--

CREATE TABLE `Consulta Exa - Oftalmologico` (
  `id` int NOT NULL,
  `idconsulta` int DEFAULT NULL,
  `nropaci` int DEFAULT NULL,
  `examennormal` smallint DEFAULT NULL,
  `comentarios` longtext,
  `iddoctor` int DEFAULT NULL,
  `fecha` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Consulta Exa - ORL`
--

CREATE TABLE `Consulta Exa - ORL` (
  `id` int NOT NULL,
  `idconsulta` int DEFAULT NULL,
  `nropaci` int DEFAULT NULL,
  `examennormal` smallint DEFAULT NULL,
  `comentarios` longtext,
  `iddoctor` int DEFAULT NULL,
  `fecha` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Consulta Exa - Respiratorio`
--

CREATE TABLE `Consulta Exa - Respiratorio` (
  `id` int NOT NULL,
  `idconsulta` int DEFAULT NULL,
  `nropaci` int DEFAULT NULL,
  `examennormal` smallint DEFAULT NULL,
  `comentarios` longtext,
  `iddoctor` int DEFAULT NULL,
  `fecha` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `consultas`
--

CREATE TABLE `consultas` (
  `id` int NOT NULL,
  `id_clinica` int NOT NULL DEFAULT '1',
  `iddoctor` int NOT NULL COMMENT 'id lista_doctores',
  `NroHC` int NOT NULL COMMENT 'paciente',
  `fecha_consulta` date DEFAULT NULL,
  `observaciones` text,
  `creado_en` datetime DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Consultas Items`
--

CREATE TABLE `Consultas Items` (
  `id` int NOT NULL,
  `tipoconsulta` smallint DEFAULT NULL,
  `idconsulta` int DEFAULT NULL,
  `NroPaci` int DEFAULT NULL,
  `tipoitem` smallint DEFAULT NULL,
  `iditem` int DEFAULT NULL,
  `item` varchar(250) DEFAULT NULL,
  `cie10` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `consultas_items`
--

CREATE TABLE `consultas_items` (
  `id` int NOT NULL,
  `id_consulta` int NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `valor` text,
  `creado_en` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ExceptoConsultas`
--

CREATE TABLE `ExceptoConsultas` (
  `id` int NOT NULL,
  `iddoctor` int DEFAULT NULL,
  `iddoctorexcepto` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Historial`
--

CREATE TABLE `Historial` (
  `id` int NOT NULL,
  `fecha` datetime DEFAULT NULL,
  `idusuario` int DEFAULT NULL,
  `iddoctor` int DEFAULT NULL,
  `equipo` varchar(100) DEFAULT NULL,
  `ventana` varchar(100) DEFAULT NULL,
  `accion` varchar(250) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Imprimir`
--

CREATE TABLE `Imprimir` (
  `nrohc` int DEFAULT NULL,
  `impri1` varchar(250) DEFAULT NULL,
  `impri2` varchar(250) DEFAULT NULL,
  `impri3` varchar(250) DEFAULT NULL,
  `impri4` varchar(250) DEFAULT NULL,
  `impri5` varchar(250) DEFAULT NULL,
  `impri6` varchar(250) DEFAULT NULL,
  `impri7` varchar(250) DEFAULT NULL,
  `impri8` varchar(250) DEFAULT NULL,
  `impri9` varchar(250) DEFAULT NULL,
  `impri10` varchar(250) DEFAULT NULL,
  `imprihc` longtext,
  `imprihc2` longtext,
  `imprihc3` longtext,
  `foto` longblob,
  `impriencabezadogeneral` longtext,
  `impriencabezadopaciente` longtext,
  `imagenenca` longblob,
  `firmadigital` longblob,
  `foto2` longblob,
  `foto3` longblob,
  `foto4` longblob
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Imprimir2`
--

CREATE TABLE `Imprimir2` (
  `nrohc` int DEFAULT NULL,
  `impri1` varchar(250) DEFAULT NULL,
  `impri2` varchar(250) DEFAULT NULL,
  `impri3` varchar(250) DEFAULT NULL,
  `impri4` varchar(250) DEFAULT NULL,
  `impri5` varchar(250) DEFAULT NULL,
  `impri6` varchar(250) DEFAULT NULL,
  `impri7` varchar(250) DEFAULT NULL,
  `impri8` varchar(250) DEFAULT NULL,
  `impri9` varchar(250) DEFAULT NULL,
  `impri10` varchar(250) DEFAULT NULL,
  `impri11` varchar(250) DEFAULT NULL,
  `impri12` varchar(250) DEFAULT NULL,
  `impri13` varchar(250) DEFAULT NULL,
  `impri14` varchar(250) DEFAULT NULL,
  `impri15` varchar(250) DEFAULT NULL,
  `impri16` varchar(250) DEFAULT NULL,
  `impri17` varchar(250) DEFAULT NULL,
  `impri18` varchar(250) DEFAULT NULL,
  `impri19` varchar(250) DEFAULT NULL,
  `impri20` varchar(250) DEFAULT NULL,
  `imprinume1` double DEFAULT NULL,
  `imprinume2` double DEFAULT NULL,
  `imprinume3` double DEFAULT NULL,
  `imprinume4` double DEFAULT NULL,
  `imprinume5` double DEFAULT NULL,
  `imprinume6` double DEFAULT NULL,
  `imprinume7` double DEFAULT NULL,
  `imprinume8` double DEFAULT NULL,
  `imprinume9` double DEFAULT NULL,
  `imprimemo` longtext,
  `imprinume10` float DEFAULT NULL,
  `imprinume11` float DEFAULT NULL,
  `imprinume12` float DEFAULT NULL,
  `imprinume13` float DEFAULT NULL,
  `imprinume14` float DEFAULT NULL,
  `imprinume15` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Imprimir3`
--

CREATE TABLE `Imprimir3` (
  `nrohc` int DEFAULT NULL,
  `impri1` varchar(250) DEFAULT NULL,
  `impri2` varchar(250) DEFAULT NULL,
  `impri3` varchar(250) DEFAULT NULL,
  `impri4` varchar(250) DEFAULT NULL,
  `impri5` varchar(250) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Lista Alcohol`
--

CREATE TABLE `Lista Alcohol` (
  `id` int NOT NULL,
  `prioridad` smallint DEFAULT NULL,
  `nombre` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Lista Analisis`
--

CREATE TABLE `Lista Analisis` (
  `id` int NOT NULL,
  `idestu` int DEFAULT NULL,
  `Detalle` varchar(30) DEFAULT NULL,
  `Unidad` varchar(15) DEFAULT NULL,
  `Minimo` float DEFAULT NULL,
  `Maximo` float DEFAULT NULL,
  `orden` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Lista Ciudad`
--

CREATE TABLE `Lista Ciudad` (
  `id` int NOT NULL,
  `prioridad` smallint DEFAULT NULL,
  `nombre` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Lista Coberturas`
--

CREATE TABLE `Lista Coberturas` (
  `id` int NOT NULL,
  `prioridad` smallint DEFAULT NULL,
  `nombre` varchar(50) DEFAULT NULL,
  `Porcentaje_Cobertura` double DEFAULT NULL,
  `plancober` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Lista Derivadores`
--

CREATE TABLE `Lista Derivadores` (
  `id` int NOT NULL,
  `prioridad` smallint DEFAULT NULL,
  `nombre` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Lista Diagnosticos`
--

CREATE TABLE `Lista Diagnosticos` (
  `id` int NOT NULL,
  `nivel` int DEFAULT NULL,
  `prioridad` smallint DEFAULT NULL,
  `Patologia` varchar(250) DEFAULT NULL,
  `Descripcion` longtext,
  `tipoespecialidad` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Lista Doctores`
--

CREATE TABLE `Lista Doctores` (
  `id` int NOT NULL,
  `prioridad` smallint DEFAULT NULL,
  `NomDoc` varchar(50) DEFAULT NULL,
  `Especialidad` varchar(100) DEFAULT NULL,
  `Matricula` varchar(20) DEFAULT NULL,
  `Domi` varchar(30) DEFAULT NULL,
  `Locali` varchar(30) DEFAULT NULL,
  `Tel` varchar(20) DEFAULT NULL,
  `Descripcion` longtext,
  `tipoespecialidadactiva` int DEFAULT NULL,
  `cie10` tinyint(1) DEFAULT NULL,
  `ventanainicio` smallint DEFAULT NULL,
  `anuncio` longtext,
  `tipo` smallint DEFAULT NULL,
  `medisecre` int DEFAULT NULL,
  `verotrasconsultas` tinyint(1) DEFAULT NULL,
  `verfichareducida` tinyint(1) DEFAULT NULL,
  `verestadisticareducida` tinyint(1) DEFAULT NULL,
  `claveusuario` varchar(30) DEFAULT NULL,
  `administrador` tinyint(1) DEFAULT NULL,
  `accesoeliminarpaciente` tinyint(1) DEFAULT NULL,
  `accesoparcialpaciente` tinyint(1) DEFAULT NULL,
  `accesopediatria` tinyint(1) DEFAULT NULL,
  `accesoginecologia` tinyint(1) DEFAULT NULL,
  `accesoagregarconsulta` tinyint(1) DEFAULT NULL,
  `accesoeliminarconsulta` tinyint(1) DEFAULT NULL,
  `accesolistados` tinyint(1) DEFAULT NULL,
  `accesoagendas` tinyint(1) DEFAULT NULL,
  `accesorecursos` tinyint(1) DEFAULT NULL,
  `accesocaja` tinyint(1) DEFAULT NULL,
  `accesoestadisticas` tinyint(1) DEFAULT NULL,
  `accesoconfiguracion` tinyint(1) DEFAULT NULL,
  `accesobackup` tinyint(1) DEFAULT NULL,
  `accesored` tinyint(1) DEFAULT NULL,
  `accesoeliminarturnos` tinyint(1) DEFAULT NULL,
  `accesooftalmologia` tinyint(1) DEFAULT NULL,
  `accesoodontologia` tinyint(1) DEFAULT NULL,
  `accesoasignarturno` tinyint(1) DEFAULT NULL,
  `accesomodificarturno` tinyint(1) DEFAULT NULL,
  `aplicaraturnos` smallint DEFAULT NULL,
  `accesoestudios` tinyint(1) DEFAULT NULL,
  `accesocirugias` tinyint(1) DEFAULT NULL,
  `accesoordenes` tinyint(1) DEFAULT NULL,
  `accesocertificados` tinyint(1) DEFAULT NULL,
  `accesocontable` tinyint(1) DEFAULT NULL,
  `accesopagos` tinyint(1) DEFAULT NULL,
  `accesohistorial` tinyint(1) DEFAULT NULL,
  `accesolistaordenes` tinyint(1) DEFAULT NULL,
  `accesocambiarusuario` tinyint(1) DEFAULT NULL,
  `moduloweb` tinyint(1) DEFAULT NULL,
  `ocultartelemail` tinyint(1) DEFAULT NULL,
  `accesocrearpaciente` tinyint(1) DEFAULT NULL,
  `accesomodificarpaciente` tinyint(1) DEFAULT NULL,
  `accesochat` tinyint(1) DEFAULT NULL,
  `accesoarchivos` tinyint(1) DEFAULT NULL,
  `accesodirecto01` smallint DEFAULT NULL,
  `accesodirecto02` smallint DEFAULT NULL,
  `accesodirecto03` smallint DEFAULT NULL,
  `accesodirecto04` smallint DEFAULT NULL,
  `accesodirecto05` smallint DEFAULT NULL,
  `accesodirecto06` smallint DEFAULT NULL,
  `accesodirecto07` smallint DEFAULT NULL,
  `accesodirecto08` smallint DEFAULT NULL,
  `accesodirecto09` smallint DEFAULT NULL,
  `accesodirecto10` smallint DEFAULT NULL,
  `accesoplantillaturnos` tinyint(1) DEFAULT NULL,
  `accesouti` tinyint(1) DEFAULT NULL,
  `accesoutiimportes` tinyint(1) DEFAULT NULL,
  `accesopagosagregar` tinyint(1) DEFAULT NULL,
  `accesopagosmodificar` tinyint(1) DEFAULT NULL,
  `accesopagoseliminar` tinyint(1) DEFAULT NULL,
  `accesopagoscoberturas` tinyint(1) DEFAULT NULL,
  `accesoordenesagregar` tinyint(1) DEFAULT NULL,
  `accesoordenesmodificar` tinyint(1) DEFAULT NULL,
  `accesoordeneseliminar` tinyint(1) DEFAULT NULL,
  `accesoordeneshonorarios` tinyint(1) DEFAULT NULL,
  `accesomodificarcaja` tinyint(1) DEFAULT NULL,
  `bloquearmisconsultas` tinyint(1) DEFAULT NULL,
  `accesoverotrasagendas` tinyint(1) DEFAULT NULL,
  `solopredefinido` tinyint(1) DEFAULT NULL,
  `accesohistoconsul` tinyint(1) DEFAULT NULL,
  `aplicaracaja` smallint DEFAULT NULL,
  `accesohc` tinyint(1) DEFAULT NULL,
  `accesoagregarhc` tinyint(1) DEFAULT NULL,
  `accesomodificarhc` tinyint(1) DEFAULT NULL,
  `turnosweb` tinyint(1) DEFAULT NULL,
  `sucursal1` smallint DEFAULT NULL,
  `sucursal2` smallint DEFAULT NULL,
  `sucursal3` smallint DEFAULT NULL,
  `sucursal4` smallint DEFAULT NULL,
  `sucursal5` smallint DEFAULT NULL,
  `idespecialidad` smallint DEFAULT NULL,
  `accesorecordatorioturnos` tinyint(1) DEFAULT NULL,
  `medicoconvenio` smallint DEFAULT NULL,
  `accesoeliminaritems` tinyint(1) DEFAULT NULL,
  `consultorio` varchar(30) DEFAULT NULL,
  `accesolistasesiones` tinyint(1) DEFAULT NULL,
  `accesosesioneshonorarios` tinyint(1) DEFAULT NULL,
  `accesoagendatelefonica` tinyint(1) DEFAULT NULL,
  `porcenhonor` float DEFAULT NULL,
  `fijohonor` float DEFAULT NULL,
  `detalleonline` varchar(250) DEFAULT NULL,
  `accesoatenderturno` tinyint(1) DEFAULT NULL,
  `accesoagregarcaja` tinyint(1) DEFAULT NULL,
  `superusuario` smallint DEFAULT NULL,
  `sucursal6` smallint DEFAULT NULL,
  `sucursal7` smallint DEFAULT NULL,
  `sucursal8` smallint DEFAULT NULL,
  `sucursal9` smallint DEFAULT NULL,
  `sucursal10` smallint DEFAULT NULL,
  `accesoasignarsobreturnos` tinyint(1) DEFAULT NULL,
  `dnimedico` varchar(20) DEFAULT NULL,
  `accesonomenprecios` tinyint(1) DEFAULT NULL,
  `verturnoslibresonline` smallint DEFAULT NULL,
  `plantillaturnosonline` datetime DEFAULT NULL,
  `permitirvariosturnosonline` tinyint(1) DEFAULT NULL,
  `infoturnosonline` longtext,
  `diasporturnero` smallint DEFAULT NULL,
  `accesopresupuestos` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Lista Drogas`
--

CREATE TABLE `Lista Drogas` (
  `id` int NOT NULL,
  `prioridad` smallint DEFAULT NULL,
  `nombre` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Lista Enfermedades Ginecologicas`
--

CREATE TABLE `Lista Enfermedades Ginecologicas` (
  `id` int NOT NULL,
  `nivel` int DEFAULT NULL,
  `prioridad` smallint DEFAULT NULL,
  `nombre` varchar(250) DEFAULT NULL,
  `Descripcion` longtext
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Lista Estado civil`
--

CREATE TABLE `Lista Estado civil` (
  `id` int NOT NULL,
  `prioridad` smallint DEFAULT NULL,
  `nombre` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Lista Estatus en el pais`
--

CREATE TABLE `Lista Estatus en el pais` (
  `id` int NOT NULL,
  `prioridad` smallint DEFAULT NULL,
  `nombre` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Lista Estudios`
--

CREATE TABLE `Lista Estudios` (
  `id` int NOT NULL,
  `tipo` smallint DEFAULT NULL,
  `nivel` int DEFAULT NULL,
  `nombre` varchar(250) DEFAULT NULL,
  `prioridad` smallint DEFAULT NULL,
  `Descripcion` longtext,
  `tipoespecialidad` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Lista Etnia`
--

CREATE TABLE `Lista Etnia` (
  `id` int NOT NULL,
  `prioridad` smallint DEFAULT NULL,
  `nombre` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Lista Motivos Consulta`
--

CREATE TABLE `Lista Motivos Consulta` (
  `id` int NOT NULL,
  `nivel` int DEFAULT NULL,
  `prioridad` smallint DEFAULT NULL,
  `Motivo` varchar(250) DEFAULT NULL,
  `Descripcion` longtext,
  `tipoespecialidad` int DEFAULT NULL,
  `importemotivo` float DEFAULT NULL,
  `honormotivo` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Lista Nomenclador`
--

CREATE TABLE `Lista Nomenclador` (
  `id` int NOT NULL,
  `codigo` varchar(15) DEFAULT NULL,
  `nombre` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Lista Ocupacion`
--

CREATE TABLE `Lista Ocupacion` (
  `id` int NOT NULL,
  `prioridad` smallint DEFAULT NULL,
  `nombre` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Lista Operaciones Ginecologicas`
--

CREATE TABLE `Lista Operaciones Ginecologicas` (
  `id` int NOT NULL,
  `nivel` int DEFAULT NULL,
  `prioridad` smallint DEFAULT NULL,
  `nombre` varchar(250) DEFAULT NULL,
  `Descripcion` longtext
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Lista Pais`
--

CREATE TABLE `Lista Pais` (
  `id` int NOT NULL,
  `prioridad` smallint DEFAULT NULL,
  `nombre` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Lista Planes`
--

CREATE TABLE `Lista Planes` (
  `id` int NOT NULL,
  `idcobertura` int DEFAULT NULL,
  `nombre` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Lista Precios`
--

CREATE TABLE `Lista Precios` (
  `id` int NOT NULL,
  `idobrasocial` int DEFAULT NULL,
  `idpractica` int DEFAULT NULL,
  `costopaciente` double DEFAULT NULL,
  `costocobertura` double DEFAULT NULL,
  `usarporcentaje` tinyint(1) DEFAULT NULL,
  `costoporcentaje` double DEFAULT NULL,
  `cobradr` double DEFAULT NULL,
  `idplan` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Lista Productos`
--

CREATE TABLE `Lista Productos` (
  `id` int NOT NULL,
  `codigo` varchar(9) DEFAULT NULL,
  `descripcion` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Lista Provincia`
--

CREATE TABLE `Lista Provincia` (
  `id` int NOT NULL,
  `prioridad` smallint DEFAULT NULL,
  `nombre` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Lista Relacion con el paciente`
--

CREATE TABLE `Lista Relacion con el paciente` (
  `id` int NOT NULL,
  `prioridad` smallint DEFAULT NULL,
  `nombre` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Lista Resultados PAP`
--

CREATE TABLE `Lista Resultados PAP` (
  `id` int NOT NULL,
  `prioridad` smallint DEFAULT NULL,
  `nombre` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Lista Stock`
--

CREATE TABLE `Lista Stock` (
  `id` int NOT NULL,
  `codigo` varchar(9) DEFAULT NULL,
  `doctorid` int DEFAULT NULL,
  `unidenvase` float DEFAULT NULL,
  `costo` float DEFAULT NULL,
  `existente` float DEFAULT NULL,
  `minimo` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Lista Tabaco`
--

CREATE TABLE `Lista Tabaco` (
  `id` int NOT NULL,
  `prioridad` smallint DEFAULT NULL,
  `nombre` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Lista Tipo de documento`
--

CREATE TABLE `Lista Tipo de documento` (
  `id` int NOT NULL,
  `prioridad` smallint DEFAULT NULL,
  `nombre` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Lista Tratamientos Complementarios`
--

CREATE TABLE `Lista Tratamientos Complementarios` (
  `id` int NOT NULL,
  `nivel` int DEFAULT NULL,
  `prioridad` smallint DEFAULT NULL,
  `nombre` varchar(250) DEFAULT NULL,
  `Descripcion` longtext,
  `tipoespecialidad` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Lista Usos`
--

CREATE TABLE `Lista Usos` (
  `id` int NOT NULL,
  `nomencla` varchar(9) DEFAULT NULL,
  `producto` varchar(9) DEFAULT NULL,
  `cantidad` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Lista Vacunas`
--

CREATE TABLE `Lista Vacunas` (
  `id` int NOT NULL,
  `prioridad` smallint DEFAULT NULL,
  `Tipo` smallint DEFAULT NULL,
  `Vacuna` varchar(50) DEFAULT NULL,
  `NroDosis` smallint DEFAULT NULL,
  `Edad1` smallint DEFAULT NULL,
  `Edad2` smallint DEFAULT NULL,
  `Edad3` smallint DEFAULT NULL,
  `Edad4` smallint DEFAULT NULL,
  `Edad5` smallint DEFAULT NULL,
  `Descripcion` longtext
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Lista Vademecum`
--

CREATE TABLE `Lista Vademecum` (
  `id` int NOT NULL,
  `nivel` int DEFAULT NULL,
  `prioridad` smallint DEFAULT NULL,
  `droga` varchar(250) DEFAULT NULL,
  `presentacion` smallint DEFAULT NULL,
  `dosisminima` varchar(15) DEFAULT NULL,
  `dosismaxima` varchar(15) DEFAULT NULL,
  `concen1` varchar(15) DEFAULT NULL,
  `concen2` varchar(15) DEFAULT NULL,
  `concen3` varchar(15) DEFAULT NULL,
  `concen4` varchar(15) DEFAULT NULL,
  `concen5` varchar(15) DEFAULT NULL,
  `concen6` varchar(15) DEFAULT NULL,
  `concen7` varchar(15) DEFAULT NULL,
  `descripcion` longtext,
  `tipoespecialidad` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `lista_ciudad`
--

CREATE TABLE `lista_ciudad` (
  `id` int NOT NULL,
  `prioridad` smallint DEFAULT NULL,
  `nombre` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `lista_coberturas`
--

CREATE TABLE `lista_coberturas` (
  `id` int NOT NULL,
  `prioridad` smallint DEFAULT NULL,
  `nombre` varchar(255) DEFAULT NULL,
  `porcentaje_cobertura` double DEFAULT NULL,
  `plancober` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `lista_derivaciones`
--

CREATE TABLE `lista_derivaciones` (
  `id` int NOT NULL,
  `prioridad` smallint DEFAULT NULL,
  `nombre` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `lista_doctores`
--

CREATE TABLE `lista_doctores` (
  `id` int NOT NULL,
  `id_clinica` int NOT NULL DEFAULT '1',
  `nombre` varchar(150) DEFAULT NULL,
  `medicoconvenio` tinyint(1) DEFAULT '0',
  `bloquearmisconsultas` tinyint(1) DEFAULT '0',
  `sucursal1` tinyint(1) DEFAULT '0',
  `sucursal2` tinyint(1) DEFAULT '0',
  `sucursal3` tinyint(1) DEFAULT '0',
  `sucursal4` tinyint(1) DEFAULT '0',
  `sucursal5` tinyint(1) DEFAULT '0',
  `sucursal6` tinyint(1) DEFAULT '0',
  `sucursal7` tinyint(1) DEFAULT '0',
  `sucursal8` tinyint(1) DEFAULT '0',
  `sucursal9` tinyint(1) DEFAULT '0',
  `sucursal10` tinyint(1) DEFAULT '0',
  `activo` tinyint(1) DEFAULT '1',
  `notas` text,
  `creado_en` datetime DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `especialidad` varchar(100) DEFAULT NULL COMMENT 'Access: Especialidad',
  `matricula` varchar(20) DEFAULT NULL COMMENT 'Access: Matricula',
  `telefono` varchar(30) DEFAULT NULL COMMENT 'Access: Tel',
  `domicilio` varchar(100) DEFAULT NULL COMMENT 'Access: Domi',
  `localidad` varchar(50) DEFAULT NULL COMMENT 'Access: Locali',
  `consultorio` varchar(30) DEFAULT NULL COMMENT 'Access: consultorio'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `lista_especialidades_doctores`
--

CREATE TABLE `lista_especialidades_doctores` (
  `id` int NOT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `lista_estado_civil`
--

CREATE TABLE `lista_estado_civil` (
  `id` int NOT NULL,
  `prioridad` smallint DEFAULT NULL,
  `nombre` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `lista_estatus_pais`
--

CREATE TABLE `lista_estatus_pais` (
  `id` int NOT NULL,
  `prioridad` smallint DEFAULT NULL,
  `nombre` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `lista_etnia`
--

CREATE TABLE `lista_etnia` (
  `id` int NOT NULL,
  `prioridad` smallint DEFAULT NULL,
  `nombre` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `lista_factor_sanguineo`
--

CREATE TABLE `lista_factor_sanguineo` (
  `id` int NOT NULL,
  `prioridad` smallint DEFAULT NULL,
  `nombre` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `lista_grupo_sanguineo`
--

CREATE TABLE `lista_grupo_sanguineo` (
  `id` int NOT NULL,
  `prioridad` smallint DEFAULT NULL,
  `nombre` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `lista_identidad_genero`
--

CREATE TABLE `lista_identidad_genero` (
  `id` int NOT NULL,
  `prioridad` smallint DEFAULT NULL,
  `nombre` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `lista_motivos_consulta`
--

CREATE TABLE `lista_motivos_consulta` (
  `id` int NOT NULL,
  `prioridad` smallint DEFAULT NULL,
  `nombre` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Access: Lista Motivos Consulta → agenda_turnos.motivo';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `lista_ocupacion`
--

CREATE TABLE `lista_ocupacion` (
  `id` int NOT NULL,
  `prioridad` smallint DEFAULT NULL,
  `nombre` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `lista_odontograma_codigos`
--

CREATE TABLE `lista_odontograma_codigos` (
  `id` int NOT NULL,
  `prioridad` smallint DEFAULT NULL,
  `codigo` varchar(12) DEFAULT NULL COMMENT 'Símbolo o abreviatura en el gráfico/leyenda',
  `nombre` varchar(255) NOT NULL,
  `color_hex` varchar(7) DEFAULT NULL COMMENT 'Color en mapa (#RRGGBB)',
  `mapa_overlay` varchar(24) DEFAULT NULL COMMENT 'Vacío=caras; pieza_diagonal|pieza_x|pieza_circulo|pieza_relleno'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `lista_orientacion_sex`
--

CREATE TABLE `lista_orientacion_sex` (
  `id` int NOT NULL,
  `prioridad` smallint DEFAULT NULL,
  `nombre` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `lista_pais`
--

CREATE TABLE `lista_pais` (
  `id` int NOT NULL,
  `prioridad` smallint DEFAULT NULL,
  `nombre` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `lista_planes`
--

CREATE TABLE `lista_planes` (
  `id` int NOT NULL,
  `id_cobertura` int DEFAULT NULL,
  `nombre` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `lista_practicas`
--

CREATE TABLE `lista_practicas` (
  `id` int NOT NULL,
  `prioridad` smallint DEFAULT NULL,
  `nombre` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `lista_primera_vez`
--

CREATE TABLE `lista_primera_vez` (
  `id` int NOT NULL,
  `prioridad` smallint DEFAULT NULL,
  `nombre` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `lista_provincia`
--

CREATE TABLE `lista_provincia` (
  `id` int NOT NULL,
  `prioridad` smallint DEFAULT NULL,
  `nombre` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `lista_relacion_paciente`
--

CREATE TABLE `lista_relacion_paciente` (
  `id` int NOT NULL,
  `prioridad` smallint DEFAULT NULL,
  `nombre` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `lista_sexo`
--

CREATE TABLE `lista_sexo` (
  `id` int NOT NULL,
  `prioridad` smallint DEFAULT NULL,
  `nombre` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `lista_sucursales`
--

CREATE TABLE `lista_sucursales` (
  `id` smallint NOT NULL,
  `prioridad` smallint DEFAULT NULL,
  `nombre` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `lista_tipo_documento`
--

CREATE TABLE `lista_tipo_documento` (
  `id` int NOT NULL,
  `prioridad` smallint DEFAULT NULL,
  `nombre` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pacientes`
--

CREATE TABLE `pacientes` (
  `id` int NOT NULL,
  `id_clinica` int NOT NULL DEFAULT '1',
  `NroHC` int NOT NULL COMMENT 'Número historia clínica',
  `Nombres` varchar(200) DEFAULT NULL,
  `DNI` varchar(20) DEFAULT NULL,
  `convenio` tinyint(1) DEFAULT '0',
  `fecha_nacimiento` date DEFAULT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `direccion` text,
  `activo` tinyint(1) DEFAULT '1',
  `notas` text,
  `creado_en` datetime DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `numehistoria` varchar(30) DEFAULT NULL COMMENT 'Access: numehistoria',
  `embarazo` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Access: Embarazo',
  `ulti_emba` smallint DEFAULT NULL COMMENT 'Access: UltiEmba',
  `ultima_cons` datetime DEFAULT NULL COMMENT 'Access: UltimaCons',
  `paciente_inactivo` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Access: pacienteinactivo',
  `motivo_inactividad` varchar(250) DEFAULT NULL COMMENT 'Access: motivoinactividad',
  `cobertura` smallint DEFAULT NULL COMMENT 'Access: cobertura',
  `id_cobertura` int DEFAULT NULL COMMENT 'Access: idcobertura',
  `nro_os` varchar(30) DEFAULT NULL COMMENT 'Access: NroOS',
  `apellido` varchar(30) DEFAULT NULL COMMENT 'Access: Apellido',
  `apellido2` varchar(30) DEFAULT NULL COMMENT 'Access: apellido2',
  `fe_nac` datetime DEFAULT NULL COMMENT 'Access: FeNac',
  `sexo` smallint DEFAULT NULL COMMENT 'Access: sexo',
  `dni_sin_uso` varchar(10) DEFAULT NULL COMMENT 'Access: DNISinUso',
  `id_tipo_doc` int DEFAULT NULL COMMENT 'Access: idtipodoc',
  `id_ocupacion` int DEFAULT NULL COMMENT 'Access: idocupacion',
  `detalle_ocupacion` varchar(250) DEFAULT NULL COMMENT 'Access: detallesocupacion',
  `tel_celular` varchar(30) DEFAULT NULL COMMENT 'Access: TelCelular',
  `tel_laboral` varchar(30) DEFAULT NULL COMMENT 'Access: TelLaboral',
  `nombre_padre` varchar(30) DEFAULT NULL COMMENT 'Access: nombrepadre',
  `naci_padre` datetime DEFAULT NULL COMMENT 'Access: nacipadre',
  `id_ocupacion_padre` int DEFAULT NULL COMMENT 'Access: idocupacionpadre',
  `horas_hogar_padre` varchar(10) DEFAULT NULL COMMENT 'Access: horashogarpadre',
  `nombre_madre` varchar(30) DEFAULT NULL COMMENT 'Access: nombremadre',
  `naci_madre` datetime DEFAULT NULL COMMENT 'Access: nacimadre',
  `id_ocupacion_madre` int DEFAULT NULL COMMENT 'Access: idocupacionmadre',
  `horas_hogar_madre` varchar(10) DEFAULT NULL COMMENT 'Access: horashogarmadre',
  `nro_hermanos` varchar(2) DEFAULT NULL COMMENT 'Access: nrohermanos',
  `edad_hermanos` varchar(50) DEFAULT NULL COMMENT 'Access: edadhermanos',
  `nro_hermanas` varchar(2) DEFAULT NULL COMMENT 'Access: nrohermanas',
  `edad_hermanas` varchar(50) DEFAULT NULL COMMENT 'Access: edadhermanas',
  `detalles_familia` varchar(250) DEFAULT NULL COMMENT 'Access: detallesfamilia',
  `ape1_contacto` varchar(30) DEFAULT NULL COMMENT 'Access: ape1contacto',
  `ape2_contacto` varchar(30) DEFAULT NULL COMMENT 'Access: ape2contacto',
  `nombre_contacto` varchar(30) DEFAULT NULL COMMENT 'Access: nombrecontacto',
  `id_relacion` int DEFAULT NULL COMMENT 'Access: idrelacion',
  `tel_par_contacto` varchar(30) DEFAULT NULL COMMENT 'Access: telparcontacto',
  `tel_cel_contacto` varchar(30) DEFAULT NULL COMMENT 'Access: telcelcontacto',
  `tel_lab_contacto` varchar(30) DEFAULT NULL COMMENT 'Access: tellabcontacto',
  `id_estado_civil` int DEFAULT NULL COMMENT 'Access: idestadocivil',
  `id_etnia` int DEFAULT NULL COMMENT 'Access: idetnia',
  `id_ciudad` int DEFAULT NULL COMMENT 'Access: idciudad',
  `cp` varchar(10) DEFAULT NULL COMMENT 'Access: CP',
  `id_provincia` int DEFAULT NULL COMMENT 'Access: idprovincia',
  `id_pais` int DEFAULT NULL COMMENT 'Access: idpais',
  `id_estatus` int DEFAULT NULL COMMENT 'Access: idestatus',
  `alergias` longtext COMMENT 'Access: alergias',
  `grupo_sanguineo` smallint DEFAULT NULL COMMENT 'Access: GrupoSanguineo',
  `factor_sanguineo` smallint DEFAULT NULL COMMENT 'Access: FactorSanguineo',
  `hc_texto` longtext COMMENT 'Access: HC',
  `referente` varchar(100) DEFAULT NULL COMMENT 'Access: referente',
  `id_cobertura2` int DEFAULT NULL COMMENT 'Access: idcobertura2',
  `nu_afiliado2` varchar(30) DEFAULT NULL COMMENT 'Access: nuafiliado2',
  `antecedentes_hc` longtext COMMENT 'Access: antecedenteshc',
  `id_plan` int DEFAULT NULL COMMENT 'Access: idplan',
  `paga_iva` smallint DEFAULT NULL COMMENT 'Access: pagaiva',
  `alta_paci_web` smallint DEFAULT NULL COMMENT 'Access: altapaciweb',
  `identidad_gen` smallint DEFAULT NULL COMMENT 'Access: identidadgen',
  `orientacion_sex` smallint DEFAULT NULL COMMENT 'Access: orientacionsex'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Pacientes Alimentacion`
--

CREATE TABLE `Pacientes Alimentacion` (
  `id` int NOT NULL,
  `NroPaci` int DEFAULT NULL,
  `pecho` varchar(3) DEFAULT NULL,
  `pechodeta` varchar(50) DEFAULT NULL,
  `mater` varchar(3) DEFAULT NULL,
  `materdeta` varchar(50) DEFAULT NULL,
  `modif` varchar(3) DEFAULT NULL,
  `modifdeta` varchar(50) DEFAULT NULL,
  `comer` varchar(3) DEFAULT NULL,
  `comerdeta` varchar(50) DEFAULT NULL,
  `purefrutas` varchar(3) DEFAULT NULL,
  `purefrutasdeta` varchar(50) DEFAULT NULL,
  `gelatina` varchar(3) DEFAULT NULL,
  `gelatinadeta` varchar(50) DEFAULT NULL,
  `purevegetales` varchar(3) DEFAULT NULL,
  `purevegetalesdeta` varchar(50) DEFAULT NULL,
  `carnes` varchar(3) DEFAULT NULL,
  `carnesdeta` varchar(50) DEFAULT NULL,
  `harina` varchar(3) DEFAULT NULL,
  `harinadeta` varchar(50) DEFAULT NULL,
  `sopas` varchar(3) DEFAULT NULL,
  `sopasdeta` varchar(50) DEFAULT NULL,
  `carnepescado` varchar(3) DEFAULT NULL,
  `carnepescadodeta` varchar(50) DEFAULT NULL,
  `precocidos` varchar(3) DEFAULT NULL,
  `precocidosdeta` varchar(50) DEFAULT NULL,
  `huevos` varchar(3) DEFAULT NULL,
  `huevosdeta` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Pacientes Analisis`
--

CREATE TABLE `Pacientes Analisis` (
  `id` int NOT NULL,
  `idestupaci` int DEFAULT NULL,
  `idana` int DEFAULT NULL,
  `NroPaci` int DEFAULT NULL,
  `Resultado` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Pacientes Biomicroscopia`
--

CREATE TABLE `Pacientes Biomicroscopia` (
  `id` int NOT NULL,
  `NroPaci` int DEFAULT NULL,
  `fecha` datetime DEFAULT NULL,
  `nombremarca1` varchar(100) DEFAULT NULL,
  `nombremarca2` varchar(100) DEFAULT NULL,
  `nombremarca3` varchar(100) DEFAULT NULL,
  `nombremarca4` varchar(100) DEFAULT NULL,
  `nombremarca5` varchar(100) DEFAULT NULL,
  `nombremarca6` varchar(100) DEFAULT NULL,
  `nombremarca7` varchar(100) DEFAULT NULL,
  `nombremarca8` varchar(100) DEFAULT NULL,
  `nombremarca9` varchar(100) DEFAULT NULL,
  `Esquema` varchar(240) DEFAULT NULL,
  `Observaciones` longtext
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Pacientes Cirugias`
--

CREATE TABLE `Pacientes Cirugias` (
  `id` int NOT NULL,
  `NroPaci` int DEFAULT NULL,
  `fecha` datetime DEFAULT NULL,
  `diasingreso` varchar(5) DEFAULT NULL,
  `cirugia` varchar(100) DEFAULT NULL,
  `anestesia` varchar(50) DEFAULT NULL,
  `lugar` varchar(50) DEFAULT NULL,
  `anestesiologo` varchar(50) DEFAULT NULL,
  `diagpreoperatorio` varchar(250) DEFAULT NULL,
  `diaghistopatologico` varchar(250) DEFAULT NULL,
  `observaciones` longtext,
  `fechaingreso` datetime DEFAULT NULL,
  `fechaegreso` datetime DEFAULT NULL,
  `cirujano` varchar(50) DEFAULT NULL,
  `tipocirujano` smallint DEFAULT NULL,
  `primerayudante` varchar(50) DEFAULT NULL,
  `segundoayudante` varchar(50) DEFAULT NULL,
  `instrumentador` varchar(50) DEFAULT NULL,
  `circulante` varchar(50) DEFAULT NULL,
  `diagposoperatorio` varchar(250) DEFAULT NULL,
  `tratamiento` longtext,
  `procequirurgico` longtext,
  `evolucion` longtext,
  `indicaciones` longtext
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Pacientes Controles Antropometricos`
--

CREATE TABLE `Pacientes Controles Antropometricos` (
  `id` int NOT NULL,
  `NroPaci` int DEFAULT NULL,
  `fecha` datetime DEFAULT NULL,
  `peso` float DEFAULT NULL,
  `talla` float DEFAULT NULL,
  `pericefa` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Pacientes Control Feto`
--

CREATE TABLE `Pacientes Control Feto` (
  `id` int NOT NULL,
  `NroPaci` int DEFAULT NULL,
  `NroEmba` smallint DEFAULT NULL,
  `NroFeto` smallint DEFAULT NULL,
  `Fecha` datetime DEFAULT NULL,
  `DiameBipa` float DEFAULT NULL,
  `CircuCraneal` float DEFAULT NULL,
  `DiameToracico` float DEFAULT NULL,
  `Abdomen` float DEFAULT NULL,
  `CircunAbdo` float DEFAULT NULL,
  `LongiFemur` float DEFAULT NULL,
  `PesoFeto` smallint DEFAULT NULL,
  `TallaFeto` float DEFAULT NULL,
  `FreCar` float DEFAULT NULL,
  `PresenFeto` smallint DEFAULT NULL,
  `UbiPlacen` smallint DEFAULT NULL,
  `Madu` smallint DEFAULT NULL,
  `Liquido` smallint DEFAULT NULL,
  `CRL` float DEFAULT NULL,
  `hechopor` varchar(50) DEFAULT NULL,
  `Observaciones` longtext
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Pacientes Control Materno`
--

CREATE TABLE `Pacientes Control Materno` (
  `id` int NOT NULL,
  `NroPaci` int DEFAULT NULL,
  `NroEmba` smallint DEFAULT NULL,
  `Fecha` datetime DEFAULT NULL,
  `AltuUter` float DEFAULT NULL,
  `pesoactual` float DEFAULT NULL,
  `PesoGana` float DEFAULT NULL,
  `DinaUter` float DEFAULT NULL,
  `Diastolica` float DEFAULT NULL,
  `Sistolica` float DEFAULT NULL,
  `MoviFeta` tinyint(1) DEFAULT NULL,
  `Edema` tinyint(1) DEFAULT NULL,
  `Observaciones` longtext,
  `prurito` varchar(20) DEFAULT NULL,
  `latidosfetales` smallint DEFAULT NULL,
  `fr` float DEFAULT NULL,
  `pulso` float DEFAULT NULL,
  `temperatura` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Pacientes Embarazos`
--

CREATE TABLE `Pacientes Embarazos` (
  `NroPaci` int DEFAULT NULL,
  `NroEmba` smallint DEFAULT NULL,
  `FinEmba` tinyint(1) DEFAULT NULL,
  `FechaParto` datetime DEFAULT NULL,
  `CantiFeto` smallint DEFAULT NULL,
  `PesoHabi` float DEFAULT NULL,
  `talla` float DEFAULT NULL,
  `imc` float DEFAULT NULL,
  `FUM` datetime DEFAULT NULL,
  `fppeco` datetime DEFAULT NULL,
  `lugarparto` varchar(50) DEFAULT NULL,
  `antitetanica` smallint DEFAULT NULL,
  `mesdosis1` varchar(1) DEFAULT NULL,
  `mesdosis2` varchar(1) DEFAULT NULL,
  `antirubeola` smallint DEFAULT NULL,
  `fumaacti1` smallint DEFAULT NULL,
  `fumapasi1` smallint DEFAULT NULL,
  `drogas1` smallint DEFAULT NULL,
  `alcohol1` smallint DEFAULT NULL,
  `violencia1` smallint DEFAULT NULL,
  `fumaacti2` smallint DEFAULT NULL,
  `fumapasi2` smallint DEFAULT NULL,
  `drogas2` smallint DEFAULT NULL,
  `alcohol2` smallint DEFAULT NULL,
  `violencia2` smallint DEFAULT NULL,
  `fumaacti3` smallint DEFAULT NULL,
  `fumapasi3` smallint DEFAULT NULL,
  `drogas3` smallint DEFAULT NULL,
  `alcohol3` smallint DEFAULT NULL,
  `violencia3` smallint DEFAULT NULL,
  `exaodon` smallint DEFAULT NULL,
  `examamas` smallint DEFAULT NULL,
  `inspecvisu` smallint DEFAULT NULL,
  `pap` smallint DEFAULT NULL,
  `colp` smallint DEFAULT NULL,
  `toxomenos20` smallint DEFAULT NULL,
  `toxomas20` smallint DEFAULT NULL,
  `consulta1` smallint DEFAULT NULL,
  `vihmenos20soli` smallint DEFAULT NULL,
  `vihmenos20reali` smallint DEFAULT NULL,
  `vihmas20soli` smallint DEFAULT NULL,
  `vihmas20reali` smallint DEFAULT NULL,
  `hb0` varchar(5) DEFAULT NULL,
  `hb1` varchar(5) DEFAULT NULL,
  `hb110` tinyint(1) DEFAULT NULL,
  `hb111` tinyint(1) DEFAULT NULL,
  `fe` smallint DEFAULT NULL,
  `folatos` smallint DEFAULT NULL,
  `chagas` smallint DEFAULT NULL,
  `palu` smallint DEFAULT NULL,
  `bactermenos20` smallint DEFAULT NULL,
  `bactermas20` smallint DEFAULT NULL,
  `glu0` varchar(5) DEFAULT NULL,
  `glu1` varchar(5) DEFAULT NULL,
  `glu1050` tinyint(1) DEFAULT NULL,
  `glu1051` tinyint(1) DEFAULT NULL,
  `estrep` smallint DEFAULT NULL,
  `prepa` smallint DEFAULT NULL,
  `conse` smallint DEFAULT NULL,
  `vdrlmenos20` smallint DEFAULT NULL,
  `vdrlmas20` smallint DEFAULT NULL,
  `sificonfir` smallint DEFAULT NULL,
  `tratasifi` smallint DEFAULT NULL,
  `anteanemia` smallint DEFAULT NULL,
  `anteciru` smallint DEFAULT NULL,
  `antemalfor` smallint DEFAULT NULL,
  `antetrombo` smallint DEFAULT NULL,
  `anteotra` smallint DEFAULT NULL,
  `anteviolen` smallint DEFAULT NULL,
  `detaanteper` longtext,
  `antepreeclam` smallint DEFAULT NULL,
  `antetrastor` smallint DEFAULT NULL,
  `anteotrafa` smallint DEFAULT NULL,
  `detaantefami` longtext,
  `fechaultiemba` datetime DEFAULT NULL,
  `antepesoulti` smallint DEFAULT NULL,
  `antegeme` smallint DEFAULT NULL,
  `antepreeclamob` smallint DEFAULT NULL,
  `anteretar` smallint DEFAULT NULL,
  `anterotu` smallint DEFAULT NULL,
  `anteparto` smallint DEFAULT NULL,
  `anteplacen` smallint DEFAULT NULL,
  `anteproduc` smallint DEFAULT NULL,
  `anteincom` smallint DEFAULT NULL,
  `antedespren` smallint DEFAULT NULL,
  `anteproblem` smallint DEFAULT NULL,
  `detaanteobste` longtext,
  `anteemba` smallint DEFAULT NULL,
  `fracameto` smallint DEFAULT NULL,
  `tsh` smallint DEFAULT NULL,
  `creatinina` smallint DEFAULT NULL,
  `tp` smallint DEFAULT NULL,
  `hctomenos20` varchar(7) DEFAULT NULL,
  `hctomas20` varchar(7) DEFAULT NULL,
  `ptgomenos20` varchar(7) DEFAULT NULL,
  `ptgomas20` varchar(7) DEFAULT NULL,
  `observaciones` longtext
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Pacientes Embarazos Finalizados`
--

CREATE TABLE `Pacientes Embarazos Finalizados` (
  `NroPaci` int DEFAULT NULL,
  `NroEmba` smallint DEFAULT NULL,
  `NroFeto` smallint DEFAULT NULL,
  `fechaparto` datetime DEFAULT NULL,
  `lugarparto` varchar(30) DEFAULT NULL,
  `partoatendidopor` smallint DEFAULT NULL,
  `iniparto` smallint DEFAULT NULL,
  `presen` smallint DEFAULT NULL,
  `desga` smallint DEFAULT NULL,
  `alum` smallint DEFAULT NULL,
  `placen` smallint DEFAULT NULL,
  `muertefetal` smallint DEFAULT NULL,
  `termi` smallint DEFAULT NULL,
  `termideta` varchar(250) DEFAULT NULL,
  `nombre` varchar(30) DEFAULT NULL,
  `sexo` smallint DEFAULT NULL,
  `pesonacer` float DEFAULT NULL,
  `alimentacion` smallint DEFAULT NULL,
  `detallesnacido` varchar(250) DEFAULT NULL,
  `vdrl` smallint DEFAULT NULL,
  `tsh` smallint DEFAULT NULL,
  `hbpatia` smallint DEFAULT NULL,
  `bili` smallint DEFAULT NULL,
  `toxo` smallint DEFAULT NULL,
  `neonatoatendidopor` smallint DEFAULT NULL,
  `cpfecha1` varchar(10) DEFAULT NULL,
  `cptemp1` varchar(10) DEFAULT NULL,
  `cppa1` varchar(10) DEFAULT NULL,
  `cppulso1` varchar(10) DEFAULT NULL,
  `cpinvo1` varchar(50) DEFAULT NULL,
  `cploquios1` varchar(50) DEFAULT NULL,
  `cpmamas1` varchar(50) DEFAULT NULL,
  `cpanimo1` varchar(50) DEFAULT NULL,
  `cpobser1` varchar(250) DEFAULT NULL,
  `cpfecha2` varchar(10) DEFAULT NULL,
  `cptemp2` varchar(10) DEFAULT NULL,
  `cppa2` varchar(10) DEFAULT NULL,
  `cppulso2` varchar(10) DEFAULT NULL,
  `cpinvo2` varchar(50) DEFAULT NULL,
  `cploquios2` varchar(50) DEFAULT NULL,
  `cpmamas2` varchar(50) DEFAULT NULL,
  `cpanimo2` varchar(50) DEFAULT NULL,
  `cpobser2` varchar(250) DEFAULT NULL,
  `cpfecha3` varchar(10) DEFAULT NULL,
  `cptemp3` varchar(10) DEFAULT NULL,
  `cppa3` varchar(10) DEFAULT NULL,
  `cppulso3` varchar(10) DEFAULT NULL,
  `cpinvo3` varchar(50) DEFAULT NULL,
  `cploquios3` varchar(50) DEFAULT NULL,
  `cpmamas3` varchar(50) DEFAULT NULL,
  `cpanimo3` varchar(50) DEFAULT NULL,
  `cpobser3` varchar(250) DEFAULT NULL,
  `notas` varchar(250) DEFAULT NULL,
  `anticoncep` smallint DEFAULT NULL,
  `anticoncepmetodo` smallint DEFAULT NULL,
  `episiotomia` smallint DEFAULT NULL,
  `fechaovito` datetime DEFAULT NULL,
  `gesta` smallint DEFAULT NULL,
  `para` smallint DEFAULT NULL,
  `parto` smallint DEFAULT NULL,
  `controlemba` smallint DEFAULT NULL,
  `fechaingreso` datetime DEFAULT NULL,
  `diagingreso` varchar(250) DEFAULT NULL,
  `serologia` varchar(250) DEFAULT NULL,
  `malfcong` varchar(250) DEFAULT NULL,
  `arm` smallint DEFAULT NULL,
  `torch` smallint DEFAULT NULL,
  `fechaalta` datetime DEFAULT NULL,
  `bacteriologia` varchar(250) DEFAULT NULL,
  `talla` float DEFAULT NULL,
  `percef` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Pacientes Enfermedades Ginecologicas`
--

CREATE TABLE `Pacientes Enfermedades Ginecologicas` (
  `id` int NOT NULL,
  `NroPaci` int DEFAULT NULL,
  `idenfergine` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Pacientes Esquema Ginecologico`
--

CREATE TABLE `Pacientes Esquema Ginecologico` (
  `id` int NOT NULL,
  `NroPaci` int DEFAULT NULL,
  `Fecha` datetime DEFAULT NULL,
  `Esquema` varchar(240) DEFAULT NULL,
  `examennormal` smallint DEFAULT NULL,
  `Observaciones` varchar(250) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Pacientes Estudios`
--

CREATE TABLE `Pacientes Estudios` (
  `id` int NOT NULL,
  `NroPaci` int DEFAULT NULL,
  `Tipo` smallint DEFAULT NULL,
  `categoria` int DEFAULT NULL,
  `idestu` int DEFAULT NULL,
  `Estudio` varchar(250) DEFAULT NULL,
  `Fecha` datetime DEFAULT NULL,
  `Numero` varchar(5) DEFAULT NULL,
  `Realizador` varchar(30) DEFAULT NULL,
  `Resultado` longtext,
  `Conclusion` longtext,
  `Observaciones` longtext
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Pacientes Estudios Imagenes`
--

CREATE TABLE `Pacientes Estudios Imagenes` (
  `id` int NOT NULL,
  `NroPaci` int DEFAULT NULL,
  `idestu` int DEFAULT NULL,
  `descripcion` varchar(200) DEFAULT NULL,
  `archivo` varchar(100) DEFAULT NULL,
  `fecha` datetime DEFAULT NULL,
  `archivo2` varchar(100) DEFAULT NULL,
  `archivo3` varchar(100) DEFAULT NULL,
  `archivo4` varchar(100) DEFAULT NULL,
  `archivo5` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Pacientes Fondo de Ojo`
--

CREATE TABLE `Pacientes Fondo de Ojo` (
  `id` int NOT NULL,
  `NroPaci` int DEFAULT NULL,
  `fecha` datetime DEFAULT NULL,
  `nombremarca1` varchar(100) DEFAULT NULL,
  `nombremarca2` varchar(100) DEFAULT NULL,
  `nombremarca3` varchar(100) DEFAULT NULL,
  `nombremarca4` varchar(100) DEFAULT NULL,
  `nombremarca5` varchar(100) DEFAULT NULL,
  `nombremarca6` varchar(100) DEFAULT NULL,
  `nombremarca7` varchar(100) DEFAULT NULL,
  `nombremarca8` varchar(100) DEFAULT NULL,
  `nombremarca9` varchar(100) DEFAULT NULL,
  `Esquema` varchar(240) DEFAULT NULL,
  `Observaciones` longtext
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Pacientes Lesiones Vulva`
--

CREATE TABLE `Pacientes Lesiones Vulva` (
  `id` int NOT NULL,
  `NroPaci` int DEFAULT NULL,
  `Fecha` datetime DEFAULT NULL,
  `Esquema` varchar(240) DEFAULT NULL,
  `ganglios` varchar(50) DEFAULT NULL,
  `examennormal` smallint DEFAULT NULL,
  `Observaciones` varchar(250) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Pacientes Nervio Optico`
--

CREATE TABLE `Pacientes Nervio Optico` (
  `id` int NOT NULL,
  `NroPaci` int DEFAULT NULL,
  `fecha` datetime DEFAULT NULL,
  `nombremarca1` varchar(100) DEFAULT NULL,
  `nombremarca2` varchar(100) DEFAULT NULL,
  `nombremarca3` varchar(100) DEFAULT NULL,
  `nombremarca4` varchar(100) DEFAULT NULL,
  `nombremarca5` varchar(100) DEFAULT NULL,
  `nombremarca6` varchar(100) DEFAULT NULL,
  `nombremarca7` varchar(100) DEFAULT NULL,
  `nombremarca8` varchar(100) DEFAULT NULL,
  `nombremarca9` varchar(100) DEFAULT NULL,
  `Esquema` varchar(240) DEFAULT NULL,
  `Observaciones` longtext
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Pacientes Odontogramas`
--

CREATE TABLE `Pacientes Odontogramas` (
  `id` int NOT NULL,
  `NroPaci` int DEFAULT NULL,
  `tipo` smallint DEFAULT NULL,
  `fecha` datetime DEFAULT NULL,
  `dientes` longtext,
  `observaciones` longtext
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Pacientes Operaciones Ginecologicas`
--

CREATE TABLE `Pacientes Operaciones Ginecologicas` (
  `id` int NOT NULL,
  `NroPaci` int DEFAULT NULL,
  `idopergine` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Pacientes Ordenes`
--

CREATE TABLE `Pacientes Ordenes` (
  `id` int NOT NULL,
  `id_clinica` int NOT NULL DEFAULT '1',
  `NroPaci` int DEFAULT NULL,
  `numero` int DEFAULT NULL,
  `fecha` datetime DEFAULT NULL,
  `entregada` tinyint(1) DEFAULT NULL,
  `autorizada` tinyint(1) DEFAULT NULL,
  `sesiones` smallint DEFAULT NULL,
  `costo` float DEFAULT NULL,
  `pago` float DEFAULT NULL,
  `iddoctor` int DEFAULT NULL,
  `idobrasocial` int DEFAULT NULL,
  `observaciones` longtext,
  `numeautorizacion` smallint DEFAULT NULL,
  `costo_os` float DEFAULT NULL,
  `estado` varchar(1) DEFAULT NULL,
  `estado_os` varchar(1) DEFAULT NULL,
  `idpractica` int DEFAULT NULL,
  `idderivado` int DEFAULT NULL,
  `fechaderivacion` datetime DEFAULT NULL,
  `fechaautorizacion` datetime DEFAULT NULL,
  `fechaentrega` datetime DEFAULT NULL,
  `idusuariocarga` int DEFAULT NULL,
  `sesionesreali` int DEFAULT NULL,
  `diente` varchar(2) DEFAULT NULL,
  `cara` varchar(5) DEFAULT NULL,
  `nusiniestro` varchar(30) DEFAULT NULL,
  `pagaiva` smallint DEFAULT NULL,
  `cerrada` smallint DEFAULT NULL,
  `tipoasistencia` smallint DEFAULT NULL,
  `liquidada` tinyint(1) DEFAULT NULL,
  `honorarioextra` float DEFAULT NULL,
  `honorariofecha` datetime DEFAULT NULL,
  `idplan` int DEFAULT NULL,
  `sucursal` smallint DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Pacientes Pagos`
--

CREATE TABLE `Pacientes Pagos` (
  `id` int NOT NULL,
  `NroPaci` int DEFAULT NULL,
  `idorden` int DEFAULT NULL,
  `fecha` datetime DEFAULT NULL,
  `importe` float DEFAULT NULL,
  `iddoctor` int DEFAULT NULL,
  `turno` smallint DEFAULT NULL,
  `observaciones` varchar(250) DEFAULT NULL,
  `quien` varchar(1) DEFAULT NULL,
  `libros` smallint DEFAULT NULL,
  `modopagoorden` smallint DEFAULT NULL,
  `sucursal` smallint DEFAULT NULL,
  `numerofactura` varchar(30) DEFAULT NULL,
  `numerobono` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Pacientes Pautas`
--

CREATE TABLE `Pacientes Pautas` (
  `id` int NOT NULL,
  `NroPaci` int DEFAULT NULL,
  `fecha1` datetime DEFAULT NULL,
  `fecha2` datetime DEFAULT NULL,
  `fecha3` datetime DEFAULT NULL,
  `fecha4` datetime DEFAULT NULL,
  `fecha5` datetime DEFAULT NULL,
  `fecha6` datetime DEFAULT NULL,
  `fecha7` datetime DEFAULT NULL,
  `fecha8` datetime DEFAULT NULL,
  `fecha9` datetime DEFAULT NULL,
  `fecha10` datetime DEFAULT NULL,
  `fecha11` datetime DEFAULT NULL,
  `notas` longtext
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Pacientes Presupuestos`
--

CREATE TABLE `Pacientes Presupuestos` (
  `id` int NOT NULL,
  `NroPaci` int DEFAULT NULL,
  `idpractica` int DEFAULT NULL,
  `costo` float DEFAULT NULL,
  `costo_os` float DEFAULT NULL,
  `iddoctor` int DEFAULT NULL,
  `idobrasocial` int DEFAULT NULL,
  `diente` varchar(2) DEFAULT NULL,
  `cara` varchar(5) DEFAULT NULL,
  `idplan` int DEFAULT NULL,
  `pagaiva` smallint DEFAULT NULL,
  `idpresupuesto` int DEFAULT NULL,
  `cantidad` smallint DEFAULT NULL,
  `practica` varchar(250) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Pacientes Presupuestos General`
--

CREATE TABLE `Pacientes Presupuestos General` (
  `id` int NOT NULL,
  `NroPaci` int DEFAULT NULL,
  `fecha` datetime DEFAULT NULL,
  `observaciones` longtext,
  `Apellido` varchar(30) DEFAULT NULL,
  `Nombres` varchar(30) DEFAULT NULL,
  `DNI` varchar(15) DEFAULT NULL,
  `Tel` varchar(30) DEFAULT NULL,
  `TelCelular` varchar(30) DEFAULT NULL,
  `Domicilio` varchar(250) DEFAULT NULL,
  `idcobertura` int DEFAULT NULL,
  `idplan` int DEFAULT NULL,
  `pagaiva` smallint DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Pacientes Sesiones`
--

CREATE TABLE `Pacientes Sesiones` (
  `id` int NOT NULL,
  `NroPaci` int DEFAULT NULL,
  `idorden` int DEFAULT NULL,
  `fecha` datetime DEFAULT NULL,
  `codigoautorizacion` varchar(20) DEFAULT NULL,
  `iddoctor` int DEFAULT NULL,
  `observaciones` longtext,
  `liquidadas` tinyint(1) DEFAULT NULL,
  `autorizadafuerafecha` tinyint(1) DEFAULT NULL,
  `honorarioextras` float DEFAULT NULL,
  `honorariofechas` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Pacientes Ultrasonografia Pelvica`
--

CREATE TABLE `Pacientes Ultrasonografia Pelvica` (
  `id` int NOT NULL,
  `NroPaci` int DEFAULT NULL,
  `NroEmba` smallint DEFAULT NULL,
  `fecha` datetime DEFAULT NULL,
  `longiutepelvi` float DEFAULT NULL,
  `anteutepelvi` float DEFAULT NULL,
  `transverutepelvi` float DEFAULT NULL,
  `posiutepelvi` smallint DEFAULT NULL,
  `descriutepelvi` varchar(250) DEFAULT NULL,
  `longiderpelvi` float DEFAULT NULL,
  `antederpelvi` float DEFAULT NULL,
  `transverderpelvi` float DEFAULT NULL,
  `voluderpelvi` float DEFAULT NULL,
  `descriderpelvi` varchar(250) DEFAULT NULL,
  `longiizqpelvi` float DEFAULT NULL,
  `anteizqpelvi` float DEFAULT NULL,
  `transverizqpelvi` float DEFAULT NULL,
  `voluizqpelvi` float DEFAULT NULL,
  `descriizqpelvi` varchar(250) DEFAULT NULL,
  `sacopelvi` varchar(250) DEFAULT NULL,
  `diagnospelvi` longtext,
  `medicoreferentepelvi` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Pacientes Ultrasonografia Primer`
--

CREATE TABLE `Pacientes Ultrasonografia Primer` (
  `id` int NOT NULL,
  `NroPaci` int DEFAULT NULL,
  `NroEmba` smallint DEFAULT NULL,
  `fecha` datetime DEFAULT NULL,
  `medicoreferenteprimer` varchar(50) DEFAULT NULL,
  `furprimer` datetime DEFAULT NULL,
  `amesemaprimer` smallint DEFAULT NULL,
  `amediasprimer` smallint DEFAULT NULL,
  `embaprimer` smallint DEFAULT NULL,
  `sacoprimer` varchar(250) DEFAULT NULL,
  `diameprimer` float DEFAULT NULL,
  `vesiprimer` float DEFAULT NULL,
  `actiprimer` float DEFAULT NULL,
  `fetaprimer` smallint DEFAULT NULL,
  `coriprimer` smallint DEFAULT NULL,
  `liquiprimer` smallint DEFAULT NULL,
  `longiprimer` float DEFAULT NULL,
  `oriprimer` smallint DEFAULT NULL,
  `cuerpoprimer` smallint DEFAULT NULL,
  `obserprimer` varchar(250) DEFAULT NULL,
  `usgsemaprimer` smallint DEFAULT NULL,
  `usgdiasprimer` smallint DEFAULT NULL,
  `partoprimer` datetime DEFAULT NULL,
  `diagnosprimer` longtext
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Pacientes Ultrasonografia Tercer`
--

CREATE TABLE `Pacientes Ultrasonografia Tercer` (
  `id` int NOT NULL,
  `NroPaci` int DEFAULT NULL,
  `NroEmba` smallint DEFAULT NULL,
  `fecha` datetime DEFAULT NULL,
  `medicoreferentetercer` varchar(50) DEFAULT NULL,
  `furtercer` datetime DEFAULT NULL,
  `amesematercer` smallint DEFAULT NULL,
  `amediastercer` smallint DEFAULT NULL,
  `embatercer` smallint DEFAULT NULL,
  `presentercer` smallint DEFAULT NULL,
  `situtercer` smallint DEFAULT NULL,
  `dorsotercer` smallint DEFAULT NULL,
  `frecutercer` float DEFAULT NULL,
  `caratercer` smallint DEFAULT NULL,
  `ventritercer` smallint DEFAULT NULL,
  `lineatercer` smallint DEFAULT NULL,
  `columtercer` smallint DEFAULT NULL,
  `estotercer` smallint DEFAULT NULL,
  `rinotercer` smallint DEFAULT NULL,
  `vejitercer` smallint DEFAULT NULL,
  `madutercer` smallint DEFAULT NULL,
  `locatercer` smallint DEFAULT NULL,
  `inditercer` float DEFAULT NULL,
  `bipacmtercer` float DEFAULT NULL,
  `bipasematercer` smallint DEFAULT NULL,
  `bipadiastercer` smallint DEFAULT NULL,
  `cefacmtercer` float DEFAULT NULL,
  `cefasematercer` smallint DEFAULT NULL,
  `cefadiastercer` smallint DEFAULT NULL,
  `abdocmtercer` float DEFAULT NULL,
  `abdosematercer` smallint DEFAULT NULL,
  `abdodiastercer` smallint DEFAULT NULL,
  `femurcmtercer` float DEFAULT NULL,
  `femursematercer` smallint DEFAULT NULL,
  `femurdiastercer` smallint DEFAULT NULL,
  `pesotercer` float DEFAULT NULL,
  `percentercer` smallint DEFAULT NULL,
  `sexotercer` smallint DEFAULT NULL,
  `tonotercer` smallint DEFAULT NULL,
  `movitercer` smallint DEFAULT NULL,
  `liquitercer` smallint DEFAULT NULL,
  `respitercer` smallint DEFAULT NULL,
  `totaltercer` smallint DEFAULT NULL,
  `biomesematercer` smallint DEFAULT NULL,
  `biomediastercer` smallint DEFAULT NULL,
  `fpptercer` datetime DEFAULT NULL,
  `diagnostercer` longtext
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Pacientes Vacunas`
--

CREATE TABLE `Pacientes Vacunas` (
  `id` int NOT NULL,
  `NroPaci` int DEFAULT NULL,
  `idvacuna` int DEFAULT NULL,
  `dosis` smallint DEFAULT NULL,
  `fecha` datetime DEFAULT NULL,
  `observaciones` varchar(250) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pacientes_hc_adjuntos`
--

CREATE TABLE `pacientes_hc_adjuntos` (
  `id` int NOT NULL,
  `id_clinica` int NOT NULL DEFAULT '1',
  `id_nota_hc` int NOT NULL,
  `id_usuario` int DEFAULT NULL,
  `tipo` varchar(20) NOT NULL COMMENT 'archivo|link',
  `nombre` varchar(255) NOT NULL,
  `url` varchar(1024) DEFAULT NULL,
  `ruta_archivo` varchar(1024) DEFAULT NULL,
  `mime` varchar(100) DEFAULT NULL,
  `tamano_bytes` int DEFAULT NULL,
  `creado_en` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pacientes_hc_notas`
--

CREATE TABLE `pacientes_hc_notas` (
  `id` int NOT NULL,
  `id_clinica` int NOT NULL DEFAULT '1',
  `id_paciente` int NOT NULL,
  `id_usuario` int DEFAULT NULL,
  `fecha_hora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `texto` mediumtext NOT NULL,
  `creado_en` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pacientes_odontograma`
--

CREATE TABLE `pacientes_odontograma` (
  `id` int NOT NULL,
  `id_clinica` int NOT NULL DEFAULT '1',
  `NroHC` int NOT NULL COMMENT 'Historia clínica (pacientes.NroHC)',
  `pieza_fdi` smallint NOT NULL COMMENT 'Notación FDI (ISO 3950): 11-48 permanentes, 51-85 temporales',
  `cara` varchar(20) DEFAULT NULL COMMENT 'Caras: M,O,D,V,L,I combinadas p. ej. MOD',
  `id_codigo` int DEFAULT NULL COMMENT 'lista_odontograma_codigos',
  `notas` text,
  `iddoctor` int DEFAULT NULL COMMENT 'lista_doctores',
  `idusuario_web` int DEFAULT NULL COMMENT 'usuarios.id registro web',
  `id_orden` int DEFAULT NULL COMMENT 'id Pacientes Ordenes',
  `creado_en` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `anulado` tinyint(1) NOT NULL DEFAULT '0',
  `anulado_motivo` varchar(255) DEFAULT NULL,
  `anulado_en` datetime DEFAULT NULL,
  `anulado_por_usuario` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pacientes_odontograma_superficies`
--

CREATE TABLE `pacientes_odontograma_superficies` (
  `id` int NOT NULL,
  `id_clinica` int NOT NULL DEFAULT '1',
  `NroHC` int NOT NULL,
  `pieza_fdi` smallint NOT NULL,
  `cara` char(1) NOT NULL COMMENT 'M O D V L P (P=marca pieza completa)',
  `id_codigo` int NOT NULL,
  `actualizado_en` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `idusuario_web` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pacientes_ordenes`
--

CREATE TABLE `pacientes_ordenes` (
  `id` int NOT NULL,
  `NroPaci` int NOT NULL COMMENT 'NroHC paciente',
  `iddoctor` int NOT NULL COMMENT 'id lista_doctores',
  `fecha_orden` date DEFAULT NULL,
  `autorizada` tinyint(1) DEFAULT '0',
  `entregada` tinyint(1) DEFAULT '0',
  `observaciones` text,
  `creado_en` datetime DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pacientes_pagos`
--

CREATE TABLE `pacientes_pagos` (
  `id` int NOT NULL,
  `id_clinica` int NOT NULL DEFAULT '1',
  `quien` char(1) NOT NULL DEFAULT 'P' COMMENT 'P=paciente, otro si aplica',
  `NroPaci` int DEFAULT NULL COMMENT 'NroHC paciente',
  `idorden` int DEFAULT NULL COMMENT 'id pacientes_ordenes si aplica',
  `importe` decimal(12,2) NOT NULL DEFAULT '0.00',
  `fecha` date NOT NULL,
  `forma_pago` varchar(50) DEFAULT NULL COMMENT 'efectivo, tarjeta_debito, tarjeta_credito, etc',
  `observaciones` text,
  `creado_en` datetime DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pacientes_sesiones`
--

CREATE TABLE `pacientes_sesiones` (
  `id` int NOT NULL,
  `id_clinica` int NOT NULL DEFAULT '1',
  `idorden` int NOT NULL COMMENT 'id pacientes_ordenes',
  `NroPaci` int NOT NULL COMMENT 'NroHC paciente',
  `iddoctor` int NOT NULL COMMENT 'id lista_doctores',
  `fecha_sesion` date DEFAULT NULL,
  `cantidad_sesiones` int DEFAULT '1',
  `observaciones` text,
  `creado_en` datetime DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Plantillas Consulta`
--

CREATE TABLE `Plantillas Consulta` (
  `revi-estadogeneral` longtext,
  `revi-cardiopulmonar` longtext,
  `revi-digestivo` longtext,
  `revi-endocrino` longtext,
  `revi-urinario-mujeres` longtext,
  `revi-genitourinario-hombres` longtext,
  `revi-saludsexual-mujeres` longtext,
  `revi-saludsexual-hombres` longtext,
  `revi-neurologico` longtext,
  `revi-ong` longtext,
  `revi-saludmental` longtext,
  `exa-fisicogeneral` longtext,
  `exa-cardiovascular` longtext,
  `exa-respiratorio` longtext,
  `exa-abdominal` longtext,
  `exa-dermatologico` longtext,
  `exa-neurologico` longtext,
  `exa-mental` longtext,
  `exa-oftalmologico` longtext,
  `exa-ong` longtext,
  `exa-genitourinario-hombres` longtext,
  `id` int NOT NULL,
  `plantillahc` longtext,
  `plantillaobser` longtext
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Puestos`
--

CREATE TABLE `Puestos` (
  `id` int NOT NULL,
  `clavepuesto` int DEFAULT NULL,
  `fechapuesto` varchar(14) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Recursos`
--

CREATE TABLE `Recursos` (
  `id` int NOT NULL,
  `idcategoria` int DEFAULT NULL,
  `idsubcategoria` int DEFAULT NULL,
  `Detalle` varchar(50) DEFAULT NULL,
  `Archivo` varchar(100) DEFAULT NULL,
  `recurso` varchar(100) DEFAULT NULL,
  `memo` longtext
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Recursos Categoria`
--

CREATE TABLE `Recursos Categoria` (
  `id` int NOT NULL,
  `nombre` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Recursos SubCategoria`
--

CREATE TABLE `Recursos SubCategoria` (
  `id` int NOT NULL,
  `idcategoria` int DEFAULT NULL,
  `nombre` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Sucursales`
--

CREATE TABLE `Sucursales` (
  `id` smallint DEFAULT NULL,
  `nombre` varchar(30) DEFAULT NULL,
  `codigoarea` varchar(7) DEFAULT NULL,
  `mensajerecordatorio` longtext,
  `servidoremail` varchar(100) DEFAULT NULL,
  `puertoemail` varchar(5) DEFAULT NULL,
  `sslemail` smallint DEFAULT NULL,
  `autenticaremail` smallint DEFAULT NULL,
  `usuarioemail` varchar(100) DEFAULT NULL,
  `passwordemail` varchar(100) DEFAULT NULL,
  `asuntoemail` varchar(100) DEFAULT NULL,
  `mensajeemail` longtext,
  `tituloweb` varchar(250) DEFAULT NULL,
  `encabezadoimagen` tinyint(1) DEFAULT NULL,
  `encabezadogeneral` longtext,
  `encabezadopaciente` longtext,
  `encabezadoticket` longtext,
  `asuntoemailpresu` varchar(100) DEFAULT NULL,
  `mensajepresu` longtext,
  `asuntoemail2` varchar(100) DEFAULT NULL,
  `mensajeemail2` longtext,
  `mensajerecordatorio2` longtext,
  `mensajeanular` longtext,
  `asuntoemailinforme` varchar(100) DEFAULT NULL,
  `mensajeinforme` longtext,
  `asuntoemailrecibo` varchar(100) DEFAULT NULL,
  `mensajerecibo` longtext,
  `direccionsucursal` varchar(250) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT '1',
  `id_clinica` int NOT NULL DEFAULT '1' COMMENT 'Clínica activa del usuario',
  `id_doctor` int DEFAULT NULL,
  `rol` varchar(30) NOT NULL DEFAULT 'admin_clinica',
  `creado_en` datetime DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_agenda_turnos_detalle`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_agenda_turnos_detalle` (
`actualizado_en` datetime
,`creado_en` datetime
,`Doctor` int
,`doctor_nombre` varchar(150)
,`estado` varchar(50)
,`Fecha` date
,`hora` time
,`id` int
,`idorden` int
,`motivo` int
,`motivo_nombre` varchar(255)
,`NroHC` int
,`observaciones` text
,`paciente_mostrar` varchar(200)
,`paciente_nombres_tabla` varchar(200)
);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `Agenda Telefonica`
--
ALTER TABLE `Agenda Telefonica`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Agenda Turnos`
--
ALTER TABLE `Agenda Turnos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Agenda Turnos Horarios`
--
ALTER TABLE `Agenda Turnos Horarios`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Agenda Turnos No Se Atiende`
--
ALTER TABLE `Agenda Turnos No Se Atiende`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `agenda_bloqueos`
--
ALTER TABLE `agenda_bloqueos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_agenda_bloq_clin_doc` (`id_clinica`,`doctor`),
  ADD KEY `idx_agenda_bloq_fechas` (`id_clinica`,`doctor`,`fecha_desde`,`fecha_hasta`);

--
-- Indices de la tabla `agenda_turnos`
--
ALTER TABLE `agenda_turnos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_agenda_fecha` (`Fecha`),
  ADD KEY `idx_agenda_doctor` (`Doctor`),
  ADD KEY `idx_agenda_nrohc` (`NroHC`),
  ADD KEY `idx_agenda_idorden` (`idorden`),
  ADD KEY `idx_agenda_motivo` (`motivo`),
  ADD KEY `idx_agenda_clinica` (`id_clinica`),
  ADD KEY `idx_agenda_clinica_fecha` (`id_clinica`,`Fecha`);

--
-- Indices de la tabla `Antecedentes Familiares`
--
ALTER TABLE `Antecedentes Familiares`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Antecedentes Generales`
--
ALTER TABLE `Antecedentes Generales`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Antecedentes Gineco - Obstetricos`
--
ALTER TABLE `Antecedentes Gineco - Obstetricos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Antecedentes Perinatologicos`
--
ALTER TABLE `Antecedentes Perinatologicos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Antecedentes Perso - Fami - Medicamentos`
--
ALTER TABLE `Antecedentes Perso - Fami - Medicamentos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Antecedentes Perso - Fami - Patologias`
--
ALTER TABLE `Antecedentes Perso - Fami - Patologias`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Anunciador`
--
ALTER TABLE `Anunciador`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `backup_legacy_Caja_20260409_131540`
--
ALTER TABLE `backup_legacy_Caja_20260409_131540`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `backup_legacy_Camas_20260409_131540`
--
ALTER TABLE `backup_legacy_Camas_20260409_131540`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `backup_legacy_Consultas_20260409_131540`
--
ALTER TABLE `backup_legacy_Consultas_20260409_131540`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `backup_legacy_Pacientes_20260409_131540`
--
ALTER TABLE `backup_legacy_Pacientes_20260409_131540`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `caja`
--
ALTER TABLE `caja`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_caja_doctor` (`doctor`),
  ADD KEY `idx_caja_fecha` (`fechacaja`),
  ADD KEY `idx_caja_clinica` (`id_clinica`),
  ADD KEY `idx_caja_clinica_fecha` (`id_clinica`,`fechacaja`);

--
-- Indices de la tabla `camas`
--
ALTER TABLE `camas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_camas_clinica` (`id_clinica`);

--
-- Indices de la tabla `CamasGastos`
--
ALTER TABLE `CamasGastos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `CamasInsumos`
--
ALTER TABLE `CamasInsumos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `CamasPacientes`
--
ALTER TABLE `CamasPacientes`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `camas_pacientes`
--
ALTER TABLE `camas_pacientes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_camas_pacientes_cama` (`idcama`),
  ADD KEY `idx_camas_pacientes_nropaci` (`nropaci`),
  ADD KEY `idx_camas_pac_clinica` (`id_clinica`);

--
-- Indices de la tabla `Certificados`
--
ALTER TABLE `Certificados`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Certificados Historial`
--
ALTER TABLE `Certificados Historial`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `CIE10`
--
ALTER TABLE `CIE10`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `clinicas`
--
ALTER TABLE `clinicas`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `config`
--
ALTER TABLE `config`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_config_clinica_clave` (`id_clinica`,`clave`),
  ADD KEY `idx_config_clinica` (`id_clinica`);

--
-- Indices de la tabla `Consulta - Cardiopulmonar`
--
ALTER TABLE `Consulta - Cardiopulmonar`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Consulta - Digestivo`
--
ALTER TABLE `Consulta - Digestivo`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Consulta - Endocrino`
--
ALTER TABLE `Consulta - Endocrino`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Consulta - Estado General`
--
ALTER TABLE `Consulta - Estado General`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Consulta - Ginecologico`
--
ALTER TABLE `Consulta - Ginecologico`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Consulta - Muscu Equilibrio Muscular Extre Inf`
--
ALTER TABLE `Consulta - Muscu Equilibrio Muscular Extre Inf`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Consulta - Muscu Equilibrio Muscular Extre Sup`
--
ALTER TABLE `Consulta - Muscu Equilibrio Muscular Extre Sup`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Consulta - Muscu Ficha Kinesica`
--
ALTER TABLE `Consulta - Muscu Ficha Kinesica`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Consulta - Musculoesqueletico`
--
ALTER TABLE `Consulta - Musculoesqueletico`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Consulta - Muscu Medicion Articular`
--
ALTER TABLE `Consulta - Muscu Medicion Articular`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Consulta - Neurologico`
--
ALTER TABLE `Consulta - Neurologico`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Consulta - ORL`
--
ALTER TABLE `Consulta - ORL`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Consulta - Salud Mental`
--
ALTER TABLE `Consulta - Salud Mental`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Consulta - Salud Sexual`
--
ALTER TABLE `Consulta - Salud Sexual`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Consulta - Urinario`
--
ALTER TABLE `Consulta - Urinario`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Consulta Exa - Abdominal`
--
ALTER TABLE `Consulta Exa - Abdominal`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Consulta Exa - Cardio Vascular`
--
ALTER TABLE `Consulta Exa - Cardio Vascular`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Consulta Exa - Dermatologico`
--
ALTER TABLE `Consulta Exa - Dermatologico`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Consulta Exa - Fisico General`
--
ALTER TABLE `Consulta Exa - Fisico General`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Consulta Exa - Genitourinario`
--
ALTER TABLE `Consulta Exa - Genitourinario`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Consulta Exa - Ginecologico`
--
ALTER TABLE `Consulta Exa - Ginecologico`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Consulta Exa - Mamario`
--
ALTER TABLE `Consulta Exa - Mamario`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Consulta Exa - Mental`
--
ALTER TABLE `Consulta Exa - Mental`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Consulta Exa - Neurologico`
--
ALTER TABLE `Consulta Exa - Neurologico`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Consulta Exa - Oftalmologico`
--
ALTER TABLE `Consulta Exa - Oftalmologico`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Consulta Exa - ORL`
--
ALTER TABLE `Consulta Exa - ORL`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Consulta Exa - Respiratorio`
--
ALTER TABLE `Consulta Exa - Respiratorio`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `consultas`
--
ALTER TABLE `consultas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_consultas_iddoctor` (`iddoctor`),
  ADD KEY `idx_consultas_nrohc` (`NroHC`),
  ADD KEY `idx_consultas_clinica` (`id_clinica`);

--
-- Indices de la tabla `Consultas Items`
--
ALTER TABLE `Consultas Items`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `consultas_items`
--
ALTER TABLE `consultas_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_consultas_items_consulta` (`id_consulta`);

--
-- Indices de la tabla `ExceptoConsultas`
--
ALTER TABLE `ExceptoConsultas`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Historial`
--
ALTER TABLE `Historial`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Lista Alcohol`
--
ALTER TABLE `Lista Alcohol`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Lista Analisis`
--
ALTER TABLE `Lista Analisis`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Lista Ciudad`
--
ALTER TABLE `Lista Ciudad`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Lista Coberturas`
--
ALTER TABLE `Lista Coberturas`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Lista Derivadores`
--
ALTER TABLE `Lista Derivadores`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Lista Diagnosticos`
--
ALTER TABLE `Lista Diagnosticos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Lista Doctores`
--
ALTER TABLE `Lista Doctores`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Lista Drogas`
--
ALTER TABLE `Lista Drogas`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Lista Enfermedades Ginecologicas`
--
ALTER TABLE `Lista Enfermedades Ginecologicas`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Lista Estado civil`
--
ALTER TABLE `Lista Estado civil`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Lista Estatus en el pais`
--
ALTER TABLE `Lista Estatus en el pais`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Lista Estudios`
--
ALTER TABLE `Lista Estudios`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Lista Etnia`
--
ALTER TABLE `Lista Etnia`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Lista Motivos Consulta`
--
ALTER TABLE `Lista Motivos Consulta`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Lista Nomenclador`
--
ALTER TABLE `Lista Nomenclador`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Lista Ocupacion`
--
ALTER TABLE `Lista Ocupacion`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Lista Operaciones Ginecologicas`
--
ALTER TABLE `Lista Operaciones Ginecologicas`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Lista Pais`
--
ALTER TABLE `Lista Pais`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Lista Planes`
--
ALTER TABLE `Lista Planes`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Lista Precios`
--
ALTER TABLE `Lista Precios`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Lista Productos`
--
ALTER TABLE `Lista Productos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Lista Provincia`
--
ALTER TABLE `Lista Provincia`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Lista Relacion con el paciente`
--
ALTER TABLE `Lista Relacion con el paciente`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Lista Resultados PAP`
--
ALTER TABLE `Lista Resultados PAP`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Lista Stock`
--
ALTER TABLE `Lista Stock`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Lista Tabaco`
--
ALTER TABLE `Lista Tabaco`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Lista Tipo de documento`
--
ALTER TABLE `Lista Tipo de documento`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Lista Tratamientos Complementarios`
--
ALTER TABLE `Lista Tratamientos Complementarios`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Lista Usos`
--
ALTER TABLE `Lista Usos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Lista Vacunas`
--
ALTER TABLE `Lista Vacunas`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Lista Vademecum`
--
ALTER TABLE `Lista Vademecum`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `lista_ciudad`
--
ALTER TABLE `lista_ciudad`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `lista_coberturas`
--
ALTER TABLE `lista_coberturas`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `lista_derivaciones`
--
ALTER TABLE `lista_derivaciones`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `lista_doctores`
--
ALTER TABLE `lista_doctores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_lista_doctores_clinica` (`id_clinica`);

--
-- Indices de la tabla `lista_especialidades_doctores`
--
ALTER TABLE `lista_especialidades_doctores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_lista_especialidades_doctores_nombre` (`nombre`);

--
-- Indices de la tabla `lista_estado_civil`
--
ALTER TABLE `lista_estado_civil`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `lista_estatus_pais`
--
ALTER TABLE `lista_estatus_pais`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `lista_etnia`
--
ALTER TABLE `lista_etnia`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `lista_factor_sanguineo`
--
ALTER TABLE `lista_factor_sanguineo`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `lista_grupo_sanguineo`
--
ALTER TABLE `lista_grupo_sanguineo`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `lista_identidad_genero`
--
ALTER TABLE `lista_identidad_genero`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `lista_motivos_consulta`
--
ALTER TABLE `lista_motivos_consulta`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `lista_ocupacion`
--
ALTER TABLE `lista_ocupacion`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `lista_odontograma_codigos`
--
ALTER TABLE `lista_odontograma_codigos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `lista_orientacion_sex`
--
ALTER TABLE `lista_orientacion_sex`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `lista_pais`
--
ALTER TABLE `lista_pais`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `lista_planes`
--
ALTER TABLE `lista_planes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_planes_cobertura` (`id_cobertura`);

--
-- Indices de la tabla `lista_practicas`
--
ALTER TABLE `lista_practicas`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `lista_primera_vez`
--
ALTER TABLE `lista_primera_vez`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `lista_provincia`
--
ALTER TABLE `lista_provincia`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `lista_relacion_paciente`
--
ALTER TABLE `lista_relacion_paciente`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `lista_sexo`
--
ALTER TABLE `lista_sexo`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `lista_sucursales`
--
ALTER TABLE `lista_sucursales`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `lista_tipo_documento`
--
ALTER TABLE `lista_tipo_documento`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `pacientes`
--
ALTER TABLE `pacientes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_pacientes_clinica_nrohc` (`id_clinica`,`NroHC`),
  ADD KEY `idx_pacientes_clinica` (`id_clinica`);

--
-- Indices de la tabla `Pacientes Alimentacion`
--
ALTER TABLE `Pacientes Alimentacion`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Pacientes Analisis`
--
ALTER TABLE `Pacientes Analisis`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Pacientes Biomicroscopia`
--
ALTER TABLE `Pacientes Biomicroscopia`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Pacientes Cirugias`
--
ALTER TABLE `Pacientes Cirugias`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Pacientes Controles Antropometricos`
--
ALTER TABLE `Pacientes Controles Antropometricos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Pacientes Control Feto`
--
ALTER TABLE `Pacientes Control Feto`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Pacientes Control Materno`
--
ALTER TABLE `Pacientes Control Materno`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Pacientes Enfermedades Ginecologicas`
--
ALTER TABLE `Pacientes Enfermedades Ginecologicas`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Pacientes Esquema Ginecologico`
--
ALTER TABLE `Pacientes Esquema Ginecologico`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Pacientes Estudios`
--
ALTER TABLE `Pacientes Estudios`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Pacientes Estudios Imagenes`
--
ALTER TABLE `Pacientes Estudios Imagenes`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Pacientes Fondo de Ojo`
--
ALTER TABLE `Pacientes Fondo de Ojo`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Pacientes Lesiones Vulva`
--
ALTER TABLE `Pacientes Lesiones Vulva`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Pacientes Nervio Optico`
--
ALTER TABLE `Pacientes Nervio Optico`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Pacientes Odontogramas`
--
ALTER TABLE `Pacientes Odontogramas`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Pacientes Operaciones Ginecologicas`
--
ALTER TABLE `Pacientes Operaciones Ginecologicas`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Pacientes Ordenes`
--
ALTER TABLE `Pacientes Ordenes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pac_ordenes_clinica` (`id_clinica`),
  ADD KEY `idx_pac_ordenes_clinica_nropaci` (`id_clinica`,`NroPaci`);

--
-- Indices de la tabla `Pacientes Pagos`
--
ALTER TABLE `Pacientes Pagos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Pacientes Pautas`
--
ALTER TABLE `Pacientes Pautas`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Pacientes Presupuestos`
--
ALTER TABLE `Pacientes Presupuestos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Pacientes Presupuestos General`
--
ALTER TABLE `Pacientes Presupuestos General`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Pacientes Sesiones`
--
ALTER TABLE `Pacientes Sesiones`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Pacientes Ultrasonografia Pelvica`
--
ALTER TABLE `Pacientes Ultrasonografia Pelvica`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Pacientes Ultrasonografia Primer`
--
ALTER TABLE `Pacientes Ultrasonografia Primer`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Pacientes Ultrasonografia Tercer`
--
ALTER TABLE `Pacientes Ultrasonografia Tercer`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Pacientes Vacunas`
--
ALTER TABLE `Pacientes Vacunas`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `pacientes_hc_adjuntos`
--
ALTER TABLE `pacientes_hc_adjuntos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_hc_adj_clin_nota` (`id_clinica`,`id_nota_hc`),
  ADD KEY `idx_hc_adj_tipo` (`tipo`);

--
-- Indices de la tabla `pacientes_hc_notas`
--
ALTER TABLE `pacientes_hc_notas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_hc_notas_clin_pac_fecha` (`id_clinica`,`id_paciente`,`fecha_hora`),
  ADD KEY `idx_hc_notas_usuario` (`id_usuario`);

--
-- Indices de la tabla `pacientes_odontograma`
--
ALTER TABLE `pacientes_odontograma`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_odontograma_nrohc` (`NroHC`),
  ADD KEY `idx_odontograma_pieza` (`pieza_fdi`),
  ADD KEY `idx_odontograma_creado` (`creado_en`),
  ADD KEY `idx_odontograma_codigo` (`id_codigo`),
  ADD KEY `idx_odontograma_doctor` (`iddoctor`),
  ADD KEY `idx_odontograma_orden` (`id_orden`),
  ADD KEY `idx_odontograma_clinica_nrohc` (`id_clinica`,`NroHC`);

--
-- Indices de la tabla `pacientes_odontograma_superficies`
--
ALTER TABLE `pacientes_odontograma_superficies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_odontograma_sup_clin_nro_pieza_cara` (`id_clinica`,`NroHC`,`pieza_fdi`,`cara`),
  ADD KEY `idx_odontograma_sup_nrohc` (`NroHC`),
  ADD KEY `fk_odontograma_sup_codigo` (`id_codigo`),
  ADD KEY `idx_odontograma_sup_clinica` (`id_clinica`,`NroHC`);

--
-- Indices de la tabla `pacientes_ordenes`
--
ALTER TABLE `pacientes_ordenes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ordenes_nropaci` (`NroPaci`),
  ADD KEY `idx_ordenes_iddoctor` (`iddoctor`);

--
-- Indices de la tabla `pacientes_pagos`
--
ALTER TABLE `pacientes_pagos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pagos_nropaci` (`NroPaci`),
  ADD KEY `idx_pagos_idorden` (`idorden`),
  ADD KEY `idx_pagos_fecha` (`fecha`),
  ADD KEY `idx_pagos_clinica` (`id_clinica`);

--
-- Indices de la tabla `pacientes_sesiones`
--
ALTER TABLE `pacientes_sesiones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sesiones_idorden` (`idorden`),
  ADD KEY `idx_sesiones_nropaci` (`NroPaci`),
  ADD KEY `idx_sesiones_iddoctor` (`iddoctor`),
  ADD KEY `idx_sesiones_clinica` (`id_clinica`);

--
-- Indices de la tabla `Plantillas Consulta`
--
ALTER TABLE `Plantillas Consulta`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Puestos`
--
ALTER TABLE `Puestos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Recursos`
--
ALTER TABLE `Recursos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Recursos Categoria`
--
ALTER TABLE `Recursos Categoria`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `Recursos SubCategoria`
--
ALTER TABLE `Recursos SubCategoria`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario` (`usuario`),
  ADD KEY `idx_usuarios_clinica` (`id_clinica`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `Agenda Telefonica`
--
ALTER TABLE `Agenda Telefonica`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Agenda Turnos`
--
ALTER TABLE `Agenda Turnos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Agenda Turnos Horarios`
--
ALTER TABLE `Agenda Turnos Horarios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Agenda Turnos No Se Atiende`
--
ALTER TABLE `Agenda Turnos No Se Atiende`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `agenda_bloqueos`
--
ALTER TABLE `agenda_bloqueos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `agenda_turnos`
--
ALTER TABLE `agenda_turnos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Antecedentes Familiares`
--
ALTER TABLE `Antecedentes Familiares`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Antecedentes Generales`
--
ALTER TABLE `Antecedentes Generales`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Antecedentes Gineco - Obstetricos`
--
ALTER TABLE `Antecedentes Gineco - Obstetricos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Antecedentes Perinatologicos`
--
ALTER TABLE `Antecedentes Perinatologicos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Antecedentes Perso - Fami - Medicamentos`
--
ALTER TABLE `Antecedentes Perso - Fami - Medicamentos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Antecedentes Perso - Fami - Patologias`
--
ALTER TABLE `Antecedentes Perso - Fami - Patologias`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Anunciador`
--
ALTER TABLE `Anunciador`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `backup_legacy_Caja_20260409_131540`
--
ALTER TABLE `backup_legacy_Caja_20260409_131540`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `backup_legacy_Camas_20260409_131540`
--
ALTER TABLE `backup_legacy_Camas_20260409_131540`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `backup_legacy_Consultas_20260409_131540`
--
ALTER TABLE `backup_legacy_Consultas_20260409_131540`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `backup_legacy_Pacientes_20260409_131540`
--
ALTER TABLE `backup_legacy_Pacientes_20260409_131540`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `caja`
--
ALTER TABLE `caja`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `camas`
--
ALTER TABLE `camas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `CamasGastos`
--
ALTER TABLE `CamasGastos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `CamasInsumos`
--
ALTER TABLE `CamasInsumos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `CamasPacientes`
--
ALTER TABLE `CamasPacientes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `camas_pacientes`
--
ALTER TABLE `camas_pacientes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Certificados`
--
ALTER TABLE `Certificados`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Certificados Historial`
--
ALTER TABLE `Certificados Historial`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `CIE10`
--
ALTER TABLE `CIE10`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `clinicas`
--
ALTER TABLE `clinicas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `config`
--
ALTER TABLE `config`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Consulta - Cardiopulmonar`
--
ALTER TABLE `Consulta - Cardiopulmonar`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Consulta - Digestivo`
--
ALTER TABLE `Consulta - Digestivo`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Consulta - Endocrino`
--
ALTER TABLE `Consulta - Endocrino`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Consulta - Estado General`
--
ALTER TABLE `Consulta - Estado General`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Consulta - Ginecologico`
--
ALTER TABLE `Consulta - Ginecologico`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Consulta - Muscu Equilibrio Muscular Extre Inf`
--
ALTER TABLE `Consulta - Muscu Equilibrio Muscular Extre Inf`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Consulta - Muscu Equilibrio Muscular Extre Sup`
--
ALTER TABLE `Consulta - Muscu Equilibrio Muscular Extre Sup`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Consulta - Muscu Ficha Kinesica`
--
ALTER TABLE `Consulta - Muscu Ficha Kinesica`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Consulta - Musculoesqueletico`
--
ALTER TABLE `Consulta - Musculoesqueletico`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Consulta - Muscu Medicion Articular`
--
ALTER TABLE `Consulta - Muscu Medicion Articular`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Consulta - Neurologico`
--
ALTER TABLE `Consulta - Neurologico`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Consulta - ORL`
--
ALTER TABLE `Consulta - ORL`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Consulta - Salud Mental`
--
ALTER TABLE `Consulta - Salud Mental`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Consulta - Salud Sexual`
--
ALTER TABLE `Consulta - Salud Sexual`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Consulta - Urinario`
--
ALTER TABLE `Consulta - Urinario`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Consulta Exa - Abdominal`
--
ALTER TABLE `Consulta Exa - Abdominal`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Consulta Exa - Cardio Vascular`
--
ALTER TABLE `Consulta Exa - Cardio Vascular`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Consulta Exa - Dermatologico`
--
ALTER TABLE `Consulta Exa - Dermatologico`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Consulta Exa - Fisico General`
--
ALTER TABLE `Consulta Exa - Fisico General`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Consulta Exa - Genitourinario`
--
ALTER TABLE `Consulta Exa - Genitourinario`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Consulta Exa - Ginecologico`
--
ALTER TABLE `Consulta Exa - Ginecologico`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Consulta Exa - Mamario`
--
ALTER TABLE `Consulta Exa - Mamario`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Consulta Exa - Mental`
--
ALTER TABLE `Consulta Exa - Mental`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Consulta Exa - Neurologico`
--
ALTER TABLE `Consulta Exa - Neurologico`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Consulta Exa - Oftalmologico`
--
ALTER TABLE `Consulta Exa - Oftalmologico`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Consulta Exa - ORL`
--
ALTER TABLE `Consulta Exa - ORL`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Consulta Exa - Respiratorio`
--
ALTER TABLE `Consulta Exa - Respiratorio`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `consultas`
--
ALTER TABLE `consultas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Consultas Items`
--
ALTER TABLE `Consultas Items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `consultas_items`
--
ALTER TABLE `consultas_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ExceptoConsultas`
--
ALTER TABLE `ExceptoConsultas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Historial`
--
ALTER TABLE `Historial`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Lista Alcohol`
--
ALTER TABLE `Lista Alcohol`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Lista Analisis`
--
ALTER TABLE `Lista Analisis`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Lista Ciudad`
--
ALTER TABLE `Lista Ciudad`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Lista Coberturas`
--
ALTER TABLE `Lista Coberturas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Lista Derivadores`
--
ALTER TABLE `Lista Derivadores`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Lista Diagnosticos`
--
ALTER TABLE `Lista Diagnosticos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Lista Doctores`
--
ALTER TABLE `Lista Doctores`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Lista Drogas`
--
ALTER TABLE `Lista Drogas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Lista Enfermedades Ginecologicas`
--
ALTER TABLE `Lista Enfermedades Ginecologicas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Lista Estado civil`
--
ALTER TABLE `Lista Estado civil`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Lista Estatus en el pais`
--
ALTER TABLE `Lista Estatus en el pais`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Lista Estudios`
--
ALTER TABLE `Lista Estudios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Lista Etnia`
--
ALTER TABLE `Lista Etnia`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Lista Motivos Consulta`
--
ALTER TABLE `Lista Motivos Consulta`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Lista Nomenclador`
--
ALTER TABLE `Lista Nomenclador`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Lista Ocupacion`
--
ALTER TABLE `Lista Ocupacion`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Lista Operaciones Ginecologicas`
--
ALTER TABLE `Lista Operaciones Ginecologicas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Lista Pais`
--
ALTER TABLE `Lista Pais`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Lista Planes`
--
ALTER TABLE `Lista Planes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Lista Precios`
--
ALTER TABLE `Lista Precios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Lista Productos`
--
ALTER TABLE `Lista Productos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Lista Provincia`
--
ALTER TABLE `Lista Provincia`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Lista Relacion con el paciente`
--
ALTER TABLE `Lista Relacion con el paciente`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Lista Resultados PAP`
--
ALTER TABLE `Lista Resultados PAP`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Lista Stock`
--
ALTER TABLE `Lista Stock`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Lista Tabaco`
--
ALTER TABLE `Lista Tabaco`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Lista Tipo de documento`
--
ALTER TABLE `Lista Tipo de documento`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Lista Tratamientos Complementarios`
--
ALTER TABLE `Lista Tratamientos Complementarios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Lista Usos`
--
ALTER TABLE `Lista Usos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Lista Vacunas`
--
ALTER TABLE `Lista Vacunas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Lista Vademecum`
--
ALTER TABLE `Lista Vademecum`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `lista_doctores`
--
ALTER TABLE `lista_doctores`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `lista_especialidades_doctores`
--
ALTER TABLE `lista_especialidades_doctores`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pacientes`
--
ALTER TABLE `pacientes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Pacientes Alimentacion`
--
ALTER TABLE `Pacientes Alimentacion`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Pacientes Analisis`
--
ALTER TABLE `Pacientes Analisis`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Pacientes Biomicroscopia`
--
ALTER TABLE `Pacientes Biomicroscopia`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Pacientes Cirugias`
--
ALTER TABLE `Pacientes Cirugias`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Pacientes Controles Antropometricos`
--
ALTER TABLE `Pacientes Controles Antropometricos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Pacientes Control Feto`
--
ALTER TABLE `Pacientes Control Feto`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Pacientes Control Materno`
--
ALTER TABLE `Pacientes Control Materno`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Pacientes Enfermedades Ginecologicas`
--
ALTER TABLE `Pacientes Enfermedades Ginecologicas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Pacientes Esquema Ginecologico`
--
ALTER TABLE `Pacientes Esquema Ginecologico`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Pacientes Estudios`
--
ALTER TABLE `Pacientes Estudios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Pacientes Estudios Imagenes`
--
ALTER TABLE `Pacientes Estudios Imagenes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Pacientes Fondo de Ojo`
--
ALTER TABLE `Pacientes Fondo de Ojo`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Pacientes Lesiones Vulva`
--
ALTER TABLE `Pacientes Lesiones Vulva`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Pacientes Nervio Optico`
--
ALTER TABLE `Pacientes Nervio Optico`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Pacientes Odontogramas`
--
ALTER TABLE `Pacientes Odontogramas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Pacientes Operaciones Ginecologicas`
--
ALTER TABLE `Pacientes Operaciones Ginecologicas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Pacientes Ordenes`
--
ALTER TABLE `Pacientes Ordenes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Pacientes Pagos`
--
ALTER TABLE `Pacientes Pagos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Pacientes Pautas`
--
ALTER TABLE `Pacientes Pautas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Pacientes Presupuestos`
--
ALTER TABLE `Pacientes Presupuestos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Pacientes Presupuestos General`
--
ALTER TABLE `Pacientes Presupuestos General`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Pacientes Sesiones`
--
ALTER TABLE `Pacientes Sesiones`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Pacientes Ultrasonografia Pelvica`
--
ALTER TABLE `Pacientes Ultrasonografia Pelvica`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Pacientes Ultrasonografia Primer`
--
ALTER TABLE `Pacientes Ultrasonografia Primer`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Pacientes Ultrasonografia Tercer`
--
ALTER TABLE `Pacientes Ultrasonografia Tercer`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Pacientes Vacunas`
--
ALTER TABLE `Pacientes Vacunas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pacientes_hc_adjuntos`
--
ALTER TABLE `pacientes_hc_adjuntos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pacientes_hc_notas`
--
ALTER TABLE `pacientes_hc_notas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pacientes_odontograma`
--
ALTER TABLE `pacientes_odontograma`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pacientes_odontograma_superficies`
--
ALTER TABLE `pacientes_odontograma_superficies`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pacientes_ordenes`
--
ALTER TABLE `pacientes_ordenes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pacientes_pagos`
--
ALTER TABLE `pacientes_pagos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pacientes_sesiones`
--
ALTER TABLE `pacientes_sesiones`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Plantillas Consulta`
--
ALTER TABLE `Plantillas Consulta`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Puestos`
--
ALTER TABLE `Puestos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Recursos`
--
ALTER TABLE `Recursos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Recursos Categoria`
--
ALTER TABLE `Recursos Categoria`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Recursos SubCategoria`
--
ALTER TABLE `Recursos SubCategoria`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_agenda_turnos_detalle`
--
DROP TABLE IF EXISTS `v_agenda_turnos_detalle`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`%` SQL SECURITY DEFINER VIEW `v_agenda_turnos_detalle`  AS SELECT `t`.`id` AS `id`, `t`.`Fecha` AS `Fecha`, `t`.`hora` AS `hora`, `t`.`NroHC` AS `NroHC`, `t`.`Doctor` AS `Doctor`, `t`.`idorden` AS `idorden`, `t`.`estado` AS `estado`, `t`.`observaciones` AS `observaciones`, `t`.`motivo` AS `motivo`, coalesce(nullif(trim(`t`.`paciente_nombre`),''),`p`.`Nombres`) AS `paciente_mostrar`, `p`.`Nombres` AS `paciente_nombres_tabla`, `d`.`nombre` AS `doctor_nombre`, `m`.`nombre` AS `motivo_nombre`, `t`.`creado_en` AS `creado_en`, `t`.`actualizado_en` AS `actualizado_en` FROM (((`agenda_turnos` `t` left join `pacientes` `p` on((`p`.`NroHC` = `t`.`NroHC`))) left join `lista_doctores` `d` on((`d`.`id` = `t`.`Doctor`))) left join `lista_motivos_consulta` `m` on((`m`.`id` = `t`.`motivo`))) ;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `camas_pacientes`
--
ALTER TABLE `camas_pacientes`
  ADD CONSTRAINT `fk_camas_pacientes_cama` FOREIGN KEY (`idcama`) REFERENCES `camas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `consultas_items`
--
ALTER TABLE `consultas_items`
  ADD CONSTRAINT `fk_consultas_items_consulta` FOREIGN KEY (`id_consulta`) REFERENCES `consultas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `pacientes_odontograma`
--
ALTER TABLE `pacientes_odontograma`
  ADD CONSTRAINT `fk_odontograma_codigo` FOREIGN KEY (`id_codigo`) REFERENCES `lista_odontograma_codigos` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `pacientes_odontograma_superficies`
--
ALTER TABLE `pacientes_odontograma_superficies`
  ADD CONSTRAINT `fk_odontograma_sup_codigo` FOREIGN KEY (`id_codigo`) REFERENCES `lista_odontograma_codigos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
