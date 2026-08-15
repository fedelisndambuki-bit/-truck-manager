<?php

namespace App\Filament\Resources\SafetyDocumentResource\Pages;

use App\Filament\Resources\SafetyDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSafetyDocument extends EditRecord
{
    protected static string $resource = SafetyDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
