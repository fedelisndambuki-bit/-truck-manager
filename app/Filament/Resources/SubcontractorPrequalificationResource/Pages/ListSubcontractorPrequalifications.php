<?php

namespace App\Filament\Resources\SubcontractorPrequalificationResource\Pages;

use App\Filament\Resources\SubcontractorPrequalificationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSubcontractorPrequalifications extends ListRecords
{
    protected static string $resource = SubcontractorPrequalificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('New Assessment'),
        ];
    }
}
