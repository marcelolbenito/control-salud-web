<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Excepcion para violaciones de reglas de negocio (ej: intentar editar
 * un resultado validado, anular un pedido ya entregado, etc.).
 *
 * Se mapea a HTTP 409 (Conflict) por defecto.
 */
class DomainException extends RuntimeException
{
    public function __construct(string $message, int $code = 409, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
