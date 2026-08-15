<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources\TelematicsReadingResource\Pages;

use App\Filament\Clusters\EquipmentAssetManagement\Resources\TelematicsReadingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListTelematicsReadings extends ListRecords
{
    protected static string $resource = TelematicsReadingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-signal',
            'title' => 'Telematics Readings',
            'subtitle' => 'Monitor telematics data collected from connected equipment.',
        ]);
    }
}
