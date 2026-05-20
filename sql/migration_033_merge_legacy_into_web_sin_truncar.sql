-- Fusiona datos desde tablas legacy importadas del exe/SQL Server (mismos nombres que el backup)
-- hacia las tablas que usa la web, SIN TRUNCATE.
--
-- Caso de uso: ya tenés datos en MySQL (local o web) y querés incorporar / actualizar lo que viene
-- del último backup sin borrar filas que solo existen en MySQL (turnos/pacientes/doctores nuevos en web).
--
-- Reglas:
-- - Instalación de una sola clínica: siempre `id_clinica = 1` (insert y actualización al fusionar).
-- - Por cada fila del backup con el mismo PRIMARY KEY (`id`), se actualizan los campos del exe (gana el backup).
-- - Filas que solo están en MySQL no se tocan.
--
-- Multi-clínica: si más adelante usás otro `id_clinica`, no ejecutes este script tal cual;
-- adaptá el literal `1` o filtrá por clínica en el SELECT legacy.
--
-- Requisitos previos:
-- 1. Las tablas legacy deben existir en MySQL: `Pacientes`, `Lista Doctores`, `Agenda Turnos`
--    (generadas al importar el .sql producido por sqlserver_backup_to_mysql_sql.py desde SQL Server restaurado).
-- 2. Esquema web ya aplicado (schema_mysql + migraciones 002/003 según tu entorno).
--
-- NO ejecutes migration_005 después de esto sobre las mismas tablas si querés conservar merges sin pisar:
-- migration_005 hace TRUNCATE y reemplaza todo el contenido de estas tres tablas.
--
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =========================
-- Doctores (merge por id)
-- =========================
INSERT INTO `lista_doctores` (
  `id`, `id_clinica`, `nombre`, `medicoconvenio`, `bloquearmisconsultas`,
  `sucursal1`, `sucursal2`, `sucursal3`, `sucursal4`, `sucursal5`,
  `sucursal6`, `sucursal7`, `sucursal8`, `sucursal9`, `sucursal10`,
  `activo`, `notas`,
  `especialidad`, `matricula`, `telefono`, `domicilio`, `localidad`, `consultorio`
)
SELECT
  `id`,
  1 AS `id_clinica`,
  TRIM(COALESCE(`NomDoc`, '')) AS `nombre`,
  COALESCE(`medicoconvenio`, 0) AS `medicoconvenio`,
  COALESCE(`bloquearmisconsultas`, 0) AS `bloquearmisconsultas`,
  COALESCE(`sucursal1`, 0), COALESCE(`sucursal2`, 0), COALESCE(`sucursal3`, 0),
  COALESCE(`sucursal4`, 0), COALESCE(`sucursal5`, 0), COALESCE(`sucursal6`, 0),
  COALESCE(`sucursal7`, 0), COALESCE(`sucursal8`, 0), COALESCE(`sucursal9`, 0),
  COALESCE(`sucursal10`, 0),
  1 AS `activo`,
  NULLIF(TRIM(COALESCE(`Descripcion`, '')), '') AS `notas`,
  NULLIF(TRIM(COALESCE(`Especialidad`, '')), '') AS `especialidad`,
  NULLIF(TRIM(COALESCE(`Matricula`, '')), '') AS `matricula`,
  NULLIF(TRIM(COALESCE(`Tel`, '')), '') AS `telefono`,
  NULLIF(TRIM(COALESCE(`Domi`, '')), '') AS `domicilio`,
  NULLIF(TRIM(COALESCE(`Locali`, '')), '') AS `localidad`,
  NULLIF(TRIM(COALESCE(`consultorio`, '')), '') AS `consultorio`
