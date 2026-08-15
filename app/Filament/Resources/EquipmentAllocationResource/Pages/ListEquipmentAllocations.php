<?php

namespace App\Filament\Resources\EquipmentAllocationResource\Pages;

use App\Filament\Resources\EquipmentAllocationResource;
use App\Filament\Widgets\EquipmentKpiWidget;
use Filament\Resources\Pages\ListRecords;

class ListEquipmentAllocations extends ListRecords
{
    protected static string $resource = EquipmentAllocationResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            EquipmentKpiWidget::class,
        ];
    }
}
