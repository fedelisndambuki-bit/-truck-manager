<?php

namespace App\Filament\Clusters\Finance\Resources\JournalEntryResource\Pages;

use App\Filament\Clusters\Finance\Resources\JournalEntryResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateJournalEntry extends CreateRecord
{
    protected static string $resource = JournalEntryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = 'Draft';
        $data['source_module'] = 'manual';
        $data['created_by'] = Auth::id();

        return $data;
    }
}
