<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ValidationException;
use App\Repositories\ValorReferenciaRepository;

/**
 * ABM de valores de referencia (rangos) de una determinacion.
 * La evaluacion de resultados sigue usando ValorReferenciaRepository::findRangoAplicable.
 */
final class ValorReferenciaService
{
    private const SEXOS_VALIDOS = ['M', 'F', 'ambos'];

    public function __construct(
        private ValorReferenciaRepository $repo,
        private AuditoriaService $auditoria,
    ) {
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function listar(int $determinacionId): array
    {
        if ($determinacionId <= 0) {
            throw new ValidationException('Determinacion invalida', ['determinacion_id' => 'Requerido']);
        }
        return $this->repo->listarPorDeterminacion($determinacionId);
    }

    /**
     * @param array<string,mixed> $input
     */
    public function crear(array $input, ?int $usuarioId): int
    {
        $datos = $this->validar($input, esNuevo: true);
        $id = $this->repo->crear($datos);
        $this->auditoria->log(
            usuarioId: $usuarioId,
            accion: 'crear',
            tablaAfectada: 'lab_valores_referencia',
            registroId: $id,
            valorAnterior: null,
            valorNuevo: $datos,
            contexto: 'Alta de valor de referencia',
        );
        return $id;
    }

    /**
     * @param array<string,mixed> $input
     */
    public function actualizar(int $id, array $input, ?int $usuarioId): void
    {
        $anterior = $this->repo->findById($id);
        if ($anterior === null) {
            throw new ValidationException('Rango no encontrado', ['id' => "No existe el rango $id"], 404);
        }
        $datos = $this->validar($input, esNuevo: false);
        $this->repo->actualizar($id, $datos);
        $this->auditoria->log(
            usuarioId: $usuarioId,
            accion: 'actualizar',
            tablaAfectada: 'lab_valores_referencia',
            registroId: $id,
            valorAnterior: $anterior,
            valorNuevo: $datos,
            contexto: 'Edicion de valor de referencia',
        );
    }

    public function eliminar(int $id, ?int $usuarioId): void
    {
        $anterior = $this->repo->findById($id);
        if ($anterior === null) {
            throw new ValidationException('Rango no encontrado', ['id' => "No existe el rango $id"], 404);
        }
        $this->repo->softDelete($id);
        $this->auditoria->log(
            usuarioId: $usuarioId,
            accion: 'eliminar',
            tablaAfectada: 'lab_valores_referencia',
            registroId: $id,
            valorAnterior: $anterior,
            valorNuevo: null,
            contexto: 'Baja de valor de referencia',
        );
    }

    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public function validar(array $input, bool $esNuevo): array
    {
        $errores = [];

        $detId = (int) ($input['determinacion_id'] ?? 0);
        if ($esNuevo && $detId <= 0) {
            $errores['determinacion_id'] = 'Requerido';
        }

        $sexo = (string) ($input['sexo'] ?? 'ambos');
        if (!in_array($sexo, self::SEXOS_VALIDOS, true)) {
            $errores['sexo'] = 'Debe ser M, F o ambos';
        }

        $eMin = $this->intOrNull($input['edad_min_dias'] ?? null);
        $eMax = $this->intOrNull($input['edad_max_dias'] ?? null);
        if ($eMin !== null && $eMin < 0) {
            $errores['edad_min_dias'] = 'No puede ser negativa';
        }
        if ($eMax !== null && $eMax < 0) {
            $errores['edad_max_dias'] = 'No puede ser negativa';
        }
        if ($eMin !== null && $eMax !== null && $eMin > $eMax) {
            $errores['edad_max_dias'] = 'La edad maxima debe ser >= a la minima';
        }

        $vMin = $this->numOrNull($input['valor_min'] ?? null);
        $vMax = $this->numOrNull($input['valor_max'] ?? null);
        if (isset($input['valor_min']) && $input['valor_min'] !== '' && $vMin === null) {
            $errores['valor_min'] = 'Debe ser numerico';
        }
        if (isset($input['valor_max']) && $input['valor_max'] !== '' && $vMax === null) {
            $errores['valor_max'] = 'Debe ser numerico';
        }
        if ($vMin !== null && $vMax !== null && $vMin > $vMax) {
            $errores['valor_max'] = 'El valor maximo debe ser >= al minimo';
        }

        $texto = trim((string) ($input['texto_referencia'] ?? ''));
        if ($vMin === null && $vMax === null && $texto === '') {
            $errores['valor_min'] = 'Cargá un rango (mín/máx) o un texto de referencia';
        }

        if ($errores !== []) {
            throw new ValidationException('Errores de validacion', $errores);
        }

        return [
            'determinacion_id' => $detId,
            'sexo'             => $sexo,
            'edad_min_dias'    => $eMin,
            'edad_max_dias'    => $eMax,
            'valor_min'        => $vMin,
            'valor_max'        => $vMax,
            'texto_referencia' => $texto !== '' ? $texto : null,
            'observaciones'    => trim((string) ($input['observaciones'] ?? '')) ?: null,
        ];
    }

    private function intOrNull(mixed $v): ?int
    {
        return ($v === null || $v === '') ? null : (int) $v;
    }

    private function numOrNull(mixed $v): ?float
    {
        if ($v === null || $v === '' || !is_numeric($v)) {
            return null;
        }
        return (float) $v;
    }
}
