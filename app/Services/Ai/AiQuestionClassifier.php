<?php

namespace App\Services\Ai;

/**
 * Classifies how deep an answer needs to be, so AIService can pick an
 * appropriate token budget and prompt structure instead of applying one
 * fixed "be extremely concise" instruction to every question regardless of
 * complexity. Deterministic and keyword-based, matching AiIntentRouter's own
 * zero-cost philosophy — no extra LLM round-trip just to classify a question.
 */
class AiQuestionClassifier
{
    private const DIAGNOSTIC_TRIGGERS = [
        'why', 'cause', 'reason', 'root cause', 'what caused', 'what is causing',
        'what\'s causing', 'explain why', 'diagnose', 'performing poorly', 'underperforming',
    ];

    private const COMPARATIVE_TRIGGERS = [
        'compare', ' vs ', ' vs.', 'versus', 'compared to', 'compared with', 'against last',
        'month-over-month', 'week-over-week', 'year-over-year', 'quarter-over-quarter',
    ];

    private const ANALYTICAL_TRIGGERS = [
        'trend', 'trending', 'over time', 'increase', 'decrease', 'change', 'growth',
        'ranking', 'rank', 'top 10', 'top ten', 'worst', 'best', 'highest', 'lowest',
        'most', 'least',
    ];

    public function classify(string $question): string
    {
        $q = strtolower($question);

        if ($this->matchesAny($q, self::DIAGNOSTIC_TRIGGERS)) {
            return 'diagnostic';
        }

        if ($this->matchesAny($q, self::COMPARATIVE_TRIGGERS)) {
            return 'comparative';
        }

        if ($this->matchesAny($q, AiIntentRouter::OVERVIEW_TRIGGERS)) {
            return 'executive';
        }

        if ($this->matchesAny($q, self::ANALYTICAL_TRIGGERS)) {
            return 'analytical';
        }

        return 'simple';
    }

    private function matchesAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }
}
