<?php

namespace App\Filament\Resources\MaterialAllocationResource\Pages;

use App\Filament\Resources\MaterialAllocationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMaterialAllocations extends ListRecords
{
    protected static string $resource = MaterialAllocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('New Allocation'),
        ];
    }
}
