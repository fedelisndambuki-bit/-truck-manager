<?php

namespace App\Filament\Clusters\CrmSales\Resources\LeadResource\Pages;

use App\Filament\Clusters\CrmSales\Resources\LeadResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListLeads extends ListRecords
{
    protected static string $resource = LeadResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-megaphone',
            'title' => 'Leads',
            'subtitle' => 'Track prospective clients from first contact through to conversion.',
        ]);
    }
}
