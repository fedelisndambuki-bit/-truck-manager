<?php

namespace App\Filament\Clusters\CrushingPlantManagement\Resources\ProductionLineResource\Pages;

use App\Filament\Clusters\CrushingPlantManagement\Resources\ProductionLineResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListProductionLines extends ListRecords
{
    protected static string $resource = ProductionLineResource::class;

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
            'title' => 'Production Lines',
            'subtitle' => 'Manage production lines and their equipment configuration within the plant.',
        ]);
    }
}
