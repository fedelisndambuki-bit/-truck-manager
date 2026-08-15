<?php

namespace App\Filament\Resources\SalaryAdvanceResource\Pages;

use App\Filament\Resources\SalaryAdvanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListSalaryAdvances extends ListRecords
{
    protected static string $resource = SalaryAdvanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-currency-dollar',
            'title' => 'Salary Advances',
            'subtitle' => 'Manage employee salary advance requests and repayments.',
        ]);
    }
}