FROM `Lista Doctores`
WHERE NULLIF(TRIM(COALESCE(`NomDoc`, '')), '') IS NOT NULL
ON DUPLICATE KEY UPDATE
  `id_clinica` = VALUES(`id_clinica`),
  `nombre` = VALUES(`nombre`),
  `medicoconvenio` = VALUES(`medicoconvenio`),
  `bloquearmisconsultas` = VALUES(`bloquearmisconsultas`),
  `sucursal1` = VALUES(`sucursal1`),
  `sucursal2` = VALUES(`sucursal2`),
  `sucursal3` = VALUES(`sucursal3`),
  `sucursal4` = VALUES(`sucursal4`),
  `sucursal5` = VALUES(`sucursal5`),
  `sucursal6` = VALUES(`sucursal6`),
  `sucursal7` = VALUES(`sucursal7`),
  `sucursal8` = VALUES(`sucursal8`),
  `sucursal9` = VALUES(`sucursal9`),
  `sucursal10` = VALUES(`sucursal10`),
  `activo` = VALUES(`activo`),
  `notas` = VALUES(`notas`),
  `especialidad` = VALUES(`especialidad`),
  `matricula` = VALUES(`matricula`),
  `telefono` = VALUES(`telefono`),
  `domicilio` = VALUES(`domicilio`),
  `localidad` = VALUES(`localidad`),
  `consultorio` = VALUES(`consultorio`);

