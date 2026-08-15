<?php

namespace App\Filament\Clusters\Finance\Resources\FinancialPeriodResource\Pages;

use App\Filament\Clusters\Finance\Resources\FinancialPeriodResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFinancialPeriod extends CreateRecord
{
    protected static string $resource = FinancialPeriodResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = 'Open';

        return $data;
    }
}
