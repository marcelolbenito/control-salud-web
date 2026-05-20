<?php

declare(strict_types=1);

namespace Tests\Repositories;

use App\Repositories\LoteOsRepository;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

final class LoteOsRepositoryTest extends TestCase
{
    public function testGenerarNumeroIncrementaCorrelativoDelAnio(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetch')->willReturn(['last_num' => '6']);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmt);

        $repo = new LoteOsRepository($pdo);

        $this->assertSame(7, $repo->getNextNumeroForYear(2026));
    }

    public function testGenerarNumeroPrimerLoteDelAnio(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetch')->willReturn(['last_num' => null]);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmt);

        $repo = new LoteOsRepository($pdo);

        $this->assertSame(1, $repo->getNextNumeroForYear(2026));
    }
}
