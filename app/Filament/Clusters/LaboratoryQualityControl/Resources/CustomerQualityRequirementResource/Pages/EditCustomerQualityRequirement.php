<?php

namespace App\Filament\Clusters\LaboratoryQualityControl\Resources\CustomerQualityRequirementResource\Pages;

use App\Filament\Clusters\LaboratoryQualityControl\Resources\CustomerQualityRequirementResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCustomerQualityRequirement extends EditRecord
{
    protected static string $resource = CustomerQualityRequirementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
