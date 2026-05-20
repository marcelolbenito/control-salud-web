<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Exceptions\ValidationException;
use App\Models\LoteOs;
use App\Repositories\LoteOsRepository;
use App\Repositories\PagoRepository;
use App\Repositories\PedidoRepository;
use App\Services\AuditoriaService;
use App\Services\FacturacionOsService;
use PDO;
use PHPUnit\Framework\TestCase;

final class FacturacionOsServiceTest extends TestCase
{
    private function makeService(
        ?LoteOsRepository $loteRepo = null,
        ?PedidoRepository $pedidoRepo = null,
        ?PagoRepository $pagoRepo = null,
    ): FacturacionOsService {
        $pdo = $this->createMock(PDO::class);
        $pdo->method('beginTransaction')->willReturn(true);
        $pdo->method('commit')->willReturn(true);
        $pdo->method('rollBack')->willReturn(true);

        return new FacturacionOsService(
            db: $pdo,
            loteRepo: $loteRepo ?? $this->createMock(LoteOsRepository::class),
            pedidoRepo: $pedidoRepo ?? $this->createMock(PedidoRepository::class),
            pagoRepo: $pagoRepo ?? $this->createMock(PagoRepository::class),
            auditoria: $this->createMock(AuditoriaService::class),
        );
    }

    // ============ generar() ============

    public function testGenerarConPedidosElegiblesCreaLoteYPivot(): void
    {
        $loteRepo = $this->createMock(LoteOsRepository::class);
        $loteRepo->method('getNextNumeroForYear')->willReturn(7);
        $loteRepo->method('findPedidosYaEnLote')->willReturn([]);
        $loteRepo->method('insert')->willReturn(99);

        // Simulamos los 2 pedidos elegibles. monto_seguro se recalcula con la vigencia NBU del dia.
        $pedidoRepo = $this->createMock(PedidoRepository::class);
        $pedidoRepo->method('findById')->willReturnMap([
            [42, ['id' => 42, 'obra_social_id' => 1, 'estado_seguro' => 'A', 'monto_seguro' => '300.00']],
            [43, ['id' => 43, 'obra_social_id' => 1, 'estado_seguro' => 'F', 'monto_seguro' => '135.50']],
        ]);
        $pedidoRepo->method('recalcularMontoSeguroAlVuelo')->willReturnMap([
            [42, date('Y-m-d'), 300.00],
            [43, date('Y-m-d'), 135.50],
        ]);

        // Esperamos: 2 inserts en pivot, 1 recalcular.
        $loteRepo->expects($this->exactly(2))->method('insertPivot');
        $loteRepo->expects($this->once())->method('recalcularTotales')->with(99);

        $service = $this->makeService($loteRepo, $pedidoRepo);

        $result = $service->generar([
            'obra_social_id' => 1,
            'fecha_desde' => '2026-05-01',
            'fecha_hasta' => '2026-05-31',
            'pedido_ids' => [42, 43],
        ], 5);

        $this->assertSame(99, $result['id']);
        // Numero deriva del ANIO de fecha_desde (no de hoy): 2026-05-01 -> L-2026-NNNNN
        $this->assertSame('L-2026-00007', $result['numero']);
    }

    public function testGenerarFallaSiAlgunPedidoYaEstaEnOtroLote(): void
    {
        $loteRepo = $this->createMock(LoteOsRepository::class);
        $loteRepo->method('findPedidosYaEnLote')->willReturn([42]);

        $service = $this->makeService($loteRepo);

        $this->expectException(ValidationException::class);
        $this->expectExceptionCode(409);

        $service->generar([
            'obra_social_id' => 1,
            'fecha_desde' => '2026-05-01',
            'fecha_hasta' => '2026-05-31',
            'pedido_ids' => [42, 43],
        ], 5);
    }

    public function testGenerarFallaSiPedidoTieneOtraOs(): void
    {
        $loteRepo = $this->createMock(LoteOsRepository::class);
        $loteRepo->method('findPedidosYaEnLote')->willReturn([]);

        $pedidoRepo = $this->createMock(PedidoRepository::class);
        $pedidoRepo->method('findById')->willReturnMap([
            [42, ['id' => 42, 'obra_social_id' => 2, 'estado_seguro' => 'A', 'monto_seguro' => '300.00']],
        ]);

        $service = $this->makeService($loteRepo, $pedidoRepo);

        $this->expectException(ValidationException::class);
        $this->expectExceptionCode(409);

        $service->generar([
            'obra_social_id' => 1,
            'fecha_desde' => '2026-05-01',
            'fecha_hasta' => '2026-05-31',
            'pedido_ids' => [42],
        ], 5);
    }

