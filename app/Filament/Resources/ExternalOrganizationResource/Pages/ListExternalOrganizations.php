<?php

namespace App\Filament\Resources\ExternalOrganizationResource\Pages;

use App\Filament\Resources\ExternalOrganizationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListExternalOrganizations extends ListRecords
{
    protected static string $resource = ExternalOrganizationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
