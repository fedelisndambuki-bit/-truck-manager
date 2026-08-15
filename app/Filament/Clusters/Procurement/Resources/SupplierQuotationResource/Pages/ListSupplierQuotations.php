<?php

namespace App\Filament\Clusters\Procurement\Resources\SupplierQuotationResource\Pages;

use App\Filament\Clusters\Procurement\Resources\SupplierQuotationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSupplierQuotations extends ListRecords
{
    protected static string $resource = SupplierQuotationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
