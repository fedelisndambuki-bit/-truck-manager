<?php

namespace App\Filament\Resources\HazardResource\Pages;

use App\Filament\Resources\HazardResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHazards extends ListRecords
{
    protected static string $resource = HazardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
