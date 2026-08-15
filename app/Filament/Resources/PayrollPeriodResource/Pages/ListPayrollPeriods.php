<?php

namespace App\Filament\Resources\PayrollPeriodResource\Pages;

use App\Filament\Resources\PayrollPeriodResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPayrollPeriods extends ListRecords
{
    protected static string $resource = PayrollPeriodResource::class;

    protected static string $view = 'filament.resources.payroll-period-resource.pages.list-payroll-periods';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('New Payroll Run'),
        ];
    }
}
