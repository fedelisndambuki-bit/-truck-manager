<?php

namespace App\Filament\Resources\ShiftSwapRequestResource\Pages;

use App\Filament\Resources\ShiftSwapRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditShiftSwapRequest extends EditRecord
{
    protected static string $resource = ShiftSwapRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
