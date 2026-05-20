<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Se lanza cuando el sistema intenta generar un informe PDF pero falta
 * configuracion institucional (firmante, datos del laboratorio).
 * Se mapea a HTTP 422 (Unprocessable Entity).
 */
final class InformeNoConfiguradoException extends DomainException
{
    public static function configFaltante(string $clave): self
    {
        return new self(
            "Falta configurar la clave '$clave' en datos del laboratorio. "
            . 'Completar desde Configuracion del laboratorio.',
            422,
        );
    }
}
