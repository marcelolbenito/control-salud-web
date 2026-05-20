<?php

declare(strict_types=1);

namespace App\Models;

/**
 * DTO de un pedido medico de laboratorio.
 *
 * Las propiedades nullable representan campos que se completan despues
 * (id tras INSERT, fechas de extraccion/entrega tras workflow, etc.).
 */
final class Pedido
{
    /**
     * @param array<string,mixed>|null $snapshotPaciente
     */
    public function __construct(
        public ?int $id,
        public string $numero,
        public int $pacienteId,
        public ?int $medicoId,
        public ?string $medicoExterno,
        public ?int $obraSocialId,
        public ?string $numeroAfiliado,
        public ?string $diagnostico,
        public string $prioridad,
        public string $estado,
        public bool $esCritico,
        public string $fechaSolicitud,
        public ?string $fechaExtraccion,
        public ?string $fechaEntrega,
        public ?int $usuarioRecepcionId,
        public ?int $usuarioAnulacionId,
        public ?string $motivoAnulacion,
        public ?string $observaciones,
        public ?array $snapshotPaciente,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
    ) {
    }
}
