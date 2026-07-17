<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class DashboardMetricsTest extends TestCase
{
    public function testCalculaCo2EvitadoComFatorAcademico(): void
    {
        self::assertSame(2500.0, DashboardMetrics::avoidedCo2(1000));
        self::assertSame(0.0, DashboardMetrics::avoidedCo2(-10));
    }

    public function testCriaSerieDosUltimosSeisMesesEmOrdemCronologica(): void
    {
        $series = DashboardMetrics::emptyMonthlyEvolution(new DateTimeImmutable('2026-07-16'));

        self::assertSame(['2026-02', '2026-03', '2026-04', '2026-05', '2026-06', '2026-07'], array_keys($series));
        self::assertSame(['Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul'], array_column($series, 'label'));
        self::assertSame([0, 0, 0, 0, 0, 0], array_column($series, 'negotiations'));
    }
}
