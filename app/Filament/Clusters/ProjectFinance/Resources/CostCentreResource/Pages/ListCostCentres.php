<?php

namespace App\Filament\Clusters\ProjectFinance\Resources\CostCentreResource\Pages;

use App\Filament\Clusters\ProjectFinance\Resources\CostCentreResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCostCentres extends ListRecords
{
    protected static string $resource = CostCentreResource::class;

    protected static string $view = 'filament.clusters.project-finance.resources.cost-centre-resource.pages.list-cost-centres';

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
