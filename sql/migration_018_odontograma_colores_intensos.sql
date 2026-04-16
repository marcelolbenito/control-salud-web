-- Sustituye colores pastel por defecto del mapa por tonos más saturados (ids 1–10 del seed 014).
-- Ejecutar si ya corriste migration_016 y querés renovar los hex sin reinstalar.
-- Opcional: comentá esta migración si personalizaste esos colores a mano.

SET NAMES utf8mb4;

UPDATE lista_odontograma_codigos SET color_hex = CASE id
  WHEN 1 THEN '#dc2626'
  WHEN 2 THEN '#2563eb'
  WHEN 3 THEN '#991b1b'
  WHEN 4 THEN '#ca8a04'
  WHEN 5 THEN '#7c3aed'
  WHEN 6 THEN '#047857'
  WHEN 7 THEN '#ea580c'
  WHEN 8 THEN '#be185d'
  WHEN 9 THEN '#4338ca'
  WHEN 10 THEN '#404040'
  ELSE color_hex
END
WHERE id BETWEEN 1 AND 10;
