<?php

namespace App\Models;

class PostingRule extends BaseModel
{
    protected $table = 'fin_posting_rules';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Resolve the active rule for an event, preferring a company-specific
     * row over the platform default (company_id null).
     */
    public static function resolveFor(string $eventKey, ?int $companyId): ?self
    {
        $query = static::withoutGlobalScope('company')
            ->where('event_key', $eventKey)
            ->where('is_active', true)
            ->orderByDesc('priority');

        if ($companyId) {
            $companyRule = (clone $query)->where('company_id', $companyId)->first();
            if ($companyRule) {
                return $companyRule;
            }
        }

        return $query->whereNull('company_id')->first();
    }
}
