<?php

namespace App\Filament\Resources\ComplianceRecordResource\Pages;

use App\Filament\Resources\ComplianceRecordResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditComplianceRecord extends EditRecord
{
    protected static string $resource = ComplianceRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
