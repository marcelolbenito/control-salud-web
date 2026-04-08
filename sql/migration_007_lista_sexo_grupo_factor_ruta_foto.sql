-- Listas descriptivas para ficha paciente (web) + ruta de foto.
-- Ejecutar después de schema_mysql.sql, migration_002 y schema_listas_minimo.sql.
-- Si los IDs no coinciden con Access, ajustá los INSERT o los valores ya importados en pacientes.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS lista_sexo (
  id INT PRIMARY KEY,
  prioridad SMALLINT NULL,
  nombre VARCHAR(100) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS lista_grupo_sanguineo (
  id INT PRIMARY KEY,
  prioridad SMALLINT NULL,
  nombre VARCHAR(20) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS lista_factor_sanguineo (
  id INT PRIMARY KEY,
  prioridad SMALLINT NULL,
  nombre VARCHAR(30) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO lista_sexo (id, prioridad, nombre) VALUES
  (1, 0, 'Masculino'),
  (2, 0, 'Mujer'),
  (3, 0, 'X / No binario'),
  (4, 0, 'Otro / No informado')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), prioridad = VALUES(prioridad);

INSERT INTO lista_grupo_sanguineo (id, prioridad, nombre) VALUES
  (1, 0, '0'),
  (2, 0, 'A'),
  (3, 0, 'B'),
  (4, 0, 'AB')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), prioridad = VALUES(prioridad);

INSERT INTO lista_factor_sanguineo (id, prioridad, nombre) VALUES
  (1, 0, 'Rh positivo (+)'),
  (2, 0, 'Rh negativo (-)')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), prioridad = VALUES(prioridad);

-- Foto del paciente: ruta bajo public (ej. /uploads/pacientes/12.jpg). Si ya existe la columna, omitir esta línea.
ALTER TABLE pacientes
  ADD COLUMN ruta_foto VARCHAR(512) NULL COMMENT 'Web: ruta relativa (/uploads/...)';
