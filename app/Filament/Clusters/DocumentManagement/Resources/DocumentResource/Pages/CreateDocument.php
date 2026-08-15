<?php

namespace App\Filament\Clusters\DocumentManagement\Resources\DocumentResource\Pages;

use App\Filament\Clusters\DocumentManagement\Resources\DocumentResource;
use App\Services\Construction\DocumentManagementService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateDocument extends CreateRecord
{
    protected static string $resource = DocumentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by_id'] = Auth::id();

        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->record;

        app(DocumentManagementService::class)->createVersion($record, [
            'file_path' => $record->file_path,
            'file_type' => $record->file_type,
            'file_size' => $record->file_size,
            'change_notes' => 'Initial upload',
        ]);

        app(DocumentManagementService::class)->logAudit($record, 'uploaded');
    }
}
