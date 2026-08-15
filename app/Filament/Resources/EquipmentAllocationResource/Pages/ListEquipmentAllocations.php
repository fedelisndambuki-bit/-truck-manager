<?php

namespace App\Filament\Resources\EquipmentAllocationResource\Pages;

use App\Filament\Resources\EquipmentAllocationResource;
use App\Filament\Widgets\EquipmentKpiWidget;
use Filament\Resources\Pages\ListRecords;

class ListEquipmentAllocations extends ListRecords
{
    protected static string $resource = EquipmentAllocationResource::class;

    protected static string $view = 'filament.resources.equipment-allocation-resource.pages.list-equipment-allocations';

    protected function getHeaderWidgets(): array
    {
        return [
            EquipmentKpiWidget::class,
        ];
    }

    /**
     * Filament's base page template auto-renders header widgets ABOVE the
     * whole page slot (before our custom Tanaki banner + table), with no way
     * to reposition that. We render getHeaderWidgets() ourselves further down
     * the custom blade view instead, so suppress the automatic render here to
     * avoid showing the KPI cards twice.
     */
    public function getVisibleHeaderWidgets(): array
    {
        return [];
    }
}
