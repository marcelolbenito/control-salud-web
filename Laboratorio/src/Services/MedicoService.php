<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ValidationException;
use App\Models\Medico;
use App\Repositories\MedicoRepository;

final class MedicoService
{
    private const ORDENES_VALIDOS = ['apellido_asc', 'apellido_desc', 'matricula_asc', 'especialidad_asc'];
    private const ORDEN_DEFAULT   = 'apellido_asc';
    private const LIMITE_MAX      = 100;

    public function __construct(private MedicoRepository $repo)
    {
    }

    /**
     * @param array<string,mixed> $crudos
     * @return list<Medico>
     */
    public function buscar(array $crudos): array
    {
        $filtros = [];
        if (isset($crudos['q']) && trim((string) $crudos['q']) !== '') {
            $filtros['q'] = trim((string) $crudos['q']);
        }
        if (isset($crudos['especialidad']) && trim((string) $crudos['especialidad']) !== '') {
            $filtros['especialidad'] = trim((string) $crudos['especialidad']);
        }
        if (!empty($crudos['incluir_inactivos'])) {
            $filtros['incluir_inactivos'] = true;
        }
        $orden = isset($crudos['orden']) ? (string) $crudos['orden'] : '';
        $filtros['orden'] = in_array($orden, self::ORDENES_VALIDOS, true) ? $orden : self::ORDEN_DEFAULT;

        $limite = self::LIMITE_MAX;
        if (isset($crudos['limite']) && is_numeric($crudos['limite'])) {
            $limite = max(1, min(self::LIMITE_MAX, (int) $crudos['limite']));
        }

        return $this->repo->buscar($filtros, $limite);
    }

    public function obtener(int $id): ?Medico
    {
        if ($id <= 0) {
            throw new ValidationException('id invalido', ['id' => 'Debe ser un entero positivo']);
        }
        return $this->repo->obtener($id);
    }

    /**
     * @return list<string>
     */
    public function listarEspecialidades(): array
    {
        return $this->repo->listarEspecialidades();
    }
}
