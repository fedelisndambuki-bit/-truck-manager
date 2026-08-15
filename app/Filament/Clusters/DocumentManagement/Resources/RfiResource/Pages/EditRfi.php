<?php

namespace App\Filament\Clusters\DocumentManagement\Resources\RfiResource\Pages;

use App\Filament\Clusters\DocumentManagement\Resources\RfiResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRfi extends EditRecord
{
    protected static string $resource = RfiResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
