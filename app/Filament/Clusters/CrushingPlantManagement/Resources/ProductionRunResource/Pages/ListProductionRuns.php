<?php

namespace App\Filament\Clusters\CrushingPlantManagement\Resources\ProductionRunResource\Pages;

use App\Filament\Clusters\CrushingPlantManagement\Resources\ProductionRunResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListProductionRuns extends ListRecords
{
    protected static string $resource = ProductionRunResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-play-circle',
            'title' => 'Production Runs',
            'subtitle' => 'Track production runs from start to approval across crushing plants.',
        ]);
    }
}
