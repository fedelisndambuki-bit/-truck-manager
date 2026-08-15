<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources\PreventiveMaintenancePlanResource\Pages;

use App\Filament\Clusters\EquipmentAssetManagement\Resources\PreventiveMaintenancePlanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListPreventiveMaintenancePlans extends ListRecords
{
    protected static string $resource = PreventiveMaintenancePlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-calendar-days',
            'title' => 'Preventive Maintenance Plans',
            'subtitle' => 'Schedule and manage recurring preventive maintenance plans for equipment.',
        ]);
    }
}
