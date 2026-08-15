<?php

namespace App\Filament\Clusters\Finance\Resources\ChartOfAccountResource\Pages;

use App\Filament\Clusters\Finance\Resources\ChartOfAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListChartOfAccounts extends ListRecords
{
    protected static string $resource = ChartOfAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-book-open',
            'title' => 'Chart of Accounts',
            'subtitle' => 'Manage the ledger accounts used to classify all financial transactions.',
        ]);
    }
}
