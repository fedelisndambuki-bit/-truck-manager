<?php

namespace App\Filament\Clusters\SmartWeighbridge\Resources\VehicleHistoryResource\Pages;

use App\Filament\Clusters\SmartWeighbridge\Resources\VehicleHistoryResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListVehicleHistory extends ListRecords
{
    protected static string $resource = VehicleHistoryResource::class;

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-clock',
            'title' => 'Vehicle History',
            'subtitle' => 'View the complete movement history of vehicles through the weighbridge.',
        ]);
    }
}
