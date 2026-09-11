-- Carpeta compartida de información / novedades (PDF e imágenes) por clínica.
-- Todos los usuarios autenticados pueden ver y subir; borrar: autor o admin.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS novedades_archivos (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  id_clinica INT NOT NULL DEFAULT 1,
  id_usuario INT NULL,
  titulo VARCHAR(255) NOT NULL,
  descripcion VARCHAR(1000) NULL,
  nombre_original VARCHAR(255) NOT NULL,
  ruta_relativa VARCHAR(512) NOT NULL,
  mime VARCHAR(100) NULL,
  tamano_bytes INT NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_novedades_clin_fecha (id_clinica, creado_en),
  KEY idx_novedades_usuario (id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
