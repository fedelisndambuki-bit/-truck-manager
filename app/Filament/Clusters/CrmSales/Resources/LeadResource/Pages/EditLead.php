<?php

namespace App\Filament\Clusters\CrmSales\Resources\LeadResource\Pages;

use App\Filament\Clusters\CrmSales\Resources\LeadResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLead extends EditRecord
{
    protected static string $resource = LeadResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
