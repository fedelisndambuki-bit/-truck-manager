<?php

namespace App\Filament\Clusters\WorkflowApprovals\Pages;

use App\Filament\Clusters\WorkflowApprovals;
use App\Filament\Clusters\WorkflowApprovals\Widgets;
use App\Traits\HasModuleAccess;
use Filament\Pages\Page;
use Illuminate\Contracts\View\View;

class Dashboard extends Page
{
    use HasModuleAccess;

    public static function getModuleSlug(): ?string
    {
        return 'workflow.view';
    }

    protected static ?string $cluster = WorkflowApprovals::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Workflow & Approvals Dashboard';

    protected static string $view = 'filament.clusters.workflow-approvals.pages.dashboard';

    /**
     * Filament renders getHeaderWidgets() BEFORE the page's own view content
     * (the {{ $slot }} in vendor page/index.blade.php) — so the custom title
     * banner must be returned here, not placed inside the view body, or it
     * ends up stranded below every widget.
     */
    public function getHeader(): ?View
    {
        return view('filament.construction.partials.header-standalone', [
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />',
            'title' => 'Workflow & Approvals',
            'subtitle' => 'The platform-wide approval engine — pending work, SLA health and usage across every module and industry.',
        ]);
    }

    public function getHeaderWidgets(): array
    {
        return [
            Widgets\WorkflowKpiWidget::class,
            Widgets\ApprovalTrendsChart::class,
            Widgets\WorkflowUsageChart::class,
            Widgets\AverageApprovalTimeChart::class,
            Widgets\DepartmentActivityChart::class,
            Widgets\PendingByModuleChart::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 2;
    }
}
