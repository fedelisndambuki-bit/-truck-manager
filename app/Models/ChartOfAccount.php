<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class ChartOfAccount extends BaseModel
{
    protected $table = 'fin_chart_of_accounts';

    protected $guarded = [];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::deleting(function (ChartOfAccount $account) {
            if ($account->is_system) {
                return false;
            }

            if (JournalLine::where('account_id', $account->id)->exists()) {
                return false;
            }

            if (PostingRule::where('debit_account_code', $account->account_code)
                ->orWhere('credit_account_code', $account->account_code)
                ->exists()) {
                return false;
            }

            return true;
        });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_account_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_account_id');
    }

    public function costCentre(): BelongsTo
    {
        return $this->belongsTo(CostCentre::class);
    }

    public function profitCentre(): BelongsTo
    {
        return $this->belongsTo(ProfitCentre::class);
    }

    /**
     * Resolve an account by code, preferring a company-specific row and
     * falling back to the platform-wide system row (company_id is null).
     */
    public static function resolveByCode(?int $companyId, int $code): ?self
    {
        $query = static::withoutGlobalScope('company')->where('account_code', $code);

        if ($companyId) {
            $companyRow = (clone $query)->where('company_id', $companyId)->first();
            if ($companyRow) {
                return $companyRow;
            }
        }

        return $query->whereNull('company_id')->first();
    }

    /**
     * Current balance for a company, signed per this account's
     * normal_balance, computed from the flat fin_ledger_entries ledger (not
     * from journal lines — that table is the actual posted source of truth
     * FinancialService already maintains). Most accounts are platform-wide
     * system rows (company_id null), so the company to scope by must be
     * passed explicitly rather than read off $this->company_id.
     */
    public function balance(int $companyId): float
    {
        $totals = LedgerEntry::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('account_code', $this->account_code)
            ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->first();

        $debit = (float) ($totals->total_debit ?? 0) / 100;
        $credit = (float) ($totals->total_credit ?? 0) / 100;

        return $this->normal_balance === 'Debit' ? $debit - $credit : $credit - $debit;
    }
}
