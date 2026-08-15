<?php

namespace App\Filament\Clusters\Procurement\Resources\ProcurementBudgetResource\Pages;

use App\Filament\Clusters\Procurement\Resources\ProcurementBudgetResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListProcurementBudgets extends ListRecords
{
    protected static string $resource = ProcurementBudgetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-banknotes',
            'title' => 'Budget Control',
            'subtitle' => 'Monitor approved procurement budgets and remaining spend by department.',
        ]);
    }
}
