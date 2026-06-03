-- 025_paciente_id_opcional.sql
-- Cambio: `lab_pedidos.paciente_id` pasa de NOT NULL a NULL.
--
-- Motivo: en el sistema clinico mayor el paciente viene del contexto (ya
-- esta seleccionado), asi que el modulo no exige cargar un paciente_id a mano.
-- La fuente de verdad por pedido es `snapshot_paciente` (JSON con nombre,
-- dni, sexo, fecha_nac), que es inmutable y refleja lo cargado en la orden.
-- El paciente_id queda como referencia opcional al registro del sistema mayor.
--
-- No hay FK sobre esta columna (la tabla `pacientes` la gestiona el sistema
-- mayor), por lo que solo se afloja el NOT NULL. El indice se mantiene.

ALTER TABLE lab_pedidos
    MODIFY COLUMN paciente_id BIGINT UNSIGNED NULL;
