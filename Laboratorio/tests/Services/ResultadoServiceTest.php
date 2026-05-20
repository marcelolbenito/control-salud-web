<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Exceptions\DomainException;
use App\Exceptions\ValidationException;
use App\Repositories\PedidoItemRepository;
use App\Repositories\PedidoRepository;
use App\Repositories\ResultadoRepository;
use App\Repositories\ValorReferenciaRepository;
use App\Services\AuditoriaService;
use App\Services\ResultadoService;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * SP9: eliminados los tests de validar() y rectificar() porque esos metodos
 * se removieron del service. Lo que queda cubre el flujo unico paso
 * (cargar = listo, re-escritura libre, transicion a pedido.completo).
 */
final class ResultadoServiceTest extends TestCase
{
    private function makeService(
        ?ResultadoRepository $resultadoRepo = null,
        ?PedidoItemRepository $itemRepo = null,
        ?PedidoRepository $pedidoRepo = null,
        ?ValorReferenciaRepository $rangoRepo = null,
    ): ResultadoService {
        $db = $this->createMock(PDO::class);
        $db->method('beginTransaction')->willReturn(true);
        $db->method('commit')->willReturn(true);
        $db->method('rollBack')->willReturn(true);
        $db->method('inTransaction')->willReturn(true);

        return new ResultadoService(
            db: $db,
            resultadoRepo: $resultadoRepo ?? $this->createMock(ResultadoRepository::class),
            itemRepo: $itemRepo ?? $this->createMock(PedidoItemRepository::class),
            pedidoRepo: $pedidoRepo ?? $this->createMock(PedidoRepository::class),
            rangoRepo: $rangoRepo ?? $this->createMock(ValorReferenciaRepository::class),
            auditoria: $this->createMock(AuditoriaService::class),
        );
    }

    /**
     * @param array<string,mixed> $overrides
     * @return array<string,mixed>
     */
    private function itemCtxFake(array $overrides = []): array
    {
        return array_merge([
            'id'                       => 10,
            'pedido_id'                => 1,
            'determinacion_id'         => 5,
            'perfil_id'                => null,
            'item_estado'              => 'pendiente',
            'determinacion_codigo'     => 'GLU',
            'determinacion_nombre'     => 'Glucemia',
            'determinacion_unidad'     => 'mg/dL',
            'tipo_resultado'           => 'numerico',
            'decimales'                => 2,
            'valor_critico_min'        => 40,
            'valor_critico_max'        => 500,
            'pedido_estado'            => 'en_proceso',
            'snapshot_paciente'        => [
                'sexo'      => 'M',
                'fecha_nac' => '1990-01-01',
            ],
            'fecha_extraccion'         => '2026-05-06 10:00:00',
        ], $overrides);
    }

    /**
     * @param array<string,mixed> $overrides
     * @return array<string,mixed>
     */
    private function resultadoFake(array $overrides = []): array
    {
        return array_merge([
            'id'                    => 100,
            'pedido_item_id'        => 10,
            'valor_numerico'        => 95,
            'valor_texto'           => null,
            'unidad'                => 'mg/dL',
            'es_anormal'            => 0,
            'es_critico'            => 0,
            'estado'                => 'cargado',
            'valor_referencia_min'  => 70,
            'valor_referencia_max'  => 110,
            'texto_referencia'      => null,
            'observaciones'         => null,
            'usuario_carga_id'      => 1,
            'fecha_carga'           => '2026-05-06 10:00:00',
        ], $overrides);
    }

