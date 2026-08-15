<?php

namespace App\Filament\Clusters\DispatchManagement\Resources\SchedulingResource\Pages;

use App\Filament\Clusters\DispatchManagement\Resources\SchedulingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListSchedules extends ListRecords
{
    protected static string $resource = SchedulingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-calendar-days',
            'title' => 'Schedules',
            'subtitle' => 'Plan truck and driver schedules for upcoming dispatch operations.',
        ]);
    }
}
