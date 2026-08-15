<?php

namespace App\Filament\Resources\QualityInspectionResource\Pages;

use App\Filament\Resources\QualityInspectionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditQualityInspection extends EditRecord
{
    protected static string $resource = QualityInspectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
