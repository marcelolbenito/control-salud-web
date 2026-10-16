-- 028: Hemograma en orden clinico para el informe impreso y la carga.
--
-- - Saca "Plaquetas" (PLT) del perfil Hemograma (HMG): el informe modelo no la
--   incluye en este layout. El recuento de plaquetas queda como determinacion
--   suelta / otro estudio. (Reversible: re-insertar la fila si se quisiera.)
-- - Fija el `orden` de las determinaciones del HMG al orden de presentacion:
--   serie roja (globulos rojos + indices), serie blanca (globulos blancos) y
--   formula leucocitaria. Asi los items se crean y se muestran en ese orden.

SET @hmg := (SELECT id FROM lab_perfiles WHERE codigo = 'HMG' LIMIT 1);

DELETE pd FROM lab_perfil_determinaciones pd
JOIN lab_determinaciones d ON d.id = pd.determinacion_id
WHERE pd.perfil_id = @hmg AND d.codigo = 'PLT';

UPDATE lab_perfil_determinaciones pd
JOIN lab_determinaciones d ON d.id = pd.determinacion_id
SET pd.orden = CASE d.codigo
    WHEN 'GR'   THEN 1
    WHEN 'HTO'  THEN 2
    WHEN 'HB'   THEN 3
    WHEN 'VCM'  THEN 4
    WHEN 'HCM'  THEN 5
    WHEN 'CHCM' THEN 6
    WHEN 'RDW'  THEN 7
    WHEN 'GB'   THEN 8
    WHEN 'NEU'  THEN 9
    WHEN 'EOS'  THEN 10
    WHEN 'BAS'  THEN 11
    WHEN 'LIN'  THEN 12
    WHEN 'MON'  THEN 13
    ELSE pd.orden
END
WHERE pd.perfil_id = @hmg;

-- Formato de presentacion de globulos rojos/blancos igual al informe modelo
-- (GR: 1 decimal; GB: entero; unidades como las imprime el equipo).
UPDATE lab_determinaciones SET decimales = 1, unidad = '/mm3 x10^6/ul' WHERE codigo = 'GR';
UPDATE lab_determinaciones SET decimales = 0, unidad = '/mm3'          WHERE codigo = 'GB';

-- GB: los umbrales criticos estaban en miles (1 a 30) pero el recuento se carga
-- en /mm3 (valor absoluto, ej. 6400). Se reescalan x1000 para que coincidan con
-- la unidad y no marquen "critico" un valor normal.
UPDATE lab_determinaciones
SET valor_critico_min = 1000, valor_critico_max = 30000
WHERE codigo = 'GB' AND valor_critico_min = 1 AND valor_critico_max = 30;
