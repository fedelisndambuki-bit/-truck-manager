<?php

namespace App\Filament\Resources\EquipmentAllocationResource\Pages;

use App\Filament\Resources\EquipmentAllocationResource;
use App\Filament\Widgets\EquipmentKpiWidget;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListEquipmentAllocations extends ListRecords
{
    protected static string $resource = EquipmentAllocationResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            EquipmentKpiWidget::class,
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-truck',
            'title' => 'Equipment Allocations',
            'subtitle' => 'Track which equipment is assigned to which sites and projects.',
        ]);
    }
}
