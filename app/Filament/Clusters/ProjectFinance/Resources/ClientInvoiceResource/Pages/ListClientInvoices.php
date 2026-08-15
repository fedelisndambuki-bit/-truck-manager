<?php

namespace App\Filament\Clusters\ProjectFinance\Resources\ClientInvoiceResource\Pages;

use App\Filament\Clusters\ProjectFinance\Resources\ClientInvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListClientInvoices extends ListRecords
{
    protected static string $resource = ClientInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
