<?php

namespace App\Filament\Resources\PhysicalStockCountResource\Pages;

use App\Filament\Resources\PhysicalStockCountResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPhysicalStockCounts extends ListRecords
{
    protected static string $resource = PhysicalStockCountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('New Stock Count'),
        ];
    }
}
