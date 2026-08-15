<?php

namespace App\Filament\Clusters\Procurement\Resources\SupplierInvoiceResource\Pages;

use App\Filament\Clusters\Procurement\Resources\SupplierInvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListSupplierInvoices extends ListRecords
{
    protected static string $resource = SupplierInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-receipt-percent',
            'title' => 'Supplier Invoices',
            'subtitle' => 'Match supplier invoices against purchase orders and goods received before posting.',
        ]);
    }
}
