<?php

namespace App\Filament\Resources\SafetyMeetingResource\Pages;

use App\Filament\Resources\SafetyMeetingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSafetyMeetings extends ListRecords
{
    protected static string $resource = SafetyMeetingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
