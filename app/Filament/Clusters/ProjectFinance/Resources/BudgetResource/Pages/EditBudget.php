<?php

namespace App\Filament\Clusters\ProjectFinance\Resources\BudgetResource\Pages;

use App\Filament\Clusters\ProjectFinance\Resources\BudgetResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBudget extends EditRecord
{
    protected static string $resource = BudgetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
