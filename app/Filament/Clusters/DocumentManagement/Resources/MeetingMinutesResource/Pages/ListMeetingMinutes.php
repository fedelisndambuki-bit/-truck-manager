<?php

namespace App\Filament\Clusters\DocumentManagement\Resources\MeetingMinutesResource\Pages;

use App\Filament\Clusters\DocumentManagement\Resources\MeetingMinutesResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMeetingMinutes extends ListRecords
{
    protected static string $resource = MeetingMinutesResource::class;

    protected static string $view = 'filament.clusters.document-management.resources.meeting-minutes-resource.pages.list-meeting-minutes';

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
