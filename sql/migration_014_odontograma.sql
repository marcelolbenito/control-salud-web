-- Odontograma: registros por paciente (Nro HC) con nomenclatura FDI y leyenda tipificada.
-- Ejecutar en MySQL/MariaDB tras schema u otras migraciones.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS lista_odontograma_codigos (
  id INT NOT NULL PRIMARY KEY,
  prioridad SMALLINT NULL,
  codigo VARCHAR(12) NULL COMMENT 'Símbolo o abreviatura en el gráfico/leyenda',
  nombre VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pacientes_odontograma (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  NroHC INT NOT NULL COMMENT 'Historia clínica (pacientes.NroHC)',
  pieza_fdi SMALLINT NOT NULL COMMENT 'Notación FDI (ISO 3950): 11-48 permanentes, 51-85 temporales',
  cara VARCHAR(20) NULL COMMENT 'Caras: M,O,D,V,L,I combinadas p. ej. MOD',
  id_codigo INT NULL COMMENT 'lista_odontograma_codigos',
  notas TEXT NULL,
  iddoctor INT NULL COMMENT 'lista_doctores',
  idusuario_web INT NULL COMMENT 'usuarios.id registro web',
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_odontograma_nrohc (NroHC),
  KEY idx_odontograma_pieza (pieza_fdi),
  KEY idx_odontograma_creado (creado_en),
  KEY idx_odontograma_codigo (id_codigo),
  KEY idx_odontograma_doctor (iddoctor),
  CONSTRAINT fk_odontograma_codigo FOREIGN KEY (id_codigo) REFERENCES lista_odontograma_codigos (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO lista_odontograma_codigos (id, prioridad, codigo, nombre) VALUES
  (1, 10, 'C', 'Caries'),
  (2, 20, 'O', 'Obturación'),
  (3, 30, 'X', 'Pérdida / ausente'),
  (4, 40, 'E', 'Endodoncia'),
  (5, 50, 'Cr', 'Corona'),
  (6, 60, 'Imp', 'Implante'),
  (7, 70, 'Er', 'Sin erupcionar / en erupción'),
  (8, 80, 'Fr', 'Fractura'),
  (9, 90, 'Pr', 'Prótesis removible'),
  (10, 100, 'Obs', 'Observación / otro (usar notas)');
