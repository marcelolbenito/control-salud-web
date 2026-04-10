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

-- Sin equivalente en Access (solo SMALLINT en Pacientes); sembrar con migration_008 + utilidad_sembrar_codigos_desde_pacientes.sql
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

CREATE TABLE IF NOT EXISTS lista_primera_vez (
  id INT PRIMARY KEY,
  prioridad SMALLINT NULL,
  nombre VARCHAR(120) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
