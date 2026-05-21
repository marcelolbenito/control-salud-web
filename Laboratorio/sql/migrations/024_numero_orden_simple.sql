-- 024_numero_orden_simple.sql
-- Cambio: el numero de orden del pedido pasa de `P-YYYY-NNNNN` a un
-- correlativo simple por año (1, 2, 3, ...).
--
-- 1. Se agrega una columna generada `anio_orden` con YEAR(fecha_solicitud).
-- 2. Se reemplaza la UNIQUE (numero) por UNIQUE (anio_orden, numero) para
--    que el correlativo pueda reiniciar cada año sin colisionar.
-- 3. Se migran los pedidos existentes parseando `P-YYYY-NNNNN` y
--    guardando solo NNNNN.
--
-- Backup recomendado antes de aplicar en produccion.

ALTER TABLE lab_pedidos
    DROP INDEX uk_lab_pedidos_numero;

UPDATE lab_pedidos
SET numero = CAST(SUBSTRING_INDEX(numero, '-', -1) AS UNSIGNED)
WHERE numero REGEXP '^P-[0-9]{4}-[0-9]+$';

ALTER TABLE lab_pedidos
    ADD COLUMN anio_orden SMALLINT UNSIGNED
        GENERATED ALWAYS AS (YEAR(fecha_solicitud)) STORED AFTER numero;

ALTER TABLE lab_pedidos
    ADD UNIQUE KEY uk_lab_pedidos_anio_numero (anio_orden, numero);
