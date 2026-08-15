<?php

namespace App\Filament\Clusters\BlastManagement\Resources\BlastComplianceResource\Pages;

use App\Filament\Clusters\BlastManagement\Resources\BlastComplianceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBlastCompliance extends EditRecord
{
    protected static string $resource = BlastComplianceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
