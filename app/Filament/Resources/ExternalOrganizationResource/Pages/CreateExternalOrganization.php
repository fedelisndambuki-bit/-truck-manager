<?php

namespace App\Filament\Resources\ExternalOrganizationResource\Pages;

use App\Filament\Resources\ExternalOrganizationResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateExternalOrganization extends CreateRecord
{
    protected static string $resource = ExternalOrganizationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = Auth::user()?->company_id;

        return $data;
    }
}
