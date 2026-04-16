-- Catálogos para tipificar órdenes (Pacientes Ordenes): prácticas, derivaciones, sucursal.
-- Ejecutar después de schema_mysql.sql / migraciones previas.
-- Los IDs se alinean a los enteros ya usados en idpractica, idderivado, sucursal del backup.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS lista_practicas (
  id INT NOT NULL PRIMARY KEY,
  prioridad SMALLINT NULL,
  nombre VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS lista_derivaciones (
  id INT NOT NULL PRIMARY KEY,
  prioridad SMALLINT NULL,
  nombre VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS lista_sucursales (
  id SMALLINT NOT NULL PRIMARY KEY,
  prioridad SMALLINT NULL,
  nombre VARCHAR(100) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO lista_sucursales (id, prioridad, nombre) VALUES
  (1, 1, 'Sucursal 1'),
  (2, 2, 'Sucursal 2'),
  (3, 3, 'Sucursal 3'),
  (4, 4, 'Sucursal 4'),
  (5, 5, 'Sucursal 5'),
  (6, 6, 'Sucursal 6'),
  (7, 7, 'Sucursal 7'),
  (8, 8, 'Sucursal 8'),
  (9, 9, 'Sucursal 9'),
  (10, 10, 'Sucursal 10');
