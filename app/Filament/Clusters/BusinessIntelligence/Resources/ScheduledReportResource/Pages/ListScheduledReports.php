<?php

namespace App\Filament\Clusters\BusinessIntelligence\Resources\ScheduledReportResource\Pages;

use App\Filament\Clusters\BusinessIntelligence\Resources\ScheduledReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListScheduledReports extends ListRecords
{
    protected static string $resource = ScheduledReportResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-calendar-days',
            'title' => 'Scheduled Reports',
            'subtitle' => 'Configure automated reports to run and deliver on a recurring schedule.',
        ]);
    }
}
