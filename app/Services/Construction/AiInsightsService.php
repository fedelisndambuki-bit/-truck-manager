<?php

namespace App\Services\Construction;

use App\Models\AiInsight;
use App\Models\Attendance;
use App\Models\Equipment;
use App\Models\Maintenance;
use App\Models\MaterialWastage;
use App\Models\Project;
use App\Models\SafetyIncident;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Construction-domain AI insights: deterministic threshold/trend detectors
 * are the reliable core (work with zero AI dependency, same try/catch-to-
 * empty-state resilience every chart widget already uses) — an optional
 * Ollama-generated narrative is layered on top when the local LLM responds.
 * Reuses the same `services.ollama.*` config as the existing fleet-focused
 * App\Services\AIService, but with construction-domain prompts/data.
 */
class AiInsightsService
{
    public function __construct(protected ProjectFinanceService $finance)
    {
    }

    /**
     * Run every detector for a company, persist results to `ai_insights`,
     * and return the freshly generated collection.
     */
    public function generate(?int $companyId): \Illuminate\Support\Collection
    {
        $insights = collect([
            ...$this->budgetOverrunRisks($companyId),
            ...$this->decliningProductivitySites($companyId),
            ...$this->equipmentAtRisk($companyId),
            ...$this->scheduleSlippage($companyId),
            ...$this->risingSafetyRisk($companyId),
            ...$this->materialWasteAnomalies($companyId),
            ...$this->highPerformingTeams($companyId),
            ...$this->costSavingRecommendations($companyId),
        ]);

        return $insights->map(function (array $insight) use ($companyId) {
            $insight['narrative'] = $this->narrate($insight);

            return AiInsight::create([
                'company_id' => $companyId,
                'type' => $insight['type'],
                'project_id' => $insight['project_id'] ?? null,
                'severity' => $insight['severity'],
                'title' => $insight['title'],
                'narrative' => $insight['narrative'],
                'facts' => $insight['facts'],
                'generated_at' => now(),
            ]);
        });
    }

    protected function projects(?int $companyId)
    {
        return Project::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->get();
    }

    // ── Detectors ────────────────────────────────────────────────────────────

    protected function budgetOverrunRisks(?int $companyId): array
    {
        $results = [];

        foreach ($this->projects($companyId) as $project) {
            $cpi = $this->finance->costPerformanceIndex($project);

            if ($cpi >= 0.9 || $this->finance->totalActualCost($project) <= 0) {
                continue;
            }

            $results[] = [
                'type' => 'budget_overrun_risk',
                'project_id' => $project->id,
                'severity' => $cpi < 0.75 ? 'Critical' : 'High',
                'title' => "{$project->name} is trending over budget",
                'facts' => [
                    'cpi' => $cpi,
                    'estimate_at_completion' => $this->finance->estimateAtCompletion($project),
                    'budget_at_completion' => $this->finance->budgetAtCompletion($project),
                ],
            ];
        }

        return $results;
    }

    protected function decliningProductivitySites(?int $companyId): array
    {
        $results = [];
        $sites = \App\Models\Site::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->get();

        foreach ($sites as $site) {
            $recent = $this->attendanceRate($site->id, 7);
            $prior = $this->attendanceRate($site->id, 14, 7);

            if ($prior <= 0 || $recent >= $prior - 10) {
                continue;
            }

            $results[] = [
                'type' => 'declining_productivity',
                'project_id' => $site->project_id,
                'severity' => ($prior - $recent) > 25 ? 'High' : 'Medium',
                'title' => "Attendance at {$site->name} has dropped {$this->pct($prior - $recent)} points",
                'facts' => ['site_id' => $site->id, 'recent_attendance_rate' => $recent, 'prior_attendance_rate' => $prior],
            ];
        }

        return $results;
    }

    protected function attendanceRate(int $siteId, int $daysBack, int $offsetDays = 0): float
    {
        $to = now()->subDays($offsetDays);
        $from = $to->copy()->subDays($daysBack);

        $records = Attendance::query()->where('site_id', $siteId)->whereBetween('date', [$from, $to])->get();

        if ($records->isEmpty()) {
            return 0.0;
        }

        return round(($records->where('status', 'Present')->count() / $records->count()) * 100, 1);
    }

    protected function equipmentAtRisk(?int $companyId): array
    {
        $results = [];

        $equipment = Equipment::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', '!=', 'Under Maintenance')
            ->get();

        foreach ($equipment as $item) {
            $lastMaintenance = Maintenance::query()->where('equipment_id', $item->id)->latest('maintenance_date')->first();
            $daysSince = $lastMaintenance ? now()->diffInDays($lastMaintenance->maintenance_date) : null;

            if ($daysSince === null || $daysSince < 180) {
                continue;
            }

            $results[] = [
                'type' => 'equipment_risk',
                'project_id' => null,
                'severity' => $daysSince > 365 ? 'High' : 'Medium',
                'title' => "{$item->name} is overdue for maintenance",
                'facts' => ['equipment_id' => $item->id, 'days_since_last_maintenance' => $daysSince],
            ];
        }

        return $results;
    }

