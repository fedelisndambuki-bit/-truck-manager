<?php

namespace App\Filament\Clusters\DocumentManagement\Widgets;

use App\Filament\Clusters\DocumentManagement\Resources\DocumentResource;
use App\Filament\Clusters\DocumentManagement\Resources\DrawingResource;
use App\Filament\Clusters\DocumentManagement\Resources\RfiResource;
use App\Filament\Clusters\DocumentManagement\Resources\TransmittalResource;
use App\Filament\Clusters\DocumentManagement\Pages\Reports;
use Filament\Widgets\Widget;

class DocumentQuickActionsWidget extends Widget
{
    // Reuses the generic quick-actions view already used by Construction and
    // Project Finance dashboards — it just loops getActions().
    protected static string $view = 'filament.widgets.quick-actions';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1;

    public function getActions(): array
    {
        return [
            ['label' => 'Upload Documents', 'icon' => 'heroicon-o-arrow-up-tray', 'url' => DocumentResource::getUrl('create')],
            ['label' => 'Issue RFI', 'icon' => 'heroicon-o-question-mark-circle', 'url' => RfiResource::getUrl('create')],
            ['label' => 'Submit Drawing', 'icon' => 'heroicon-o-pencil-square', 'url' => DrawingResource::getUrl('create')],
            ['label' => 'Approve Documents', 'icon' => 'heroicon-o-check-circle', 'url' => DocumentResource::getUrl('index', ['tableFilters[status][value]' => 'Submitted'])],
            ['label' => 'Create Transmittal', 'icon' => 'heroicon-o-paper-airplane', 'url' => TransmittalResource::getUrl('create')],
            ['label' => 'Generate Reports', 'icon' => 'heroicon-o-document-chart-bar', 'url' => Reports::getUrl()],
        ];
    }
}
