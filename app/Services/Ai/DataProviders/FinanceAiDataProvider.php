<?php

namespace App\Services\Ai\DataProviders;

use App\Contracts\AiDataProviderInterface;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Payout;
use App\Services\Ai\AiComparisonEngine;
use App\Services\Ai\AiPeriodParser;

/**
 * Core financials — shared across all three industries (Invoice/Payment/
 * Expense/Payout are all-industry models, confirmed during the audit).
 * Gated behind the same 'finance.chart_of_accounts' permission Filament's
 * Chart of Accounts resource already uses, so a user without Finance access
 * gets denied the same way they would in the Filament panel.
 */
class FinanceAiDataProvider implements AiDataProviderInterface
{
    public function __construct(protected AiComparisonEngine $comparisons, protected AiPeriodParser $periods)
    {
    }

    public function key(): string
    {
        return 'finance';
    }

    public function label(): string
    {
        return 'Finance';
    }

    public function requiredPermission(): ?string
    {
        return 'finance.chart_of_accounts';
    }

    public function appliesToIndustry(?string $industry): bool
    {
        return true;
    }

    public function summary(int $companyId, ?array $period = null): array
    {
        $current = $period ?? ['from' => now()->startOfMonth(), 'to' => now(), 'label' => 'this month'];
        $previous = $this->periods->previousEquivalent($current);

        $currentRevenue = $this->paidRevenue($companyId, $current['from'], $current['to']);
        $previousRevenue = $this->paidRevenue($companyId, $previous['from'], $previous['to']);

        $currentExpenses = $this->totalExpenses($companyId, $current['from'], $current['to']);
        $previousExpenses = $this->totalExpenses($companyId, $previous['from'], $previous['to']);

        return [
            'period' => $current['label'],
            'previous_period' => $previous['label'],
            'cash_revenue' => $currentRevenue,
            'cash_revenue_comparison' => $this->comparisons->compare('cash_revenue', $currentRevenue, $previousRevenue, 'KES', $current['label'], $previous['label']),
            'expenses_total' => $currentExpenses,
            'expenses_total_comparison' => $this->comparisons->compare('expenses_total', $currentExpenses, $previousExpenses, 'KES', $current['label'], $previous['label']),
            'expenses_count' => Expense::where('company_id', $companyId)->whereBetween('created_at', [$current['from'], $current['to']])->count(),
            'profit' => round($currentRevenue - $currentExpenses, 2),
            'previous_profit' => round($previousRevenue - $previousExpenses, 2),
            'unpaid_invoices' => Invoice::where('company_id', $companyId)->whereIn('status', ['sent', 'overdue'])->count(),
            'overdue_invoices_amount' => (float) Invoice::where('company_id', $companyId)->where('status', 'overdue')->sum('total_amount'),
            'pending_payouts' => Payout::where('company_id', $companyId)->where('status', 'pending')->count(),
        ];
    }

    private function paidRevenue(int $companyId, $from, $to): float
    {
        return round((float) Payment::where('company_id', $companyId)
            ->where('status', 'Paid')
            ->whereBetween('payment_date', [$from, $to])
            ->sum('amount'), 2);
    }

    private function totalExpenses(int $companyId, $from, $to): float
    {
        return round((float) Expense::where('company_id', $companyId)
            ->whereBetween('created_at', [$from, $to])
            ->sum('amount'), 2);
    }

    public function tools(): array
    {
        return [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_financial_summary',
                    'description' => 'Get revenue, expenses, profit, unpaid/overdue invoices and pending payouts for a period, each compared against the equivalent previous period. Requires Finance access.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => ['period' => ['type' => 'string', 'description' => 'e.g. this month, last quarter, this year']],
                        'required' => [],
                    ],
                ],
            ],
        ];
    }

    public function callTool(string $name, array $arguments, int $companyId): array
    {
        $period = empty($arguments['period']) ? null : $this->periods->parse((string) $arguments['period']);

        return $this->summary($companyId, $period);
    }
}
