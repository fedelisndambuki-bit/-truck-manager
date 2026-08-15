<?php

namespace App\Filament\Resources\StaffResource\Pages;

use App\Filament\Resources\StaffResource;
use App\Filament\Widgets\StaffKpiWidget;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListStaff extends ListRecords
{
    protected static string $resource = StaffResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            StaffKpiWidget::class,
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-users',
            'title' => 'Staff',
            'subtitle' => 'Manage your workforce records and employment details.',
        ]);
    }
}
