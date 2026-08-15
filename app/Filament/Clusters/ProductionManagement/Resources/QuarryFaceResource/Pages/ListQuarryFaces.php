<?php

namespace App\Filament\Clusters\ProductionManagement\Resources\QuarryFaceResource\Pages;

use App\Filament\Clusters\ProductionManagement\Resources\QuarryFaceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListQuarryFaces extends ListRecords
{
    protected static string $resource = QuarryFaceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-viewfinder-circle',
            'title' => 'Quarry Faces',
            'subtitle' => 'Manage active quarry faces, reserves, and safety notes.',
        ]);
    }
}
