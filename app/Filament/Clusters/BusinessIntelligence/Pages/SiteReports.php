<?php

namespace App\Filament\Clusters\BusinessIntelligence\Pages;

use App\Filament\Clusters\BusinessIntelligence;
use App\Models\Site;
use App\Services\Construction\BusinessIntelligenceService;
use App\Services\Construction\ReportExportService;
use App\Traits\HasModuleAccess;
use Illuminate\Support\Facades\Auth;
use Filament\Pages\Page;

class SiteReports extends Page
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

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationLabel = 'Site Reports';

    protected static ?int $navigationSort = 5;

    protected static ?string $title = 'Site Reports';

    protected static string $view = 'filament.clusters.business-intelligence.pages.hub-page';

    public function getPageIntro(): string
    {
        return 'Daily/Weekly/Monthly Site Reports and Site Progress are already available in Daily Site Diary, plus 3 new cross-site reports.';
    }

    public function getPageIcon(): string
    {
        return '<path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />';
    }

    public function getHubCards(): array
    {
        return [
            ['label' => 'Site Performance', 'description' => 'New: attendance rate and equipment allocation per site.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportSitePerformance'],
            ['label' => 'Site Costs', 'description' => 'New: material consumption and site expenses per site.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportSiteCosts'],
            ['label' => 'Site Comparison', 'description' => 'New: side-by-side progress, workforce and equipment across all sites.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportSiteComparison'],
            ['label' => 'Open Daily Site Diary Reports', 'description' => 'Daily, Weekly, Monthly, Supervisor, Weather, Equipment, Labour, Material, Delay, Progress and Executive Summary reports.', 'icon' => 'heroicon-o-arrow-top-right-on-square', 'url' => \App\Filament\Pages\DailySiteDiaryReports::getUrl()],
        ];
    }

    protected function companyId(): ?int
    {
        return Auth::user()?->company_id;
    }

    protected function sites()
    {
        return Site::query()->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))->get();
    }

    public function exportSitePerformance()
    {
        $bi = app(BusinessIntelligenceService::class);

        $rows = $this->sites()->map(fn (Site $s) => [
            $s->name, $s->status, $bi->siteAttendanceRate($s) . '%', $bi->siteWorkforceCount($s), $bi->siteEquipmentCount($s),
        ])->all();

        return app(ReportExportService::class)->toPdf('Site Performance Report', [
            ['title' => 'Site Performance', 'columns' => ['Site', 'Status', 'Attendance Rate (7d)', 'Workforce', 'Equipment'], 'rows' => $rows],
        ]);
    }

    public function exportSiteCosts()
    {
        $currency = session('currency', 'KES');

        $rows = $this->sites()->map(function (Site $s) use ($currency) {
            $materialCost = \App\Models\MaterialConsumption::query()->where('site_id', $s->id)->sum('cost');
            $siteExpenses = \App\Models\Expense::query()->where('project_id', $s->project_id)->sum('amount');

            return [$s->name, $currency . ' ' . number_format((float) $materialCost, 0), $currency . ' ' . number_format((float) $siteExpenses, 0)];
        })->all();

        return app(ReportExportService::class)->toPdf('Site Costs Report', [
            ['title' => 'Cost by Site', 'columns' => ['Site', 'Material Cost', 'Project Expenses'], 'rows' => $rows],
        ]);
    }

    public function exportSiteComparison()
    {
        $bi = app(BusinessIntelligenceService::class);

        $rows = $this->sites()->map(fn (Site $s) => [
            $s->name, ($s->progress_percent ?? 0) . '%', $bi->siteWorkforceCount($s), $bi->siteEquipmentCount($s), $bi->siteAttendanceRate($s) . '%',
        ])->all();

        return app(ReportExportService::class)->toPdf('Site Comparison Report', [
            ['title' => 'All Sites', 'columns' => ['Site', 'Progress', 'Workforce', 'Equipment', 'Attendance Rate'], 'rows' => $rows],
        ]);
    }
}
