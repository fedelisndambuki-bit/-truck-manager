<?php

namespace App\Models;

use App\Services\PayrollCalculator;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class PayrollPeriod extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    public function runBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'run_by_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    /**
     * Generate payslips for every active staff member for this period,
     * running each through the statutory PayrollCalculator, then update
     * this period's totals. Existing payslips for the period are replaced.
     */
    public function process(): void
    {
        DB::transaction(function () {
            $this->payslips()->delete();

            $staffMembers = Staff::query()
                ->where('company_id', $this->company_id)
                ->where('status', 'Active')
                ->get();

            $totalGross = 0;
            $totalDeductions = 0;
            $totalNet = 0;

            foreach ($staffMembers as $staff) {
                $basicSalary = (float) ($staff->basic_salary ?? 0);
                $allowances = (float) ($staff->allowances ?? 0);
                $grossPay = $basicSalary + $allowances;

                $breakdown = PayrollCalculator::calculate($grossPay);

                $activeAdvance = SalaryAdvance::query()
                    ->where('staff_id', $staff->id)
                    ->where('status', 'Active')
                    ->first();

                $loanDeduction = 0;
                if ($activeAdvance) {
                    $loanDeduction = min($activeAdvance->monthly_deduction, $activeAdvance->balance_remaining);
                }

                $netPay = $breakdown['net_pay_before_other_deductions'] - $loanDeduction;

                Payslip::create([
                    'company_id' => $this->company_id,
                    'payroll_period_id' => $this->id,
                    'staff_id' => $staff->id,
                    'basic_salary' => $basicSalary,
                    'allowances' => $allowances,
                    'gross_pay' => $breakdown['gross_pay'],
                    'nssf_amount' => $breakdown['nssf'],
                    'sha_amount' => $breakdown['sha'],
                    'housing_levy_amount' => $breakdown['housing_levy'],
                    'paye_amount' => $breakdown['paye'],
                    'loan_deduction' => $loanDeduction,
                    'net_pay' => $netPay,
                ]);

                if ($activeAdvance && $loanDeduction > 0) {
                    $activeAdvance->balance_remaining -= $loanDeduction;
                    if ($activeAdvance->balance_remaining <= 0) {
                        $activeAdvance->balance_remaining = 0;
                        $activeAdvance->status = 'Settled';
                    }
                    $activeAdvance->save();
                }

                $totalGross += $breakdown['gross_pay'];
                $totalDeductions += $breakdown['total_statutory_deductions'] + $loanDeduction;
                $totalNet += $netPay;
            }

            $this->update([
                'status' => 'Processing',
                'total_gross' => $totalGross,
                'total_deductions' => $totalDeductions,
                'total_net' => $totalNet,
            ]);
        });
    }
}
