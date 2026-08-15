<?php

namespace App\Filament\Resources\SubcontractorAssignmentResource\Pages;

use App\Filament\Resources\SubcontractorAssignmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSubcontractorAssignments extends ListRecords
{
    protected static string $resource = SubcontractorAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('New Assignment'),
        ];
    }
}
