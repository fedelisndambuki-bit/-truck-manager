<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources\WorkshopBayResource\Pages;

use App\Filament\Clusters\EquipmentAssetManagement\Resources\WorkshopBayResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListWorkshopBays extends ListRecords
{
    protected static string $resource = WorkshopBayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-squares-plus',
            'title' => 'Workshop Bays',
            'subtitle' => 'Manage workshop bay capacity and availability for maintenance work.',
        ]);
    }
}
