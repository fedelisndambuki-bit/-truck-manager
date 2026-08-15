<?php

namespace App\Filament\Clusters\DocumentManagement\Resources\MeetingMinutesResource\Pages;

use App\Filament\Clusters\DocumentManagement\Resources\MeetingMinutesResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListMeetingMinutes extends ListRecords
{
    protected static string $resource = MeetingMinutesResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-users',
            'title' => 'Meeting Minutes',
            'subtitle' => 'Record and manage minutes from project meetings and stakeholder discussions.',
        ]);
    }
}
