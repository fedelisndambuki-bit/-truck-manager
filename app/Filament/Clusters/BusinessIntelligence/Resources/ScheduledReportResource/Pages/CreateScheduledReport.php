<?php

namespace App\Filament\Clusters\BusinessIntelligence\Resources\ScheduledReportResource\Pages;

use App\Filament\Clusters\BusinessIntelligence\Resources\ScheduledReportResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateScheduledReport extends CreateRecord
{
    protected static string $resource = ScheduledReportResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by_id'] = Auth::id();

        return $data;
    }
}
