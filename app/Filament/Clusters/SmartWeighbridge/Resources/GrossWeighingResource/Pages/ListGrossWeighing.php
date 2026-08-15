<?php

namespace App\Filament\Clusters\SmartWeighbridge\Resources\GrossWeighingResource\Pages;

use App\Filament\Clusters\SmartWeighbridge\Resources\GrossWeighingResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListGrossWeighing extends ListRecords
{
    protected static string $resource = GrossWeighingResource::class;

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-scale',
            'title' => 'Gross Weighing',
            'subtitle' => 'Record gross weight readings for vehicles at the weighbridge.',
        ]);
    }
}
