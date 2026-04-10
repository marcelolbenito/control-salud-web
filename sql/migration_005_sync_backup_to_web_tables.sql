-- Sincroniza datos desde tablas importadas del backup SQL Server
-- (nombres originales con espacios/mayúsculas) hacia las tablas
-- que usa la web (snake_case/minúsculas).
--
-- Requiere que ya exista el dump full importado en MySQL:
--   `Pacientes`, `Lista Doctores`, `Agenda Turnos`
--
-- Ejecutar en la base `control_salud`.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =========================
-- Doctores
-- =========================
TRUNCATE TABLE `lista_doctores`;

INSERT INTO `lista_doctores` (
  `id`, `nombre`, `medicoconvenio`, `bloquearmisconsultas`,
  `sucursal1`, `sucursal2`, `sucursal3`, `sucursal4`, `sucursal5`,
  `sucursal6`, `sucursal7`, `sucursal8`, `sucursal9`, `sucursal10`,
  `activo`, `notas`,
  `especialidad`, `matricula`, `telefono`, `domicilio`, `localidad`, `consultorio`
)
SELECT
  `id`,
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
WHERE NULLIF(TRIM(COALESCE(`NomDoc`, '')), '') IS NOT NULL;

-- =========================
-- Pacientes
-- =========================
TRUNCATE TABLE `pacientes`;

INSERT INTO `pacientes` (
  `id`, `NroHC`, `Nombres`, `DNI`, `convenio`, `fecha_nacimiento`,
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
WHERE `NroHC` IS NOT NULL;

-- =========================
-- Agenda
-- =========================
TRUNCATE TABLE `agenda_turnos`;

INSERT INTO `agenda_turnos` (
  `id`, `Fecha`, `hora`, `NroHC`, `Doctor`, `idorden`, `estado`, `observaciones`,
  `paciente_nombre`, `motivo`, `atendido`, `pagado`, `llegado`, `llegado_hora`,
  `confirmado`, `falta_turno`, `reingresar`, `primera_vez`, `num_sesion`,
  `id_sesion`, `id_caja`, `usuario_asignado`, `alta_paci_web`, `fechahora_asignado`
)
SELECT
  `id`,
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
WHERE `Fecha` IS NOT NULL;

SET FOREIGN_KEY_CHECKS = 1;
