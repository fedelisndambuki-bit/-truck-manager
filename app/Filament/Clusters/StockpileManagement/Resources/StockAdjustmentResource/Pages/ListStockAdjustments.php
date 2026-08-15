<?php

namespace App\Filament\Clusters\StockpileManagement\Resources\StockAdjustmentResource\Pages;

use App\Filament\Clusters\StockpileManagement\Resources\StockAdjustmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListStockAdjustments extends ListRecords
{
    protected static string $resource = StockAdjustmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-adjustments-horizontal',
            'title' => 'Stock Adjustments',
            'subtitle' => 'Track and approve manual adjustments to stockpile quantities.',
        ]);
    }
}
