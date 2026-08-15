<?php

namespace App\Filament\Clusters\StockpileManagement\Resources\PhysicalStockCountResource\Pages;

use App\Filament\Clusters\StockpileManagement\Resources\PhysicalStockCountResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListPhysicalStockCounts extends ListRecords
{
    protected static string $resource = PhysicalStockCountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-clipboard-document-check',
            'title' => 'Physical Stock Counts',
            'subtitle' => 'Record and reconcile physical stock counts against system records.',
        ]);
    }
}
