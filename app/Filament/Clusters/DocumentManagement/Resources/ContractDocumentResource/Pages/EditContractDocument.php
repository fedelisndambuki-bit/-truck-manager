<?php

namespace App\Filament\Clusters\DocumentManagement\Resources\ContractDocumentResource\Pages;

use App\Filament\Clusters\DocumentManagement\Resources\ContractDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditContractDocument extends EditRecord
{
    protected static string $resource = ContractDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
