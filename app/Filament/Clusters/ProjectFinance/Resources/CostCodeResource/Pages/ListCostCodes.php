<?php

namespace App\Filament\Clusters\ProjectFinance\Resources\CostCodeResource\Pages;

use App\Filament\Clusters\ProjectFinance\Resources\CostCodeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCostCodes extends ListRecords
{
    protected static string $resource = CostCodeResource::class;

    protected static string $view = 'filament.clusters.project-finance.resources.cost-code-resource.pages.list-cost-codes';

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
