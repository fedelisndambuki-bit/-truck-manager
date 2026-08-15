<?php

namespace App\Filament\Clusters\ProjectFinance\Resources\BudgetResource\Pages;

use App\Filament\Clusters\ProjectFinance\Resources\BudgetResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListBudgets extends ListRecords
{
    protected static string $resource = BudgetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-calculator',
            'title' => 'Budgets',
            'subtitle' => 'Set and monitor project budgets against actual and committed costs.',
        ]);
    }
}
