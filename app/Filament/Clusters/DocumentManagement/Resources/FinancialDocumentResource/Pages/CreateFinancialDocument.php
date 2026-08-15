<?php

namespace App\Filament\Clusters\DocumentManagement\Resources\FinancialDocumentResource\Pages;

use App\Filament\Clusters\DocumentManagement\Resources\FinancialDocumentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFinancialDocument extends CreateRecord
{
    protected static string $resource = FinancialDocumentResource::class;
}
