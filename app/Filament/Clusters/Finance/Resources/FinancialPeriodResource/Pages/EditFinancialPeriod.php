<?php

namespace App\Filament\Clusters\Finance\Resources\FinancialPeriodResource\Pages;

use App\Filament\Clusters\Finance\Resources\FinancialPeriodResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFinancialPeriod extends EditRecord
{
    protected static string $resource = FinancialPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
