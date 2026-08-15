<?php

namespace App\Filament\Resources\ScopeOfWorkResource\Pages;

use App\Filament\Resources\ScopeOfWorkResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditScopeOfWork extends EditRecord
{
    protected static string $resource = ScopeOfWorkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
