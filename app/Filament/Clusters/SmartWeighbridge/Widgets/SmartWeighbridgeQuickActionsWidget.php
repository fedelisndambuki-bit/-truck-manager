<?php

namespace App\Filament\Clusters\SmartWeighbridge\Widgets;

use App\Filament\Clusters\SmartWeighbridge\Resources\DispatchApprovalResource;
use App\Filament\Clusters\SmartWeighbridge\Resources\GrossWeighingResource;
use App\Filament\Clusters\SmartWeighbridge\Resources\VehicleCheckInResource;
use App\Filament\Clusters\SmartWeighbridge\Resources\VehicleExitResource;
use App\Filament\Clusters\SmartWeighbridge\Resources\VehicleQueueResource;
use Filament\Widgets\Widget;

class SmartWeighbridgeQuickActionsWidget extends Widget
{
    protected static string $view = 'filament.widgets.quarry.quick-actions';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1;

    public function getActions(): array
    {
        return [
            ['label' => 'Check-In Vehicle', 'icon' => 'heroicon-o-identification', 'url' => VehicleCheckInResource::getUrl('create')],
            ['label' => 'View Queue', 'icon' => 'heroicon-o-queue-list', 'url' => VehicleQueueResource::getUrl('index')],
            ['label' => 'Record Gross Weight', 'icon' => 'heroicon-o-scale', 'url' => GrossWeighingResource::getUrl('index')],
            ['label' => 'Approve Dispatch', 'icon' => 'heroicon-o-clipboard-document-check', 'url' => DispatchApprovalResource::getUrl('index')],
            ['label' => 'Vehicle Exit', 'icon' => 'heroicon-o-arrow-right-on-rectangle', 'url' => VehicleExitResource::getUrl('index')],
            ['label' => 'Print Ticket', 'icon' => 'heroicon-o-printer', 'url' => \App\Filament\Clusters\SmartWeighbridge\Pages\Tickets::getUrl()],
            ['label' => 'Generate Report', 'icon' => 'heroicon-o-document-arrow-down', 'url' => \App\Filament\Clusters\SmartWeighbridge\Pages\Reports::getUrl()],
        ];
    }
}
