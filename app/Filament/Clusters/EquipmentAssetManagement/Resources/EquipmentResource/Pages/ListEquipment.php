<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources\EquipmentResource\Pages;

use App\Filament\Clusters\EquipmentAssetManagement\Resources\EquipmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListEquipment extends ListRecords
{
    protected static string $resource = EquipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-truck',
            'title' => 'Equipment',
            'subtitle' => "Manage your fleet's equipment inventory and asset details.",
        ]);
    }
}
