<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources\WorkshopResource\Pages;

use App\Filament\Clusters\EquipmentAssetManagement\Resources\WorkshopResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListWorkshops extends ListRecords
{
    protected static string $resource = WorkshopResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-building-office-2',
            'title' => 'Workshops',
            'subtitle' => 'Manage workshop locations used for equipment maintenance and repairs.',
        ]);
    }
}
