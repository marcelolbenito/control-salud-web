-- Tablas catálogo para el formulario de órdenes (cobertura, plan, práctica, derivación, sucursal).
-- Si instalaste solo sql/schema_mysql.sql antes de abril 2026, puede faltar este bloque y la web
-- muestra inputs numéricos en lugar de desplegables aunque los datos estén en `Pacientes Ordenes`.
-- Ejecutar una vez; no borra datos existentes (CREATE IF NOT EXISTS + INSERT IGNORE sucursales).

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS lista_coberturas (
  id INT PRIMARY KEY,
  prioridad SMALLINT NULL,
  nombre VARCHAR(255) NULL,
  porcentaje_cobertura DOUBLE NULL,
  plancober VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS lista_planes (
  id INT PRIMARY KEY,
  id_cobertura INT NULL,
  nombre VARCHAR(255) NULL,
  KEY idx_planes_cobertura (id_cobertura)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
