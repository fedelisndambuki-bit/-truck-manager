<?php

namespace App\Filament\Clusters\ProductionManagement\Resources\PitResource\Pages;

use App\Filament\Clusters\ProductionManagement\Resources\PitResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListPits extends ListRecords
{
    protected static string $resource = PitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-arrow-down-circle',
            'title' => 'Pits',
            'subtitle' => 'Monitor pit depth, reserves, and safety status across your quarry sites.',
        ]);
    }
}
