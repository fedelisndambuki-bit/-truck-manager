<?php

namespace App\Services\Ai;

/**
 * Turns a time-series (the exact `label => value` shape every existing
 * `*Trend()` analytics method already returns, e.g.
 * CrushingPlantAnalyticsService::plantThroughputTrend()) into a direction,
 * average, highest/lowest period, and flagged spikes/drops — so the LLM
 * narrates an already-computed trend instead of eyeballing a list of raw
 * numbers itself.
 */
class AiTrendAnalyzer
{
    /**
     * @param array<string, float|int> $series label => value, in chronological order
     * @return array{
     *     direction: string,
     *     average: ?float,
     *     highest: ?array{label: string, value: float},
     *     lowest: ?array{label: string, value: float},
     *     notable_changes: array<int, array{label: string, value: float, deviation_percent: float, type: string}>,
     * }
     */
    public function analyze(array $series, ?float $anomalyThresholdPercent = null): array
    {
        if (empty($series)) {
            return [
                'direction' => 'unknown',
                'average' => null,
                'highest' => null,
                'lowest' => null,
                'notable_changes' => [],
            ];
        }

        $labels = array_keys($series);
        $values = array_map(fn ($v) => (float) $v, array_values($series));
        $count = count($values);
        $average = round(array_sum($values) / $count, 2);

        $highestIndex = array_keys($values, max($values))[0];
        $lowestIndex = array_keys($values, min($values))[0];

        $direction = $this->direction($values);

        $threshold = $anomalyThresholdPercent ?? (float) config('ai.anomaly_threshold_percent', 30);
        $notableChanges = [];
        if ($average != 0.0) {
            foreach ($series as $label => $value) {
                $deviationPercent = round((((float) $value - $average) / abs($average)) * 100, 1);
                if (abs($deviationPercent) >= $threshold) {
                    $notableChanges[] = [
                        'label' => $label,
                        'value' => round((float) $value, 2),
                        'deviation_percent' => $deviationPercent,
                        'type' => $deviationPercent > 0 ? 'spike' : 'drop',
                    ];
                }
            }
        }

        return [
            'direction' => $direction,
            'average' => $average,
            'highest' => ['label' => $labels[$highestIndex], 'value' => round($values[$highestIndex], 2)],
            'lowest' => ['label' => $labels[$lowestIndex], 'value' => round($values[$lowestIndex], 2)],
            'notable_changes' => $notableChanges,
        ];
    }

    /**
     * First-half vs second-half average, rather than simple first-vs-last
     * point, so a single noisy endpoint doesn't flip the whole trend.
     */
    private function direction(array $values): string
    {
        $count = count($values);
        if ($count < 2) {
            return 'flat';
        }

        $half = max(1, intdiv($count, 2));
        $firstHalf = array_slice($values, 0, $half);
        $secondHalf = array_slice($values, -$half);

        $firstAvg = array_sum($firstHalf) / count($firstHalf);
        $secondAvg = array_sum($secondHalf) / count($secondHalf);

        if ($firstAvg == 0.0) {
            return $secondAvg > 0 ? 'increasing' : 'flat';
        }

        $change = ($secondAvg - $firstAvg) / abs($firstAvg);

        return match (true) {
            $change > 0.05 => 'increasing',
            $change < -0.05 => 'decreasing',
            default => 'flat',
        };
    }
}
