<?php

namespace App\Filament\Clusters\DocumentManagement\Resources\BoqDocumentResource\Pages;

use App\Filament\Clusters\DocumentManagement\Resources\BoqDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListBoqDocuments extends ListRecords
{
    protected static string $resource = BoqDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-table-cells',
            'title' => 'BOQs',
            'subtitle' => 'Manage bills of quantities and cost breakdown documentation for the project.',
        ]);
    }
}
