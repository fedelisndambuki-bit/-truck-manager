<?php

namespace App\Filament\Clusters\SmartWeighbridge\Resources\VehicleCheckInResource\Pages;

use App\Filament\Clusters\SmartWeighbridge\Resources\VehicleCheckInResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListVehicleCheckIns extends ListRecords
{
    protected static string $resource = VehicleCheckInResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-identification',
            'title' => 'Vehicle Check-Ins',
            'subtitle' => 'Track vehicles checking in at the weighbridge gate.',
        ]);
    }
}
