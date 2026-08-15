<?php

namespace App\Filament\Clusters\BusinessIntelligence\Resources\CustomReportResource\Pages;

use App\Filament\Clusters\BusinessIntelligence\Resources\CustomReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCustomReports extends ListRecords
{
    protected static string $resource = CustomReportResource::class;

    protected static string $view = 'filament.clusters.business-intelligence.resources.custom-report-resource.pages.list-custom-reports';

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
