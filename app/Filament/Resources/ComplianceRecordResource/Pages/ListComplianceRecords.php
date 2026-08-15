<?php

namespace App\Filament\Resources\ComplianceRecordResource\Pages;

use App\Filament\Resources\ComplianceRecordResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListComplianceRecords extends ListRecords
{
    protected static string $resource = ComplianceRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
