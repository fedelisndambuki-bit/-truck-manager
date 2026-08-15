<?php

namespace App\Filament\Clusters\BusinessIntelligence\Resources\ScheduledReportResource\Pages;

use App\Filament\Clusters\BusinessIntelligence\Resources\ScheduledReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListScheduledReports extends ListRecords
{
    protected static string $resource = ScheduledReportResource::class;

    protected static string $view = 'filament.clusters.business-intelligence.resources.scheduled-report-resource.pages.list-scheduled-reports';

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
