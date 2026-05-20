<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ValidationException;
use App\Models\Paciente;
use App\Repositories\PacienteRepository;
use DateTimeImmutable;

final class PacienteService
{
    private const ORDENES_VALIDOS = [
        'apellido_asc', 'apellido_desc',
        'nombres_asc',  'nombres_desc',
        'nro_hc_asc',   'nro_hc_desc',
        'dni_asc',      'dni_desc',
    ];

    private const ORDEN_DEFAULT = 'apellido_asc';

    private const SEXOS_VALIDOS = ['M', 'F', 'X'];

    private const LIMITE_MAX = 100;

    public function __construct(private PacienteRepository $pacienteRepo)
    {
    }

    /**
     * @param array<string,mixed> $filtrosCrudos
     * @return array{pacientes: list<Paciente>, total_encontrados: int, truncado: bool}
     */
    public function buscar(array $filtrosCrudos): array
    {
        $filtros = $this->validarYSanear($filtrosCrudos);
        $limite  = $this->clampLimite($filtrosCrudos['limite'] ?? null);

        $r = $this->pacienteRepo->buscar($filtros, $limite);

        return [
            'pacientes' => $r['pacientes'],
            'total_encontrados' => $r['total_encontrados'],
            'truncado' => $r['total_encontrados'] > $limite,
        ];
    }

    public function obtener(int $id): ?Paciente
    {
        if ($id <= 0) {
            throw new ValidationException('id invalido', ['id' => 'Debe ser un entero positivo']);
        }
        return $this->pacienteRepo->obtener($id);
    }

    /**
     * @return list<\App\Models\ObraSocial>
     */
    public function listarObrasSociales(): array
    {
        return $this->pacienteRepo->listarObrasSociales();
    }

    /**
     * @param array<string,mixed> $crudos
     * @return array<string,mixed>
     */
    private function validarYSanear(array $crudos): array
    {
        $errores = [];
        $out = [];

        if (isset($crudos['q']) && trim((string) $crudos['q']) !== '') {
            $out['q'] = trim((string) $crudos['q']);
        }

        // Strings simples (trim).
        foreach (['nro_hc', 'dni', 'apellido', 'nombres', 'telefono', 'nro_afiliado'] as $k) {
            if (isset($crudos[$k]) && trim((string) $crudos[$k]) !== '') {
                $out[$k] = trim((string) $crudos[$k]);
            }
        }

        // obra_social_id: debe ser entero positivo si esta presente.
        if (isset($crudos['obra_social_id']) && $crudos['obra_social_id'] !== '') {
            if (!is_numeric($crudos['obra_social_id']) || (int) $crudos['obra_social_id'] <= 0) {
                $errores['obra_social_id'] = 'Debe ser un id valido';
            } else {
                $out['obra_social_id'] = (int) $crudos['obra_social_id'];
            }
        }

        // fecha_nacimiento: YYYY-MM-DD.
        if (isset($crudos['fecha_nacimiento']) && trim((string) $crudos['fecha_nacimiento']) !== '') {
            $f = trim((string) $crudos['fecha_nacimiento']);
            $dt = DateTimeImmutable::createFromFormat('Y-m-d', $f);
            if ($dt === false || $dt->format('Y-m-d') !== $f) {
                $errores['fecha_nacimiento'] = 'Formato esperado YYYY-MM-DD';
            } else {
                $out['fecha_nacimiento'] = $f;
            }
        }

        // sexo: ENUM('M','F','X').
        if (isset($crudos['sexo']) && trim((string) $crudos['sexo']) !== '') {
            $s = strtoupper(trim((string) $crudos['sexo']));
            if (!in_array($s, self::SEXOS_VALIDOS, true)) {
                $errores['sexo'] = 'Debe ser M, F o X';
            } else {
                $out['sexo'] = $s;
            }
        }

        // orden: whitelist.
        $orden = isset($crudos['orden']) ? (string) $crudos['orden'] : '';
        $out['orden'] = in_array($orden, self::ORDENES_VALIDOS, true)
            ? $orden
            : self::ORDEN_DEFAULT;

        if (!empty($errores)) {
            throw new ValidationException('Filtros invalidos', $errores);
        }

        return $out;
    }

    private function clampLimite(mixed $crudo): int
    {
        if ($crudo === null || $crudo === '') {
            return self::LIMITE_MAX;
        }
        $n = is_numeric($crudo) ? (int) $crudo : self::LIMITE_MAX;
        return max(1, min(self::LIMITE_MAX, $n));
    }
}
