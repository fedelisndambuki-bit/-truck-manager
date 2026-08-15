<?php

namespace App\Filament\Resources\SubcontractorAssignmentResource\Pages;

use App\Filament\Resources\SubcontractorAssignmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSubcontractorAssignment extends EditRecord
{
    protected static string $resource = SubcontractorAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
