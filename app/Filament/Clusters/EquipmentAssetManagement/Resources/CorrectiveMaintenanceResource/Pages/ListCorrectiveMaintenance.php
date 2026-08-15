<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources\CorrectiveMaintenanceResource\Pages;

use App\Filament\Clusters\EquipmentAssetManagement\Resources\CorrectiveMaintenanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListCorrectiveMaintenance extends ListRecords
{
    protected static string $resource = CorrectiveMaintenanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-wrench',
            'title' => 'Corrective Maintenance',
            'subtitle' => 'Manage unscheduled repairs performed to fix equipment faults.',
        ]);
    }
}
