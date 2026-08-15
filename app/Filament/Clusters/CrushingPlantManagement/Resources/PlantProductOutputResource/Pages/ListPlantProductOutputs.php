<?php

namespace App\Filament\Clusters\CrushingPlantManagement\Resources\PlantProductOutputResource\Pages;

use App\Filament\Clusters\CrushingPlantManagement\Resources\PlantProductOutputResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListPlantProductOutputs extends ListRecords
{
    protected static string $resource = PlantProductOutputResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-cube-transparent',
            'title' => 'Plant Product Outputs',
            'subtitle' => 'Track finished product output produced by the crushing plant.',
        ]);
    }
}
