<?php

namespace App\Filament\Clusters\DispatchManagement\Resources\LoadingScheduleResource\Pages;

use App\Filament\Clusters\DispatchManagement\Resources\LoadingScheduleResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListLoadingSchedules extends ListRecords
{
    protected static string $resource = LoadingScheduleResource::class;

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-queue-list',
            'title' => 'Loading Schedules',
            'subtitle' => 'Plan and manage loading bay schedules for outgoing trucks.',
        ]);
    }
}
