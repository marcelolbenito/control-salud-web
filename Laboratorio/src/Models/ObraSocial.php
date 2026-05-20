<?php

declare(strict_types=1);

namespace App\Models;

final class ObraSocial
{
    public function __construct(
        public readonly int $id,
        public readonly string $nombre,
        public readonly bool $activo,
    ) {
    }

    /**
     * @param array<string,mixed> $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            nombre: (string) $row['nombre'],
            activo: (bool) ($row['activo'] ?? 1),
        );
    }

    /**
     * @return array{id:int,nombre:string,activo:bool}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'activo' => $this->activo,
        ];
    }
}
