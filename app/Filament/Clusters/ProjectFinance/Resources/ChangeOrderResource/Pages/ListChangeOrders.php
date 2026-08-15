<?php

namespace App\Filament\Clusters\ProjectFinance\Resources\ChangeOrderResource\Pages;

use App\Filament\Clusters\ProjectFinance\Resources\ChangeOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListChangeOrders extends ListRecords
{
    protected static string $resource = ChangeOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-arrow-path-rounded-square',
            'title' => 'Change Orders',
            'subtitle' => 'Review and approve contract change orders affecting project scope and cost.',
        ]);
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Clusters\ProjectFinance\Widgets\ChangeOrdersChart::class,
        ];
    }
}
