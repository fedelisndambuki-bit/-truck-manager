<?php

namespace App\Filament\Clusters\StockpileManagement\Resources\StockMovementResource\Pages;

use App\Filament\Clusters\StockpileManagement\Resources\StockMovementResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListStockMovements extends ListRecords
{
    protected static string $resource = StockMovementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-arrows-right-left',
            'title' => 'Stock Movements',
            'subtitle' => 'Track all stock movement transactions across stockpiles.',
        ]);
    }
}
