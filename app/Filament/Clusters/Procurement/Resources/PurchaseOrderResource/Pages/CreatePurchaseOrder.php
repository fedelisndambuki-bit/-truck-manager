<?php

namespace App\Filament\Clusters\Procurement\Resources\PurchaseOrderResource\Pages;

use App\Filament\Clusters\Procurement\Resources\PurchaseOrderResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePurchaseOrder extends CreateRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function afterCreate(): void
    {
        $this->record->recalculateTotals();

        if ($this->record->blanket_purchase_order_id) {
            $this->record->blanketPurchaseOrder?->recalculateReleased();
        }
    }
}
