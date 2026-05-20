<?php

declare(strict_types=1);

namespace App\Helpers;

use JsonException;

/**
 * Helper para respuestas JSON estandar de la API.
 *
 * Estructura definida:
 *   exito:  { "success": true,  "data": ..., "error": null }
 *   error:  { "success": false, "data": null, "error": { "code": "...", "message": "...", "fields": {...} } }
 *
 * No llama a exit(): el caller decide si seguir ejecutando (logging, cleanup, etc.).
 */
final class Response
{
    /**
     * Mapeo por defecto de codigo HTTP -> codigo de error simbolico.
     */
    private const CODE_MAP = [
        400 => 'BAD_REQUEST',
        401 => 'UNAUTHORIZED',
        403 => 'FORBIDDEN',
        404 => 'NOT_FOUND',
        409 => 'CONFLICT',
        422 => 'VALIDATION',
        500 => 'INTERNAL_ERROR',
    ];

    private function __construct()
    {
    }

    public static function success(mixed $data = null, int $code = 200): void
    {
        self::send($code, [
            'success' => true,
            'data'    => $data,
            'error'   => null,
        ]);
    }

    /**
     * Envia bytes de PDF como respuesta.
     * El caller decide si el navegador lo abre inline o lo descarga via $disposition.
     */
    public static function pdf(string $bytes, string $filename, string $disposition = 'inline'): void
    {
        if (!headers_sent()) {
            http_response_code(200);
            header('Content-Type: application/pdf');
            // Sanitizamos filename para evitar inyeccion de headers.
            $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $filename) ?? 'documento.pdf';
            header(sprintf('Content-Disposition: %s; filename="%s"', $disposition, $safeName));
            header('Content-Length: ' . strlen($bytes));
            header('X-Content-Type-Options: nosniff');
        }
        echo $bytes;
    }

    /**
     * @param array<string,mixed> $fields Errores por campo (ej: ['email' => 'invalido']).
     * @param string|null         $errorCode Codigo simbolico opcional. Si es null, se deriva del HTTP code.
     */
    public static function error(
        string $message,
        int $code = 400,
        array $fields = [],
        ?string $errorCode = null
    ): void {
        self::send($code, [
            'success' => false,
            'data'    => null,
            'error'   => [
                'code'    => $errorCode ?? (self::CODE_MAP[$code] ?? 'ERROR'),
                'message' => $message,
                'fields'  => (object) $fields,
            ],
        ]);
    }

    /**
     * @param array<string,mixed> $payload
     */
    private static function send(int $code, array $payload): void
    {
        if (!headers_sent()) {
            http_response_code($code);
            header('Content-Type: application/json; charset=utf-8');
        }

        try {
            echo json_encode(
                $payload,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
            );
        } catch (JsonException $e) {
            // Fallback minimo: si la serializacion fallo, devolver un error estatico.
            if (!headers_sent()) {
                http_response_code(500);
            }
            echo '{"success":false,"data":null,"error":{"code":"INTERNAL_ERROR","message":"Failed to encode response","fields":{}}}';
        }
    }
}
