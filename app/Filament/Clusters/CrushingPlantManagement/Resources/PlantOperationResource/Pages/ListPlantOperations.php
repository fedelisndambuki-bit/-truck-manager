<?php

namespace App\Filament\Clusters\CrushingPlantManagement\Resources\PlantOperationResource\Pages;

use App\Filament\Clusters\CrushingPlantManagement\Resources\PlantOperationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListPlantOperations extends ListRecords
{
    protected static string $resource = PlantOperationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-clipboard-document-check',
            'title' => 'Plant Operations',
            'subtitle' => 'Record daily plant operational activity and shift performance.',
        ]);
    }
}
