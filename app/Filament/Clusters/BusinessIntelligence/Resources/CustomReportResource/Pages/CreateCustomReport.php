<?php

namespace App\Filament\Clusters\BusinessIntelligence\Resources\CustomReportResource\Pages;

use App\Filament\Clusters\BusinessIntelligence\Resources\CustomReportResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateCustomReport extends CreateRecord
{
    protected static string $resource = CustomReportResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by_id'] = Auth::id();

        return $data;
    }
}
