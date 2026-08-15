<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Employee;
use App\Models\Expense;
use App\Traits\HasIndustryAccess;
use Illuminate\Support\Carbon;

class DriverPayslips extends Page
{
    use HasIndustryAccess;

    public static function getRequiredIndustries(): array
    {
        return ['trucking'];
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (! $user) return false;
        if ($user->hasRole('Super Admin')) return true;

        return static::passesIndustryGate() && $user->can('payroll');
    }

    protected static ?string $navigationGroup = 'Financials';
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.pages.driver-payslips';
    protected static ?string $title = 'Driver Payslips';

    /** @var string YYYY-MM — month being viewed on the cards */
    public string $payslipMonth;

    public function mount(): void
    {
        $this->payslipMonth = now()->format('Y-m');
    }

    public function getDrivers()
    {
        $companyId = auth()->user()?->company_id;

        return Employee::when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->orderBy('full_name')
            ->get();
    }

    /**
     * Returns current-month salary summaries keyed by employee_id.
     * Uses actual Expense records — not the stale cumulative totals on Employee.
     *
     * @return array<int, array{base: float, allowances: float, tax: float, net: float}>
     */
    public function getMonthSummaries(): array
    {
        $companyId = auth()->user()?->company_id;
        $period    = Carbon::parse($this->payslipMonth);

        $expenses = Expense::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereYear('date', $period->year)
            ->whereMonth('date', $period->month)
            ->get(['employee_id', 'category', 'amount', 'tax_amount', 'payslip_earning_type']);

        $summaries = [];

        foreach ($expenses as $expense) {
            $id = $expense->employee_id;
            if (! $id) continue;

            if (! isset($summaries[$id])) {
                $summaries[$id] = ['base' => 0.0, 'allowances' => 0.0, 'tax' => 0.0];
            }

            $amount  = (float) $expense->amount;
            $taxAmt  = (float) ($expense->tax_amount ?? 0);
            $earning = strtolower((string) ($expense->payslip_earning_type ?: $expense->category));

            if (in_array($earning, ['salary', 'basic_pay', 'basic pay', 'basic salary'])) {
                $summaries[$id]['base'] += $amount;
            } elseif (in_array($earning, ['allowance', 'commission', 'commission_bonus', 'bonus', 'overtime_pay', 'holiday_pay'])) {
                $summaries[$id]['allowances'] += $amount;
            }

            $summaries[$id]['tax'] += $taxAmt;
        }

        foreach ($summaries as $id => &$row) {
            $row['net'] = $row['base'] + $row['allowances'] - $row['tax'];
        }

        return $summaries;
    }
}
