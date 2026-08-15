<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\DocumentResource;
use App\Filament\Resources\EquipmentAllocationResource;
use App\Filament\Resources\LabourerResource;
use App\Filament\Resources\MaterialResource;
use App\Filament\Resources\ProjectResource;
use App\Filament\Resources\SiteInspectionResource;
use App\Filament\Resources\SiteResource;
use App\Filament\Pages\ConstructionReports;
use Filament\Widgets\Widget;

class QuickActionsWidget extends Widget
{
    protected static string $view = 'filament.widgets.quick-actions';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1;

    public function getActions(): array
    {
        return [
            ['label' => 'Create Project', 'icon' => 'heroicon-o-building-office-2', 'url' => ProjectResource::getUrl('create')],
            ['label' => 'Add Site', 'icon' => 'heroicon-o-map-pin', 'url' => SiteResource::getUrl('create')],
            ['label' => 'Assign Equipment', 'icon' => 'heroicon-o-truck', 'url' => EquipmentAllocationResource::getUrl('create')],
            ['label' => 'Add Material Delivery', 'icon' => 'heroicon-o-cube', 'url' => MaterialResource::getUrl('create')],
            ['label' => 'Register Worker', 'icon' => 'heroicon-o-user-group', 'url' => LabourerResource::getUrl('create')],
            ['label' => 'Record Inspection', 'icon' => 'heroicon-o-clipboard-document-check', 'url' => SiteInspectionResource::getUrl('create')],
            ['label' => 'Upload Documents', 'icon' => 'heroicon-o-document-text', 'url' => DocumentResource::getUrl('create')],
            ['label' => 'Generate Report', 'icon' => 'heroicon-o-document-chart-bar', 'url' => ConstructionReports::getUrl()],
        ];
    }
}
