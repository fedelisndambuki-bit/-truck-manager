<?php

namespace App\Filament\Clusters\BusinessIntelligence\Resources\ScheduledReportResource\Pages;

use App\Filament\Clusters\BusinessIntelligence\Resources\ScheduledReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListScheduledReports extends ListRecords
{
    protected static string $resource = ScheduledReportResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
