<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduledReport extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'recipients' => 'array',
        'next_run_at' => 'datetime',
        'last_run_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (ScheduledReport $report) {
            $report->next_run_at ??= static::nextRunFor($report->frequency);
        });
    }

    public static function nextRunFor(string $frequency, ?\Illuminate\Support\Carbon $from = null): \Illuminate\Support\Carbon
    {
        $from ??= now();

        return match ($frequency) {
            'Daily' => $from->copy()->addDay(),
            'Weekly' => $from->copy()->addWeek(),
            'Monthly' => $from->copy()->addMonth(),
            'Quarterly' => $from->copy()->addMonths(3),
            'Yearly' => $from->copy()->addYear(),
            default => $from->copy()->addWeek(),
        };
    }

    public function customReport(): BelongsTo
    {
        return $this->belongsTo(CustomReport::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function markRun(): void
    {
        $this->update([
            'last_run_at' => now(),
            'next_run_at' => static::nextRunFor($this->frequency, now()),
        ]);
    }
}
