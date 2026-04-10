-- Catálogo para agenda_turnos.primera_vez (antes se cargaba como código crudo).
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS lista_primera_vez (
  id INT PRIMARY KEY,
  prioridad SMALLINT NULL,
  nombre VARCHAR(120) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Semillas iniciales (ajustables según uso real del consultorio).
INSERT INTO lista_primera_vez (id, prioridad, nombre) VALUES
  (1, 1, 'Primera consulta'),
  (2, 2, 'Control / seguimiento')
ON DUPLICATE KEY UPDATE
  prioridad = VALUES(prioridad),
  nombre = VALUES(nombre);
