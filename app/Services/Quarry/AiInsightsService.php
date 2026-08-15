<?php

namespace App\Services\Quarry;

use App\Models\AiInsight;
use App\Models\AiUsageLog;
use App\Models\Crusher;
use App\Models\ProductionTarget;
use App\Models\SafetyIncident;
use App\Models\Stockpile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Quarry-domain AI insights — same shape as
 * App\Services\Construction\AiInsightsService (deterministic detectors, real
 * data, an optional Ollama narrative on top with a templated fallback). The
 * `ai_insights.project_id` column is a real FK to `projects`, which Quarry
 * has no equivalent of, so it is never set here — extraction site / crusher
 * / stockpile identifiers live inside `facts` instead. There is no Quarry
 * equivalent of Construction's ProjectFinanceService::companyCostBreakdown(),
 * so no cost-saving detector is included rather than fabricating one.
 */
class AiInsightsService
{
    public function __construct(
        protected BlastAnalyticsService $blast,
        protected CrushingPlantAnalyticsService $crushingPlant,
        protected WeighbridgeAnalyticsService $weighbridge,
        protected DispatchAnalyticsService $dispatch,
    ) {
    }

    public function generate(?int $companyId): \Illuminate\Support\Collection
    {
        $insights = collect([
            ...$this->productionShortfalls($companyId),
            ...$this->blastSafetyRisk($companyId),
            ...$this->crushersAtRisk($companyId),
            ...$this->dispatchDelays($companyId),
            ...$this->weighbridgeBottleneck($companyId),
            ...$this->stockpileLow($companyId),
            ...$this->risingSafetyRisk($companyId),
        ]);

        return $insights->map(function (array $insight) use ($companyId) {
            $insight['narrative'] = $this->narrate($insight, $companyId);

            return AiInsight::create([
                'company_id' => $companyId,
                'type' => $insight['type'],
                'project_id' => null,
                'severity' => $insight['severity'],
                'title' => $insight['title'],
                'narrative' => $insight['narrative'],
                'facts' => $insight['facts'],
                'generated_at' => now(),
            ]);
        });
    }

    // ── Detectors ────────────────────────────────────────────────────────────

    protected function productionShortfalls(?int $companyId): array
    {
        $results = [];

        $targets = ProductionTarget::query()
            ->whereHas('extractionSite', fn ($q) => $q->when($companyId, fn ($qq) => $qq->where('company_id', $companyId)))
            ->where('period_end', '>=', now()->subDays(7))
            ->where('period_end', '<=', now())
            ->with('extractionSite')
            ->get();

        foreach ($targets as $target) {
            $achievement = $target->achievementPercent();

            if ($achievement >= 80 || (float) $target->target_quantity <= 0) {
                continue;
            }

            $results[] = [
                'type' => 'production_shortfall',
                'severity' => $achievement < 50 ? 'Critical' : 'High',
                'title' => ($target->extractionSite?->name ?? "Site #{$target->extraction_site_id}") . ' is falling short of its production target',
                'facts' => [
                    'extraction_site_id' => $target->extraction_site_id,
                    'achievement_percent' => $achievement,
                    'target_quantity' => (float) $target->target_quantity,
                    'actual_quantity' => $target->actualQuantity(),
                ],
            ];
        }

        return $results;
    }

    protected function blastSafetyRisk(?int $companyId): array
    {
        $misfires = $this->blast->misfiresCount($companyId);
        $delays = $this->blast->blastDelaysCount($companyId);
        $environmentalAlerts = $this->blast->environmentalAlertsCount($companyId);

        if ($misfires === 0 && $environmentalAlerts === 0) {
            return [];
        }

        return [[
            'type' => 'blast_safety_risk',
            'severity' => ($misfires > 0 || $environmentalAlerts > 1) ? 'Critical' : 'High',
            'title' => 'Blast operations show safety/compliance warning signs',
            'facts' => [
                'misfires' => $misfires,
                'blast_delays' => $delays,
                'environmental_alerts' => $environmentalAlerts,
                'compliance_score_percent' => $this->blast->avgComplianceScore($companyId),
            ],
        ]];
    }

    protected function crushersAtRisk(?int $companyId): array
    {
        $results = [];

        $crushers = Crusher::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereIn('status', ['Breakdown', 'Maintenance'])
            ->get();

        foreach ($crushers as $crusher) {
            $results[] = [
                'type' => 'crusher_risk',
                'severity' => $crusher->status === 'Breakdown' ? 'Critical' : 'Medium',
                'title' => ($crusher->crusher_code ?? "Crusher #{$crusher->id}") . " is currently {$crusher->status}",
                'facts' => ['crusher_id' => $crusher->id, 'crusher_code' => $crusher->crusher_code, 'status' => $crusher->status],
            ];
        }

        return $results;
    }

