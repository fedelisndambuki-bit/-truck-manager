<?php

namespace App\Filament\Clusters\DocumentManagement\Resources\MeetingMinutesResource\Pages;

use App\Filament\Clusters\DocumentManagement\Resources\MeetingMinutesResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMeetingMinutes extends EditRecord
{
    protected static string $resource = MeetingMinutesResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
