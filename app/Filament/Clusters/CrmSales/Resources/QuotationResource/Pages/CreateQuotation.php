<?php

namespace App\Filament\Clusters\CrmSales\Resources\QuotationResource\Pages;

use App\Filament\Clusters\CrmSales\Resources\QuotationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateQuotation extends CreateRecord
{
    protected static string $resource = QuotationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = 'Draft';

        return $data;
    }
}
