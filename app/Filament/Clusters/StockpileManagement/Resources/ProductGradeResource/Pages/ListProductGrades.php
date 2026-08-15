<?php

namespace App\Filament\Clusters\StockpileManagement\Resources\ProductGradeResource\Pages;

use App\Filament\Clusters\StockpileManagement\Resources\ProductGradeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProductGrades extends ListRecords
{
    protected static string $resource = ProductGradeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
