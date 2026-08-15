<?php

namespace App\Filament\Clusters\ProjectFinance\Resources\BudgetResource\Pages;

use App\Filament\Clusters\ProjectFinance\Resources\BudgetResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBudgets extends ListRecords
{
    protected static string $resource = BudgetResource::class;

    protected static string $view = 'filament.clusters.project-finance.resources.budget-resource.pages.list-budgets';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
