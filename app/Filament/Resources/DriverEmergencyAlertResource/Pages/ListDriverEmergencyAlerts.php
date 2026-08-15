<?php

namespace App\Filament\Resources\DriverEmergencyAlertResource\Pages;

use App\Filament\Resources\DriverEmergencyAlertResource;
use Filament\Resources\Pages\ListRecords;

class ListDriverEmergencyAlerts extends ListRecords
{
    protected static string $resource = DriverEmergencyAlertResource::class;

    protected static string $view = 'filament.resources.driver-emergency-alert-resource.pages.list-driver-emergency-alerts';
}
