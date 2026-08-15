<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources\EquipmentHealthReadingResource\Pages;

use App\Filament\Clusters\EquipmentAssetManagement\Resources\EquipmentHealthReadingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListEquipmentHealthReadings extends ListRecords
{
    protected static string $resource = EquipmentHealthReadingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-heart',
            'title' => 'Equipment Health Readings',
            'subtitle' => 'Monitor equipment health metrics and diagnostic readings.',
        ]);
    }
}
