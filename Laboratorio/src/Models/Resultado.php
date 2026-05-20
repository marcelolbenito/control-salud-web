<?php

declare(strict_types=1);

namespace App\Models;

/**
 * DTO de un resultado vivo (estado actual) sobre un item de pedido.
 *
 * SP9: se eliminaron usuarioValidacionId, fechaValidacion y version.
 * El flujo de validacion fue removido (cargar = listo, editable siempre).
 * La tabla lab_resultados_historico queda como datos legacy de rectificaciones
 * previas; el service ya no escribe ahi.
 */
final class Resultado
{
    public function __construct(
        public ?int $id,
        public int $pedidoItemId,
        public ?float $valorNumerico,
        public ?string $valorTexto,
        public ?string $unidad,
        public bool $esAnormal,
        public bool $esCritico,
        public string $estado,
        public ?float $valorReferenciaMin,
        public ?float $valorReferenciaMax,
        public ?string $textoReferencia,
        public ?string $observaciones,
        public int $usuarioCargaId,
        public string $fechaCarga,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
    ) {
    }
}
