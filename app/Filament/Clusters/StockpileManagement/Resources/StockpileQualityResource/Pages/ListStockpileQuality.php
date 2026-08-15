<?php

namespace App\Filament\Clusters\StockpileManagement\Resources\StockpileQualityResource\Pages;

use App\Filament\Clusters\StockpileManagement\Resources\StockpileQualityResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListStockpileQuality extends ListRecords
{
    protected static string $resource = StockpileQualityResource::class;

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-beaker',
            'title' => 'Stockpile Quality',
            'subtitle' => 'Monitor and update quality assessments for stockpiled material.',
        ]);
    }
}
