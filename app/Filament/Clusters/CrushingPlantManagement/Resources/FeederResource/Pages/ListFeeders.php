<?php

namespace App\Filament\Clusters\CrushingPlantManagement\Resources\FeederResource\Pages;

use App\Filament\Clusters\CrushingPlantManagement\Resources\FeederResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListFeeders extends ListRecords
{
    protected static string $resource = FeederResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-inbox-arrow-down',
            'title' => 'Feeders',
            'subtitle' => 'Manage feeder units that regulate material flow into the crushing process.',
        ]);
    }
}
