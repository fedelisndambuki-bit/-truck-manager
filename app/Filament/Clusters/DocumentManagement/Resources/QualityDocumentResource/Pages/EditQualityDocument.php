<?php

namespace App\Filament\Clusters\DocumentManagement\Resources\QualityDocumentResource\Pages;

use App\Filament\Clusters\DocumentManagement\Resources\QualityDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditQualityDocument extends EditRecord
{
    protected static string $resource = QualityDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
