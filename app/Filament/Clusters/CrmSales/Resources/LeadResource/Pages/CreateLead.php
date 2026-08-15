<?php

namespace App\Filament\Clusters\CrmSales\Resources\LeadResource\Pages;

use App\Filament\Clusters\CrmSales\Resources\LeadResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLead extends CreateRecord
{
    protected static string $resource = LeadResource::class;
}
