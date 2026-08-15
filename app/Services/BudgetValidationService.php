<?php

namespace App\Services;

use App\Models\ProcurementBudget;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;

/**
 * Validates a prospective purchase against whichever ProcurementBudget row
 * matches its dimensions (business unit / department / cost center /
 * project) for the current financial year — committed spend is computed
 * live from open requisitions + open purchase orders rather than
 * maintained in a separate encumbrance ledger.
 */
class BudgetValidationService
{
    public function findBudget(?int $companyId, array $dimensions, ?string $financialYear = null): ?ProcurementBudget
    {
        $financialYear = $financialYear ?? (string) now()->year;

        $query = ProcurementBudget::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('financial_year', $financialYear)
            ->where('status', 'Active');

        foreach (['business_unit_id', 'department_id', 'cost_centre_id', 'project_id'] as $field) {
            if (! empty($dimensions[$field])) {
                $query->where($field, $dimensions[$field]);
            }
        }

        return $query->first();
    }

    /**
     * @return array{within_budget: bool, budget: ?ProcurementBudget, committed: float, remaining: float}
     */
    public function validate(?int $companyId, array $dimensions, float $amount, ?string $financialYear = null): array
    {
        $budget = $this->findBudget($companyId, $dimensions, $financialYear);

        if (! $budget) {
            // No budget configured for this dimension — nothing to validate
            // against, so the purchase is allowed through untouched.
            return ['within_budget' => true, 'budget' => null, 'committed' => 0.0, 'remaining' => 0.0];
        }

        $committed = $this->committedSpend($companyId, $dimensions, $financialYear ?? (string) now()->year);
        $remaining = round((float) $budget->approved_budget - $committed, 2);

        return [
            'within_budget' => ($committed + $amount) <= (float) $budget->approved_budget,
            'budget' => $budget,
            'committed' => $committed,
            'remaining' => $remaining,
        ];
    }

    protected function committedSpend(?int $companyId, array $dimensions, string $financialYear): float
    {
        $yearStart = "{$financialYear}-01-01";
        $yearEnd = "{$financialYear}-12-31";

        $requisitionSpend = (float) PurchaseRequest::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereIn('status', ['Pending', 'Approved'])
            ->whereBetween('created_at', [$yearStart, $yearEnd])
            ->when(! empty($dimensions['business_unit_id']), fn ($q) => $q->where('business_unit_id', $dimensions['business_unit_id']))
            ->when(! empty($dimensions['department_id']), fn ($q) => $q->where('department_id', $dimensions['department_id']))
            ->when(! empty($dimensions['cost_centre_id']), fn ($q) => $q->where('cost_centre_id', $dimensions['cost_centre_id']))
            ->when(! empty($dimensions['project_id']), fn ($q) => $q->where('project_id', $dimensions['project_id']))
            ->sum('expected_cost');

        $orderSpend = (float) PurchaseOrder::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereIn('status', ['Draft', 'Pending', 'Approved'])
            ->whereBetween('created_at', [$yearStart, $yearEnd])
            ->when(! empty($dimensions['business_unit_id']), fn ($q) => $q->where('business_unit_id', $dimensions['business_unit_id']))
            ->when(! empty($dimensions['department_id']), fn ($q) => $q->where('department_id', $dimensions['department_id']))
            ->when(! empty($dimensions['cost_centre_id']), fn ($q) => $q->where('cost_centre_id', $dimensions['cost_centre_id']))
            ->when(! empty($dimensions['project_id']), fn ($q) => $q->where('project_id', $dimensions['project_id']))
            ->sum('total_amount');

        return round($requisitionSpend + $orderSpend, 2);
    }
}
