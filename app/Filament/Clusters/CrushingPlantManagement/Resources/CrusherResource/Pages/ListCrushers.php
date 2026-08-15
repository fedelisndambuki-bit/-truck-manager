<?php

namespace App\Filament\Clusters\CrushingPlantManagement\Resources\CrusherResource\Pages;

use App\Filament\Clusters\CrushingPlantManagement\Resources\CrusherResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListCrushers extends ListRecords
{
    protected static string $resource = CrusherResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-cog-8-tooth',
            'title' => 'Crushers',
            'subtitle' => 'Track and manage crusher units used to break down raw material at the plant.',
        ]);
    }
}
