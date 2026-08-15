<?php

namespace App\Filament\Clusters\BlastManagement\Resources\BlastDrillHoleResource\Pages;

use App\Filament\Clusters\BlastManagement\Resources\BlastDrillHoleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBlastDrillHole extends EditRecord
{
    protected static string $resource = BlastDrillHoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
