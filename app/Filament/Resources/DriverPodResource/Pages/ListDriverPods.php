<?php

namespace App\Filament\Resources\DriverPodResource\Pages;

use App\Filament\Resources\DriverPodResource;
use Filament\Resources\Pages\ListRecords;

class ListDriverPods extends ListRecords
{
    protected static string $resource = DriverPodResource::class;

    protected static string $view = 'filament.resources.driver-pod-resource.pages.list-driver-pods';
}
