<?php

declare(strict_types=1);

namespace App\Models;

final class LabConfig
{
    public function __construct(
        public readonly string $clave,
        public readonly ?string $valor,
        public readonly ?string $descripcion,
        public readonly ?string $updatedAt,
    ) {
    }

    /**
     * @param array<string,mixed> $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            clave: (string) $row['clave'],
            valor: isset($row['valor']) ? (string) $row['valor'] : null,
            descripcion: isset($row['descripcion']) ? (string) $row['descripcion'] : null,
            updatedAt: isset($row['updated_at']) ? (string) $row['updated_at'] : null,
        );
    }
}
