<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Response;
use App\Repositories\NbuDeterminacionRepository;
use App\Repositories\NbuValorOsRepository;
use App\Repositories\PedidoRepository;
use App\Services\AuditoriaService;

final class AranceladorController
{
    public function __construct(
        private NbuDeterminacionRepository $nbuDetRepo,
        private NbuValorOsRepository $nbuOsRepo,
        private PedidoRepository $pedidoRepo,
        private AuditoriaService $auditoria,
    ) {
    }

    public function listarNbuDeterminaciones(): void
    {
        Response::success($this->nbuDetRepo->listAll());
    }

    public function upsertNbuDeterminacion(?int $usuarioId): void
    {
        $body = json_decode((string) file_get_contents('php://input'), true) ?? [];
        $detId    = isset($body['determinacion_id']) ? (int) $body['determinacion_id'] : 0;
        $unidades = isset($body['unidades']) ? (float) $body['unidades'] : -1.0;

        if ($detId <= 0) {
            Response::error('determinacion_id invalido', 422, ['determinacion_id' => 'Debe ser > 0']);
            return;
        }
        if ($unidades < 0) {
            Response::error('unidades invalidas', 422, ['unidades' => 'No puede ser negativo']);
            return;
        }

        $previo = $this->nbuDetRepo->findUnidades($detId);
        $this->nbuDetRepo->upsert($detId, round($unidades, 2));

        $this->auditoria->log(
            usuarioId: $usuarioId,
            accion: $previo === null ? 'crear' : 'actualizar',
            tablaAfectada: 'lab_nbu_determinaciones',
            registroId: $detId,
            valorAnterior: $previo !== null ? ['unidades' => $previo] : null,
            valorNuevo: ['unidades' => round($unidades, 2)],
            contexto: 'Upsert NBU por determinacion',
        );

        Response::success(['determinacion_id' => $detId, 'unidades' => round($unidades, 2)]);
    }

    public function listarValoresOs(): void
    {
        Response::success($this->nbuOsRepo->listAllVigentes());
    }

    public function listarVigencias(int $osId): void
    {
        if ($osId <= 0) {
            Response::error('os_id invalido', 422, ['os_id' => 'Debe ser > 0']);
            return;
        }
        Response::success($this->nbuOsRepo->listarVigencias($osId));
    }

    public function crearVigenciaOs(?int $usuarioId): void
    {
        $body = json_decode((string) file_get_contents('php://input'), true) ?? [];
        $osId       = isset($body['obra_social_id']) ? (int) $body['obra_social_id'] : 0;
        $valor      = isset($body['valor_unitario']) ? (float) $body['valor_unitario'] : -1.0;
        $fechaDesde = isset($body['fecha_desde']) ? (string) $body['fecha_desde'] : '';

        $errors = [];
        if ($osId <= 0) {
            $errors['obra_social_id'] = 'Debe ser > 0';
        }
        if ($valor < 0) {
            $errors['valor_unitario'] = 'No puede ser negativo';
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaDesde)) {
            $errors['fecha_desde'] = 'Formato YYYY-MM-DD requerido';
        }
        if ($errors !== []) {
            Response::error('Errores de validacion', 422, $errors);
            return;
        }

        $vigenciaId = $this->nbuOsRepo->crearVigencia($osId, round($valor, 2), $fechaDesde);
        $repreciados = $this->pedidoRepo->reprecioPorVigenciaOs($osId);

        $this->auditoria->log(
            usuarioId: $usuarioId,
            accion: 'crear',
            tablaAfectada: 'lab_nbu_valores_os',
            registroId: $vigenciaId,
            valorNuevo: [
                'obra_social_id' => $osId,
                'valor_unitario' => round($valor, 2),
                'fecha_desde'    => $fechaDesde,
                'pedidos_repreciados' => $repreciados,
            ],
            contexto: 'Crear vigencia NBU por obra social',
        );

        Response::success([
            'id'                  => $vigenciaId,
            'obra_social_id'      => $osId,
            'valor_unitario'      => round($valor, 2),
            'fecha_desde'         => $fechaDesde,
            'pedidos_repreciados' => $repreciados,
        ]);
    }
}
