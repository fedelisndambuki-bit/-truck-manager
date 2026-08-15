<?php

namespace App\Filament\Clusters\DocumentManagement\Resources\FinancialDocumentResource\Pages;

use App\Filament\Clusters\DocumentManagement\Resources\FinancialDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListFinancialDocuments extends ListRecords
{
    protected static string $resource = FinancialDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-banknotes',
            'title' => 'Financial Documents',
            'subtitle' => 'Manage invoices, payment certificates, and other project financial records.',
        ]);
    }
}
