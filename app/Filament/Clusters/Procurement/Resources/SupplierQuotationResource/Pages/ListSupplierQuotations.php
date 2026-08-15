<?php

namespace App\Filament\Clusters\Procurement\Resources\SupplierQuotationResource\Pages;

use App\Filament\Clusters\Procurement\Resources\SupplierQuotationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListSupplierQuotations extends ListRecords
{
    protected static string $resource = SupplierQuotationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-document-currency-dollar',
            'title' => 'Supplier Quotations',
            'subtitle' => 'Compare supplier quotations received against open RFQs.',
        ]);
    }
}
