<?php

namespace App\Filament\Clusters\BusinessIntelligence\Pages;

use App\Filament\Clusters\BusinessIntelligence;
use App\Models\QualityInspection;
use App\Models\QualityIssue;
use App\Services\Construction\ReportExportService;
use App\Traits\HasModuleAccess;
use Illuminate\Support\Facades\Auth;
use Filament\Pages\Page;

class QualityReports extends Page
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

    protected static ?string $navigationIcon = 'heroicon-o-check-badge';

    protected static ?string $navigationLabel = 'Quality Reports';

    protected static ?int $navigationSort = 11;

    protected static ?string $title = 'Quality Reports';

    protected static string $view = 'filament.clusters.business-intelligence.pages.hub-page';

    public function getPageIntro(): string
    {
        return 'No dedicated Quality Reports page existed anywhere in the app — inspection analytics and NCR/CAPA/Defect/Punch List registers below are all new.';
    }

    public function getPageIcon(): string
    {
        return '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />';
    }

    public function getHubCards(): array
    {
        return [
            ['label' => 'Inspection Summary', 'description' => 'New: pass/fail breakdown by site.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportInspectionSummary'],
            ['label' => 'Quality Score Trend', 'description' => 'New: monthly pass rate over the last 6 months.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportQualityScoreTrend'],
            ['label' => 'NCR Register', 'description' => 'New: all Non-Conformance Reports.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportNcrRegister'],
            ['label' => 'CAPA Register', 'description' => 'New: all Corrective & Preventive Actions.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportCapaRegister'],
            ['label' => 'Defects Register', 'description' => 'New: all logged defects.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportDefectsRegister'],
            ['label' => 'Punch List Register', 'description' => 'New: all punch list items.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportPunchListRegister'],
        ];
    }

    protected function companyId(): ?int
    {
        return Auth::user()?->company_id;
    }

    public function exportInspectionSummary()
    {
        $rows = QualityInspection::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->with('site')
            ->get()
            ->groupBy(fn (QualityInspection $i) => $i->site?->name ?? 'Unassigned')
            ->map(function ($group, $site) {
                $pass = $group->where('result', 'Pass')->count();

                return [$site, $group->count(), $pass, $group->count() - $pass, $group->count() ? round(($pass / $group->count()) * 100, 1) . '%' : '—'];
            })
            ->values()
            ->all();

        return app(ReportExportService::class)->toPdf('Quality Inspection Summary', [
            ['title' => 'Inspections by Site', 'columns' => ['Site', 'Total', 'Pass', 'Fail', 'Pass Rate'], 'rows' => $rows],
        ]);
    }

    public function exportQualityScoreTrend()
    {
        $rows = collect(range(5, 0))->map(function (int $i) {
            $month = now()->subMonths($i);
            $inspections = QualityInspection::query()
                ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
                ->whereYear('date', $month->year)->whereMonth('date', $month->month)
                ->get();
            $rate = $inspections->isNotEmpty() ? round(($inspections->where('result', 'Pass')->count() / $inspections->count()) * 100, 1) : 0;

            return [$month->format('M Y'), $inspections->count(), $rate . '%'];
        })->all();

        return app(ReportExportService::class)->toPdf('Quality Score Trend', [
            ['title' => 'Monthly Pass Rate', 'columns' => ['Month', 'Inspections', 'Pass Rate'], 'rows' => $rows],
        ]);
    }

    protected function issuesByType(string $type): array
    {
        return QualityIssue::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->where('type', $type)
            ->with(['project', 'site', 'assignedTo'])
            ->latest()
            ->get()
            ->map(fn (QualityIssue $i) => [$i->issue_number, $i->title, $i->project?->name, $i->severity, $i->status, $i->assignedTo?->full_name ?? '—'])
            ->all();
    }

    public function exportNcrRegister()
    {
        return app(ReportExportService::class)->toPdf('NCR Register', [
            ['title' => 'Non-Conformance Reports', 'columns' => ['Ref', 'Title', 'Project', 'Severity', 'Status', 'Assigned To'], 'rows' => $this->issuesByType('NCR')],
        ]);
    }

    public function exportCapaRegister()
    {
        return app(ReportExportService::class)->toPdf('CAPA Register', [
            ['title' => 'Corrective & Preventive Actions', 'columns' => ['Ref', 'Title', 'Project', 'Severity', 'Status', 'Assigned To'], 'rows' => $this->issuesByType('CAPA')],
        ]);
    }

    public function exportDefectsRegister()
    {
        return app(ReportExportService::class)->toPdf('Defects Register', [
            ['title' => 'Defects', 'columns' => ['Ref', 'Title', 'Project', 'Severity', 'Status', 'Assigned To'], 'rows' => $this->issuesByType('Defect')],
        ]);
    }

    public function exportPunchListRegister()
    {
        return app(ReportExportService::class)->toPdf('Punch List Register', [
            ['title' => 'Punch List Items', 'columns' => ['Ref', 'Title', 'Project', 'Severity', 'Status', 'Assigned To'], 'rows' => $this->issuesByType('Punch List')],
        ]);
    }
}
