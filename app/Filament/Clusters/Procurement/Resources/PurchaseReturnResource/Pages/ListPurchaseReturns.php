<?php

namespace App\Filament\Clusters\Procurement\Resources\PurchaseReturnResource\Pages;

use App\Filament\Clusters\Procurement\Resources\PurchaseReturnResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPurchaseReturns extends ListRecords
{
    protected static string $resource = PurchaseReturnResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
