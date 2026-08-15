<?php

namespace App\Filament\Clusters\ProjectFinance\Resources\ClientInvoiceResource\Pages;

use App\Filament\Clusters\ProjectFinance\Resources\ClientInvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListClientInvoices extends ListRecords
{
    protected static string $resource = ClientInvoiceResource::class;

    protected static string $view = 'filament.clusters.project-finance.resources.client-invoice-resource.pages.list-client-invoices';

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
