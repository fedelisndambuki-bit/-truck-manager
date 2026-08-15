<?php

namespace App\Filament\Clusters\ProductionManagement\Resources\ProductionTargetResource\Pages;

use App\Filament\Clusters\ProductionManagement\Resources\ProductionTargetResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProductionTarget extends CreateRecord
{
    protected static string $resource = ProductionTargetResource::class;
}
