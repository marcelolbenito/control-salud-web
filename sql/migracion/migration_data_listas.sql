-- Generado por generar_migration_sql.py — datos catálogo desde Access
-- Ejecutar DESPUÉS de schema_listas_minimo.sql
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DELETE FROM `lista_pais`;
INSERT INTO `lista_pais` (`id`, `prioridad`, `nombre`) VALUES (1, 0, 'Argentina');

DELETE FROM `lista_provincia`;
INSERT INTO `lista_provincia` (`id`, `prioridad`, `nombre`) VALUES (2, 0, 'Mendoza');

DELETE FROM `lista_ciudad`;
INSERT INTO `lista_ciudad` (`id`, `prioridad`, `nombre`) VALUES (1, 0, 'San Rafael');

DELETE FROM `lista_coberturas`;
INSERT INTO `lista_coberturas` (`id`, `prioridad`, `nombre`, `porcentaje_cobertura`, `plancober`) VALUES (1, 0, 'PARTICULAR', 0.0, NULL);
INSERT INTO `lista_coberturas` (`id`, `prioridad`, `nombre`, `porcentaje_cobertura`, `plancober`) VALUES (2, 0, 'OSEP', 0.0, NULL);
INSERT INTO `lista_coberturas` (`id`, `prioridad`, `nombre`, `porcentaje_cobertura`, `plancober`) VALUES (3, 0, 'OSECAC', 0.0, NULL);

DELETE FROM `lista_planes`;

DELETE FROM `lista_tipo_documento`;
INSERT INTO `lista_tipo_documento` (`id`, `prioridad`, `nombre`) VALUES (1, 0, 'DNI');
INSERT INTO `lista_tipo_documento` (`id`, `prioridad`, `nombre`) VALUES (2, 0, 'Pasaporte');
INSERT INTO `lista_tipo_documento` (`id`, `prioridad`, `nombre`) VALUES (3, 0, 'Cédula');

DELETE FROM `lista_ocupacion`;
INSERT INTO `lista_ocupacion` (`id`, `prioridad`, `nombre`) VALUES (1, 0, 'Estudiante');
INSERT INTO `lista_ocupacion` (`id`, `prioridad`, `nombre`) VALUES (4, 0, 'Desempleado/a');
INSERT INTO `lista_ocupacion` (`id`, `prioridad`, `nombre`) VALUES (5, 0, 'Ama/o de casa');
INSERT INTO `lista_ocupacion` (`id`, `prioridad`, `nombre`) VALUES (6, 0, 'Obrero/a');
INSERT INTO `lista_ocupacion` (`id`, `prioridad`, `nombre`) VALUES (7, 0, 'Técnico/a');
INSERT INTO `lista_ocupacion` (`id`, `prioridad`, `nombre`) VALUES (8, 0, 'Comerciante');
INSERT INTO `lista_ocupacion` (`id`, `prioridad`, `nombre`) VALUES (9, 0, 'Profesional');
INSERT INTO `lista_ocupacion` (`id`, `prioridad`, `nombre`) VALUES (10, 0, 'Empresario/a');
INSERT INTO `lista_ocupacion` (`id`, `prioridad`, `nombre`) VALUES (11, 0, 'Jubilado/a');
INSERT INTO `lista_ocupacion` (`id`, `prioridad`, `nombre`) VALUES (12, 0, 'Niño/a preescolar');

DELETE FROM `lista_estado_civil`;
INSERT INTO `lista_estado_civil` (`id`, `prioridad`, `nombre`) VALUES (1, 0, 'Casado/a');
INSERT INTO `lista_estado_civil` (`id`, `prioridad`, `nombre`) VALUES (2, 0, 'Soltero/a');
INSERT INTO `lista_estado_civil` (`id`, `prioridad`, `nombre`) VALUES (3, 0, 'Divorciado/a');
INSERT INTO `lista_estado_civil` (`id`, `prioridad`, `nombre`) VALUES (4, 0, 'Viudo/a');
INSERT INTO `lista_estado_civil` (`id`, `prioridad`, `nombre`) VALUES (5, 0, 'En unión estable');
INSERT INTO `lista_estado_civil` (`id`, `prioridad`, `nombre`) VALUES (6, 0, 'Con novio/a');

