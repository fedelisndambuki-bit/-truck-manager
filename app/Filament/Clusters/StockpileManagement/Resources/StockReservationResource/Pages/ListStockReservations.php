<?php

namespace App\Filament\Clusters\StockpileManagement\Resources\StockReservationResource\Pages;

use App\Filament\Clusters\StockpileManagement\Resources\StockReservationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListStockReservations extends ListRecords
{
    protected static string $resource = StockReservationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-bookmark',
            'title' => 'Stock Reservations',
            'subtitle' => 'Manage stock reserved for pending orders and dispatches.',
        ]);
    }
}
