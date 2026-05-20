<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Response;
use App\Services\LabConfigService;

final class LabConfigController
{
    private const USUARIO_ID_HARDCODED = 1; // TODO: integrar auth real.

    public function __construct(private LabConfigService $service)
    {
    }

    public function index(): void
    {
        Response::success($this->service->getDatosLaboratorio());
    }

    /**
     * @param array<string,mixed> $input
     */
    public function actualizar(array $input): void
    {
        $kvs = [];
        foreach ($input as $clave => $valor) {
            if (!is_string($clave)) continue;
            $kvs[$clave] = $valor === null ? null : (string) $valor;
        }
        $this->service->actualizarDatos($kvs, self::USUARIO_ID_HARDCODED);
        Response::success(['ok' => true]);
    }

    /**
     * @param array{tmp_name:string,name:string,error:int} $file
     */
    public function subirLogo(array $file): void
    {
        try {
            $path = $this->service->subirLogo($file, self::USUARIO_ID_HARDCODED);
            Response::success(['logo_path' => $path]);
        } catch (\RuntimeException $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    /**
     * @param array{tmp_name:string,name:string,error:int} $file
     */
    public function subirFirma(array $file): void
    {
        try {
            $path = $this->service->subirFirma($file, self::USUARIO_ID_HARDCODED);
            Response::success(['firma_path' => $path]);
        } catch (\RuntimeException $e) {
            Response::error($e->getMessage(), 400);
        }
    }
}
