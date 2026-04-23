-- Esquema MySQL para versión web de Control Salud
-- Basado en estructura del .mdb y cadenas extraídas del .exe
-- Ejecutar en MySQL 8 o MariaDB

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------
-- Tabla de configuración (opcional)
-- ------------------------------------------
CREATE TABLE IF NOT EXISTS clinicas (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(150) NOT NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO clinicas (id, nombre, activo) VALUES (1, 'Clínica principal', 1);

CREATE TABLE IF NOT EXISTS config (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_clinica INT NOT NULL DEFAULT 1,
  clave VARCHAR(100) NOT NULL,
  valor MEDIUMTEXT,
  UNIQUE KEY uk_config_clinica_clave (id_clinica, clave),
  KEY idx_config_clinica (id_clinica)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------
-- Usuarios del sistema web (login)
-- ------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  nombre VARCHAR(100),
  email VARCHAR(100),
  activo TINYINT(1) DEFAULT 1,
  id_clinica INT NOT NULL DEFAULT 1,
  creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
  actualizado_en DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_usuarios_clinica (id_clinica)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------
-- Lista Doctores (equivalente a [Lista Doctores])
-- ------------------------------------------
CREATE TABLE IF NOT EXISTS lista_doctores (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_clinica INT NOT NULL DEFAULT 1,
  nombre VARCHAR(150),
  medicoconvenio TINYINT(1) DEFAULT 0,
  bloquearmisconsultas TINYINT(1) DEFAULT 0,
  sucursal1 TINYINT(1) DEFAULT 0,
  sucursal2 TINYINT(1) DEFAULT 0,
  sucursal3 TINYINT(1) DEFAULT 0,
  sucursal4 TINYINT(1) DEFAULT 0,
  sucursal5 TINYINT(1) DEFAULT 0,
  sucursal6 TINYINT(1) DEFAULT 0,
  sucursal7 TINYINT(1) DEFAULT 0,
  sucursal8 TINYINT(1) DEFAULT 0,
  sucursal9 TINYINT(1) DEFAULT 0,
  sucursal10 TINYINT(1) DEFAULT 0,
  activo TINYINT(1) DEFAULT 1,
  notas TEXT,
  creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
  actualizado_en DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_lista_doctores_clinica (id_clinica)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------
-- Catálogos órdenes / coberturas (desplegables en orden_form; datos vía migración MDB/SQL Server)
-- ------------------------------------------
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

-- ------------------------------------------
-- Pacientes (equivalente a Pacientes)
-- ------------------------------------------
CREATE TABLE IF NOT EXISTS pacientes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_clinica INT NOT NULL DEFAULT 1,
  NroHC INT NOT NULL COMMENT 'Número historia clínica',
  Nombres VARCHAR(200),
  DNI VARCHAR(20),
  convenio TINYINT(1) DEFAULT 0,
  fecha_nacimiento DATE,
  telefono VARCHAR(50),
  email VARCHAR(100),
  direccion TEXT,
  activo TINYINT(1) DEFAULT 1,
  notas TEXT,
  creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
  actualizado_en DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_pacientes_clinica_nrohc (id_clinica, NroHC),
  KEY idx_pacientes_clinica (id_clinica)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------
-- Agenda Turnos (equivalente a [Agenda Turnos])
-- ------------------------------------------
CREATE TABLE IF NOT EXISTS agenda_turnos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_clinica INT NOT NULL DEFAULT 1,
  Fecha DATE NOT NULL,
  hora TIME,
  NroHC INT NOT NULL COMMENT 'Nro historia clínica paciente',
  Doctor INT NOT NULL COMMENT 'id lista_doctores',
  idorden INT DEFAULT NULL COMMENT 'id Pacientes Ordenes si aplica',
  estado VARCHAR(50) DEFAULT 'pendiente' COMMENT 'pendiente, atendido, cancelado, no_asistio',
  observaciones TEXT,
  creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
  actualizado_en DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_agenda_fecha (Fecha),
  KEY idx_agenda_doctor (Doctor),
  KEY idx_agenda_nrohc (NroHC),
  KEY idx_agenda_idorden (idorden),
  KEY idx_agenda_clinica (id_clinica),
  KEY idx_agenda_clinica_fecha (id_clinica, Fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bloqueos: profesional no atiende (día completo o franja); la grilla de turnos no ofrece esos huecos.
CREATE TABLE IF NOT EXISTS agenda_bloqueos (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  id_clinica INT NOT NULL DEFAULT 1,
  doctor INT NOT NULL COMMENT 'id lista_doctores',
  fecha_desde DATE NOT NULL,
  fecha_hasta DATE NOT NULL,
  hora_desde TIME NULL COMMENT 'NULL = todo el día en cada fecha del rango',
  hora_hasta TIME NULL COMMENT 'Fin exclusivo del intervalo',
  motivo VARCHAR(255) NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_agenda_bloq_clin_doc (id_clinica, doctor),
  KEY idx_agenda_bloq_fechas (id_clinica, doctor, fecha_desde, fecha_hasta)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------
-- Pacientes Ordenes (mismo nombre que backup / exe; la web usa solo esta tabla)
-- ------------------------------------------
CREATE TABLE IF NOT EXISTS `Pacientes Ordenes` (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  id_clinica INT NOT NULL DEFAULT 1,
  NroPaci INT DEFAULT NULL COMMENT 'NroHC paciente',
  numero INT DEFAULT NULL,
  fecha DATETIME DEFAULT NULL,
  entregada TINYINT(1) DEFAULT NULL,
  autorizada TINYINT(1) DEFAULT NULL,
  sesiones SMALLINT DEFAULT NULL,
  costo FLOAT DEFAULT NULL,
  pago FLOAT DEFAULT NULL,
  iddoctor INT DEFAULT NULL,
  idobrasocial INT DEFAULT NULL,
  observaciones LONGTEXT,
  numeautorizacion SMALLINT DEFAULT NULL,
  costo_os FLOAT DEFAULT NULL,
  estado VARCHAR(1) DEFAULT NULL,
  estado_os VARCHAR(1) DEFAULT NULL,
  idpractica INT DEFAULT NULL,
  idderivado INT DEFAULT NULL,
  fechaderivacion DATETIME DEFAULT NULL,
  fechaautorizacion DATETIME DEFAULT NULL,
  fechaentrega DATETIME DEFAULT NULL,
  idusuariocarga INT DEFAULT NULL,
  sesionesreali INT DEFAULT NULL,
  diente VARCHAR(2) DEFAULT NULL,
  cara VARCHAR(5) DEFAULT NULL,
  nusiniestro VARCHAR(30) DEFAULT NULL,
  pagaiva SMALLINT DEFAULT NULL,
  cerrada SMALLINT DEFAULT NULL,
  tipoasistencia SMALLINT DEFAULT NULL,
  liquidada TINYINT(1) DEFAULT NULL,
  honorarioextra FLOAT DEFAULT NULL,
  honorariofecha DATETIME DEFAULT NULL,
  idplan INT DEFAULT NULL,
  sucursal SMALLINT DEFAULT NULL,
  KEY idx_pac_ordenes_nropaci (NroPaci),
  KEY idx_pac_ordenes_iddoctor (iddoctor),
  KEY idx_pac_ordenes_fecha (fecha),
  KEY idx_pac_ordenes_idpractica (idpractica),
  KEY idx_pac_ordenes_idobrasocial (idobrasocial),
  KEY idx_pac_ordenes_sucursal (sucursal),
  KEY idx_pac_ordenes_clinica (id_clinica),
  KEY idx_pac_ordenes_clinica_nropaci (id_clinica, NroPaci)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------
-- Pacientes Sesiones (equivalente a [Pacientes Sesiones])
-- ------------------------------------------
CREATE TABLE IF NOT EXISTS pacientes_sesiones (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_clinica INT NOT NULL DEFAULT 1,
  idorden INT NOT NULL COMMENT 'id Pacientes Ordenes',
  NroPaci INT NOT NULL COMMENT 'NroHC paciente',
  iddoctor INT NOT NULL COMMENT 'id lista_doctores',
  fecha_sesion DATE,
  cantidad_sesiones INT DEFAULT 1,
  observaciones TEXT,
  creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
  actualizado_en DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_sesiones_idorden (idorden),
  KEY idx_sesiones_nropaci (NroPaci),
  KEY idx_sesiones_iddoctor (iddoctor),
  KEY idx_sesiones_clinica (id_clinica)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------
-- Pacientes Pagos (equivalente a [Pacientes Pagos])
-- ------------------------------------------
CREATE TABLE IF NOT EXISTS pacientes_pagos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_clinica INT NOT NULL DEFAULT 1,
  quien CHAR(1) NOT NULL DEFAULT 'P' COMMENT 'P=paciente, otro si aplica',
  NroPaci INT DEFAULT NULL COMMENT 'NroHC paciente',
  idorden INT DEFAULT NULL COMMENT 'id Pacientes Ordenes si aplica',
  importe DECIMAL(12,2) NOT NULL DEFAULT 0,
  fecha DATE NOT NULL,
  forma_pago VARCHAR(50) COMMENT 'efectivo, tarjeta_debito, tarjeta_credito, etc',
  observaciones TEXT,
  creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
  actualizado_en DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_pagos_nropaci (NroPaci),
  KEY idx_pagos_idorden (idorden),
  KEY idx_pagos_fecha (fecha),
  KEY idx_pagos_clinica (id_clinica)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------
-- Caja (equivalente a Caja)
-- ------------------------------------------
CREATE TABLE IF NOT EXISTS caja (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_clinica INT NOT NULL DEFAULT 1,
  doctor INT NOT NULL COMMENT 'id lista_doctores',
  fechacaja DATE NOT NULL,
  importecaja DECIMAL(12,2) DEFAULT 0,
  idcoberturacaja INT DEFAULT NULL,
  turnocaja TEXT,
  observaciones TEXT,
  creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
  actualizado_en DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_caja_doctor (doctor),
  KEY idx_caja_fecha (fechacaja),
  KEY idx_caja_clinica (id_clinica),
  KEY idx_caja_clinica_fecha (id_clinica, fechacaja)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------
-- Consultas (equivalente a Consultas)
-- ------------------------------------------
CREATE TABLE IF NOT EXISTS consultas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_clinica INT NOT NULL DEFAULT 1,
  iddoctor INT NOT NULL COMMENT 'id lista_doctores',
  NroHC INT NOT NULL COMMENT 'paciente',
  fecha_consulta DATE,
  observaciones TEXT,
  creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
  actualizado_en DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_consultas_iddoctor (iddoctor),
  KEY idx_consultas_nrohc (NroHC),
  KEY idx_consultas_clinica (id_clinica)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------
-- Consultas Items (ítems por consulta)
-- ------------------------------------------
CREATE TABLE IF NOT EXISTS consultas_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_consulta INT NOT NULL,
  descripcion VARCHAR(255),
  valor TEXT,
  creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
  KEY idx_consultas_items_consulta (id_consulta),
  CONSTRAINT fk_consultas_items_consulta FOREIGN KEY (id_consulta) REFERENCES consultas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------
-- Camas (si aplica al negocio)
-- ------------------------------------------
CREATE TABLE IF NOT EXISTS camas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_clinica INT NOT NULL DEFAULT 1,
  sucursal VARCHAR(50),
  nombre VARCHAR(100),
  activo TINYINT(1) DEFAULT 1,
  creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
  KEY idx_camas_clinica (id_clinica)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS camas_pacientes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_clinica INT NOT NULL DEFAULT 1,
  idcama INT NOT NULL,
  nropaci INT NOT NULL COMMENT 'NroHC',
  fecha_desde DATE,
  fecha_hasta DATE,
  creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
  KEY idx_camas_pacientes_cama (idcama),
  KEY idx_camas_pacientes_nropaci (nropaci),
  KEY idx_camas_pac_clinica (id_clinica),
  CONSTRAINT fk_camas_pacientes_cama FOREIGN KEY (idcama) REFERENCES camas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------
-- Odontograma (registros FDI por paciente; leyenda tipificada)
-- ------------------------------------------
CREATE TABLE IF NOT EXISTS lista_odontograma_codigos (
  id INT NOT NULL PRIMARY KEY,
  prioridad SMALLINT NULL,
  codigo VARCHAR(12) NULL COMMENT 'Símbolo o abreviatura en leyenda',
  nombre VARCHAR(255) NOT NULL,
  color_hex VARCHAR(7) NULL COMMENT 'Color en mapa (#RRGGBB)',
  mapa_overlay VARCHAR(24) NULL COMMENT 'Vacío=caras; pieza_diagonal|pieza_x|pieza_circulo|pieza_relleno'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pacientes_odontograma (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  id_clinica INT NOT NULL DEFAULT 1,
  NroHC INT NOT NULL COMMENT 'NroHC paciente',
  pieza_fdi SMALLINT NOT NULL COMMENT 'FDI ISO 3950',
  cara VARCHAR(20) NULL COMMENT 'Caras M,O,D,V,L,I',
  id_codigo INT NULL,
  notas TEXT NULL,
  iddoctor INT NULL,
  idusuario_web INT NULL,
  id_orden INT NULL COMMENT 'id Pacientes Ordenes',
  anulado TINYINT(1) NOT NULL DEFAULT 0,
  anulado_motivo VARCHAR(255) NULL,
  anulado_en DATETIME NULL,
  anulado_por_usuario INT NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_odontograma_nrohc (NroHC),
  KEY idx_odontograma_pieza (pieza_fdi),
  KEY idx_odontograma_creado (creado_en),
  KEY idx_odontograma_codigo (id_codigo),
  KEY idx_odontograma_doctor (iddoctor),
  KEY idx_odontograma_orden (id_orden),
  KEY idx_odontograma_clinica_nrohc (id_clinica, NroHC),
  CONSTRAINT fk_odontograma_codigo FOREIGN KEY (id_codigo) REFERENCES lista_odontograma_codigos (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pacientes_odontograma_superficies (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  id_clinica INT NOT NULL DEFAULT 1,
  NroHC INT NOT NULL,
  pieza_fdi SMALLINT NOT NULL,
  cara CHAR(1) NOT NULL COMMENT 'M O D V L P (P=marca pieza completa)',
  id_codigo INT NOT NULL,
  actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  idusuario_web INT NULL,
  UNIQUE KEY uk_odontograma_sup_clin_nro_pieza_cara (id_clinica, NroHC, pieza_fdi, cara),
  KEY idx_odontograma_sup_clinica (id_clinica, NroHC),
  CONSTRAINT fk_odontograma_sup_codigo FOREIGN KEY (id_codigo) REFERENCES lista_odontograma_codigos (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- Usuario inicial de ejemplo (cambiar contraseña en producción)
-- password_hash para 'admin123' con password_hash() PHP
-- INSERT INTO usuarios (usuario, password_hash, nombre) VALUES ('admin', '$2y$10$...', 'Administrador');
