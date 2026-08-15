<?php

namespace App\Filament\Clusters\LaboratoryQualityControl\Pages;

use App\Filament\Clusters\LaboratoryQualityControl;
use App\Models\Calibration;
use App\Models\Certificate;
use App\Models\CustomerQualityRequirement;
use App\Models\LabSample;
use App\Models\LabTest;
use App\Models\NonConformance;
use App\Models\QualityCorrectiveAction;
use App\Services\Construction\ReportExportService;
use App\Services\Quarry\LabAnalyticsService;
use App\Traits\HasModuleAccess;
use Filament\Pages\Page;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class Reports extends Page
{
    use HasModuleAccess;

    public static function getRequiredIndustries(): array
    {
        return ['quarry'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'lab.export';
    }

    protected static ?string $cluster = LaboratoryQualityControl::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationLabel = 'Reports';

    protected static ?int $navigationSort = 16;

    protected static ?string $title = 'Reports';

    protected static string $view = 'filament.clusters.laboratory-quality-control.pages.reports';

    protected function companyId(): ?int
    {
        return Auth::user()?->company_id;
    }

    public function getHeader(): ?View
    {
        return view('filament.construction.partials.header-standalone', [
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.098 4.02 8.25 4.982 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />',
            'title' => 'Laboratory & Quality Control Reports',
            'subtitle' => 'Laboratory register, sample register, test results, quality trend, pass/fail, NCR, CAPA, calibration, certificate register, customer quality, and executive reporting.',
        ]);
    }

    public function getHubCards(): array
    {
        return [
            ['label' => 'Laboratory Register', 'description' => 'Every laboratory test recorded today.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportLaboratoryRegister'],
            ['label' => 'Sample Register', 'description' => 'Every sample collected over the last 30 days.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportSampleRegister'],
            ['label' => 'Test Results Report', 'description' => 'Completed tests with measured results.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportTestResultsReport'],
            ['label' => 'Quality Trend Report', 'description' => 'Pass rate trend over the last 14 days.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportQualityTrendReport'],
            ['label' => 'Pass/Fail Report', 'description' => 'Test outcomes over the last 30 days.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportPassFailReport'],
            ['label' => 'NCR Report', 'description' => 'Non-conformances and their status.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportNcrReport'],
            ['label' => 'CAPA Report', 'description' => 'Corrective actions and progress.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportCapaReport'],
            ['label' => 'Calibration Report', 'description' => 'Equipment calibration history and due dates.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportCalibrationReport'],
            ['label' => 'Certificate Register', 'description' => 'Every certificate issued.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportCertificateRegister'],
            ['label' => 'Customer Quality Report', 'description' => 'Customer-specific quality requirements on file.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportCustomerQualityReport'],
            ['label' => 'Executive Quality Dashboard', 'description' => 'KPI snapshot across the whole quality operation.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportExecutiveSummary'],
        ];
    }

    public function exportLaboratoryRegister()
    {
        $rows = LabTest::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->whereDate('created_at', today())
            ->with(['sample', 'technician'])
            ->get()
            ->map(fn (LabTest $t) => [$t->test_number, $t->sample?->sample_number, $t->technician?->full_name, $t->status, $t->result ?? '—'])
            ->all();

        return app(ReportExportService::class)->toPdf('Laboratory Register — ' . today()->toFormattedDateString(), [
            ['title' => "Today's Tests", 'columns' => ['Test #', 'Sample #', 'Technician', 'Status', 'Result'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportSampleRegister()
    {
        $rows = LabSample::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->where('created_at', '>=', now()->subDays(30))
            ->with(['material', 'stockpile'])
            ->get()
            ->map(fn (LabSample $s) => [$s->sample_number, $s->sample_type, $s->material?->name, $s->stockpile?->label, $s->status])
            ->all();

        return app(ReportExportService::class)->toPdf('Sample Register (30 Days)', [
            ['title' => 'Samples', 'columns' => ['Sample #', 'Type', 'Product', 'Stockpile', 'Status'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportTestResultsReport()
    {
        $rows = LabTest::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->where('status', 'Completed')
            ->where('end_time', '>=', now()->subDays(30))
            ->with(['sample', 'testMethod'])
            ->get()
            ->map(fn (LabTest $t) => [$t->test_number, $t->sample?->sample_number, $t->testMethod?->method_name, $t->result, $t->end_time?->format('d M Y H:i')])
            ->all();

        return app(ReportExportService::class)->toPdf('Test Results Report (30 Days)', [
            ['title' => 'Completed Tests', 'columns' => ['Test #', 'Sample #', 'Method', 'Result', 'Completed'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportQualityTrendReport()
    {
        $svc = app(LabAnalyticsService::class);
        $rows = collect($svc->passRateTrend($this->companyId()))->map(fn ($rate, $day) => [$day, $rate === null ? 'No tests' : $rate . '%'])->values()->all();

        return app(ReportExportService::class)->toPdf('Quality Trend Report (14 Days)', [
            ['title' => 'Daily Pass Rate', 'columns' => ['Date', 'Pass Rate'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportPassFailReport()
    {
        $rows = LabTest::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->where('status', 'Completed')
            ->where('end_time', '>=', now()->subDays(30))
            ->with('sample')
            ->get()
            ->map(fn (LabTest $t) => [$t->test_number, $t->sample?->sample_number, $t->result])
            ->all();

        return app(ReportExportService::class)->toPdf('Pass/Fail Report (30 Days)', [
            ['title' => 'Test Outcomes', 'columns' => ['Test #', 'Sample #', 'Result'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportNcrReport()
    {
        $rows = NonConformance::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->latest('created_at')
            ->limit(100)
            ->get()
            ->map(fn (NonConformance $n) => [$n->ncr_number, $n->severity, $n->description, $n->status])
            ->all();

        return app(ReportExportService::class)->toPdf('NCR Report', [
            ['title' => 'Non-Conformances', 'columns' => ['NCR #', 'Severity', 'Description', 'Status'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportCapaReport()
    {
        $rows = QualityCorrectiveAction::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->with('nonConformance')
            ->latest('created_at')
            ->limit(100)
            ->get()
            ->map(fn (QualityCorrectiveAction $a) => [$a->action_number, $a->nonConformance?->ncr_number, $a->progress_percent . '%', $a->status])
            ->all();

        return app(ReportExportService::class)->toPdf('CAPA Report', [
            ['title' => 'Corrective Actions', 'columns' => ['Action #', 'NCR #', 'Progress', 'Status'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportCalibrationReport()
    {
        $rows = Calibration::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->with('labEquipment')
            ->latest('calibration_date')
            ->limit(100)
            ->get()
            ->map(fn (Calibration $c) => [$c->calibration_number, $c->labEquipment?->name, $c->calibration_date?->format('d M Y'), $c->due_date?->format('d M Y'), $c->status])
            ->all();

        return app(ReportExportService::class)->toPdf('Calibration Report', [
            ['title' => 'Calibrations', 'columns' => ['Calibration #', 'Equipment', 'Calibrated', 'Due', 'Status'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportCertificateRegister()
    {
        $rows = Certificate::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->with('customer')
            ->latest('created_at')
            ->limit(100)
            ->get()
            ->map(fn (Certificate $c) => [$c->certificate_number, $c->type, $c->customer?->company_name, $c->status])
            ->all();

        return app(ReportExportService::class)->toPdf('Certificate Register', [
            ['title' => 'Certificates', 'columns' => ['Certificate #', 'Type', 'Customer', 'Status'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportCustomerQualityReport()
    {
        $rows = CustomerQualityRequirement::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->with(['customer', 'material'])
            ->get()
            ->map(fn (CustomerQualityRequirement $r) => [$r->customer?->company_name, $r->material?->name, $r->max_dust_percent, $r->moisture_limit, $r->status])
            ->all();

        return app(ReportExportService::class)->toPdf('Customer Quality Report', [
            ['title' => 'Customer Quality Requirements', 'columns' => ['Customer', 'Product', 'Max Dust %', 'Moisture Limit %', 'Status'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportExecutiveSummary()
    {
        $svc = app(LabAnalyticsService::class);
        $companyId = $this->companyId();

        $kpis = [
            ['label' => 'Samples Received Today', 'value' => (string) $svc->samplesReceivedTodayCount($companyId)],
            ['label' => 'Quality Pass Rate', 'value' => $svc->qualityPassRatePercent($companyId) . '%'],
            ['label' => 'Products On Hold', 'value' => (string) $svc->productsOnHoldCount($companyId)],
            ['label' => 'Open Non-Conformances', 'value' => (string) $svc->openNonConformancesCount($companyId)],
            ['label' => 'Corrective Actions', 'value' => (string) $svc->correctiveActionsCount($companyId)],
            ['label' => 'Certificates Issued', 'value' => (string) $svc->certificatesIssuedCount($companyId)],
            ['label' => 'Avg. Test Time', 'value' => number_format($svc->averageTestTimeMinutes($companyId), 0) . ' min'],
            ['label' => 'Lab Utilization', 'value' => $svc->laboratoryUtilizationPercent($companyId) . '%'],
        ];

        $recentRows = LabSample::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->latest('created_at')
            ->limit(10)
            ->get()
            ->map(fn (LabSample $s) => [$s->sample_number, $s->sample_type, $s->status])
            ->all();

        return app(ReportExportService::class)->toPdf('Executive Quality Dashboard', [
            ['title' => 'Recent Samples', 'columns' => ['Sample #', 'Type', 'Status'], 'rows' => $recentRows],
        ], $kpis, $companyId);
    }
}