-- =========================
-- Pacientes (merge por id)
-- =========================
INSERT INTO `pacientes` (
  `id`, `id_clinica`, `NroHC`, `Nombres`, `DNI`, `convenio`, `fecha_nacimiento`,
  `telefono`, `email`, `direccion`, `activo`, `notas`,
  `numehistoria`, `embarazo`, `ulti_emba`, `ultima_cons`,
  `paciente_inactivo`, `motivo_inactividad`, `cobertura`,
  `id_cobertura`, `nro_os`, `apellido`, `apellido2`, `fe_nac`, `sexo`,
  `dni_sin_uso`, `id_tipo_doc`, `id_ocupacion`, `detalle_ocupacion`,
  `tel_celular`, `tel_laboral`, `nombre_padre`, `naci_padre`,
  `id_ocupacion_padre`, `horas_hogar_padre`, `nombre_madre`, `naci_madre`,
  `id_ocupacion_madre`, `horas_hogar_madre`, `nro_hermanos`, `edad_hermanos`,
  `nro_hermanas`, `edad_hermanas`, `detalles_familia`, `ape1_contacto`,
  `ape2_contacto`, `nombre_contacto`, `id_relacion`, `tel_par_contacto`,
  `tel_cel_contacto`, `tel_lab_contacto`, `id_estado_civil`, `id_etnia`,
  `id_ciudad`, `cp`, `id_provincia`, `id_pais`, `id_estatus`, `alergias`,
  `grupo_sanguineo`, `factor_sanguineo`, `hc_texto`, `referente`,
  `id_cobertura2`, `nu_afiliado2`, `antecedentes_hc`, `id_plan`, `paga_iva`,
  `alta_paci_web`, `identidad_gen`, `orientacion_sex`
)
SELECT
  `id`,
  1 AS `id_clinica`,
  `NroHC`,
  TRIM(COALESCE(`Nombres`, '')) AS `Nombres`,
  NULLIF(TRIM(COALESCE(`DNI`, '')), '') AS `DNI`,
  COALESCE(`convenio`, 0) AS `convenio`,
  DATE(`FeNac`) AS `fecha_nacimiento`,
  NULLIF(TRIM(COALESCE(`Tel`, '')), '') AS `telefono`,
  NULLIF(TRIM(COALESCE(`Email`, '')), '') AS `email`,
  NULLIF(TRIM(COALESCE(`Domicilio`, '')), '') AS `direccion`,
  CASE WHEN COALESCE(`pacienteinactivo`, 0) = 1 THEN 0 ELSE 1 END AS `activo`,
  `Notas`,
  NULLIF(TRIM(COALESCE(`numehistoria`, '')), '') AS `numehistoria`,
  COALESCE(`Embarazo`, 0) AS `embarazo`,
  `UltiEmba`,
  `UltimaCons`,
  COALESCE(`pacienteinactivo`, 0) AS `paciente_inactivo`,
  NULLIF(TRIM(COALESCE(`motivoinactividad`, '')), '') AS `motivo_inactividad`,
  `cobertura`,
  `idcobertura`,
  NULLIF(TRIM(COALESCE(`NroOS`, '')), '') AS `nro_os`,
  NULLIF(TRIM(COALESCE(`Apellido`, '')), '') AS `apellido`,
  NULLIF(TRIM(COALESCE(`apellido2`, '')), '') AS `apellido2`,
  `FeNac`,
  `sexo`,
  NULLIF(TRIM(COALESCE(`DNISinUso`, '')), '') AS `dni_sin_uso`,
  `idtipodoc`,
  `idocupacion`,
  NULLIF(TRIM(COALESCE(`detallesocupacion`, '')), '') AS `detalle_ocupacion`,
  NULLIF(TRIM(COALESCE(`TelCelular`, '')), '') AS `tel_celular`,
  NULLIF(TRIM(COALESCE(`TelLaboral`, '')), '') AS `tel_laboral`,
  NULLIF(TRIM(COALESCE(`nombrepadre`, '')), '') AS `nombre_padre`,
  `nacipadre`,
  `idocupacionpadre`,
  NULLIF(TRIM(COALESCE(`horashogarpadre`, '')), '') AS `horas_hogar_padre`,
  NULLIF(TRIM(COALESCE(`nombremadre`, '')), '') AS `nombre_madre`,
  `nacimadre`,
  `idocupacionmadre`,
  NULLIF(TRIM(COALESCE(`horashogarmadre`, '')), '') AS `horas_hogar_madre`,
  NULLIF(TRIM(COALESCE(`nrohermanos`, '')), '') AS `nro_hermanos`,
  NULLIF(TRIM(COALESCE(`edadhermanos`, '')), '') AS `edad_hermanos`,
  NULLIF(TRIM(COALESCE(`nrohermanas`, '')), '') AS `nro_hermanas`,
  NULLIF(TRIM(COALESCE(`edadhermanas`, '')), '') AS `edad_hermanas`,
  NULLIF(TRIM(COALESCE(`detallesfamilia`, '')), '') AS `detalles_familia`,
  NULLIF(TRIM(COALESCE(`ape1contacto`, '')), '') AS `ape1_contacto`,
  NULLIF(TRIM(COALESCE(`ape2contacto`, '')), '') AS `ape2_contacto`,
  NULLIF(TRIM(COALESCE(`nombrecontacto`, '')), '') AS `nombre_contacto`,
  `idrelacion`,
  NULLIF(TRIM(COALESCE(`telparcontacto`, '')), '') AS `tel_par_contacto`,
  NULLIF(TRIM(COALESCE(`telcelcontacto`, '')), '') AS `tel_cel_contacto`,
  NULLIF(TRIM(COALESCE(`tellabcontacto`, '')), '') AS `tel_lab_contacto`,
  `idestadocivil`,
  `idetnia`,
  `idciudad`,
  NULLIF(TRIM(COALESCE(`CP`, '')), '') AS `cp`,
  `idprovincia`,
  `idpais`,
  `idestatus`,
  `alergias`,
  `GrupoSanguineo`,
  `FactorSanguineo`,
  `HC`,
  NULLIF(TRIM(COALESCE(`referente`, '')), '') AS `referente`,
  `idcobertura2`,
  NULLIF(TRIM(COALESCE(`nuafiliado2`, '')), '') AS `nu_afiliado2`,
  `antecedenteshc`,
  `idplan`,
  `pagaiva`,
  `altapaciweb`,
  `identidadgen`,
  `orientacionsex`
