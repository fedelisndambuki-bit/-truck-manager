<?php

namespace App\Filament\Resources\LeaveRequestResource\Pages;

use App\Filament\Resources\LeaveRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLeaveRequests extends ListRecords
{
    protected static string $resource = LeaveRequestResource::class;

    protected static string $view = 'filament.resources.leave-request-resource.pages.list-leave-requests';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Request Leave'),
        ];
    }
}
