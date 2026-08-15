<?php

namespace App\Filament\Pages;

use App\Models\DailySiteDiary;
use App\Models\DiaryDelay;
use App\Traits\HasIndustryAccess;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class DailySiteDiaryReports extends Page
{
    use HasIndustryAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction'];
    }

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationLabel = 'Reports';

    protected static ?string $navigationGroup = 'Construction';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'Daily Site Diary Reports';

    protected static string $view = 'filament.pages.daily-site-diary-reports';

    private function companyId(): ?int
    {
        return Auth::user()?->company_id;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('daily_report')->label('Daily Site Report')->icon('heroicon-o-document-arrow-down')->color('info')->action('generateDailyReport'),
            Action::make('weekly_report')->label('Weekly Site Report')->icon('heroicon-o-document-arrow-down')->color('info')->action('generateWeeklyReport'),
            Action::make('monthly_report')->label('Monthly Site Report')->icon('heroicon-o-document-arrow-down')->color('info')->action('generateMonthlyReport'),
            Action::make('supervisor_report')->label('Supervisor Report')->icon('heroicon-o-document-arrow-down')->color('success')->action('generateSupervisorReport'),
            Action::make('weather_report')->label('Weather Report')->icon('heroicon-o-document-arrow-down')->color('info')->action('generateWeatherReport'),
            Action::make('equipment_report')->label('Equipment Report')->icon('heroicon-o-document-arrow-down')->color('warning')->action('generateEquipmentReport'),
            Action::make('labour_report')->label('Labour Report')->icon('heroicon-o-document-arrow-down')->color('success')->action('generateLabourReport'),
            Action::make('material_report')->label('Material Report')->icon('heroicon-o-document-arrow-down')->color('warning')->action('generateMaterialReport'),
            Action::make('delay_report')->label('Delay Report')->icon('heroicon-o-document-arrow-down')->color('danger')->action('generateDelayReport'),
            Action::make('progress_report')->label('Progress Report')->icon('heroicon-o-document-arrow-down')->color('info')->action('generateProgressReport'),
            Action::make('executive_summary')->label('Executive Summary')->icon('heroicon-o-document-arrow-down')->color('gray')->action('generateExecutiveSummary'),
        ];
    }

    private function scopedDiaries()
    {
        return DailySiteDiary::query()->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()));
    }

    public function generateDailyReport()
    {
        $diaries = $this->scopedDiaries()->whereDate('date', now())->with(['site', 'project', 'loggedBy', 'activities', 'delays', 'diaryVisitors'])->get();

        return $this->streamDiaries('reports.diary-daily', 'Daily Site Report', $diaries, 'daily-site-report');
    }

    public function generateWeeklyReport()
    {
        $diaries = $this->scopedDiaries()->whereBetween('date', [now()->subDays(6)->toDateString(), now()->toDateString()])
            ->with(['site', 'project', 'loggedBy', 'activities', 'delays', 'diaryVisitors'])->get();

        return $this->streamDiaries('reports.diary-daily', 'Weekly Site Report', $diaries, 'weekly-site-report');
    }

    public function generateMonthlyReport()
    {
        $diaries = $this->scopedDiaries()->whereBetween('date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
            ->with(['site', 'project', 'loggedBy', 'activities', 'delays', 'diaryVisitors'])->get();

        return $this->streamDiaries('reports.diary-daily', 'Monthly Site Report', $diaries, 'monthly-site-report');
    }

    public function generateSupervisorReport()
    {
        $diaries = $this->scopedDiaries()->where('date', '>=', now()->subDays(30))->with('loggedBy', 'site')->get();

        $bySupervisor = $diaries->groupBy(fn (DailySiteDiary $d) => $d->loggedBy?->full_name ?? 'Unassigned');

        $pdf = Pdf::loadView('reports.diary-supervisor', [
            'title' => 'Supervisor Report',
            'date' => now()->format('d M Y'),
            'bySupervisor' => $bySupervisor,
        ]);

        return $this->download($pdf, 'supervisor-report');
    }

    public function generateWeatherReport()
    {
        $diaries = $this->scopedDiaries()->where('date', '>=', now()->subDays(30))->with('site')->orderByDesc('date')->get();

        $pdf = Pdf::loadView('reports.diary-weather', [
            'title' => 'Weather Report',
            'date' => now()->format('d M Y'),
            'diaries' => $diaries,
        ]);

        return $this->download($pdf, 'weather-report');
    }

    public function generateEquipmentReport()
    {
        $diaries = $this->scopedDiaries()->where('date', '>=', now()->subDays(30))->with('site')->orderByDesc('date')->get();

        $pdf = Pdf::loadView('reports.diary-equipment', [
            'title' => 'Equipment Report',
            'date' => now()->format('d M Y'),
            'diaries' => $diaries,
        ]);

        return $this->download($pdf, 'equipment-report');
    }

    public function generateLabourReport()
    {
        $diaries = $this->scopedDiaries()->where('date', '>=', now()->subDays(30))->with('site')->orderByDesc('date')->get();

        $pdf = Pdf::loadView('reports.diary-labour', [
            'title' => 'Labour Report',
            'date' => now()->format('d M Y'),
            'diaries' => $diaries,
        ]);

        return $this->download($pdf, 'labour-report');
    }

    public function generateMaterialReport()
    {
        $diaries = $this->scopedDiaries()->where('date', '>=', now()->subDays(30))->with('site')->orderByDesc('date')->get();

        $pdf = Pdf::loadView('reports.diary-material', [
            'title' => 'Material Report',
            'date' => now()->format('d M Y'),
            'diaries' => $diaries,
        ]);

        return $this->download($pdf, 'material-report');
    }

    public function generateDelayReport()
    {
        $delays = DiaryDelay::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->with('diary.site')
            ->latest('created_at')
            ->get();

        $pdf = Pdf::loadView('reports.diary-delay', [
            'title' => 'Delay Report',
            'date' => now()->format('d M Y'),
            'delays' => $delays,
        ]);

        return $this->download($pdf, 'delay-report');
    }

    public function generateProgressReport()
    {
        $diaries = $this->scopedDiaries()->where('date', '>=', now()->subDays(30))->with(['site', 'project'])->orderBy('date')->get();

        $pdf = Pdf::loadView('reports.diary-progress', [
            'title' => 'Progress Report',
            'date' => now()->format('d M Y'),
            'diaries' => $diaries,
        ]);

        return $this->download($pdf, 'progress-report');
    }

    public function generateExecutiveSummary()
    {
        $companyId = $this->companyId();
        $diaries = $this->scopedDiaries()->where('date', '>=', now()->subDays(30))->get();

        $summary = [
            'total_diaries' => $diaries->count(),
            'approved' => $diaries->whereIn('status', ['Approved', 'Locked'])->count(),
            'pending' => $diaries->whereIn('status', ['Submitted', 'Reviewed'])->count(),
            'rejected' => $diaries->where('status', 'Rejected')->count(),
            'avg_progress' => round($diaries->avg('progress_pct') ?? 0, 1),
            'avg_workforce' => round($diaries->avg('workers_on_site') ?? 0, 1),
            'total_deliveries' => $diaries->sum('material_deliveries_count'),
            'total_shortages' => $diaries->sum('material_shortages_count'),
            'open_delays' => DiaryDelay::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->where('status', 'Open')->count(),
            'severe_weather_days' => $diaries->where('weather_impact', 'Severe')->count(),
        ];

        $pdf = Pdf::loadView('reports.diary-executive-summary', [
            'title' => 'Executive Summary',
            'date' => now()->format('d M Y'),
            'summary' => $summary,
        ]);

        return $this->download($pdf, 'executive-summary');
    }

    private function streamDiaries(string $view, string $title, $diaries, string $filename)
    {
        $pdf = Pdf::loadView($view, [
            'title' => $title,
            'date' => now()->format('d M Y'),
            'diaries' => $diaries,
            'currency' => session('currency', 'KES'),
        ]);

        return $this->download($pdf, $filename);
    }

    private function download($pdf, string $filename)
    {
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, $filename . '-' . now()->format('Y-m-d') . '.pdf');
    }

    public function getReportCards(): array
    {
        $companyId = $this->companyId();
        $recentDiaries = $this->scopedDiaries()->where('date', '>=', now()->subDays(30));

        return [
            ['title' => 'Daily Site Report', 'description' => "Today's diaries across all sites.", 'icon' => 'heroicon-o-calendar', 'count' => $this->scopedDiaries()->whereDate('date', now())->count() . ' today'],
            ['title' => 'Weekly Site Report', 'description' => 'Diaries logged in the last 7 days.', 'icon' => 'heroicon-o-calendar-days', 'count' => (clone $recentDiaries)->where('date', '>=', now()->subDays(6))->count() . ' this week'],
            ['title' => 'Monthly Site Report', 'description' => 'Diaries logged this calendar month.', 'icon' => 'heroicon-o-document-duplicate', 'count' => (clone $recentDiaries)->where('date', '>=', now()->startOfMonth())->count() . ' this month'],
            ['title' => 'Supervisor Report', 'description' => 'Diary activity grouped by supervisor.', 'icon' => 'heroicon-o-user', 'count' => (clone $recentDiaries)->distinct('logged_by_id')->count('logged_by_id') . ' supervisors'],
            ['title' => 'Weather Report', 'description' => 'Weather conditions and impact history.', 'icon' => 'heroicon-o-cloud', 'count' => (clone $recentDiaries)->count() . ' days tracked'],
            ['title' => 'Equipment Report', 'description' => 'Equipment utilization and breakdowns by day.', 'icon' => 'heroicon-o-truck', 'count' => (clone $recentDiaries)->sum('equipment_active_count') . ' equipment-days'],
            ['title' => 'Labour Report', 'description' => 'Workforce presence, absence, and overtime trend.', 'icon' => 'heroicon-o-users', 'count' => (clone $recentDiaries)->sum('workers_on_site') . ' worker-days'],
            ['title' => 'Material Report', 'description' => 'Deliveries and shortages by day.', 'icon' => 'heroicon-o-cube', 'count' => (clone $recentDiaries)->sum('material_deliveries_count') . ' deliveries'],
            ['title' => 'Delay Report', 'description' => 'All logged delays with type and impact.', 'icon' => 'heroicon-o-exclamation-triangle', 'count' => DiaryDelay::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->count() . ' delays'],
            ['title' => 'Progress Report', 'description' => 'Daily progress % trend by site and project.', 'icon' => 'heroicon-o-chart-bar', 'count' => round((clone $recentDiaries)->avg('progress_pct') ?? 0, 1) . '% avg'],
            ['title' => 'Executive Summary', 'description' => 'High-level rollup across all diary metrics.', 'icon' => 'heroicon-o-presentation-chart-line', 'count' => 'Last 30 days'],
        ];
    }
}
