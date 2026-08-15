<?php

namespace App\Filament\Resources\StaffResource\Pages;

use App\Filament\Resources\StaffResource;
use App\Filament\Widgets\StaffKpiWidget;
use Filament\Resources\Pages\ListRecords;

class ListStaff extends ListRecords
{
    protected static string $resource = StaffResource::class;

    protected static string $view = 'filament.resources.staff-resource.pages.list-staff';

    protected function getHeaderWidgets(): array
    {
        return [
            StaffKpiWidget::class,
        ];
    }

    public function getVisibleHeaderWidgets(): array
    {
        return [];
    }
}
