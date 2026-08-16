<?php

namespace App\Services\Ai\DataProviders;

use App\Contracts\AiDataProviderInterface;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Services\Ai\AiComparisonEngine;
use App\Services\Ai\AiPeriodParser;

/**
 * CRM & Sales — all-industry shared module (no industry gate on Lead/
 * Opportunity).
 */
class CrmAiDataProvider implements AiDataProviderInterface
{
    public function __construct(protected AiComparisonEngine $comparisons, protected AiPeriodParser $periods)
    {
    }

    public function key(): string
    {
        return 'crm';
    }

    public function label(): string
    {
        return 'CRM & Sales';
    }

    public function requiredPermission(): ?string
    {
        return null;
    }

    public function appliesToIndustry(?string $industry): bool
    {
        return true;
    }

    public function summary(int $companyId, ?array $period = null): array
    {
        $current = $period ?? ['from' => now()->startOfMonth(), 'to' => now(), 'label' => 'this month'];
        $previous = $this->periods->previousEquivalent($current);

        $currentNewLeads = Lead::where('company_id', $companyId)->whereBetween('created_at', [$current['from'], $current['to']])->count();
        $previousNewLeads = Lead::where('company_id', $companyId)->whereBetween('created_at', [$previous['from'], $previous['to']])->count();

        return [
            'open_leads' => Lead::where('company_id', $companyId)->whereNotIn('status', ['Converted', 'Lost'])->count(),
            'open_opportunities' => Opportunity::where('company_id', $companyId)->whereNotIn('stage', ['Won', 'Lost'])->count(),
            'new_leads_comparison' => $this->comparisons->compare('new_leads', $currentNewLeads, $previousNewLeads, 'leads', $current['label'], $previous['label']),
        ];
    }

    public function tools(): array
    {
        return [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_crm_summary',
                    'description' => 'Get open leads and open sales opportunities count, plus new-leads-created compared to the equivalent previous period.',
                    'parameters' => ['type' => 'object', 'properties' => new \stdClass(), 'required' => []],
                ],
            ],
        ];
    }

    public function callTool(string $name, array $arguments, int $companyId): array
    {
        return $this->summary($companyId);
    }
}
