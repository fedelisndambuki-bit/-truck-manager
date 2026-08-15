<?php

namespace App\Filament\Resources\SafetyMeetingResource\Pages;

use App\Filament\Resources\SafetyMeetingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListSafetyMeetings extends ListRecords
{
    protected static string $resource = SafetyMeetingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-user-group',
            'title' => 'Safety Meetings',
            'subtitle' => 'Organize and track safety meetings and attendance.',
        ]);
    }
}
