<?php

namespace App\Filament\Clusters\Finance\Resources\JournalEntryResource\Pages;

use App\Filament\Clusters\Finance\Resources\JournalEntryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListJournalEntries extends ListRecords
{
    protected static string $resource = JournalEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-document-text',
            'title' => 'Journal Entries',
            'subtitle' => 'Record, post and reverse double-entry journals across the ledger.',
        ]);
    }
}
