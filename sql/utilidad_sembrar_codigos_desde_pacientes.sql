-- Inserta en listas web las filas que faltan para cada código ya presente en pacientes.
-- Útil después de importar desde SQL Server / backup: los nombres quedan como "(código N — revisar etiqueta)".
-- Ejecutar cuando existan tablas pacientes, lista_identidad_genero y lista_orientacion_sex (migration_008).
-- Podés editar luego los nombres en MySQL o desde futura pantalla de administración.

SET NAMES utf8mb4;

INSERT INTO lista_identidad_genero (id, prioridad, nombre)
SELECT d.codigo, 500, CONCAT('(código ', d.codigo, ' — revisar etiqueta)')
FROM (
  SELECT DISTINCT CAST(identidad_gen AS SIGNED) AS codigo
  FROM pacientes
  WHERE identidad_gen IS NOT NULL
) AS d
LEFT JOIN lista_identidad_genero AS l ON l.id = d.codigo
WHERE l.id IS NULL AND d.codigo IS NOT NULL;

INSERT INTO lista_orientacion_sex (id, prioridad, nombre)
SELECT d.codigo, 500, CONCAT('(código ', d.codigo, ' — revisar etiqueta)')
FROM (
  SELECT DISTINCT CAST(orientacion_sex AS SIGNED) AS codigo
  FROM pacientes
  WHERE orientacion_sex IS NOT NULL
) AS d
LEFT JOIN lista_orientacion_sex AS l ON l.id = d.codigo
WHERE l.id IS NULL AND d.codigo IS NOT NULL;

INSERT INTO lista_sexo (id, prioridad, nombre)
SELECT d.codigo, 500, CONCAT('(código ', d.codigo, ' — revisar etiqueta)')
FROM (
  SELECT DISTINCT CAST(sexo AS SIGNED) AS codigo
  FROM pacientes
  WHERE sexo IS NOT NULL
) AS d
LEFT JOIN lista_sexo AS l ON l.id = d.codigo
WHERE l.id IS NULL AND d.codigo IS NOT NULL;
