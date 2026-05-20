<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\ValidationException;
use App\Helpers\Response;
use App\Services\MedicoService;
use PDOException;

final class MedicoController
{
    public function __construct(private MedicoService $service)
    {
    }

    /**
     * @param array<string,mixed> $query
     */
    public function buscar(array $query): void
    {
        try {
            $medicos = $this->service->buscar($query);
            Response::success([
                'medicos' => array_map(fn ($m) => $m->toArray(), $medicos),
            ]);
        } catch (ValidationException $e) {
            Response::error($e->getMessage(), 422, $e->getFields(), 'VALIDATION');
        } catch (PDOException) {
            Response::error('No se pudo conectar con la base de datos', 500, [], 'DB_ERROR');
        }
    }

    public function obtener(int $id): void
    {
        try {
            $m = $this->service->obtener($id);
            if ($m === null) {
                Response::error("Medico $id no encontrado", 404);
                return;
            }
            Response::success($m->toArray());
        } catch (ValidationException $e) {
            Response::error($e->getMessage(), 422, $e->getFields(), 'VALIDATION');
        } catch (PDOException) {
            Response::error('No se pudo conectar con la base de datos', 500, [], 'DB_ERROR');
        }
    }

    public function especialidades(): void
    {
        try {
            Response::success(['especialidades' => $this->service->listarEspecialidades()]);
        } catch (PDOException) {
            Response::error('No se pudo conectar con la base de datos', 500, [], 'DB_ERROR');
        }
    }
}