FROM `Pacientes`
WHERE `NroHC` IS NOT NULL
ON DUPLICATE KEY UPDATE
  `id_clinica` = VALUES(`id_clinica`),
  `NroHC` = VALUES(`NroHC`),
  `Nombres` = VALUES(`Nombres`),
  `DNI` = VALUES(`DNI`),
  `convenio` = VALUES(`convenio`),
  `fecha_nacimiento` = VALUES(`fecha_nacimiento`),
  `telefono` = VALUES(`telefono`),
  `email` = VALUES(`email`),
  `direccion` = VALUES(`direccion`),
  `activo` = VALUES(`activo`),
  `notas` = VALUES(`notas`),
  `numehistoria` = VALUES(`numehistoria`),
  `embarazo` = VALUES(`embarazo`),
  `ulti_emba` = VALUES(`ulti_emba`),
  `ultima_cons` = VALUES(`ultima_cons`),
  `paciente_inactivo` = VALUES(`paciente_inactivo`),
  `motivo_inactividad` = VALUES(`motivo_inactividad`),
  `cobertura` = VALUES(`cobertura`),
  `id_cobertura` = VALUES(`id_cobertura`),
  `nro_os` = VALUES(`nro_os`),
  `apellido` = VALUES(`apellido`),
  `apellido2` = VALUES(`apellido2`),
  `fe_nac` = VALUES(`fe_nac`),
  `sexo` = VALUES(`sexo`),
  `dni_sin_uso` = VALUES(`dni_sin_uso`),
  `id_tipo_doc` = VALUES(`id_tipo_doc`),
  `id_ocupacion` = VALUES(`id_ocupacion`),
  `detalle_ocupacion` = VALUES(`detalle_ocupacion`),
  `tel_celular` = VALUES(`tel_celular`),
  `tel_laboral` = VALUES(`tel_laboral`),
  `nombre_padre` = VALUES(`nombre_padre`),
  `naci_padre` = VALUES(`naci_padre`),
  `id_ocupacion_padre` = VALUES(`id_ocupacion_padre`),
  `horas_hogar_padre` = VALUES(`horas_hogar_padre`),
  `nombre_madre` = VALUES(`nombre_madre`),
  `naci_madre` = VALUES(`naci_madre`),
  `id_ocupacion_madre` = VALUES(`id_ocupacion_madre`),
  `horas_hogar_madre` = VALUES(`horas_hogar_madre`),
  `nro_hermanos` = VALUES(`nro_hermanos`),
  `edad_hermanos` = VALUES(`edad_hermanos`),
  `nro_hermanas` = VALUES(`nro_hermanas`),
  `edad_hermanas` = VALUES(`edad_hermanas`),
  `detalles_familia` = VALUES(`detalles_familia`),
  `ape1_contacto` = VALUES(`ape1_contacto`),
  `ape2_contacto` = VALUES(`ape2_contacto`),
  `nombre_contacto` = VALUES(`nombre_contacto`),
  `id_relacion` = VALUES(`id_relacion`),
  `tel_par_contacto` = VALUES(`tel_par_contacto`),
  `tel_cel_contacto` = VALUES(`tel_cel_contacto`),
  `tel_lab_contacto` = VALUES(`tel_lab_contacto`),
  `id_estado_civil` = VALUES(`id_estado_civil`),
  `id_etnia` = VALUES(`id_etnia`),
  `id_ciudad` = VALUES(`id_ciudad`),
  `cp` = VALUES(`cp`),
  `id_provincia` = VALUES(`id_provincia`),
  `id_pais` = VALUES(`id_pais`),
  `id_estatus` = VALUES(`id_estatus`),
  `alergias` = VALUES(`alergias`),
  `grupo_sanguineo` = VALUES(`grupo_sanguineo`),
  `factor_sanguineo` = VALUES(`factor_sanguineo`),
  `hc_texto` = VALUES(`hc_texto`),
  `referente` = VALUES(`referente`),
  `id_cobertura2` = VALUES(`id_cobertura2`),
  `nu_afiliado2` = VALUES(`nu_afiliado2`),
  `antecedentes_hc` = VALUES(`antecedentes_hc`),
  `id_plan` = VALUES(`id_plan`),
  `paga_iva` = VALUES(`paga_iva`),
  `alta_paci_web` = VALUES(`alta_paci_web`),
  `identidad_gen` = VALUES(`identidad_gen`),
  `orientacion_sex` = VALUES(`orientacion_sex`);

