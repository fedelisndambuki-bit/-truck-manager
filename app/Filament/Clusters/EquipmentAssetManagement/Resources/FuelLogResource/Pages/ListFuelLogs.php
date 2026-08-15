<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources\FuelLogResource\Pages;

use App\Filament\Clusters\EquipmentAssetManagement\Resources\FuelLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListFuelLogs extends ListRecords
{
    protected static string $resource = FuelLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-beaker',
            'title' => 'Fuel Logs',
            'subtitle' => 'Record and monitor fuel consumption across equipment.',
        ]);
    }
}
