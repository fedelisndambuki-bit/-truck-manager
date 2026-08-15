<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JournalEntry extends BaseModel
{
    protected $table = 'fin_journal_entries';

    protected $guarded = [];

    protected $casts = [
        'date' => 'date',
        'exchange_rate' => 'decimal:6',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (JournalEntry $journal) {
            if (empty($journal->journal_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $journal->journal_number = 'JE-' . str_pad((string) ($lastId + 1), 6, '0', STR_PAD_LEFT);
            }
        });
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }

    public function reversedJournal(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversed_journal_id');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(FinancialPeriod::class, 'financial_period_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function isBalanced(): bool
    {
        if (! $this->lines()->exists()) {
            return false;
        }

        $totalDebit = round((float) $this->lines()->sum('debit'), 2);
        $totalCredit = round((float) $this->lines()->sum('credit'), 2);

        return abs($totalDebit - $totalCredit) < 0.01;
    }

    public function canPost(): bool
    {
        return $this->status === 'Draft' && $this->isBalanced();
    }

    public function canReverse(): bool
    {
        return $this->status === 'Posted';
    }

    public function canCancel(): bool
    {
        return $this->status === 'Draft';
    }
}
