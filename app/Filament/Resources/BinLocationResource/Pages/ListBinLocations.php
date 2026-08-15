<?php

namespace App\Filament\Resources\BinLocationResource\Pages;

use App\Filament\Resources\BinLocationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBinLocations extends ListRecords
{
    protected static string $resource = BinLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
