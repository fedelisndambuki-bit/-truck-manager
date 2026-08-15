<?php

namespace App\Filament\Resources\AttendanceResource\Pages;

use App\Filament\Resources\AttendanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAttendances extends ListRecords
{
    protected static string $resource = AttendanceResource::class;

    protected static string $view = 'filament.resources.attendance-resource.pages.list-attendances';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Log Attendance'),
        ];
    }
}
