<?php

namespace App\Filament\Clusters\BusinessIntelligence\Pages;

use App\Filament\Clusters\BusinessIntelligence;
use App\Models\Attendance;
use App\Models\Labourer;
use App\Services\Construction\ReportExportService;
use App\Traits\HasModuleAccess;
use Illuminate\Support\Facades\Auth;
use Filament\Pages\Page;

class LabourReports extends Page
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

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Labour Reports';

    protected static ?int $navigationSort = 6;

    protected static ?string $title = 'Labour Reports';

    protected static string $view = 'filament.clusters.business-intelligence.pages.hub-page';

    public function getPageIntro(): string
    {
        return 'Attendance, Payroll, Training, Certification and Leave reports are already available in Labour & HR Management, plus 4 new reports.';
    }

    public function getPageIcon(): string
    {
        return '<path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />';
    }

    public function getHubCards(): array
    {
        return [
            ['label' => 'Productivity', 'description' => 'New: attendance-based productivity rate by role.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportProductivity'],
            ['label' => 'Overtime', 'description' => 'New: workers with extended site hours (proxy from attendance check-in/out).', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportOvertime'],
            ['label' => 'Headcount', 'description' => 'New: current workforce headcount by role and site.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportHeadcount'],
            ['label' => 'Labour Cost', 'description' => 'New: daily rate cost by project.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportLabourCost'],
            ['label' => 'Open HR Reports', 'description' => 'Workforce, Attendance, Leave, Payroll, Training Compliance, Certification Compliance, Recruitment Pipeline and PPE Compliance.', 'icon' => 'heroicon-o-arrow-top-right-on-square', 'url' => \App\Filament\Pages\HrReports::getUrl()],
        ];
    }

    protected function companyId(): ?int
    {
        return Auth::user()?->company_id;
    }

    protected function labourers()
    {
        return Labourer::query()->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))->with(['employee', 'site'])->get();
    }

    public function exportProductivity()
    {
        $rows = $this->labourers()->groupBy('role_on_site')->map(function ($group, $role) {
            $employeeIds = $group->pluck('employee_id')->unique();
            $records = Attendance::query()->whereIn('employee_id', $employeeIds)->where('date', '>=', now()->subDays(30))->get();
            $rate = $records->isNotEmpty() ? round(($records->where('status', 'Present')->count() / $records->count()) * 100, 1) : 0;

            return [$role, $group->count(), $rate . '%'];
        })->values()->all();

        return app(ReportExportService::class)->toPdf('Labour Productivity Report', [
            ['title' => 'Productivity by Role (30d)', 'columns' => ['Role', 'Workers', 'Attendance Rate'], 'rows' => $rows],
        ]);
    }

    public function exportOvertime()
    {
        $records = Attendance::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->whereNotNull('check_in_at')->whereNotNull('check_out_at')
            ->where('date', '>=', now()->subDays(30))
            ->with('employee')
            ->get()
            ->filter(fn (Attendance $a) => $a->check_in_at->diffInHours($a->check_out_at) > 9);

        $rows = $records->map(fn (Attendance $a) => [$a->employee?->full_name, $a->date->format('d M Y'), round($a->check_in_at->diffInHours($a->check_out_at), 1) . ' hrs'])->all();

        return app(ReportExportService::class)->toPdf('Overtime Report', [
            ['title' => 'Extended Site Hours (30d, >9h)', 'columns' => ['Employee', 'Date', 'Hours on Site'], 'rows' => $rows],
        ]);
    }

    public function exportHeadcount()
    {
        $rows = $this->labourers()->groupBy(fn (Labourer $l) => $l->site?->name ?? 'Unassigned')->map(fn ($group, $site) => [$site, $group->count()])->values()->all();

        return app(ReportExportService::class)->toPdf('Headcount Report', [
            ['title' => 'Headcount by Site', 'columns' => ['Site', 'Headcount'], 'rows' => $rows],
        ]);
    }

    public function exportLabourCost()
    {
        $currency = session('currency', 'KES');

        $rows = $this->labourers()
            ->groupBy('project_id')
            ->map(function ($group, $projectId) use ($currency) {
                $project = \App\Models\Project::find($projectId);
                $dailyTotal = $group->sum('daily_rate');

                return [$project?->name ?? 'Unassigned', $group->count(), $currency . ' ' . number_format((float) $dailyTotal, 0) . '/day'];
            })
            ->values()
            ->all();

        return app(ReportExportService::class)->toPdf('Labour Cost Report', [
            ['title' => 'Daily Labour Cost by Project', 'columns' => ['Project', 'Workers', 'Daily Cost'], 'rows' => $rows],
        ]);
    }
}
