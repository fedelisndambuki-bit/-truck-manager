<?php

namespace App\Filament\Clusters\DocumentManagement\Resources\BoqDocumentResource\Pages;

use App\Filament\Clusters\DocumentManagement\Resources\BoqDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBoqDocument extends EditRecord
{
    protected static string $resource = BoqDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
