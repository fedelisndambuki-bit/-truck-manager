<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources\MaintenanceWorkOrderResource\Pages;

use App\Filament\Clusters\EquipmentAssetManagement\Resources\MaintenanceWorkOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListMaintenanceWorkOrders extends ListRecords
{
    protected static string $resource = MaintenanceWorkOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-clipboard-document-list',
            'title' => 'Maintenance Work Orders',
            'subtitle' => 'Create and track work orders for equipment maintenance tasks.',
        ]);
    }
}
