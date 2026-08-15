<?php

namespace App\Filament\Clusters\CrmSales\Resources\OpportunityResource\Pages;

use App\Filament\Clusters\CrmSales\Resources\OpportunityResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListOpportunities extends ListRecords
{
    protected static string $resource = OpportunityResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-chart-bar-square',
            'title' => 'Opportunities',
            'subtitle' => 'Manage active sales opportunities through each stage of the pipeline.',
        ]);
    }
}
