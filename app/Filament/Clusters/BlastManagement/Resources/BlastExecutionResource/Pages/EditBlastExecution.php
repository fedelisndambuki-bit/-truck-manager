<?php

namespace App\Filament\Clusters\BlastManagement\Resources\BlastExecutionResource\Pages;

use App\Filament\Clusters\BlastManagement\Resources\BlastExecutionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBlastExecution extends EditRecord
{
    protected static string $resource = BlastExecutionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
