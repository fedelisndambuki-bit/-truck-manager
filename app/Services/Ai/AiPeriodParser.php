<?php

namespace App\Services\Ai;

use Illuminate\Support\Carbon;

/**
 * Extracts a date range from a free-text question so providers can answer
 * "this month" vs "last quarter" vs a custom range without the LLM ever
 * having to reason about calendar math itself.
 */
class AiPeriodParser
{
    /**
     * @return array{from: Carbon, to: Carbon, label: string}
     */
    public function parse(string $text): array
    {
        $text = strtolower($text);
        $now = now();

        if (str_contains($text, 'yesterday')) {
            return $this->range($now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay(), 'yesterday');
        }

        // "this X" is checked before "last X" so that a comparison phrased
        // as "this month vs last month" (both substrings present) resolves
        // to "this month" as the primary period — matching how a user
        // actually means it ("compare [current] to [previous]") — with the
        // previous period handled separately via previousEquivalent().
        if (str_contains($text, 'this week')) {
            return $this->range($now->copy()->startOfWeek(), $now, 'this week');
        }

        if (str_contains($text, 'last week')) {
            return $this->range($now->copy()->subWeek()->startOfWeek(), $now->copy()->subWeek()->endOfWeek(), 'last week');
        }

        if (str_contains($text, 'this month')) {
            return $this->range($now->copy()->startOfMonth(), $now, 'this month');
        }

        if (str_contains($text, 'last month')) {
            $m = $now->copy()->subMonthNoOverflow();

            return $this->range($m->copy()->startOfMonth(), $m->copy()->endOfMonth(), 'last month');
        }

        if (str_contains($text, 'this quarter')) {
            return $this->quarterRange($now, 0);
        }

        if (str_contains($text, 'last quarter')) {
            return $this->quarterRange($now, -1);
        }

        if (str_contains($text, 'this year')) {
            return $this->range($now->copy()->startOfYear(), $now, 'this year');
        }

        if (str_contains($text, 'last year')) {
            $y = $now->copy()->subYear();

            return $this->range($y->copy()->startOfYear(), $y->copy()->endOfYear(), 'last year');
        }

        if (str_contains($text, 'today')) {
            return $this->range($now->copy()->startOfDay(), $now->copy()->endOfDay(), 'today');
        }

        $custom = $this->tryCustomRange($text);
        if ($custom) {
            return $custom;
        }

        // Default: matches the pre-existing behaviour of the fixed monthly
        // summaries this replaces, so unmatched questions don't regress.
        return $this->range($now->copy()->startOfMonth(), $now, 'this month');
    }

    /**
     * The calendar-aware "previous equivalent" of a period already returned
     * by parse() — e.g. "this month" (month-to-date) compares against the
     * SAME day-range last month, not a full prior calendar month, so a
     * partial current period is never unfairly compared against a complete
     * one. "last X" periods (already complete) compare against the full X
     * before that.
     *
     * @param array{from: Carbon, to: Carbon, label: string} $period
     * @return array{from: Carbon, to: Carbon, label: string}
     */
    public function previousEquivalent(array $period): array
    {
        ['from' => $from, 'to' => $to, 'label' => $label] = $period;

        return match ($label) {
            'today' => $this->range($from->copy()->subDay(), $to->copy()->subDay(), 'yesterday'),
            'yesterday' => $this->range($from->copy()->subDay(), $to->copy()->subDay(), 'the day before yesterday'),
            'this week' => $this->range($from->copy()->subWeek(), $to->copy()->subWeek(), 'the same period last week'),
            'last week' => $this->range($from->copy()->subWeek(), $to->copy()->subWeek(), 'the week before last'),
            'this month' => $this->range($from->copy()->subMonthNoOverflow(), $to->copy()->subMonthNoOverflow(), 'the same period last month'),
            'last month' => $this->range($from->copy()->subMonthNoOverflow(), $to->copy()->subMonthNoOverflow(), 'the month before last'),
            'this quarter' => $this->range($from->copy()->subMonthsNoOverflow(3), $to->copy()->subMonthsNoOverflow(3), 'the same period last quarter'),
            'last quarter' => $this->range($from->copy()->subMonthsNoOverflow(3), $to->copy()->subMonthsNoOverflow(3), 'the quarter before last'),
            'this year' => $this->range($from->copy()->subYear(), $to->copy()->subYear(), 'the same period last year'),
            'last year' => $this->range($from->copy()->subYear(), $to->copy()->subYear(), 'the year before last'),
            default => $this->shiftByDuration($from, $to),
        };
    }

    private function shiftByDuration(Carbon $from, Carbon $to): array
    {
        $durationSeconds = $from->diffInSeconds($to);
        $newTo = $from->copy()->subSecond();
        $newFrom = $newTo->copy()->subSeconds($durationSeconds);

        return $this->range($newFrom, $newTo, $newFrom->toDateString().' to '.$newTo->toDateString());
    }

    private function tryCustomRange(string $text): ?array
    {
        if (! preg_match('/(?:from|between)\s+(.+?)\s+(?:to|and)\s+(.+?)(?:\s|$)/i', $text, $m)) {
            return null;
        }

        try {
            $from = Carbon::parse($m[1])->startOfDay();
            $to = Carbon::parse($m[2])->endOfDay();
        } catch (\Throwable $e) {
            return null;
        }

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to, $from];
        }

        return $this->range($from, $to, $from->toDateString().' to '.$to->toDateString());
    }

    private function quarterRange(Carbon $now, int $offsetQuarters): array
    {
        $quarterStartMonth = (intdiv($now->month - 1, 3) * 3) + 1;
        $start = $now->copy()->month($quarterStartMonth)->startOfMonth()->addMonthsNoOverflow($offsetQuarters * 3);
        $end = $start->copy()->addMonthsNoOverflow(3)->subSecond();

        $label = $offsetQuarters === 0 ? 'this quarter' : 'last quarter';

        return $this->range($start, $offsetQuarters === 0 ? $now : $end, $label);
    }

    private function range(Carbon $from, Carbon $to, string $label): array
    {
        return ['from' => $from, 'to' => $to, 'label' => $label];
    }
}