    protected function dispatchDelays(?int $companyId): array
    {
        $delayed = $this->dispatch->delayedDispatchesCount($companyId);
        $onTime = $this->dispatch->onTimeDeliveryPercent($companyId);

        if ($delayed === 0) {
            return [];
        }

        return [[
            'type' => 'dispatch_delay',
            'severity' => $delayed > 5 ? 'High' : 'Medium',
            'title' => "{$delayed} dispatch(es) are currently delayed",
            'facts' => ['delayed_dispatches' => $delayed, 'on_time_delivery_percent_30d' => $onTime],
        ]];
    }

    protected function weighbridgeBottleneck(?int $companyId): array
    {
        $queueLength = $this->weighbridge->currentQueueLength($companyId);

        if ($queueLength < 8) {
            return [];
        }

        return [[
            'type' => 'weighbridge_bottleneck',
            'severity' => $queueLength > 15 ? 'High' : 'Medium',
            'title' => 'Weighbridge queue is building up',
            'facts' => [
                'current_queue_length' => $queueLength,
                'average_turnaround_minutes_7d' => $this->weighbridge->averageTruckTurnaroundMinutes($companyId),
            ],
        ]];
    }

    protected function stockpileLow(?int $companyId): array
    {
        $results = [];

        $lowStockpiles = Stockpile::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereColumn('quantity_available', '<=', 'min_level')
            ->with(['material', 'extractionSite'])
            ->get();

        foreach ($lowStockpiles as $stockpile) {
            $results[] = [
                'type' => 'stockpile_low',
                'severity' => (float) $stockpile->quantity_available <= 0 ? 'Critical' : 'Medium',
                'title' => ($stockpile->material?->name ?? 'A material') . ' is running low at ' . ($stockpile->extractionSite?->name ?? "site #{$stockpile->extraction_site_id}"),
                'facts' => [
                    'stockpile_id' => $stockpile->id,
                    'material' => $stockpile->material?->name,
                    'extraction_site_id' => $stockpile->extraction_site_id,
                    'quantity_available' => (float) $stockpile->quantity_available,
                    'min_level' => (float) $stockpile->min_level,
                ],
            ];
        }

        return $results;
    }

    /**
     * Same "compare this month to last month" shape as Construction's
     * risingSafetyRisk(), scoped to site-attributed incidents only, matching
     * QuarryAnalyticsService::openSafetyIncidents()'s own convention.
     */
    protected function risingSafetyRisk(?int $companyId): array
    {
        $thisMonth = SafetyIncident::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereNotNull('extraction_site_id')
            ->whereBetween('date', [now()->startOfMonth(), now()])
            ->count();

        $lastMonth = SafetyIncident::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereNotNull('extraction_site_id')
            ->whereBetween('date', [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()])
            ->count();

        if ($lastMonth === 0 || $thisMonth <= $lastMonth) {
            return [];
        }

        return [[
            'type' => 'safety_risk',
            'severity' => $thisMonth > $lastMonth * 2 ? 'Critical' : 'High',
            'title' => 'Safety incidents are trending upward across quarry sites',
            'facts' => ['incidents_this_month' => $thisMonth, 'incidents_last_month' => $lastMonth],
        ]];
    }

    protected function narrate(array $insight, ?int $companyId = null): string
    {
        $fallback = $insight['title'] . '. ' . collect($insight['facts'])
            ->map(fn ($v, $k) => str($k)->replace('_', ' ') . ': ' . (is_numeric($v) ? round($v, 2) : $v))
            ->implode(', ') . '.';

        $start = microtime(true);

        try {
            $prompt = "You are a quarry operations analyst. In 1-2 concise sentences, explain this finding to a quarry manager and suggest one concrete action. Finding: {$insight['title']}. Supporting data: " . json_encode($insight['facts']);

            $response = Http::timeout(10)->post(config('services.ollama.url'), [
                'model' => config('services.ollama.model'),
                'prompt' => $prompt,
                'stream' => false,
                'options' => ['num_predict' => 150, 'temperature' => 0.2],
            ]);

            if ($response->successful() && filled($response->json('response'))) {
                if ($companyId) {
                    AiUsageLog::create([
                        'company_id' => $companyId,
                        'tokens_used' => (int) $response->json('prompt_eval_count', 0) + (int) $response->json('eval_count', 0),
                        'response_ms' => (int) round((microtime(true) - $start) * 1000),
                    ]);
                }

                return trim($response->json('response'));
            }
        } catch (\Throwable $e) {
            Log::info('Quarry AiInsightsService: Ollama narrative unavailable, using fallback.', ['error' => $e->getMessage()]);
        }

        return $fallback;
    }
}
