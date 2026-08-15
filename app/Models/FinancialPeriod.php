<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialPeriod extends BaseModel
{
    protected $table = 'fin_periods';

    protected $guarded = [];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'closed_at' => 'datetime',
    ];

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public static function openPeriodCovering(int $companyId, Carbon $date): ?self
    {
        return static::where('company_id', $companyId)
            ->whereDate('period_start', '<=', $date)
            ->whereDate('period_end', '>=', $date)
            ->first();
    }

    public function close(User $by): void
    {
        $this->update([
            'status' => 'Closed',
            'closed_by' => $by->id,
            'closed_at' => now(),
        ]);
    }

    public function reopen(): void
    {
        $this->update([
            'status' => 'Open',
            'closed_by' => null,
            'closed_at' => null,
        ]);
    }
}
