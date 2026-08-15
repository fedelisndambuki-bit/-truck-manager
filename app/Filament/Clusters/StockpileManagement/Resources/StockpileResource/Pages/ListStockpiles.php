<?php

namespace App\Filament\Clusters\StockpileManagement\Resources\StockpileResource\Pages;

use App\Filament\Clusters\StockpileManagement\Resources\StockpileResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListStockpiles extends ListRecords
{
    protected static string $resource = StockpileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-cube',
            'title' => 'Stockpiles',
            'subtitle' => 'Manage stockpile sites and their material composition.',
        ]);
    }
}
