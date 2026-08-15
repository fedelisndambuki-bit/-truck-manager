<?php

namespace App\Filament\Clusters\DocumentManagement\Resources\ContractDocumentResource\Pages;

use App\Filament\Clusters\DocumentManagement\Resources\ContractDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListContractDocuments extends ListRecords
{
    protected static string $resource = ContractDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-document-check',
            'title' => 'Contracts',
            'subtitle' => 'Manage contract documents and agreements associated with the project.',
        ]);
    }
}
