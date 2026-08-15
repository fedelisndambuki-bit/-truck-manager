<?php

namespace App\Filament\Clusters\CrushingPlantManagement\Resources\PlantDowntimeResource\Pages;

use App\Filament\Clusters\CrushingPlantManagement\Resources\PlantDowntimeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListPlantDowntimes extends ListRecords
{
    protected static string $resource = PlantDowntimeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-pause-circle',
            'title' => 'Plant Downtimes',
            'subtitle' => 'Log and track unplanned and scheduled downtime events at the crushing plant.',
        ]);
    }
}
