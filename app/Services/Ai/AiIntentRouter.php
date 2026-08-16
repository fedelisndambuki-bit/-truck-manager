<?php

namespace App\Services\Ai;

/**
 * Deterministic keyword -> provider-key router. No extra LLM call, zero
 * added latency/cost — a question either obviously mentions a module
 * ("fuel", "payroll", "quarry") or it doesn't, and guessing via another AI
 * round-trip would be slower and less predictable than a lookup table for
 * this. The tool-calling loop in AiProviderService covers genuine
 * ambiguity/drill-down beyond what this can resolve up front.
 */
class AiIntentRouter
{
    /** @var array<string, string[]> */
    private const KEYWORD_MAP = [
        'fuel' => ['fuel', 'fleet'],
        'petrol' => ['fuel', 'fleet'],
        'diesel' => ['fuel', 'fleet'],
        'maintenance' => ['workshop', 'fleet', 'finance', 'procurement'],
        'workshop' => ['workshop', 'fleet', 'finance', 'procurement'],
        'repair' => ['workshop', 'fleet'],
        'breakdown' => ['workshop', 'fleet'],
        'service' => ['workshop', 'fleet'],
        'quarry' => ['quarry', 'weighbridge', 'fleet', 'fuel'],
        'production' => ['quarry', 'weighbridge', 'fleet', 'fuel'],
        'blast' => ['quarry'],
        'crusher' => ['quarry'],
        'crushing' => ['quarry'],
        'stockpile' => ['quarry'],
        'dispatch' => ['quarry'],
        'weighbridge' => ['weighbridge', 'quarry'],
        'tonnage' => ['weighbridge', 'quarry'],
        'tonnes' => ['weighbridge', 'quarry'],
        'weight' => ['weighbridge'],
        'project' => ['construction', 'finance'],
        'site' => ['construction'],
        'construction' => ['construction', 'finance'],
        'driver' => ['fleet', 'driverPerformance'],
        'trip' => ['fleet'],
        'vehicle' => ['fleet'],
        'truck' => ['fleet'],
        'tyre' => ['fleet'],
        'tire' => ['fleet'],
        'payroll' => ['hr', 'finance'],
        'salary' => ['hr', 'finance'],
        'wages' => ['hr', 'finance'],
        'staff' => ['hr'],
        'employee' => ['hr'],
        'attendance' => ['hr'],
        'leave' => ['hr'],
        'invoice' => ['finance'],
        'payment' => ['finance'],
        'expense' => ['finance'],
        'revenue' => ['finance', 'construction'],
        'profit' => ['finance', 'construction'],
        'margin' => ['finance', 'construction'],
        'payout' => ['finance'],
        'cost' => ['finance', 'fleet', 'fuel', 'workshop', 'procurement'],
        'procurement' => ['procurement'],
        'supplier' => ['procurement'],
        'purchase' => ['procurement'],
        'lead' => ['crm'],
        'opportunity' => ['crm'],
        'crm' => ['crm'],
        'sales' => ['crm'],
    ];

    /** @var string[] Also reused by AiQuestionClassifier for "executive" depth detection. */
    public const OVERVIEW_TRIGGERS = [
        'overall', 'business performance', 'how are we doing', 'how is the business',
        'general overview', 'company performance', 'everything', 'across the business',
        'operating well', 'doing well', 'performing well', 'going well',
        'is the company', 'is this company', 'is my company', 'is our company',
        'company doing', 'company healthy', 'in good shape', 'how are we performing',
        'how is the company', 'how is my company', 'profitable', 'making a profit',
        'making money', 'losing money',
    ];

    /**
     * @param string[] $availableProviderKeys already filtered by industry + permission
     * @return string[] the subset of $availableProviderKeys relevant to this question
     */
    public function route(string $question, array $availableProviderKeys): array
    {
        $q = strtolower($question);

        foreach (self::OVERVIEW_TRIGGERS as $trigger) {
            if (str_contains($q, $trigger)) {
                return $availableProviderKeys;
            }
        }

        $matched = [];
        foreach (self::KEYWORD_MAP as $keyword => $providers) {
            if (str_contains($q, $keyword)) {
                foreach ($providers as $p) {
                    $matched[$p] = true;
                }
            }
        }

        if (empty($matched)) {
            // No keyword matched — fall back to whatever industry-primary
            // provider(s) are available, matching this app's pre-existing
            // default-summary behaviour so unmatched questions don't regress.
            $fallback = array_values(array_intersect(['fleet', 'construction', 'quarry'], $availableProviderKeys));

            return $fallback ?: $availableProviderKeys;
        }

        return array_values(array_intersect(array_keys($matched), $availableProviderKeys));
    }
}
