<?php

namespace App\Filament\Resources\DriverEmergencyAlertResource\Pages;

use App\Filament\Resources\DriverEmergencyAlertResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListDriverEmergencyAlerts extends ListRecords
{
    protected static string $resource = DriverEmergencyAlertResource::class;

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-exclamation-triangle',
            'title' => 'Emergency Alerts',
            'subtitle' => 'Monitor emergency alerts raised by drivers in the field.',
        ]);
    }
}
