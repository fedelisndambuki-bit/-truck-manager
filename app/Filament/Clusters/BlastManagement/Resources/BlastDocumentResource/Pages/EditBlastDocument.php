<?php

namespace App\Filament\Clusters\BlastManagement\Resources\BlastDocumentResource\Pages;

use App\Filament\Clusters\BlastManagement\Resources\BlastDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBlastDocument extends EditRecord
{
    protected static string $resource = BlastDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
