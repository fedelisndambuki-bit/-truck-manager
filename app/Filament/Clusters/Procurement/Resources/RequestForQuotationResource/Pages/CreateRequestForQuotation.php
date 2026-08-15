<?php

namespace App\Filament\Clusters\Procurement\Resources\RequestForQuotationResource\Pages;

use App\Filament\Clusters\Procurement\Resources\RequestForQuotationResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateRequestForQuotation extends CreateRecord
{
    protected static string $resource = RequestForQuotationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by_id'] = Auth::id();

        return $data;
    }

    protected function afterCreate(): void
    {
        $supplierIds = $this->data['invitedSuppliersPicker'] ?? [];

        if (! empty($supplierIds)) {
            $this->record->invitedSuppliers()->sync($supplierIds);
        }
    }
}
