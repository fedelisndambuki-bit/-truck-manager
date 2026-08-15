<?php

namespace App\Filament\Resources\LabourerResource\Pages;

use App\Filament\Resources\LabourerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLabourer extends EditRecord
{
    protected static string $resource = LabourerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
