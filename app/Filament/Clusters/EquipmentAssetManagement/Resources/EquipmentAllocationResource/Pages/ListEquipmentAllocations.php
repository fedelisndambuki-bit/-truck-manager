<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources\EquipmentAllocationResource\Pages;

use App\Filament\Clusters\EquipmentAssetManagement\Resources\EquipmentAllocationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListEquipmentAllocations extends ListRecords
{
    protected static string $resource = EquipmentAllocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Assign Equipment'),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-user-group',
            'title' => 'Equipment Allocations',
            'subtitle' => 'Track which equipment is assigned to which drivers, sites, or projects.',
        ]);
    }
}
