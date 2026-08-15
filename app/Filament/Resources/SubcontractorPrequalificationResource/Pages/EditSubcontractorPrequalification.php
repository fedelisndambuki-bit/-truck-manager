<?php

namespace App\Filament\Resources\SubcontractorPrequalificationResource\Pages;

use App\Filament\Resources\SubcontractorPrequalificationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSubcontractorPrequalification extends EditRecord
{
    protected static string $resource = SubcontractorPrequalificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
