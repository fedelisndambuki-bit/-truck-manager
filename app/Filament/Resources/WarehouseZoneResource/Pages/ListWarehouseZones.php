<?php

namespace App\Filament\Resources\WarehouseZoneResource\Pages;

use App\Filament\Resources\WarehouseZoneResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListWarehouseZones extends ListRecords
{
    protected static string $resource = WarehouseZoneResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-squares-2x2',
            'title' => 'Warehouse Zones',
            'subtitle' => 'Organize warehouse floor space into zones such as receiving, storage, and dispatch.',
        ]);
    }
}
