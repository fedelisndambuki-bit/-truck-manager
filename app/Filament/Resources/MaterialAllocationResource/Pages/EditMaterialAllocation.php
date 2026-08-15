<?php

namespace App\Filament\Resources\MaterialAllocationResource\Pages;

use App\Filament\Resources\MaterialAllocationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMaterialAllocation extends EditRecord
{
    protected static string $resource = MaterialAllocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