    protected function scheduleSlippage(?int $companyId): array
    {
        $results = [];

        foreach ($this->projects($companyId) as $project) {
            $spi = $this->finance->schedulePerformanceIndex($project);

            if ($spi >= 0.9 || $project->status === 'Completed') {
                continue;
            }

            $results[] = [
                'type' => 'schedule_slippage',
                'project_id' => $project->id,
                'severity' => $spi < 0.75 ? 'High' : 'Medium',
                'title' => "{$project->name} is behind schedule",
                'facts' => ['spi' => $spi, 'percent_complete' => $this->finance->percentComplete($project), 'planned_percent_complete' => $this->finance->plannedPercentComplete($project)],
            ];
        }

        return $results;
    }

    protected function risingSafetyRisk(?int $companyId): array
    {
        $thisMonth = SafetyIncident::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereBetween('date', [now()->startOfMonth(), now()])
            ->count();

        $lastMonth = SafetyIncident::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereBetween('date', [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()])
            ->count();

        if ($lastMonth === 0 || $thisMonth <= $lastMonth) {
            return [];
        }

        return [[
            'type' => 'safety_risk',
            'project_id' => null,
            'severity' => $thisMonth > $lastMonth * 2 ? 'Critical' : 'High',
            'title' => 'Safety incidents are trending upward company-wide',
            'facts' => ['incidents_this_month' => $thisMonth, 'incidents_last_month' => $lastMonth],
        ]];
    }

    protected function materialWasteAnomalies(?int $companyId): array
    {
        $results = [];

        $byProject = MaterialWastage::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereNotNull('project_id')
            ->where('created_at', '>=', now()->subDays(30))
            ->get()
            ->groupBy('project_id');

        foreach ($byProject as $projectId => $wastages) {
            $totalQty = $wastages->sum('quantity');

            if ($totalQty < 50) {
                continue;
            }

            $project = Project::find($projectId);

            $results[] = [
                'type' => 'material_waste_anomaly',
                'project_id' => $projectId,
                'severity' => $totalQty > 150 ? 'High' : 'Medium',
                'title' => 'Unusually high material waste on ' . ($project?->name ?? "project #{$projectId}"),
                'facts' => ['total_wasted_quantity_30d' => (float) $totalQty, 'wastage_events' => $wastages->count()],
            ];
        }

        return $results;
    }

    protected function highPerformingTeams(?int $companyId): array
    {
        $results = [];

        foreach ($this->projects($companyId) as $project) {
            $cpi = $this->finance->costPerformanceIndex($project);
            $spi = $this->finance->schedulePerformanceIndex($project);

            if ($cpi < 1.1 || $spi < 1.05 || $this->finance->totalActualCost($project) <= 0) {
                continue;
            }

            $results[] = [
                'type' => 'high_performing_team',
                'project_id' => $project->id,
                'severity' => 'Low',
                'title' => "{$project->name}'s team is outperforming plan",
                'facts' => ['cpi' => $cpi, 'spi' => $spi],
            ];
        }

        return $results;
    }

    protected function costSavingRecommendations(?int $companyId): array
    {
        $results = [];
        $breakdown = $this->finance->companyCostBreakdown($companyId);
        $total = array_sum($breakdown);

        if ($total <= 0) {
            return [];
        }

        arsort($breakdown);
        $topCategory = array_key_first($breakdown);
        $topShare = round(($breakdown[$topCategory] / $total) * 100, 1);

        if ($topShare < 35) {
            return [];
        }

        $results[] = [
            'type' => 'cost_saving_recommendation',
            'project_id' => null,
            'severity' => 'Low',
            'title' => "{$topCategory} accounts for {$topShare}% of total cost — review for savings",
            'facts' => ['category' => $topCategory, 'share_of_total_cost_percent' => $topShare, 'amount' => $breakdown[$topCategory]],
        ];

        return $results;
    }

    protected function pct(float $value): string
    {
        return number_format($value, 1);
    }

    /**
     * Optional AI-generated plain-English narrative for a detected insight.
     * Falls back to a templated sentence built from the facts if Ollama is
     * unreachable — the insight is always usable either way.
     */
    protected function narrate(array $insight): string
    {
        $fallback = $insight['title'] . '. ' . collect($insight['facts'])
            ->map(fn ($v, $k) => str($k)->replace('_', ' ') . ': ' . (is_numeric($v) ? round($v, 2) : $v))
            ->implode(', ') . '.';

        try {
            $prompt = "You are a construction project analyst. In 1-2 concise sentences, explain this finding to a construction executive and suggest one concrete action. Finding: {$insight['title']}. Supporting data: " . json_encode($insight['facts']);

            $response = Http::timeout(10)->post(config('services.ollama.url'), [
                'model' => config('services.ollama.model'),
                'prompt' => $prompt,
                'stream' => false,
                'options' => ['num_predict' => 150, 'temperature' => 0.2],
            ]);

            if ($response->successful() && filled($response->json('response'))) {
                return trim($response->json('response'));
            }
        } catch (\Throwable $e) {
            Log::info('AiInsightsService: Ollama narrative unavailable, using fallback.', ['error' => $e->getMessage()]);
        }

        return $fallback;
    }
}
