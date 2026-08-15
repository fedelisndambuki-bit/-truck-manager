<?php

namespace App\Filament\Clusters\DispatchManagement\Resources\TruckAssignmentResource\Pages;

use App\Filament\Clusters\DispatchManagement\Resources\TruckAssignmentResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListTruckAssignments extends ListRecords
{
    protected static string $resource = TruckAssignmentResource::class;

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-truck',
            'title' => 'Truck Assignments',
            'subtitle' => 'Assign trucks to routes, orders, and driver schedules.',
        ]);
    }
}
