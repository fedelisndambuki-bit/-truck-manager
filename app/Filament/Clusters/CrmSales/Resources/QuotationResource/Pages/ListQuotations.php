<?php

namespace App\Filament\Clusters\CrmSales\Resources\QuotationResource\Pages;

use App\Filament\Clusters\CrmSales\Resources\QuotationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListQuotations extends ListRecords
{
    protected static string $resource = QuotationResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-document-currency-dollar',
            'title' => 'Quotations',
            'subtitle' => 'Prepare, submit and track price quotes issued to clients.',
        ]);
    }
}
