<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources\EquipmentCostEntryResource\Pages;

use App\Filament\Clusters\EquipmentAssetManagement\Resources\EquipmentCostEntryResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListEquipmentCostEntries extends ListRecords
{
    protected static string $resource = EquipmentCostEntryResource::class;

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-calculator',
            'title' => 'Equipment Cost Entries',
            'subtitle' => 'Record and review cost entries associated with equipment ownership and operation.',
        ]);
    }
}
