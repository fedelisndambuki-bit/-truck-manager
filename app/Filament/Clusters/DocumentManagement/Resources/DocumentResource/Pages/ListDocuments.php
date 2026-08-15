<?php

namespace App\Filament\Clusters\DocumentManagement\Resources\DocumentResource\Pages;

use App\Filament\Clusters\DocumentManagement\Resources\DocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListDocuments extends ListRecords
{
    protected static string $resource = DocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-document-text',
            'title' => 'Document Library',
            'subtitle' => 'Browse, submit, and approve general project documents in one central library.',
        ]);
    }
}
