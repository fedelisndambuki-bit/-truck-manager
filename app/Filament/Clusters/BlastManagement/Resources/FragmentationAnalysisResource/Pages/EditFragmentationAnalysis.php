<?php

namespace App\Filament\Clusters\BlastManagement\Resources\FragmentationAnalysisResource\Pages;

use App\Filament\Clusters\BlastManagement\Resources\FragmentationAnalysisResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFragmentationAnalysis extends EditRecord
{
    protected static string $resource = FragmentationAnalysisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
