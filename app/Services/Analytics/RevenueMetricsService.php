<?php

namespace App\Services\Analytics;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Builder;

/**
 * Single source of truth for what counts as "revenue" (cash-basis: a Payment
 * with status Paid) so every widget/report filters and scopes it the same
 * way. Matches the convention already established by
 * ProjectFinanceService::totalRevenue() — this doesn't replace that method,
 * it generalizes its filter for callers (dashboard-wide charts) that aren't
 * scoped to a single Project and need their own date bucketing.
 */
class RevenueMetricsService
{
    public function paidPaymentsQuery(?int $companyId = null, ?int $projectId = null): Builder
    {
        return Payment::query()
            ->where('status', 'Paid')
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->when($projectId, fn ($q) => $q->where('project_id', $projectId));
    }

    public function cashRevenue(?int $companyId = null, ?int $projectId = null): float
    {
        return round((float) $this->paidPaymentsQuery($companyId, $projectId)->sum('amount'), 2);
    }
}
