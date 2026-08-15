<?php

namespace App\Filament\Clusters\StockpileManagement\Resources\LiveInventoryResource\Pages;

use App\Filament\Clusters\StockpileManagement\Resources\LiveInventoryResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListLiveInventory extends ListRecords
{
    protected static string $resource = LiveInventoryResource::class;

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-list-bullet',
            'title' => 'Live Inventory',
            'subtitle' => 'Monitor real-time stockpile inventory levels across sites.',
        ]);
    }
}
