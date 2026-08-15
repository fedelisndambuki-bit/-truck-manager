<?php

namespace App\Filament\Clusters\LaboratoryQualityControl\Pages;

use App\Filament\Clusters\LaboratoryQualityControl;
use App\Filament\Clusters\LaboratoryQualityControl\Widgets;
use App\Traits\HasModuleAccess;
use Filament\Pages\Page;
use Illuminate\Contracts\View\View;

class Dashboard extends Page
{
    use HasModuleAccess;

    public static function getRequiredIndustries(): array
    {
        return ['quarry'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'lab.view';
    }

    protected static ?string $cluster = LaboratoryQualityControl::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = 0;

    protected static ?string $title = 'Laboratory & Quality Control Dashboard';

    protected static string $view = 'filament.clusters.laboratory-quality-control.pages.dashboard';

    public function getHeader(): ?View
    {
        return view('filament.construction.partials.header-standalone', [
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23-.693L5 14.5m14.8.8l1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0112 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5" />',
            'title' => 'Laboratory & Quality Control',
            'subtitle' => 'The single source of truth for product quality — samples, laboratory tests, quality approvals, holds/releases, non-conformance/CAPA, certification, equipment/calibration, and customer quality validation.',
        ]);
    }

    public function getHeaderWidgets(): array
    {
        return [
            Widgets\LabQuickActionsWidget::class,
            Widgets\LabKpiWidget::class,
            Widgets\PassRateTrendChart::class,
            Widgets\FailedTestsChart::class,
            Widgets\ProductQualityTrendChart::class,
            Widgets\LaboratoryWorkloadChart::class,
            Widgets\QualityByProductChart::class,
            Widgets\QualityByQuarryChart::class,
            Widgets\CustomerComplaintsChart::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 2;
    }
}