-- =========================
-- Turnos (merge por id)
-- =========================
INSERT INTO `agenda_turnos` (
  `id`, `id_clinica`, `Fecha`, `hora`, `NroHC`, `Doctor`, `idorden`, `estado`, `observaciones`,
  `paciente_nombre`, `motivo`, `atendido`, `pagado`, `llegado`, `llegado_hora`,
  `confirmado`, `falta_turno`, `reingresar`, `primera_vez`, `num_sesion`,
  `id_sesion`, `id_caja`, `usuario_asignado`, `alta_paci_web`, `fechahora_asignado`
)
SELECT
  `id`,
  1 AS `id_clinica`,
  DATE(`Fecha`) AS `Fecha`,
  TIME(`Fecha`) AS `hora`,
  `NroHC`,
  `Doctor`,
  `idorden`,
  CASE
    WHEN COALESCE(`faltoturno`, 0) = 1 THEN 'no_asistio'
    WHEN COALESCE(`Atendido`, 0) = 1 THEN 'atendido'
    ELSE 'pendiente'
  END AS `estado`,
  `observacionestur` AS `observaciones`,
  NULLIF(TRIM(COALESCE(`Paciente`, '')), '') AS `paciente_nombre`,
  `motivo`,
  COALESCE(`Atendido`, 0) AS `atendido`,
  COALESCE(`pagado`, 0) AS `pagado`,
  COALESCE(`llegado`, 0) AS `llegado`,
  NULLIF(TRIM(COALESCE(`llegadohora`, '')), '') AS `llegado_hora`,
  COALESCE(`confirmado`, 0) AS `confirmado`,
  COALESCE(`faltoturno`, 0) AS `falta_turno`,
  COALESCE(`reingresar`, 0) AS `reingresar`,
  `primeravez` AS `primera_vez`,
  `numesesion` AS `num_sesion`,
  `idsesion` AS `id_sesion`,
  `idcaja` AS `id_caja`,
  NULLIF(TRIM(COALESCE(`usuarioasigtur`, '')), '') AS `usuario_asignado`,
  `altapaciweb` AS `alta_paci_web`,
  `fechahoraasignado` AS `fechahora_asignado`
FROM `Agenda Turnos`
WHERE `Fecha` IS NOT NULL
ON DUPLICATE KEY UPDATE
  `id_clinica` = VALUES(`id_clinica`),
  `Fecha` = VALUES(`Fecha`),
  `hora` = VALUES(`hora`),
  `NroHC` = VALUES(`NroHC`),
  `Doctor` = VALUES(`Doctor`),
  `idorden` = VALUES(`idorden`),
  `estado` = VALUES(`estado`),
  `observaciones` = VALUES(`observaciones`),
  `paciente_nombre` = VALUES(`paciente_nombre`),
  `motivo` = VALUES(`motivo`),
  `atendido` = VALUES(`atendido`),
  `pagado` = VALUES(`pagado`),
  `llegado` = VALUES(`llegado`),
  `llegado_hora` = VALUES(`llegado_hora`),
  `confirmado` = VALUES(`confirmado`),
  `falta_turno` = VALUES(`falta_turno`),
  `reingresar` = VALUES(`reingresar`),
  `primera_vez` = VALUES(`primera_vez`),
  `num_sesion` = VALUES(`num_sesion`),
  `id_sesion` = VALUES(`id_sesion`),
  `id_caja` = VALUES(`id_caja`),
  `usuario_asignado` = VALUES(`usuario_asignado`),
  `alta_paci_web` = VALUES(`alta_paci_web`),
  `fechahora_asignado` = VALUES(`fechahora_asignado`);

SET FOREIGN_KEY_CHECKS = 1;
