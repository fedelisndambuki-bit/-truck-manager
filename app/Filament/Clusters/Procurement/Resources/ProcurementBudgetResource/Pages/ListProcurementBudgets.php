<?php

namespace App\Filament\Clusters\Procurement\Resources\ProcurementBudgetResource\Pages;

use App\Filament\Clusters\Procurement\Resources\ProcurementBudgetResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProcurementBudgets extends ListRecords
{
    protected static string $resource = ProcurementBudgetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
