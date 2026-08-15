<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources\EquipmentDocumentResource\Pages;

use App\Filament\Clusters\EquipmentAssetManagement\Resources\EquipmentDocumentResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateEquipmentDocument extends CreateRecord
{
    protected static string $resource = EquipmentDocumentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uploaded_by_id'] = Auth::id();

        return $data;
    }
}
