<?php

namespace App\Filament\Resources\SafetyDocumentResource\Pages;

use App\Filament\Resources\SafetyDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListSafetyDocuments extends ListRecords
{
    protected static string $resource = SafetyDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-document-text',
            'title' => 'Safety Documents',
            'subtitle' => 'Store and manage safety-related documents and certifications.',
        ]);
    }
}
