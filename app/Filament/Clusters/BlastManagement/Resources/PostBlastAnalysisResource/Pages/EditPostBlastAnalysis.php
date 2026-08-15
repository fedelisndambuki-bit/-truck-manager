<?php

namespace App\Filament\Clusters\BlastManagement\Resources\PostBlastAnalysisResource\Pages;

use App\Filament\Clusters\BlastManagement\Resources\PostBlastAnalysisResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPostBlastAnalysis extends EditRecord
{
    protected static string $resource = PostBlastAnalysisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
