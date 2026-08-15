<?php

namespace App\Filament\Clusters\Procurement\Resources\SupplierInvoiceResource\Pages;

use App\Filament\Clusters\Procurement\Resources\SupplierInvoiceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSupplierInvoice extends CreateRecord
{
    protected static string $resource = SupplierInvoiceResource::class;

    protected function afterCreate(): void
    {
        $this->record->recalculateTotals();
    }
}
