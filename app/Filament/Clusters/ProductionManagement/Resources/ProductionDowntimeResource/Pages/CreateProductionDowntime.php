<?php

namespace App\Filament\Clusters\ProductionManagement\Resources\ProductionDowntimeResource\Pages;

use App\Filament\Clusters\ProductionManagement\Resources\ProductionDowntimeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProductionDowntime extends CreateRecord
{
    protected static string $resource = ProductionDowntimeResource::class;
}
