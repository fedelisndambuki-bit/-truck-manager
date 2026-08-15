<?php

namespace App\Filament\Clusters\ProjectFinance\Resources\ClientInvoiceResource\Pages;

use App\Filament\Clusters\ProjectFinance\Resources\ClientInvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListClientInvoices extends ListRecords
{
    protected static string $resource = ClientInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-receipt-percent',
            'title' => 'Client Invoices',
            'subtitle' => 'Issue and track invoices billed to clients on active projects.',
        ]);
    }
}
