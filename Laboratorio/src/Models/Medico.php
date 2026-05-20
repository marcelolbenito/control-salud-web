<?php

declare(strict_types=1);

namespace App\Models;

final class Medico
{
    public function __construct(
        public readonly int $id,
        public readonly string $apellido,
        public readonly string $nombres,
        public readonly string $matricula,
        public readonly ?string $especialidad,
        public readonly ?string $telefono,
        public readonly ?string $email,
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
            apellido: (string) $row['apellido'],
            nombres: (string) $row['nombres'],
            matricula: (string) $row['matricula'],
            especialidad: isset($row['especialidad']) ? (string) $row['especialidad'] : null,
            telefono: isset($row['telefono']) ? (string) $row['telefono'] : null,
            email: isset($row['email']) ? (string) $row['email'] : null,
            activo: (bool) ($row['activo'] ?? 1),
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'apellido' => $this->apellido,
            'nombres' => $this->nombres,
            'matricula' => $this->matricula,
            'especialidad' => $this->especialidad,
            'telefono' => $this->telefono,
            'email' => $this->email,
            'activo' => $this->activo,
        ];
    }
}
