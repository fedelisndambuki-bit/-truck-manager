<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payslip extends BaseModel
{
    protected $guarded = [];

    public function payrollPeriod(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function getTotalDeductionsAttribute(): float
    {
        return round(
            (float) $this->nssf_amount + (float) $this->sha_amount + (float) $this->housing_levy_amount
            + (float) $this->paye_amount + (float) $this->loan_deduction + (float) $this->other_deductions,
            2
        );
    }
}
