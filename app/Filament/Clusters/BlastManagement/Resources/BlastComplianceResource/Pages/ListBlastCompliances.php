<?php

namespace App\Filament\Clusters\BlastManagement\Resources\BlastComplianceResource\Pages;

use App\Filament\Clusters\BlastManagement\Resources\BlastComplianceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBlastCompliances extends ListRecords
{
    protected static string $resource = BlastComplianceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
