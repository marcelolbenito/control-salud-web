<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class ValorReferenciaRepository
{
    public function __construct(private PDO $db)
    {
    }

    /**
     * Devuelve el rango de referencia mas especifico para una determinacion
     * dado el sexo y la edad en dias del paciente.
     *
     * Especificidad (de mas a menos):
     *   1) sexo exacto (M/F) gana sobre sexo='ambos'
     *   2) rango de edad acotado gana sobre rango con limites NULL
     *
     * NULL en edad_min_dias significa "sin limite inferior", idem max.
     *
     * @return array<string,mixed>|null
     */
    public function findRangoAplicable(int $determinacionId, string $sexo, int $edadDias): ?array
    {
        // PDO_MYSQL con EMULATE_PREPARES=false no permite reusar placeholders
        // nombrados, asi que numero los repetidos: :sexo1/:sexo2 y :edad1/:edad2.
        $sql = "SELECT id, determinacion_id, sexo,
                       edad_min_dias, edad_max_dias,
                       valor_min, valor_max, texto_referencia
                FROM lab_valores_referencia
                WHERE determinacion_id = :det
                  AND deleted_at IS NULL
                  AND (sexo = :sexo1 OR sexo = 'ambos')
                  AND (edad_min_dias IS NULL OR edad_min_dias <= :edad1)
                  AND (edad_max_dias IS NULL OR edad_max_dias >= :edad2)
                ORDER BY
                    CASE WHEN sexo = :sexo2 THEN 0 ELSE 1 END,
                    CASE WHEN edad_min_dias IS NULL THEN 1 ELSE 0 END,
                    CASE WHEN edad_max_dias IS NULL THEN 1 ELSE 0 END,
                    edad_min_dias DESC
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':det'   => $determinacionId,
            ':sexo1' => $sexo,
            ':sexo2' => $sexo,
            ':edad1' => $edadDias,
            ':edad2' => $edadDias,
        ]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }
}
