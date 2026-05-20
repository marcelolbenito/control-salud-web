<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Repositories\DeterminacionRepository;
use App\Repositories\NbuDeterminacionRepository;
use App\Repositories\NbuValorOsRepository;
use App\Services\AranceladorService;
use PHPUnit\Framework\TestCase;

final class AranceladorServiceTest extends TestCase
{
    private function makeService(
        ?float $unidades,
        ?float $valor,
        ?float $precioParticular,
    ): AranceladorService {
        $nbuDet = $this->createMock(NbuDeterminacionRepository::class);
        $nbuDet->method('findUnidades')->willReturn($unidades);

        $nbuOs = $this->createMock(NbuValorOsRepository::class);
        $nbuOs->method('findValorAt')->willReturn($valor);

        $detRepo = $this->createMock(DeterminacionRepository::class);
        $detRepo->method('findActivasByIds')->willReturn(
            $precioParticular !== null
                ? [['id' => 1, 'precio' => (string) $precioParticular]]
                : [['id' => 1, 'precio' => null]]
        );

        return new AranceladorService($nbuDet, $nbuOs, $detRepo);
    }

    public function testCalculaNbuPorValorOsCuandoTodoCargado(): void
    {
        $svc = $this->makeService(unidades: 2.5, valor: 120.0, precioParticular: null);
        $r = $svc->calcularMontoItem(1, 99);

        $this->assertSame(2.5, $r->unidades);
        $this->assertSame(120.0, $r->valorUnit);
        $this->assertSame(300.00, $r->total);
        $this->assertSame('nbu_os', $r->origen);
    }

    public function testRedondeaADosDecimales(): void
    {
        $svc = $this->makeService(unidades: 2.501, valor: 120.0, precioParticular: null);
        $r = $svc->calcularMontoItem(1, 99);

        $this->assertSame(300.12, $r->total);
    }

    public function testSinValorOsDevuelveSinArancel(): void
    {
        $svc = $this->makeService(unidades: 2.5, valor: null, precioParticular: null);
        $r = $svc->calcularMontoItem(1, 99);

        $this->assertNull($r->unidades);
        $this->assertNull($r->valorUnit);
        $this->assertSame(0.00, $r->total);
        $this->assertSame('sin_arancel', $r->origen);
    }

    public function testSinUnidadesNbuDevuelveSinArancel(): void
    {
        $svc = $this->makeService(unidades: null, valor: 120.0, precioParticular: null);
        $r = $svc->calcularMontoItem(1, 99);

        $this->assertSame(0.00, $r->total);
        $this->assertSame('sin_arancel', $r->origen);
    }

    public function testSinObraSocialDevuelvePrecioParticular(): void
    {
        $svc = $this->makeService(unidades: null, valor: null, precioParticular: 500.0);
        $r = $svc->calcularMontoItem(1, null);

        $this->assertNull($r->unidades);
        $this->assertSame(500.00, $r->total);
        $this->assertSame('precio_particular', $r->origen);
    }

    public function testSinObraSocialYSinPrecioCaeACero(): void
    {
        $svc = $this->makeService(unidades: null, valor: null, precioParticular: null);
        $r = $svc->calcularMontoItem(1, null);

        $this->assertSame(0.00, $r->total);
        $this->assertSame('precio_particular', $r->origen);
    }
}
