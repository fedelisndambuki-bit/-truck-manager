<?php

namespace App\Filament\Clusters\CrmSales\Resources\OpportunityResource\Pages;

use App\Filament\Clusters\CrmSales\Resources\OpportunityResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOpportunities extends ListRecords
{
    protected static string $resource = OpportunityResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
