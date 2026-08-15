<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources\EquipmentUtilizationLogResource\Pages;

use App\Filament\Clusters\EquipmentAssetManagement\Resources\EquipmentUtilizationLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListEquipmentUtilizationLogs extends ListRecords
{
    protected static string $resource = EquipmentUtilizationLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-chart-bar-square',
            'title' => 'Equipment Utilization Logs',
            'subtitle' => 'Track usage and utilization metrics for equipment over time.',
        ]);
    }
}
