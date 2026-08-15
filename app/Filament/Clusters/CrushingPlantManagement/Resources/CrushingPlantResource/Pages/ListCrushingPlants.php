<?php

namespace App\Filament\Clusters\CrushingPlantManagement\Resources\CrushingPlantResource\Pages;

use App\Filament\Clusters\CrushingPlantManagement\Resources\CrushingPlantResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListCrushingPlants extends ListRecords
{
    protected static string $resource = CrushingPlantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-building-office-2',
            'title' => 'Crushing Plants',
            'subtitle' => 'Manage crushing plant facilities and their operational configuration.',
        ]);
    }
}
