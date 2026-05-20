<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Errores de validacion de input. Se mapea a HTTP 422.
 *
 * fields = [
 *   'nombre_campo' => 'mensaje de error',
 *   ...
 * ]
 */
class ValidationException extends RuntimeException
{
    /**
     * @param array<string,string> $fields
     */
    public function __construct(
        string $message = 'Errores de validacion',
        private array $fields = [],
        int $code = 422,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return array<string,string>
     */
    public function getFields(): array
    {
        return $this->fields;
    }
}
