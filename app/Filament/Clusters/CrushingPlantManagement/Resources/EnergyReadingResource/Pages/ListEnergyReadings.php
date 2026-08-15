<?php

namespace App\Filament\Clusters\CrushingPlantManagement\Resources\EnergyReadingResource\Pages;

use App\Filament\Clusters\CrushingPlantManagement\Resources\EnergyReadingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListEnergyReadings extends ListRecords
{
    protected static string $resource = EnergyReadingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-bolt',
            'title' => 'Energy Readings',
            'subtitle' => 'Record and monitor power consumption readings across plant equipment.',
        ]);
    }
}
