<?php

namespace App\Filament\Resources\BinLocationResource\Pages;

use App\Filament\Resources\BinLocationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListBinLocations extends ListRecords
{
    protected static string $resource = BinLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-archive-box',
            'title' => 'Bin Locations',
            'subtitle' => 'Manage warehouse bin locations and track current stock levels in each bin.',
        ]);
    }
}
