<?php

declare(strict_types=1);

namespace App\Models;

final class Paciente
{
    public function __construct(
        public readonly int $id,
        public readonly string $nroHc,
        public readonly string $dni,
        public readonly string $apellido,
        public readonly string $nombres,
        public readonly ?string $telefono,
        public readonly ?string $fechaNacimiento, // ISO 'YYYY-MM-DD' o null
        public readonly string $sexo, // 'M' | 'F' | 'X'
        public readonly ?int $obraSocialId,
        public readonly ?string $obraSocialNombre,
        public readonly ?string $nroAfiliado,
    ) {
    }

    /**
     * @param array<string,mixed> $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            nroHc: (string) $row['nro_hc'],
            dni: (string) $row['dni'],
            apellido: (string) $row['apellido'],
            nombres: (string) $row['nombres'],
            telefono: isset($row['telefono']) ? (string) $row['telefono'] : null,
            fechaNacimiento: isset($row['fecha_nacimiento']) ? (string) $row['fecha_nacimiento'] : null,
            sexo: (string) $row['sexo'],
            obraSocialId: isset($row['obra_social_id']) ? (int) $row['obra_social_id'] : null,
            obraSocialNombre: isset($row['obra_social_nombre']) ? (string) $row['obra_social_nombre'] : null,
            nroAfiliado: isset($row['nro_afiliado']) ? (string) $row['nro_afiliado'] : null,
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nro_hc' => $this->nroHc,
            'dni' => $this->dni,
            'apellido' => $this->apellido,
            'nombres' => $this->nombres,
            'telefono' => $this->telefono,
            'fecha_nacimiento' => $this->fechaNacimiento,
            'sexo' => $this->sexo,
            'obra_social_id' => $this->obraSocialId,
            'obra_social_nombre' => $this->obraSocialNombre,
            'nro_afiliado' => $this->nroAfiliado,
        ];
    }
}
