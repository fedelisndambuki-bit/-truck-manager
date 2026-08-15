<?php

namespace App\Filament\Clusters\DocumentManagement\Resources\QualityDocumentResource\Pages;

use App\Filament\Clusters\DocumentManagement\Resources\QualityDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListQualityDocuments extends ListRecords
{
    protected static string $resource = QualityDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-clipboard-document-list',
            'title' => 'Quality Documents',
            'subtitle' => 'Manage quality assurance records and inspection documentation for the project.',
        ]);
    }
}
