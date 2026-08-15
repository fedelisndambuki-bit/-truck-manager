<?php

namespace App\Filament\Clusters\Procurement\Resources\ProcurementBudgetResource\Pages;

use App\Filament\Clusters\Procurement\Resources\ProcurementBudgetResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProcurementBudget extends EditRecord
{
    protected static string $resource = ProcurementBudgetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
