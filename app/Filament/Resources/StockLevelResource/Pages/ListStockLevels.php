<?php

namespace App\Filament\Resources\StockLevelResource\Pages;

use App\Filament\Resources\StockLevelResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListStockLevels extends ListRecords
{
    protected static string $resource = StockLevelResource::class;

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-archive-box',
            'title' => 'Stock Levels',
            'subtitle' => 'Monitor current stock quantities across all warehouses.',
        ]);
    }
}
