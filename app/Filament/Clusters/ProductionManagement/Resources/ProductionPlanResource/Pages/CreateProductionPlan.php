<?php

namespace App\Filament\Clusters\ProductionManagement\Resources\ProductionPlanResource\Pages;

use App\Filament\Clusters\ProductionManagement\Resources\ProductionPlanResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProductionPlan extends CreateRecord
{
    protected static string $resource = ProductionPlanResource::class;
}