DELETE FROM `lista_etnia`;
INSERT INTO `lista_etnia` (`id`, `prioridad`, `nombre`) VALUES (1, 0, 'Blanca');
INSERT INTO `lista_etnia` (`id`, `prioridad`, `nombre`) VALUES (2, 0, 'Indígena');
INSERT INTO `lista_etnia` (`id`, `prioridad`, `nombre`) VALUES (3, 0, 'Mestiza');
INSERT INTO `lista_etnia` (`id`, `prioridad`, `nombre`) VALUES (4, 0, 'Negra');

DELETE FROM `lista_relacion_paciente`;
INSERT INTO `lista_relacion_paciente` (`id`, `prioridad`, `nombre`) VALUES (1, 0, 'Esposo/a');
INSERT INTO `lista_relacion_paciente` (`id`, `prioridad`, `nombre`) VALUES (2, 0, 'Pareja');
INSERT INTO `lista_relacion_paciente` (`id`, `prioridad`, `nombre`) VALUES (3, 0, 'Madre/padre');
INSERT INTO `lista_relacion_paciente` (`id`, `prioridad`, `nombre`) VALUES (4, 0, 'Hermano/a');
INSERT INTO `lista_relacion_paciente` (`id`, `prioridad`, `nombre`) VALUES (5, 0, 'Hijo/a');
INSERT INTO `lista_relacion_paciente` (`id`, `prioridad`, `nombre`) VALUES (6, 0, 'Otro familiar');
INSERT INTO `lista_relacion_paciente` (`id`, `prioridad`, `nombre`) VALUES (7, 0, 'Amigo/a');
INSERT INTO `lista_relacion_paciente` (`id`, `prioridad`, `nombre`) VALUES (8, 0, 'Vecino/a');

DELETE FROM `lista_estatus_pais`;
INSERT INTO `lista_estatus_pais` (`id`, `prioridad`, `nombre`) VALUES (1, 0, 'Turista');
INSERT INTO `lista_estatus_pais` (`id`, `prioridad`, `nombre`) VALUES (2, 0, 'Inversionista');
INSERT INTO `lista_estatus_pais` (`id`, `prioridad`, `nombre`) VALUES (3, 0, 'Cooperante');
INSERT INTO `lista_estatus_pais` (`id`, `prioridad`, `nombre`) VALUES (4, 0, 'Voluntario/a');
INSERT INTO `lista_estatus_pais` (`id`, `prioridad`, `nombre`) VALUES (5, 0, 'Diplomático/a');
INSERT INTO `lista_estatus_pais` (`id`, `prioridad`, `nombre`) VALUES (6, 0, 'Expatriado/a');
INSERT INTO `lista_estatus_pais` (`id`, `prioridad`, `nombre`) VALUES (7, 0, 'Residente jubilado/a');

DELETE FROM `lista_sexo`;
INSERT INTO `lista_sexo` (`id`, `prioridad`, `nombre`) VALUES (1, 0, 'Masculino');
INSERT INTO `lista_sexo` (`id`, `prioridad`, `nombre`) VALUES (2, 0, 'Mujer');
INSERT INTO `lista_sexo` (`id`, `prioridad`, `nombre`) VALUES (3, 0, 'X / No binario');
INSERT INTO `lista_sexo` (`id`, `prioridad`, `nombre`) VALUES (4, 0, 'Otro / No informado');

DELETE FROM `lista_grupo_sanguineo`;
INSERT INTO `lista_grupo_sanguineo` (`id`, `prioridad`, `nombre`) VALUES (1, 0, '0');
INSERT INTO `lista_grupo_sanguineo` (`id`, `prioridad`, `nombre`) VALUES (2, 0, 'A');
INSERT INTO `lista_grupo_sanguineo` (`id`, `prioridad`, `nombre`) VALUES (3, 0, 'B');
INSERT INTO `lista_grupo_sanguineo` (`id`, `prioridad`, `nombre`) VALUES (4, 0, 'AB');

DELETE FROM `lista_factor_sanguineo`;
INSERT INTO `lista_factor_sanguineo` (`id`, `prioridad`, `nombre`) VALUES (1, 0, 'Rh positivo (+)');
INSERT INTO `lista_factor_sanguineo` (`id`, `prioridad`, `nombre`) VALUES (2, 0, 'Rh negativo (-)');

SET FOREIGN_KEY_CHECKS = 1;