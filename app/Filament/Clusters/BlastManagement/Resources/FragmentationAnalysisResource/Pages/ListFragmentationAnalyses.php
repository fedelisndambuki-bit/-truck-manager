<?php

namespace App\Filament\Clusters\BlastManagement\Resources\FragmentationAnalysisResource\Pages;

use App\Filament\Clusters\BlastManagement\Resources\FragmentationAnalysisResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFragmentationAnalyses extends ListRecords
{
    protected static string $resource = FragmentationAnalysisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
