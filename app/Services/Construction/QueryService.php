<?php

namespace App\Services\Construction;

use App\Models\Material;
use App\Models\MaterialWastage;
use App\Models\Project;
use App\Models\SafetyIncident;
use App\Models\Site;
use App\Models\Subcontractor;
use Illuminate\Support\Str;

/**
 * Construction-domain slash-command fast path for the chat assistant — same
 * shape as App\Services\FleetQueryService: a leading "/" bypasses the LLM
 * entirely and answers straight from real data.
 */
class QueryService
{
    public function __construct(protected ProjectFinanceService $finance)
    {
    }

    public function handle(int $companyId, string $message): ?string
    {
        $message = trim($message);
        if (! Str::startsWith($message, '/')) {
            return null;
        }

        $parts = preg_split('/\s+/', $message);
        $command = strtolower(ltrim(array_shift($parts), '/'));
        $arg = trim(implode(' ', $parts));

        return match ($command) {
            'help' => $this->helpText(),
            'projects' => $this->projectsSummary($companyId, strtolower($arg)),
            'project' => $this->projectDetail($companyId, $arg),
            'site' => $this->siteDetail($companyId, $arg),
            'subcontractor' => $this->subcontractorDetail($companyId, $arg),
            'safety' => $this->safetySummary($companyId, strtolower($arg)),
            'materials' => $this->materialsSummary($companyId, strtolower($arg)),
            'insights' => trim($arg) === 'daily' ? $this->dailyInsights($companyId) : 'Usage: /insights daily',
            default => 'Unknown command. Type /help for available commands.',
        };
    }

    private function helpText(): string
    {
        return implode("\n", [
            'Commands:',
            '/projects [active|delayed|completed]',
            '/project [name]',
            '/site [name]',
            '/subcontractor [name]',
            '/safety incidents-this-month',
            '/materials wastage-this-month',
            '/insights daily',
        ]);
    }

    private function projectsSummary(int $companyId, string $statusArg): string
    {
        $map = ['active' => 'Active', 'delayed' => 'Delayed', 'completed' => 'Completed', 'planning' => 'Planning'];
        $status = $map[$statusArg] ?? null;

        $query = Project::where('company_id', $companyId);
        if ($status) {
            $query->where('status', $status);
        }

        $total = $query->count();
        $recent = $query->latest()->take(5)->get()->map(fn (Project $p) => "{$p->status} • {$p->name} • {$p->progress_percent}% complete")->toArray();

        $lines = ['Projects' . ($status ? " ({$status})" : '') . ": {$total}"];
        foreach ($recent as $line) {
            $lines[] = "• {$line}";
        }

        return implode("\n", $lines);
    }

    private function projectDetail(int $companyId, string $name): string
    {
        if ($name === '') {
            return 'Usage: /project [name]';
        }

        $project = Project::where('company_id', $companyId)->where('name', 'like', "%{$name}%")->first();

        if (! $project) {
            return 'No project matches that name.';
        }

        $cpi = $this->finance->costPerformanceIndex($project);
        $spi = $this->finance->schedulePerformanceIndex($project);

        return implode("\n", [
            "Project: {$project->name}",
            "Status: {$project->status} • {$project->progress_percent}% complete",
            'Contract value: KES ' . number_format((float) $project->contract_value, 2),
            'Actual cost to date: KES ' . number_format($this->finance->totalActualCost($project), 2),
            "Cost performance index: {$cpi} • Schedule performance index: {$spi}",
        ]);
    }

    private function siteDetail(int $companyId, string $name): string
    {
        if ($name === '') {
            return 'Usage: /site [name]';
        }

        $site = Site::where('company_id', $companyId)->where('name', 'like', "%{$name}%")->with(['project', 'siteManager'])->first();

        if (! $site) {
            return 'No site matches that name.';
        }

        return implode("\n", [
            "Site: {$site->name}",
            'Project: ' . ($site->project?->name ?? 'N/A'),
            "Status: {$site->status}",
            'Site manager: ' . ($site->siteManager?->full_name ?? 'Unassigned'),
        ]);
    }

    private function subcontractorDetail(int $companyId, string $name): string
    {
        if ($name === '') {
            return 'Usage: /subcontractor [name]';
        }

        $subcontractor = Subcontractor::where('company_id', $companyId)->where('company_name', 'like', "%{$name}%")->first();

        if (! $subcontractor) {
            return 'No subcontractor matches that name.';
        }

        $rating = $subcontractor->average_performance_rating;

        return implode("\n", [
            "Subcontractor: {$subcontractor->company_name}",
            "Trade: {$subcontractor->trade_specialty} • Status: {$subcontractor->status}",
            'Contract value: KES ' . number_format((float) $subcontractor->contract_value, 2),
            'Average performance rating: ' . ($rating !== null ? round($rating, 1) . ' / 5' : 'No reviews yet'),
            'Compliance: ' . $subcontractor->compliance_status,
        ]);
    }

    private function safetySummary(int $companyId, string $arg): string
    {
        if ($arg !== 'incidents-this-month') {
            return 'Usage: /safety incidents-this-month';
        }

        $query = SafetyIncident::where('company_id', $companyId)->whereBetween('date', [now()->startOfMonth(), now()]);
        $count = $query->count();
        $items = $query->latest('date')->take(5)->get()->map(fn (SafetyIncident $i) => "{$i->severity} • {$i->description}")->toArray();

        $lines = ["Safety incidents this month: {$count}"];
        foreach ($items as $item) {
            $lines[] = "• " . Str::limit($item, 90);
        }

        return implode("\n", $lines);
    }

    private function materialsSummary(int $companyId, string $arg): string
    {
        if ($arg !== 'wastage-this-month') {
            return 'Usage: /materials wastage-this-month';
        }

        $wastage = MaterialWastage::where('company_id', $companyId)->where('created_at', '>=', now()->startOfMonth())->sum('quantity');
        $lowStock = Material::where('company_id', $companyId)->whereColumn('quantity_on_hand', '<=', 'reorder_level')->count();

        return "Material wastage this month: {$wastage} units • Low-stock stockpiles: {$lowStock}";
    }

    private function dailyInsights(int $companyId): string
    {
        $user = auth()->user();
        $ai = app(\App\Services\AIService::class);
        $res = $ai->generateDailyInsights($companyId, $user->id);

        return $res['success'] ? (string) $res['response'] : 'AI unavailable.';
    }
}
