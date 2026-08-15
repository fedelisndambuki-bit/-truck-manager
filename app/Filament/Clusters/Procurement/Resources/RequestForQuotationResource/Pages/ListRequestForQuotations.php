<?php

namespace App\Filament\Clusters\Procurement\Resources\RequestForQuotationResource\Pages;

use App\Filament\Clusters\Procurement\Resources\RequestForQuotationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRequestForQuotations extends ListRecords
{
    protected static string $resource = RequestForQuotationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('New RFQ'),
        ];
    }
}
