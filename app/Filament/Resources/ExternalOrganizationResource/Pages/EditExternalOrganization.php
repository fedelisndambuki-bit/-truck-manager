<?php

namespace App\Filament\Resources\ExternalOrganizationResource\Pages;

use App\Filament\Resources\ExternalOrganizationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditExternalOrganization extends EditRecord
{
    protected static string $resource = ExternalOrganizationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
