<?php

namespace App\Filament\Resources\MaterialWastageResource\Pages;

use App\Filament\Resources\MaterialWastageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMaterialWastage extends EditRecord
{
    protected static string $resource = MaterialWastageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
