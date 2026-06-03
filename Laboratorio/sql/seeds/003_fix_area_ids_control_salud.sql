-- Reparación: area_id huérfanos en lab_determinaciones (Control Salud prod)
--
-- Ocurre cuando lab_areas se recreó (INSERT IGNORE) y los IDs auto-increment
-- ya no son 1-5, pero determinaciones siguen apuntando a 1-6 del seed.
-- El JOIN en findAllActivas() devuelve 0 filas → nuevo pedido sin catálogo.
--
-- Idempotente: remapea por codigo de area, no por ID fijo destino.

INSERT IGNORE INTO lab_areas (codigo, nombre, descripcion, orden, activo) VALUES
('OTR', 'Otros', 'Determinaciones sin area asignada (clasificar manualmente)', 99, 1);

UPDATE lab_determinaciones d
INNER JOIN (
    SELECT 1 AS old_id, 'HEM' AS cod UNION ALL
    SELECT 2, 'QC'  UNION ALL
    SELECT 3, 'HOR' UNION ALL
    SELECT 4, 'INM' UNION ALL
    SELECT 5, 'MIC' UNION ALL
    SELECT 6, 'OTR'
) map ON map.old_id = d.area_id
INNER JOIN lab_areas a ON a.codigo = map.cod AND a.deleted_at IS NULL
SET d.area_id = a.id
WHERE d.area_id <> a.id;
