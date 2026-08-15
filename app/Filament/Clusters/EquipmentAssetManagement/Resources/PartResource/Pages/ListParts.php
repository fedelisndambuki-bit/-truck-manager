<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources\PartResource\Pages;

use App\Filament\Clusters\EquipmentAssetManagement\Resources\PartResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListParts extends ListRecords
{
    protected static string $resource = PartResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-cog',
            'title' => 'Parts',
            'subtitle' => 'Manage spare parts inventory used for equipment maintenance.',
        ]);
    }
}
