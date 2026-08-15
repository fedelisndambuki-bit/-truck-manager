<?php

namespace App\Filament\Resources\DriverPreTripInspectionResource\Pages;

use App\Filament\Resources\DriverPreTripInspectionResource;
use Filament\Resources\Pages\ListRecords;

class ListDriverPreTripInspections extends ListRecords
{
    protected static string $resource = DriverPreTripInspectionResource::class;

    protected static string $view = 'filament.resources.driver-pre-trip-inspection-resource.pages.list-driver-pre-trip-inspections';
}
