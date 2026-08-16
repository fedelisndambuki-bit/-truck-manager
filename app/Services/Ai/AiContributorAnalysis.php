<?php

namespace App\Services\Ai;

/**
 * For diagnostic ("why did X increase?") questions: given several named
 * AiComparisonEngine results (one per module/vehicle/project), ranks them by
 * the size of their change so the response can lead with "main contributors"
 * instead of the model guessing which number mattered most.
 */
class AiContributorAnalysis
{
    /**
     * @param array<string, array> $comparisons name => AiComparisonEngine::compare() result
     * @return array<int, array> ranked list, largest absolute change first
     */
    public function rank(array $comparisons): array
    {
        $ranked = [];

        foreach ($comparisons as $name => $comparison) {
            $ranked[] = array_merge(['name' => $name], $comparison);
        }

        usort($ranked, fn ($a, $b) => abs($b['absolute_change']) <=> abs($a['absolute_change']));

        return $ranked;
    }
}
