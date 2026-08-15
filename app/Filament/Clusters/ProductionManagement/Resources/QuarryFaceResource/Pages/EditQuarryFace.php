<?php

namespace App\Filament\Clusters\ProductionManagement\Resources\QuarryFaceResource\Pages;

use App\Filament\Clusters\ProductionManagement\Resources\QuarryFaceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditQuarryFace extends EditRecord
{
    protected static string $resource = QuarryFaceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
