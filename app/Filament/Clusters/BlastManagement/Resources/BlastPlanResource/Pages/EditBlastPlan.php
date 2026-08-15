<?php

namespace App\Filament\Clusters\BlastManagement\Resources\BlastPlanResource\Pages;

use App\Filament\Clusters\BlastManagement\Resources\BlastPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBlastPlan extends EditRecord
{
    protected static string $resource = BlastPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
