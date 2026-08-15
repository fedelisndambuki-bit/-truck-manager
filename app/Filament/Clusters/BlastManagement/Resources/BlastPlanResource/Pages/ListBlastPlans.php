<?php

namespace App\Filament\Clusters\BlastManagement\Resources\BlastPlanResource\Pages;

use App\Filament\Clusters\BlastManagement\Resources\BlastPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBlastPlans extends ListRecords
{
    protected static string $resource = BlastPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
