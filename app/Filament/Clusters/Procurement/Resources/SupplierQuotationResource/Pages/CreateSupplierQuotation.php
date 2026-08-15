<?php

namespace App\Filament\Clusters\Procurement\Resources\SupplierQuotationResource\Pages;

use App\Filament\Clusters\Procurement\Resources\SupplierQuotationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSupplierQuotation extends CreateRecord
{
    protected static string $resource = SupplierQuotationResource::class;

    protected function afterCreate(): void
    {
        $this->record->recalculateTotals();
    }
}
