<?php

namespace App\Filament\Widgets\Quarry;

use App\Filament\Clusters\CrushingPlantManagement\Resources\CrushingPlantResource;
use App\Filament\Resources\FuelLogResource;
use App\Filament\Resources\MaintenanceResource;
use App\Filament\Clusters\BlastManagement\Resources\BlastPlanResource;
use App\Filament\Clusters\ProductionManagement\Resources\ProductionRecordResource;
use App\Filament\Resources\SafetyIncidentResource;
use App\Filament\Clusters\StockpileManagement\Resources\StockpileResource;
use App\Filament\Resources\WeighbridgeTicketResource;
use Filament\Widgets\Widget;

class QuarryQuickActionsWidget extends Widget
{
    protected static string $view = 'filament.widgets.quarry.quick-actions';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1;

    public function getActions(): array
    {
        return [
            ['label' => 'Log Production', 'icon' => 'heroicon-o-cube', 'url' => ProductionRecordResource::getUrl('create')],
            ['label' => 'Generate Weighbridge Ticket', 'icon' => 'heroicon-o-scale', 'url' => WeighbridgeTicketResource::getUrl('create')],
            ['label' => 'Add Crushing Plant', 'icon' => 'heroicon-o-cog-6-tooth', 'url' => CrushingPlantResource::getUrl('create')],
            ['label' => 'Update Stockpile', 'icon' => 'heroicon-o-archive-box', 'url' => StockpileResource::getUrl('create')],
            ['label' => 'Issue Fuel', 'icon' => 'heroicon-o-beaker', 'url' => FuelLogResource::getUrl('create')],
            ['label' => 'Create Maintenance Work Order', 'icon' => 'heroicon-o-wrench-screwdriver', 'url' => MaintenanceResource::getUrl('create')],
            ['label' => 'Report Safety Incident', 'icon' => 'heroicon-o-exclamation-triangle', 'url' => SafetyIncidentResource::getUrl('create')],
            ['label' => 'Schedule Blast', 'icon' => 'heroicon-o-fire', 'url' => BlastPlanResource::getUrl('create')],
            ['label' => 'Create Customer Order', 'icon' => 'heroicon-o-shopping-cart', 'url' => null, 'comingSoon' => true],
        ];
    }
}
