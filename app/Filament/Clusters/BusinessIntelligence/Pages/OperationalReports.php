<?php

namespace App\Filament\Clusters\BusinessIntelligence\Pages;

use App\Filament\Clusters\BusinessIntelligence;
use App\Models\Attendance;
use App\Models\EquipmentAllocation;
use App\Models\Site;
use App\Services\Construction\BusinessIntelligenceService;
use App\Services\Construction\ReportExportService;
use App\Traits\HasModuleAccess;
use Illuminate\Support\Facades\Auth;
use Filament\Pages\Page;

class OperationalReports extends Page
{
    use HasModuleAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'bi.view';
    }

    protected static ?string $cluster = BusinessIntelligence::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Operational Reports';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Operational Reports';

    protected static string $view = 'filament.clusters.business-intelligence.pages.hub-page';

    public function getPageIntro(): string
    {
        return 'New cross-module operational summaries — no equivalent page existed anywhere in the app.';
    }

    public function getPageIcon(): string
    {
        return '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.223 4.01 8.5 4.847 8.5 5.845" />';
    }

    public function getHubCards(): array
    {
        return [
            ['label' => 'Daily Operations Summary', 'description' => 'New: today\'s attendance, equipment on site and active sites.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportDailySummary'],
            ['label' => 'Weekly Operations Summary', 'description' => 'New: 7-day attendance and equipment utilization trend.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportWeeklySummary'],
            ['label' => 'Multi-Site Comparison', 'description' => 'New: side-by-side operational KPIs across every active site.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportMultiSiteComparison'],
            ['label' => 'Company Operations Snapshot', 'description' => 'New: a single cross-module operational health summary.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportCompanySnapshot'],
        ];
    }

    protected function companyId(): ?int
    {
        return Auth::user()?->company_id;
    }

    protected function sites()
    {
        return Site::query()->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))->where('status', 'Active')->get();
    }

    public function exportDailySummary()
    {
        $companyId = $this->companyId();
        $today = now()->toDateString();

        $present = Attendance::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->where('date', $today)->where('status', 'Present')->count();
        $totalMarked = Attendance::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->where('date', $today)->count();
        $equipmentOut = EquipmentAllocation::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->whereNull('actual_return_date')->count();

        $kpis = [
            ['label' => 'Active Sites', 'value' => (string) $this->sites()->count()],
            ['label' => 'Workers Present Today', 'value' => (string) $present . ' / ' . $totalMarked],
            ['label' => 'Equipment Deployed', 'value' => (string) $equipmentOut],
        ];

        $rows = $this->sites()->map(fn (Site $s) => [$s->name, $s->status, optional($s->project)->name])->all();

        return app(ReportExportService::class)->toPdf('Daily Operations Summary — ' . now()->format('d M Y'), [
            ['title' => 'Active Sites', 'columns' => ['Site', 'Status', 'Project'], 'rows' => $rows],
        ], $kpis);
    }

    public function exportWeeklySummary()
    {
        $companyId = $this->companyId();

        $rows = collect(range(6, 0))->map(function (int $i) use ($companyId) {
            $date = now()->subDays($i);
            $present = Attendance::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->whereDate('date', $date)->where('status', 'Present')->count();
            $total = Attendance::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->whereDate('date', $date)->count();

            return [$date->format('D, d M'), $present . ' / ' . $total, $total ? round(($present / $total) * 100, 1) . '%' : '—'];
        })->all();

        return app(ReportExportService::class)->toPdf('Weekly Operations Summary', [
            ['title' => 'Attendance — Last 7 Days', 'columns' => ['Date', 'Present / Marked', 'Rate'], 'rows' => $rows],
        ]);
    }

    public function exportMultiSiteComparison()
    {
        $bi = app(BusinessIntelligenceService::class);

        $rows = $this->sites()->map(fn (Site $s) => [
            $s->name, ($s->progress_percent ?? 0) . '%', $bi->siteWorkforceCount($s), $bi->siteEquipmentCount($s), $bi->siteAttendanceRate($s) . '%',
        ])->all();

        return app(ReportExportService::class)->toPdf('Multi-Site Comparison', [
            ['title' => 'All Active Sites', 'columns' => ['Site', 'Progress', 'Workforce', 'Equipment', 'Attendance Rate'], 'rows' => $rows],
        ]);
    }

    public function exportCompanySnapshot()
    {
        $bi = app(BusinessIntelligenceService::class);
        $companyId = $this->companyId();
        $currency = session('currency', 'KES');

        $kpis = [
            ['label' => 'Active Projects', 'value' => (string) $bi->activeProjects($companyId)],
            ['label' => 'Projects at Risk', 'value' => (string) $bi->projectsAtRisk($companyId)],
            ['label' => 'Workforce', 'value' => (string) $bi->workforceCount($companyId)],
            ['label' => 'Equipment Utilization', 'value' => $bi->equipmentUtilization($companyId) . '%'],
            ['label' => 'Safety Score', 'value' => (string) $bi->safetyScore($companyId)],
            ['label' => 'Quality Score', 'value' => (string) $bi->qualityScore($companyId)],
            ['label' => 'Profit Margin', 'value' => $bi->profitMargin($companyId) . '%'],
        ];

        $rows = $this->sites()->map(fn (Site $s) => [$s->name, $bi->siteWorkforceCount($s), $bi->siteEquipmentCount($s), $bi->siteAttendanceRate($s) . '%'])->all();

        return app(ReportExportService::class)->toPdf('Company Operations Snapshot', [
            ['title' => 'Active Sites Overview', 'columns' => ['Site', 'Workforce', 'Equipment', 'Attendance Rate'], 'rows' => $rows],
        ], $kpis);
    }
}
