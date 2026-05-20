-- ============================================================================
-- seed_nbu.sql
-- ============================================================================
-- Datos minimos para testear el arancelador.
-- Asume que ya existen en la BD:
--   - lab_determinaciones con codigos GLU, TSH, COL, URE
--   - obras_sociales con ids 1 (OSDE) y 2 (Swiss Medical)
-- Nota: HEM no existe en la BD actual, se usa GLU, TSH, COL, URE (4 items)
-- ============================================================================

INSERT INTO lab_nbu_determinaciones (determinacion_id, unidades)
SELECT id, CASE codigo
    WHEN 'GLU' THEN 2.50
    WHEN 'TSH' THEN 8.00
    WHEN 'COL' THEN 2.50
    WHEN 'URE' THEN 2.50
END
FROM lab_determinaciones
WHERE codigo IN ('GLU', 'TSH', 'COL', 'URE')
AND deleted_at IS NULL
ON DUPLICATE KEY UPDATE unidades = VALUES(unidades);

INSERT INTO lab_nbu_valores_os (obra_social_id, valor_unitario) VALUES
    (1, 120.00),
    (2, 135.50)
ON DUPLICATE KEY UPDATE valor_unitario = VALUES(valor_unitario);
