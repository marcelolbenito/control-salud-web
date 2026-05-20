<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\ValidationException;
use App\Helpers\Response;
use App\Services\PacienteService;
use PDOException;

final class PacienteController
{
    public function __construct(private PacienteService $service)
    {
    }

    /**
     * @param array<string,mixed> $query Query string (typically $_GET).
     */
    public function buscar(array $query): void
    {
        try {
            $r = $this->service->buscar($query);
            Response::success([
                'pacientes' => array_map(fn ($p) => $p->toArray(), $r['pacientes']),
                'total_encontrados' => $r['total_encontrados'],
                'truncado' => $r['truncado'],
            ]);
        } catch (ValidationException $e) {
            Response::error($e->getMessage(), 422, $e->getFields(), 'VALIDATION');
        } catch (PDOException $e) {
            Response::error($this->dbErrorMessage($e), 500, [], 'DB_ERROR');
        } catch (\Throwable $e) {
            Response::error($this->serverErrorMessage($e), 500, [], 'INTERNAL_ERROR');
        }
    }

    public function obtener(int $id): void
    {
        try {
            $p = $this->service->obtener($id);
            if ($p === null) {
                Response::error("Paciente $id no encontrado", 404);
                return;
            }
            Response::success($p->toArray());
        } catch (ValidationException $e) {
            Response::error($e->getMessage(), 422, $e->getFields(), 'VALIDATION');
        } catch (PDOException $e) {
            Response::error($this->dbErrorMessage($e), 500, [], 'DB_ERROR');
        } catch (\Throwable $e) {
            Response::error($this->serverErrorMessage($e), 500, [], 'INTERNAL_ERROR');
        }
    }

    public function obrasSociales(): void
    {
        try {
            $obras = $this->service->listarObrasSociales();
            Response::success([
                'obras_sociales' => array_map(fn ($o) => $o->toArray(), $obras),
            ]);
        } catch (PDOException $e) {
            Response::error($this->dbErrorMessage($e), 500, [], 'DB_ERROR');
        } catch (\Throwable $e) {
            Response::error($this->serverErrorMessage($e), 500, [], 'INTERNAL_ERROR');
        }
    }

    private function dbErrorMessage(PDOException $e): string
    {
        $debug = filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN);

        return $debug ? $e->getMessage() : 'Error de base de datos al consultar pacientes';
    }

    private function serverErrorMessage(\Throwable $e): string
    {
        $debug = filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN);

        return $debug ? $e->getMessage() : 'Error interno al consultar pacientes';
    }
}
