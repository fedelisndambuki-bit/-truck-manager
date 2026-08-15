<?php

namespace App\Filament\Clusters\BlastManagement\Resources\BlastDocumentResource\Pages;

use App\Filament\Clusters\BlastManagement\Resources\BlastDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListBlastDocuments extends ListRecords
{
    protected static string $resource = BlastDocumentResource::class;

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
            'title' => 'Blast Documents',
            'subtitle' => 'Manage permits, licenses, and supporting documents for blasting operations.',
        ]);
    }
}
