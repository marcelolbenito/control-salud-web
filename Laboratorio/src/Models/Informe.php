<?php

declare(strict_types=1);

namespace App\Models;

/**
 * DTO de un informe PDF emitido. El archivo vive en storage/informes/.
 * hash_pdf es SHA-256 del archivo: cualquier modificacion lo invalida.
 */
final class Informe
{
    public function __construct(
        public ?int $id,
        public int $pedidoId,
        public string $numero,
        public string $rutaPdf,
        public string $hashPdf,
        public bool $esParcial,
        public int $usuarioEmisionId,
        public string $fechaEmision,
        public bool $entregado,
        public ?string $fechaEntrega,
        public ?int $usuarioEntregaId,
        public ?string $destinatario,
        public ?string $observaciones,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
    ) {
    }
}
