<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources\BreakdownResource\Pages;

use App\Filament\Clusters\EquipmentAssetManagement\Resources\BreakdownResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBreakdowns extends ListRecords
{
    protected static string $resource = BreakdownResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Report Breakdown'),
        ];
    }
}
