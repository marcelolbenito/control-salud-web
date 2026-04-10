-- Catálogos web para identidad de género y orientación sexual (Access: identidadgen, orientacionsex SMALLINT sin Lista *).
-- Misma forma que lista_sexo: id = código guardado en pacientes.
-- Tras importar datos reales, ejecutar sql/utilidad_sembrar_codigos_desde_pacientes.sql para crear filas faltantes.
-- Ejecutar después de schema_mysql.sql, migration_002 y schema_listas_minimo.sql (o migration_007).

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS lista_identidad_genero (
  id INT PRIMARY KEY,
  prioridad SMALLINT NULL,
  nombre VARCHAR(150) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS lista_orientacion_sex (
  id INT PRIMARY KEY,
  prioridad SMALLINT NULL,
  nombre VARCHAR(150) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Valores iniciales (ajustar IDs/etiquetas según backup o exe; ON DUPLICATE preserva ediciones manuales de nombre).
INSERT INTO lista_identidad_genero (id, prioridad, nombre) VALUES
  (1, 10, 'Varón'),
  (2, 20, 'Mujer'),
  (3, 30, 'No binario'),
  (4, 40, 'Otro / (definir)')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), prioridad = VALUES(prioridad);

INSERT INTO lista_orientacion_sex (id, prioridad, nombre) VALUES
  (1, 10, 'Heterosexual'),
  (2, 20, 'Homosexual'),
  (3, 30, 'Bisexual'),
  (4, 40, 'Otro'),
  (5, 50, 'Prefiero no decir / No informa')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), prioridad = VALUES(prioridad);
