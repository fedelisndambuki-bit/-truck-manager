<?php

namespace App\Filament\Clusters\DocumentManagement\Resources\SafetyDocumentResource\Pages;

use App\Filament\Clusters\DocumentManagement\Resources\SafetyDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListSafetyDocuments extends ListRecords
{
    protected static string $resource = SafetyDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-shield-check',
            'title' => 'Safety Documents',
            'subtitle' => 'Manage safety plans, incident reports, and compliance documentation for the project.',
        ]);
    }
}
