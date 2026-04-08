-- Tablas catálogo equivalentes a las "Lista *" del Access (para importar datos).
-- Ejecutar después de schema_mysql.sql (y migration_002 si ampliaste Pacientes).

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

CREATE TABLE IF NOT EXISTS lista_pais (
  id INT PRIMARY KEY,
  prioridad SMALLINT NULL,
  nombre VARCHAR(100) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS lista_provincia (
  id INT PRIMARY KEY,
  prioridad SMALLINT NULL,
  nombre VARCHAR(100) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS lista_ciudad (
  id INT PRIMARY KEY,
  prioridad SMALLINT NULL,
  nombre VARCHAR(100) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS lista_tipo_documento (
  id INT PRIMARY KEY,
  prioridad SMALLINT NULL,
  nombre VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS lista_ocupacion (
  id INT PRIMARY KEY,
  prioridad SMALLINT NULL,
  nombre VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS lista_estado_civil (
  id INT PRIMARY KEY,
  prioridad SMALLINT NULL,
  nombre VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS lista_etnia (
  id INT PRIMARY KEY,
  prioridad SMALLINT NULL,
  nombre VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS lista_relacion_paciente (
  id INT PRIMARY KEY,
  prioridad SMALLINT NULL,
  nombre VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS lista_estatus_pais (
  id INT PRIMARY KEY,
  prioridad SMALLINT NULL,
  nombre VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
