<?php

declare(strict_types=1);

final class DashboardMetrics
{
    private const CO2_FACTOR = 2.5;

    public static function avoidedCo2(float $reusedKilograms): float
    {
        return max(0.0, $reusedKilograms) * self::CO2_FACTOR;
    }

    public static function emptyMonthlyEvolution(?DateTimeImmutable $reference = null): array
    {
        $months = [];
        $cursor = ($reference ?? new DateTimeImmutable())->modify('first day of this month');
        $labels = [1 => 'Jan', 2 => 'Fev', 3 => 'Mar', 4 => 'Abr', 5 => 'Mai', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago', 9 => 'Set', 10 => 'Out', 11 => 'Nov', 12 => 'Dez'];

        for ($offset = 5; $offset >= 0; $offset--) {
            $month = $cursor->modify("-{$offset} months");
            $key = $month->format('Y-m');
            $months[$key] = [
                'key' => $key,
                'label' => $labels[(int) $month->format('n')],
                'negotiations' => 0,
                'revenue' => 0.0,
            ];
        }

        return $months;
    }
}
