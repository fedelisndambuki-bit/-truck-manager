<?php

namespace App\Filament\Clusters\BlastManagement\Resources\BlastEnvironmentalReadingResource\Pages;

use App\Filament\Clusters\BlastManagement\Resources\BlastEnvironmentalReadingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBlastEnvironmentalReading extends EditRecord
{
    protected static string $resource = BlastEnvironmentalReadingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
