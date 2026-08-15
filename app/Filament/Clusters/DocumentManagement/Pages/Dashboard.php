<?php

namespace App\Filament\Clusters\DocumentManagement\Pages;

use App\Filament\Clusters\DocumentManagement;
use App\Filament\Clusters\DocumentManagement\Widgets;
use App\Traits\HasModuleAccess;
use Filament\Pages\Page;

class Dashboard extends Page
{
    use HasModuleAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'documents.view';
    }

    protected static ?string $cluster = DocumentManagement::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Document Management Dashboard';

    protected static string $view = 'filament.clusters.document-management.pages.dashboard';

    public function getHeaderWidgets(): array
    {
        return [
            Widgets\DocumentQuickActionsWidget::class,
            Widgets\DocumentKpiWidget::class,
            Widgets\DocumentTypesChart::class,
            Widgets\DocumentsByProjectChart::class,
            Widgets\ApprovalStatusChart::class,
            Widgets\RevisionTrendChart::class,
            Widgets\MonthlyUploadsChart::class,
            Widgets\RfiStatusChart::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 2;
    }
}