    public function testGenerarFallaSiPedidoIdsVacio(): void
    {
        $service = $this->makeService();

        $this->expectException(ValidationException::class);
        $this->expectExceptionCode(422);

        $service->generar([
            'obra_social_id' => 1,
            'fecha_desde' => '2026-05-01',
            'fecha_hasta' => '2026-05-31',
            'pedido_ids' => [],
        ], 5);
    }

    public function testGenerarFallaSiFechaDesdeMayorQueHasta(): void
    {
        $service = $this->makeService();

        $this->expectException(ValidationException::class);
        $this->expectExceptionCode(422);

        $service->generar([
            'obra_social_id' => 1,
            'fecha_desde' => '2026-05-31',
            'fecha_hasta' => '2026-05-01',
            'pedido_ids' => [42],
        ], 5);
    }

    // ============ quitarPedido() ============

    public function testQuitarPedidoRecalculaMontoYCantidad(): void
    {
        $loteRepo = $this->createMock(LoteOsRepository::class);
        $loteRepo->method('findById')->willReturn(new LoteOs(
            id: 7, numero: 'L-2026-00007', obraSocialId: 1,
            fechaDesde: '2026-05-01', fechaHasta: '2026-05-31',
            estado: 'abierto', montoTotal: 435.50, cantidadPedidos: 2,
        ));
        $loteRepo->method('deletePivot')->willReturn(true);
        $loteRepo->expects($this->once())->method('recalcularTotales')->with(7);

        $service = $this->makeService($loteRepo);

        $service->quitarPedido(7, 42, 5);
        $this->assertTrue(true); // Si llego aca, paso
    }

    public function testQuitarPedidoFallaSiLoteNoEstaAbierto(): void
    {
        $loteRepo = $this->createMock(LoteOsRepository::class);
        $loteRepo->method('findById')->willReturn(new LoteOs(
            id: 7, numero: 'L-2026-00007', obraSocialId: 1,
            fechaDesde: '2026-05-01', fechaHasta: '2026-05-31',
            estado: 'cobrado', montoTotal: 435.50, cantidadPedidos: 2,
        ));

        $service = $this->makeService($loteRepo);

        $this->expectException(ValidationException::class);
        $this->expectExceptionCode(409);

        $service->quitarPedido(7, 42, 5);
    }

    public function testQuitarPedidoFallaSiPedidoNoEstaEnElLote(): void
    {
        $loteRepo = $this->createMock(LoteOsRepository::class);
        $loteRepo->method('findById')->willReturn(new LoteOs(
            id: 7, numero: 'L-2026-00007', obraSocialId: 1,
            fechaDesde: '2026-05-01', fechaHasta: '2026-05-31',
            estado: 'abierto', montoTotal: 435.50, cantidadPedidos: 2,
        ));
        $loteRepo->method('deletePivot')->willReturn(false);

        $service = $this->makeService($loteRepo);

        $this->expectException(ValidationException::class);
        $this->expectExceptionCode(404);

        $service->quitarPedido(7, 999, 5);
    }

    // ============ anular() ============

    public function testAnularLoteAbiertoMarcaEstadoYRegistraMotivo(): void
    {
        $loteRepo = $this->createMock(LoteOsRepository::class);
        $loteRepo->method('findById')->willReturn(new LoteOs(
            id: 7, numero: 'L-2026-00007', obraSocialId: 1,
            fechaDesde: '2026-05-01', fechaHasta: '2026-05-31',
            estado: 'abierto', montoTotal: 435.50, cantidadPedidos: 2,
        ));
        $loteRepo->expects($this->once())
                 ->method('marcarAnulado')
                 ->with(7, 'Error en filtros', 5)
                 ->willReturn(true);

        $service = $this->makeService($loteRepo);

        $service->anular(7, 'Error en filtros', 5);
        $this->assertTrue(true);
    }

