<?php

namespace App\Filament\Clusters\BusinessIntelligence\Resources\CustomReportResource\Pages;

use App\Filament\Clusters\BusinessIntelligence\Resources\CustomReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListCustomReports extends ListRecords
{
    protected static string $resource = CustomReportResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-adjustments-horizontal',
            'title' => 'Custom Reports',
            'subtitle' => 'Build and run custom reports tailored to your fleet and finance data.',
        ]);
    }
}
