<?php

namespace App\Filament\Clusters\DocumentManagement\Resources\MethodStatementResource\Pages;

use App\Filament\Clusters\DocumentManagement\Resources\MethodStatementResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMethodStatement extends EditRecord
{
    protected static string $resource = MethodStatementResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
