<?php

namespace App\Filament\Clusters\DocumentManagement\Resources\SafetyDocumentResource\Pages;

use App\Filament\Clusters\DocumentManagement\Resources\SafetyDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSafetyDocument extends EditRecord
{
    protected static string $resource = SafetyDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
