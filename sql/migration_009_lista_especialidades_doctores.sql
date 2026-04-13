-- Catálogo tipificado para especialidades de profesionales.
-- Mantiene compatibilidad: lista_doctores.especialidad sigue siendo texto.

CREATE TABLE IF NOT EXISTS lista_especialidades_doctores (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  UNIQUE KEY uq_lista_especialidades_doctores_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sembrar valores existentes en lista_doctores para no perder continuidad.
INSERT IGNORE INTO lista_especialidades_doctores (nombre)
SELECT DISTINCT TRIM(especialidad) AS nombre
FROM lista_doctores
WHERE especialidad IS NOT NULL
  AND TRIM(especialidad) <> '';
