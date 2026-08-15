<?php

namespace App\Filament\Pages;

use App\Models\Project;
use App\Models\SafetyIncident;
use App\Models\Site;
use App\Traits\HasIndustryAccess;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class ConstructionReports extends Page
{
    use HasIndustryAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction'];
    }

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationLabel = 'Reports';

    protected static ?string $navigationGroup = 'Construction';

    protected static ?int $navigationSort = 16;

    protected static ?string $title = 'Reports';

    protected static string $view = 'filament.pages.construction-reports';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('project_status_report')
                ->label('Project Status Report')
                ->icon('heroicon-o-document-arrow-down')
                ->color('info')
                ->action('generateProjectStatusReport'),
            Action::make('safety_report')
                ->label('Safety Incident Report')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->action('generateSafetyReport'),
            Action::make('site_overview_report')
                ->label('Site Overview')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->action('generateSiteOverviewReport'),
        ];
    }

    public function generateProjectStatusReport()
    {
        $companyId = Auth::user()?->company_id;

        $projects = Project::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->with(['client', 'projectManager'])
            ->get();

        $pdf = Pdf::loadView('reports.construction-project-status', [
            'title' => 'Project Status Report',
            'date' => now()->format('d M Y'),
            'projects' => $projects,
            'currency' => session('currency', 'KES'),
        ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'project-status-report-' . now()->format('Y-m-d') . '.pdf');
    }

    public function generateSafetyReport()
    {
        $companyId = Auth::user()?->company_id;

        $incidents = SafetyIncident::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->with(['site', 'reportedBy'])
            ->latest('date')
            ->get();

        $pdf = Pdf::loadView('reports.construction-safety', [
            'title' => 'Safety Incident Report',
            'date' => now()->format('d M Y'),
            'incidents' => $incidents,
        ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'safety-incident-report-' . now()->format('Y-m-d') . '.pdf');
    }

    public function generateSiteOverviewReport()
    {
        $companyId = Auth::user()?->company_id;

        $sites = Site::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->with('project')
            ->get();

        $pdf = Pdf::loadView('reports.construction-site-overview', [
            'title' => 'Site Overview Report',
            'date' => now()->format('d M Y'),
            'sites' => $sites,
        ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'site-overview-report-' . now()->format('Y-m-d') . '.pdf');
    }

    public function getReportCards(): array
    {
        $companyId = Auth::user()?->company_id;

        return [
            [
                'title' => 'Project Status Report',
                'description' => 'All projects with progress, budget, and timeline status.',
                'icon' => 'heroicon-o-building-office-2',
                'count' => Project::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->count() . ' projects',
            ],
            [
                'title' => 'Safety Incident Report',
                'description' => 'Full history of reported safety incidents across all sites.',
                'icon' => 'heroicon-o-exclamation-triangle',
                'count' => SafetyIncident::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->count() . ' incidents',
            ],
            [
                'title' => 'Site Overview',
                'description' => 'Active/inactive sites summary with progress and phase.',
                'icon' => 'heroicon-o-map-pin',
                'count' => Site::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->count() . ' sites',
            ],
        ];
    }
}
