<?php

namespace App\Filament\Clusters\SmartWeighbridge\Resources\VehicleExitResource\Pages;

use App\Filament\Clusters\SmartWeighbridge\Resources\VehicleExitResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListVehicleExits extends ListRecords
{
    protected static string $resource = VehicleExitResource::class;

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-arrow-right-on-rectangle',
            'title' => 'Vehicle Exits',
            'subtitle' => 'Record vehicle exits after loading and final weighing.',
        ]);
    }
}
