<?php

namespace App\Filament\Clusters\DocumentManagement\Resources\TechnicalSubmittalResource\Pages;

use App\Filament\Clusters\DocumentManagement\Resources\TechnicalSubmittalResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTechnicalSubmittal extends EditRecord
{
    protected static string $resource = TechnicalSubmittalResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
