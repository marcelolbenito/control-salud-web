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

    /**
     * Lista todos los rangos (no borrados) de una determinacion, para el ABM.
     *
     * @return array<int,array<string,mixed>>
     */
    public function listarPorDeterminacion(int $determinacionId): array
    {
        $sql = "SELECT id, determinacion_id, sexo, edad_min_dias, edad_max_dias,
                       valor_min, valor_max, texto_referencia, observaciones
                FROM lab_valores_referencia
                WHERE determinacion_id = :det AND deleted_at IS NULL
                ORDER BY
                    CASE sexo WHEN 'ambos' THEN 0 WHEN 'F' THEN 1 ELSE 2 END,
                    (edad_min_dias IS NULL) DESC, edad_min_dias ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':det' => $determinacionId]);
        return $stmt->fetchAll();
    }

    /**
     * @return array<string,mixed>|null
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id, determinacion_id, sexo, edad_min_dias, edad_max_dias,
                    valor_min, valor_max, texto_referencia, observaciones
             FROM lab_valores_referencia
             WHERE id = :id AND deleted_at IS NULL"
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    /**
     * @param array<string,mixed> $d
     */
    public function crear(array $d): int
    {
        $sql = "INSERT INTO lab_valores_referencia
                (determinacion_id, sexo, edad_min_dias, edad_max_dias,
                 valor_min, valor_max, texto_referencia, observaciones)
                VALUES (:det, :sexo, :emin, :emax, :vmin, :vmax, :texto, :obs)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($this->bindParams($d));
        return (int) $this->db->lastInsertId();
    }

    /**
     * @param array<string,mixed> $d
     */
    public function actualizar(int $id, array $d): bool
    {
        $sql = "UPDATE lab_valores_referencia
                SET sexo = :sexo, edad_min_dias = :emin, edad_max_dias = :emax,
                    valor_min = :vmin, valor_max = :vmax,
                    texto_referencia = :texto, observaciones = :obs
                WHERE id = :id AND deleted_at IS NULL";
        $params = $this->bindParams($d);
        unset($params[':det']);
        $params[':id'] = $id;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount() >= 0;
    }

    public function softDelete(int $id): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE lab_valores_referencia SET deleted_at = NOW()
             WHERE id = :id AND deleted_at IS NULL"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * @param array<string,mixed> $d
     * @return array<string,mixed>
     */
    private function bindParams(array $d): array
    {
        $intOrNull = static fn (mixed $v): ?int => ($v === null || $v === '') ? null : (int) $v;
        $numOrNull = static fn (mixed $v): ?float => ($v === null || $v === '') ? null : (float) $v;
        $strOrNull = static fn (mixed $v): ?string => ($v === null || trim((string) $v) === '') ? null : (string) $v;
        return [
            ':det'   => (int) ($d['determinacion_id'] ?? 0),
            ':sexo'  => (string) ($d['sexo'] ?? 'ambos'),
            ':emin'  => $intOrNull($d['edad_min_dias'] ?? null),
            ':emax'  => $intOrNull($d['edad_max_dias'] ?? null),
            ':vmin'  => $numOrNull($d['valor_min'] ?? null),
            ':vmax'  => $numOrNull($d['valor_max'] ?? null),
            ':texto' => $strOrNull($d['texto_referencia'] ?? null),
            ':obs'   => $strOrNull($d['observaciones'] ?? null),
        ];
    }
}
