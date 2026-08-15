<?php

namespace App\Filament\Clusters\DispatchManagement\Resources\DriverAssignmentResource\Pages;

use App\Filament\Clusters\DispatchManagement\Resources\DriverAssignmentResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListDriverAssignments extends ListRecords
{
    protected static string $resource = DriverAssignmentResource::class;

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-user-circle',
            'title' => 'Driver Assignments',
            'subtitle' => 'Assign drivers to trucks and dispatch orders.',
        ]);
    }
}
