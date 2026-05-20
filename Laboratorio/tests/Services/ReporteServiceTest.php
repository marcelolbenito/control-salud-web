<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Exceptions\ValidationException;
use App\Repositories\ReporteRepository;
use App\Services\ReporteService;
use PHPUnit\Framework\TestCase;

final class ReporteServiceTest extends TestCase
{
    private function makeService(?ReporteRepository $repo = null): ReporteService
    {
        return new ReporteService(
            $repo ?? $this->createMock(ReporteRepository::class)
        );
    }

    public function testDelMesUsaMesActualSiNoSeIndica(): void
    {
        $repo = $this->createMock(ReporteRepository::class);
        $repo->expects($this->once())
            ->method('recaudacionDelMes')
            ->with($this->matchesRegularExpression('/^\d{4}-(0[1-9]|1[0-2])$/'))
            ->willReturn([
                'mes' => '2026-05',
                'particular' => 0.0,
                'obra_social' => 0.0,
                'total' => 0.0,
                'cantidad_pagos' => 0,
            ]);

        $service = $this->makeService($repo);
        $r = $service->delMes();
        $this->assertSame(0.0, $r['total']);
    }

    public function testDelMesFallaConFormatoInvalido(): void
    {
        $service = $this->makeService();

        try {
            $service->delMes('2026/05');
            $this->fail('Esperaba ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('mes', $e->getFields());
        }
    }

    public function testDelMesFallaConMesInexistente(): void
    {
        $service = $this->makeService();

        try {
            $service->delMes('2026-13');
            $this->fail('Esperaba ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('mes', $e->getFields());
        }
    }

    public function testDelMesPasaParametroAlRepo(): void
    {
        $repo = $this->createMock(ReporteRepository::class);
        $repo->expects($this->once())
            ->method('recaudacionDelMes')
            ->with('2026-04')
            ->willReturn([
                'mes' => '2026-04',
                'particular' => 12000.50,
                'obra_social' => 8000.0,
                'total' => 20000.50,
                'cantidad_pagos' => 7,
            ]);

        $service = $this->makeService($repo);
        $r = $service->delMes('2026-04');

        $this->assertSame('2026-04', $r['mes']);
        $this->assertEquals(20000.50, $r['total']);
    }

    public function testUltimosMesesRecortaCantidad(): void
    {
        $repo = $this->createMock(ReporteRepository::class);
        $repo->expects($this->once())
            ->method('recaudacionPorMes')
            ->with(
                $this->matchesRegularExpression('/^\d{4}-(0[1-9]|1[0-2])$/'),
                $this->matchesRegularExpression('/^\d{4}-(0[1-9]|1[0-2])$/'),
            )
            ->willReturn([]);

        $service = $this->makeService($repo);
        $r = $service->ultimosMeses(99); // se recorta a 36
        $this->assertSame([], $r);
    }

    public function testUltimosMesesDevuelveListaDelRepo(): void
    {
        $sample = [
            ['mes' => '2026-05', 'particular' => 5000.0, 'obra_social' => 3000.0, 'total' => 8000.0, 'cantidad_pagos' => 4],
            ['mes' => '2026-04', 'particular' => 4500.0, 'obra_social' => 2500.0, 'total' => 7000.0, 'cantidad_pagos' => 3],
        ];

        $repo = $this->createMock(ReporteRepository::class);
        $repo->method('recaudacionPorMes')->willReturn($sample);

        $service = $this->makeService($repo);
        $r = $service->ultimosMeses(2);

        $this->assertCount(2, $r);
        $this->assertSame('2026-05', $r[0]['mes']);
    }
}
