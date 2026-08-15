<?php

namespace App\Filament\Clusters\ProjectFinance\Resources\CostCodeResource\Pages;

use App\Filament\Clusters\ProjectFinance\Resources\CostCodeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCostCode extends EditRecord
{
    protected static string $resource = CostCodeResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