    public function testAnularFallaSiYaEstaCobrado(): void
    {
        $loteRepo = $this->createMock(LoteOsRepository::class);
        $loteRepo->method('findById')->willReturn(new LoteOs(
            id: 7, numero: 'L-2026-00007', obraSocialId: 1,
            fechaDesde: '2026-05-01', fechaHasta: '2026-05-31',
            estado: 'cobrado', montoTotal: 435.50, cantidadPedidos: 2,
        ));

        $service = $this->makeService($loteRepo);

        $this->expectException(ValidationException::class);
        $this->expectExceptionCode(409);

        $service->anular(7, 'Lo que sea', 5);
    }

    public function testAnularFallaSiMotivoVacio(): void
    {
        $service = $this->makeService();

        $this->expectException(ValidationException::class);
        $this->expectExceptionCode(422);

        $service->anular(7, '', 5);
    }

    // ============ cobrar() ============

    public function testCobrarGeneraNPagosYActualizaPedidos(): void
    {
        $loteRepo = $this->createMock(LoteOsRepository::class);
        $loteRepo->method('findById')->willReturn(new LoteOs(
            id: 7, numero: 'L-2026-00007', obraSocialId: 1,
            fechaDesde: '2026-05-01', fechaHasta: '2026-05-31',
            estado: 'abierto', montoTotal: 435.50, cantidadPedidos: 2,
        ));
        $loteRepo->method('findPedidosDelLote')->willReturn([
            ['pedido_id' => 42, 'monto_seguro_snapshot' => '300.00'],
            ['pedido_id' => 43, 'monto_seguro_snapshot' => '135.50'],
        ]);
        $loteRepo->expects($this->once())->method('marcarCobrado')->with(7, 5)->willReturn(true);

        $pedidoRepo = $this->createMock(PedidoRepository::class);
        $pedidoRepo->expects($this->exactly(2))
                   ->method('cambiarEstadoFacturacion')
                   ->willReturn(true);

        $pagoRepo = $this->createMock(PagoRepository::class);
        $pagoRepo->expects($this->exactly(2))->method('crear')->willReturn(101);

        $service = $this->makeService($loteRepo, $pedidoRepo, $pagoRepo);

        $service->cobrar(7, [
            'fecha_pago' => '2026-05-09',
            'medio_pago' => 'transferencia',
            'referencia' => 'TRX-12345',
        ], 5);
        $this->assertTrue(true);
    }

    public function testCobrarFallaSiLoteVacio(): void
    {
        $loteRepo = $this->createMock(LoteOsRepository::class);
        $loteRepo->method('findById')->willReturn(new LoteOs(
            id: 7, numero: 'L-2026-00007', obraSocialId: 1,
            fechaDesde: '2026-05-01', fechaHasta: '2026-05-31',
            estado: 'abierto', montoTotal: 0.0, cantidadPedidos: 0,
        ));
        $loteRepo->method('findPedidosDelLote')->willReturn([]);

        $service = $this->makeService($loteRepo);

        $this->expectException(ValidationException::class);
        $this->expectExceptionCode(409);

        $service->cobrar(7, [
            'fecha_pago' => '2026-05-09',
            'medio_pago' => 'transferencia',
        ], 5);
    }

    public function testCobrarFallaSiYaCobradoOAnulado(): void
    {
        $loteRepo = $this->createMock(LoteOsRepository::class);
        $loteRepo->method('findById')->willReturn(new LoteOs(
            id: 7, numero: 'L-2026-00007', obraSocialId: 1,
            fechaDesde: '2026-05-01', fechaHasta: '2026-05-31',
            estado: 'cobrado', montoTotal: 435.50, cantidadPedidos: 2,
        ));

        $service = $this->makeService($loteRepo);

        $this->expectException(ValidationException::class);
        $this->expectExceptionCode(409);

        $service->cobrar(7, [
            'fecha_pago' => '2026-05-09',
            'medio_pago' => 'transferencia',
        ], 5);
    }

    public function testCobrarFallaConMedioPagoInvalido(): void
    {
        $service = $this->makeService();

        $this->expectException(ValidationException::class);
        $this->expectExceptionCode(422);

        $service->cobrar(7, [
            'fecha_pago' => '2026-05-09',
            'medio_pago' => 'bitcoin',
        ], 5);
    }
}
