<?php

namespace App\Filament\Clusters\CrushingPlantManagement\Resources\ConveyorResource\Pages;

use App\Filament\Clusters\CrushingPlantManagement\Resources\ConveyorResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListConveyors extends ListRecords
{
    protected static string $resource = ConveyorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-arrows-right-left',
            'title' => 'Conveyors',
            'subtitle' => 'Monitor and manage conveyor belts transporting material through the crushing plant.',
        ]);
    }
}
