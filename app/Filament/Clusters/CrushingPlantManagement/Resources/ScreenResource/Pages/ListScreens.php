<?php

namespace App\Filament\Clusters\CrushingPlantManagement\Resources\ScreenResource\Pages;

use App\Filament\Clusters\CrushingPlantManagement\Resources\ScreenResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListScreens extends ListRecords
{
    protected static string $resource = ScreenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-table-cells',
            'title' => 'Screens',
            'subtitle' => 'Manage screening equipment used to sort and grade crushed material.',
        ]);
    }
}
