<?php

namespace App\Filament\Resources\PayrollPeriodResource\Pages;

use App\Filament\Resources\PayrollPeriodResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListPayrollPeriods extends ListRecords
{
    protected static string $resource = PayrollPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('New Payroll Run'),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-banknotes',
            'title' => 'Payroll Periods',
            'subtitle' => 'Manage payroll runs and staff compensation cycles.',
        ]);
    }
}
