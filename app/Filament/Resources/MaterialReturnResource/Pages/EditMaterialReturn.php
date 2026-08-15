<?php

namespace App\Filament\Resources\MaterialReturnResource\Pages;

use App\Filament\Resources\MaterialReturnResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMaterialReturn extends EditRecord
{
    protected static string $resource = MaterialReturnResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
