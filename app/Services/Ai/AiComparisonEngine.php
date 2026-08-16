<?php

namespace App\Services\Ai;

/**
 * The single place every "current vs previous period" comparison is
 * computed — pure calculation, no I/O. Providers hand it two numbers they
 * already queried; the LLM only ever sees the finished comparison block, so
 * it never has to (and never should) compute a percentage change itself.
 */
class AiComparisonEngine
{
    /**
     * @return array{
     *     metric: string,
     *     current_value: float,
     *     previous_value: float,
     *     absolute_change: float,
     *     percentage_change: ?float,
     *     direction: string,
     *     trend: string,
     *     unit: string,
     *     period: string,
     *     previous_period: string,
     * }
     */
    public function compare(
        string $metric,
        float $current,
        float $previous,
        string $unit = '',
        string $period = 'this period',
        string $previousPeriod = 'previous period'
    ): array {
        $absoluteChange = round($current - $previous, 2);

        $percentageChange = $previous != 0.0
            ? round(($absoluteChange / abs($previous)) * 100, 2)
            : null;

        $direction = match (true) {
            $absoluteChange > 0 => 'increase',
            $absoluteChange < 0 => 'decrease',
            default => 'unchanged',
        };

        return [
            'metric' => $metric,
            'current_value' => round($current, 2),
            'previous_value' => round($previous, 2),
            'absolute_change' => $absoluteChange,
            'percentage_change' => $percentageChange,
            'direction' => $direction,
            'trend' => $direction,
            'unit' => $unit,
            'period' => $period,
            'previous_period' => $previousPeriod,
        ];
    }
}
