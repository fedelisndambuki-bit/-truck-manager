<?php

namespace App\Filament\Resources\ShiftSwapRequestResource\Pages;

use App\Filament\Resources\ShiftSwapRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListShiftSwapRequests extends ListRecords
{
    protected static string $resource = ShiftSwapRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
