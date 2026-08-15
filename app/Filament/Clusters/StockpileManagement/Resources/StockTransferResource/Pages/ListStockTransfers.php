<?php

namespace App\Filament\Clusters\StockpileManagement\Resources\StockTransferResource\Pages;

use App\Filament\Clusters\StockpileManagement\Resources\StockTransferResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListStockTransfers extends ListRecords
{
    protected static string $resource = StockTransferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-arrow-path',
            'title' => 'Stock Transfers',
            'subtitle' => 'Track stock transfers between stockpiles and production sites.',
        ]);
    }
}