    public function testCargarFallaSinPedidoItemId(): void
    {
        $service = $this->makeService();

        try {
            $service->cargar(['valor_numerico' => 100], 1);
            $this->fail('Esperaba ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('pedido_item_id', $e->getFields());
        }
    }

    public function testCargarFallaSinValor(): void
    {
        $service = $this->makeService();

        try {
            $service->cargar(['pedido_item_id' => 10], 1);
            $this->fail('Esperaba ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('valor', $e->getFields());
        }
    }

    public function testCargarFallaSiItemAnulado(): void
    {
        $itemRepo = $this->createMock(PedidoItemRepository::class);
        $itemRepo->method('findByIdConContexto')->willReturn(
            $this->itemCtxFake(['item_estado' => 'anulado'])
        );

        $service = $this->makeService(null, $itemRepo);

        $this->expectException(DomainException::class);
        $service->cargar([
            'pedido_item_id' => 10,
            'valor_numerico' => 100,
        ], 1);
    }

    public function testCargarMarcaAnormalCuandoExcedeRango(): void
    {
        $itemRepo = $this->createMock(PedidoItemRepository::class);
        $itemRepo->method('findByIdConContexto')->willReturn($this->itemCtxFake());
        $itemRepo->method('listarEstadosByPedidoId')->willReturn(['cargado']);

        $rangoRepo = $this->createMock(ValorReferenciaRepository::class);
        $rangoRepo->method('findRangoAplicable')->willReturn([
            'valor_min' => 70, 'valor_max' => 110, 'texto_referencia' => null,
        ]);

        $resRepo = $this->createMock(ResultadoRepository::class);
        $resRepo->method('findByPedidoItemId')->willReturn(null);
        $resRepo->expects($this->once())->method('insert')->willReturn(100);

        $service = $this->makeService($resRepo, $itemRepo, null, $rangoRepo);

        $r = $service->cargar([
            'pedido_item_id' => 10,
            'valor_numerico' => 200,
        ], 1);

        $this->assertTrue($r['es_anormal']);
        $this->assertFalse($r['es_critico']);
    }

    public function testCargarMarcaCriticoYActualizaPedido(): void
    {
        $itemRepo = $this->createMock(PedidoItemRepository::class);
        $itemRepo->method('findByIdConContexto')->willReturn($this->itemCtxFake());
        $itemRepo->method('listarEstadosByPedidoId')->willReturn(['cargado']);

        $rangoRepo = $this->createMock(ValorReferenciaRepository::class);
        $rangoRepo->method('findRangoAplicable')->willReturn([
            'valor_min' => 70, 'valor_max' => 110, 'texto_referencia' => null,
        ]);

        $resRepo = $this->createMock(ResultadoRepository::class);
        $resRepo->method('findByPedidoItemId')->willReturn(null);
        $resRepo->method('insert')->willReturn(100);

        $pedidoRepo = $this->createMock(PedidoRepository::class);
        $pedidoRepo->expects($this->once())
            ->method('updateEsCritico')
            ->with(1, true)
            ->willReturn(true);

        $service = $this->makeService($resRepo, $itemRepo, $pedidoRepo, $rangoRepo);

        $r = $service->cargar([
            'pedido_item_id' => 10,
            'valor_numerico' => 700,
        ], 1);

        $this->assertTrue($r['es_critico']);
    }

    public function testCargarSobreCargadoExistenteHaceUpdate(): void
    {
        $itemRepo = $this->createMock(PedidoItemRepository::class);
        $itemRepo->method('findByIdConContexto')->willReturn(
            $this->itemCtxFake(['item_estado' => 'cargado'])
        );
        $itemRepo->method('listarEstadosByPedidoId')->willReturn(['cargado']);

        $rangoRepo = $this->createMock(ValorReferenciaRepository::class);
        $rangoRepo->method('findRangoAplicable')->willReturn(null);

        $resRepo = $this->createMock(ResultadoRepository::class);
        $resRepo->method('findByPedidoItemId')->willReturn($this->resultadoFake());
        $resRepo->expects($this->never())->method('insert');
        $resRepo->expects($this->once())->method('update')->willReturn(true);

        $service = $this->makeService($resRepo, $itemRepo, null, $rangoRepo);

        $r = $service->cargar([
            'pedido_item_id' => 10,
            'valor_numerico' => 105,
        ], 1);

        $this->assertSame(100, $r['id']);
        $this->assertSame('cargado', $r['estado']);
    }

    public function testCargarTransicionaPedidoACompletoSiTodosCargados(): void
    {
        $itemRepo = $this->createMock(PedidoItemRepository::class);
        $itemRepo->method('findByIdConContexto')->willReturn($this->itemCtxFake());
        $itemRepo->method('listarEstadosByPedidoId')->willReturn(['cargado', 'cargado']);

        $resRepo = $this->createMock(ResultadoRepository::class);
        $resRepo->method('findByPedidoItemId')->willReturn(null);
        $resRepo->method('insert')->willReturn(100);

        $pedidoRepo = $this->createMock(PedidoRepository::class);
        $pedidoRepo->expects($this->once())
            ->method('updateEstado')
            ->with(1, 'completo')
            ->willReturn(true);

        $service = $this->makeService($resRepo, $itemRepo, $pedidoRepo);

        $r = $service->cargar([
            'pedido_item_id' => 10,
            'valor_numerico' => 95,
        ], 1);

        $this->assertTrue($r['pedido_completado']);
    }

    public function testCargarNoTransicionaPedidoSiAlgunoSiguePendiente(): void
    {
        $itemRepo = $this->createMock(PedidoItemRepository::class);
        $itemRepo->method('findByIdConContexto')->willReturn($this->itemCtxFake());
        $itemRepo->method('listarEstadosByPedidoId')->willReturn(['cargado', 'pendiente']);

        $resRepo = $this->createMock(ResultadoRepository::class);
        $resRepo->method('findByPedidoItemId')->willReturn(null);
        $resRepo->method('insert')->willReturn(100);

        $pedidoRepo = $this->createMock(PedidoRepository::class);
        $pedidoRepo->expects($this->never())->method('updateEstado');

        $service = $this->makeService($resRepo, $itemRepo, $pedidoRepo);

        $r = $service->cargar([
            'pedido_item_id' => 10,
            'valor_numerico' => 95,
        ], 1);

        $this->assertFalse($r['pedido_completado']);
    }
}
