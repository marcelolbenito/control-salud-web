-- 026_texto_referencia_text.sql
-- Cambio: lab_valores_referencia.texto_referencia pasa de VARCHAR(200) a TEXT.
--
-- Motivo: el catalogo de referencias del cliente trae textos largos y
-- multilinea (tablas de percentiles por edad/sexo) que superan los 200
-- caracteres. TEXT permite guardarlos completos sin truncar.

ALTER TABLE lab_valores_referencia
    MODIFY COLUMN texto_referencia TEXT NULL;
