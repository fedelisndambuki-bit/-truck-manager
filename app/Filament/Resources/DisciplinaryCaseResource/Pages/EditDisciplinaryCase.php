<?php

namespace App\Filament\Resources\DisciplinaryCaseResource\Pages;

use App\Filament\Resources\DisciplinaryCaseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDisciplinaryCase extends EditRecord
{
    protected static string $resource = DisciplinaryCaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
