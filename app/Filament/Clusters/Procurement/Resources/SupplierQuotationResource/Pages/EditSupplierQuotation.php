<?php

namespace App\Filament\Clusters\Procurement\Resources\SupplierQuotationResource\Pages;

use App\Filament\Clusters\Procurement\Resources\SupplierQuotationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSupplierQuotation extends EditRecord
{
    protected static string $resource = SupplierQuotationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $this->record->recalculateTotals();
    }
}
