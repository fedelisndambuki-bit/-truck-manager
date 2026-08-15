<?php

namespace App\Filament\SuperAdmin\Resources\LandingSeoResource\Pages;

use App\Filament\SuperAdmin\Resources\LandingSeoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLandingSeos extends ListRecords
{
    protected static string $resource = LandingSeoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
