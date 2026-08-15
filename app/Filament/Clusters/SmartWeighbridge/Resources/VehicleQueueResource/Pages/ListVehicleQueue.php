<?php

namespace App\Filament\Clusters\SmartWeighbridge\Resources\VehicleQueueResource\Pages;

use App\Filament\Clusters\SmartWeighbridge\Resources\VehicleQueueResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListVehicleQueue extends ListRecords
{
    protected static string $resource = VehicleQueueResource::class;

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-queue-list',
            'title' => 'Vehicle Queue',
            'subtitle' => 'Monitor vehicles queued for weighing and dispatch processing.',
        ]);
    }
}
