<?php

namespace App\Filament\Resources\PhysicalStockCountResource\Pages;

use App\Filament\Resources\PhysicalStockCountResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePhysicalStockCount extends CreateRecord
{
    protected static string $resource = PhysicalStockCountResource::class;

    public function form(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        return $form->schema(PhysicalStockCountResource::headerSchema());
    }

    protected function afterCreate(): void
    {
        $this->record->populateFromCurrentStock();
    }
}
