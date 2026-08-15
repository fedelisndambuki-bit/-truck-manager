<?php

namespace App\Filament\Clusters\Finance\Resources\FinancialPeriodResource\Pages;

use App\Filament\Clusters\Finance\Resources\FinancialPeriodResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListFinancialPeriods extends ListRecords
{
    protected static string $resource = FinancialPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-calendar-days',
            'title' => 'Financial Periods',
            'subtitle' => 'Open, close and lock the accounting periods used for reporting.',
        ]);
    }
}
