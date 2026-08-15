<?php

namespace App\Filament\Clusters\Procurement\Resources\RequestForQuotationResource\Pages;

use App\Filament\Clusters\Procurement\Resources\RequestForQuotationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRequestForQuotation extends EditRecord
{
    protected static string $resource = RequestForQuotationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $supplierIds = $this->data['invitedSuppliersPicker'] ?? [];

        $this->record->invitedSuppliers()->sync($supplierIds);
    }
}
