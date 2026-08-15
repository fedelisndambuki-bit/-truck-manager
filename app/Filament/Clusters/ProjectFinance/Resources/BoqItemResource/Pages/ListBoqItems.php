<?php

namespace App\Filament\Clusters\ProjectFinance\Resources\BoqItemResource\Pages;

use App\Filament\Clusters\ProjectFinance\Resources\BoqItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListBoqItems extends ListRecords
{
    protected static string $resource = BoqItemResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-clipboard-document-list',
            'title' => 'BOQ Management',
            'subtitle' => 'Manage bill of quantities line items for project costing and budgeting.',
        ]);
    }
}
