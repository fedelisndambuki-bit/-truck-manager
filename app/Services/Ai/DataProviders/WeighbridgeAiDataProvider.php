<?php

namespace App\Services\Ai\DataProviders;

use App\Contracts\AiDataProviderInterface;
use App\Enums\CompanyIndustry;
use App\Services\Ai\AiTrendAnalyzer;
use App\Services\Quarry\WeighbridgeAnalyticsService;

class WeighbridgeAiDataProvider implements AiDataProviderInterface
{
    public function __construct(protected WeighbridgeAnalyticsService $weighbridge, protected AiTrendAnalyzer $trends)
    {
    }

    public function key(): string
    {
        return 'weighbridge';
    }

    public function label(): string
    {
        return 'Weighbridge';
    }

    public function requiredPermission(): ?string
    {
        return null;
    }

    public function appliesToIndustry(?string $industry): bool
    {
        return in_array($industry, [CompanyIndustry::Quarry->value, CompanyIndustry::MultiOperation->value], true);
    }

    public function summary(int $companyId, ?array $period = null): array
    {
        return [
            'current_queue_length' => $this->weighbridge->currentQueueLength($companyId),
            'todays_tonnes' => $this->weighbridge->todaysTonnes($companyId),
            'tonnes_dispatched_trend_14d' => $this->trends->analyze($this->weighbridge->tonnesDispatchedTrend($companyId, 14)),
            'todays_revenue' => $this->weighbridge->todaysRevenue($companyId),
            'average_truck_turnaround_minutes' => $this->weighbridge->averageTruckTurnaroundMinutes($companyId),
            'weight_accuracy_percent' => $this->weighbridge->weightAccuracyPercent($companyId),
            'rejected_loads_7d' => $this->weighbridge->rejectedLoadsCount($companyId),
            'utilization_percent' => $this->weighbridge->weighbridgeUtilizationPercent($companyId),
            'open_exceptions' => $this->weighbridge->openExceptionsCount($companyId),
        ];
    }

    public function tools(): array
    {
        return [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_weighbridge_summary',
                    'description' => 'Get weighbridge queue length, tonnes weighed today (with a 14-day dispatch trend), turnaround time, weight accuracy and open exceptions.',
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
