<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources\TyreResource\Pages;

use App\Filament\Clusters\EquipmentAssetManagement\Resources\TyreResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListTyres extends ListRecords
{
    protected static string $resource = TyreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-circle-stack',
            'title' => 'Tyres',
            'subtitle' => 'Manage tyre inventory and track usage across equipment.',
        ]);
    }
}
