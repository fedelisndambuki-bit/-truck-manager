<?php

namespace App\Services\Quarry;

use App\Models\BlastPlan;
use App\Models\ProductionTarget;
use App\Models\SafetyIncident;
use App\Models\Stockpile;
use Illuminate\Support\Str;

/**
 * Quarry-domain slash-command fast path for the chat assistant — same shape
 * as App\Services\FleetQueryService: a leading "/" bypasses the LLM entirely
 * and answers straight from real data.
 */
class QueryService
{
    public function __construct(
        protected WeighbridgeAnalyticsService $weighbridge,
        protected DispatchAnalyticsService $dispatch,
        protected StockpileAnalyticsService $stockpileAnalytics,
        protected QuarryAnalyticsService $quarry,
    ) {
    }

    public function handle(int $companyId, string $message): ?string
    {
        $message = trim($message);
        if (! Str::startsWith($message, '/')) {
            return null;
        }

        $parts = preg_split('/\s+/', $message);
        $command = strtolower(ltrim(array_shift($parts), '/'));
        $arg = strtolower(trim(implode(' ', $parts)));

        return match ($command) {
            'help' => $this->helpText(),
            'production' => $arg === 'today' ? $this->productionToday($companyId) : 'Usage: /production today',
            'dispatch' => $arg === 'queue' ? $this->dispatchQueue($companyId) : 'Usage: /dispatch queue',
            'blast' => $arg === 'upcoming' ? $this->blastUpcoming($companyId) : 'Usage: /blast upcoming',
            'stockpile' => $arg === 'low' ? $this->stockpileLow($companyId) : 'Usage: /stockpile low',
            'weighbridge' => $arg === 'queue' ? $this->weighbridgeQueue($companyId) : 'Usage: /weighbridge queue',
            'safety' => $arg === 'incidents' ? $this->safetyIncidents($companyId) : 'Usage: /safety incidents',
            'insights' => $arg === 'daily' ? $this->dailyInsights($companyId) : 'Usage: /insights daily',
            default => 'Unknown command. Type /help for available commands.',
        };
    }

    private function helpText(): string
    {
        return implode("\n", [
            'Commands:',
            '/production today',
            '/dispatch queue',
            '/blast upcoming',
            '/stockpile low',
            '/weighbridge queue',
            '/safety incidents',
            '/insights daily',
        ]);
    }

    private function productionToday(int $companyId): string
    {
        $targets = ProductionTarget::query()
            ->whereHas('extractionSite', fn ($q) => $q->where('company_id', $companyId))
            ->where('period_start', '<=', now())
            ->where('period_end', '>=', now())
            ->with('extractionSite')
            ->get();

        if ($targets->isEmpty()) {
            return 'No active production targets for today.';
        }

        $lines = ['Production targets in progress: ' . $targets->count()];
        foreach ($targets->take(5) as $target) {
            $lines[] = '• ' . ($target->extractionSite?->name ?? "Site #{$target->extraction_site_id}") . ' • ' . $target->achievementPercent() . '% of target';
        }

        return implode("\n", $lines);
    }

    private function dispatchQueue(int $companyId): string
    {
        $queueLength = $this->dispatch->delayedDispatchesCount($companyId);
        $onTime = $this->dispatch->onTimeDeliveryPercent($companyId);

        return "Delayed dispatches: {$queueLength} • On-time delivery (30d): {$onTime}%";
    }

    private function blastUpcoming(int $companyId): string
    {
        $plans = BlastPlan::where('company_id', $companyId)
            ->whereNotNull('scheduled_date')
            ->where('scheduled_date', '>=', now())
            ->orderBy('scheduled_date')
            ->take(5)
            ->get();

        if ($plans->isEmpty()) {
            return 'No upcoming blasts scheduled.';
        }

        $lines = ['Upcoming blasts:'];
        foreach ($plans as $plan) {
            $date = $plan->scheduled_date ? $plan->scheduled_date->format('M d, Y') : 'N/A';
            $lines[] = "• {$plan->blast_name} • {$date} • {$plan->status}";
        }

        return implode("\n", $lines);
    }

    private function stockpileLow(int $companyId): string
    {
        $low = Stockpile::where('company_id', $companyId)
            ->whereColumn('quantity_available', '<=', 'min_level')
            ->with(['material', 'extractionSite'])
            ->take(5)
            ->get();

        if ($low->isEmpty()) {
            return 'No stockpiles are currently low.';
        }

        $lines = ['Low stockpiles: ' . $low->count()];
        foreach ($low as $stockpile) {
            $lines[] = '• ' . ($stockpile->material?->name ?? 'Material') . ' at ' . ($stockpile->extractionSite?->name ?? 'site') . ' • ' . (float) $stockpile->quantity_available . ' remaining';
        }

        return implode("\n", $lines);
    }

    private function weighbridgeQueue(int $companyId): string
    {
        $queue = $this->weighbridge->currentQueueLength($companyId);
        $turnaround = $this->weighbridge->averageTruckTurnaroundMinutes($companyId);

        return "Weighbridge queue: {$queue} trucks • Average turnaround (7d): {$turnaround} minutes";
    }

    private function safetyIncidents(int $companyId): string
    {
        $count = $this->quarry->openSafetyIncidents($companyId);
        $daysSinceLti = $this->quarry->daysWithoutLostTimeInjury($companyId);

        return "Open safety incidents: {$count} • Days without a lost-time injury: " . ($daysSinceLti ?? 'N/A');
    }

    private function dailyInsights(int $companyId): string
    {
        $user = auth()->user();
        $ai = app(\App\Services\AIService::class);
        $res = $ai->generateDailyInsights($companyId, $user->id);

        return $res['success'] ? (string) $res['response'] : 'AI unavailable.';
    }
}
