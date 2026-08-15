<?php

namespace App\Models;

use App\Traits\HasApprovalWorkflow;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends BaseModel
{
    use HasApprovalWorkflow;

    protected $guarded = [];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
    ];

    protected static function booted()
    {
        parent::booted();

        static::saving(function (LeaveRequest $request) {
            if ($request->start_date && $request->end_date) {
                $request->days = $request->start_date->diffInDays($request->end_date) + 1;
            }
        });

        static::updated(function (LeaveRequest $request) {
            if (! $request->wasChanged('status')) {
                return;
            }

            if ($request->status === 'Approved') {
                $request->applyToBalance();
            } elseif ($request->getOriginal('status') === 'Approved') {
                $request->reverseFromBalance();
            }
        });
    }

    public function applyToBalance(): void
    {
        $year = (int) $this->start_date->year;

        $balance = LeaveBalance::firstOrCreate(
            ['staff_id' => $this->staff_id, 'leave_type_id' => $this->leave_type_id, 'year' => $year],
            ['company_id' => $this->company_id, 'allocated_days' => $this->leaveType?->default_days_per_year ?? 0]
        );

        $balance->increment('used_days', $this->days);
    }

    public function reverseFromBalance(): void
    {
        $year = (int) $this->start_date->year;

        $balance = LeaveBalance::where('staff_id', $this->staff_id)
            ->where('leave_type_id', $this->leave_type_id)
            ->where('year', $year)
            ->first();

        $balance?->decrement('used_days', min($this->days, $balance->used_days));
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }
}
