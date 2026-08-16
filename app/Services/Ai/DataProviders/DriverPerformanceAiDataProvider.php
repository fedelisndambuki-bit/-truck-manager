<?php

namespace App\Services\Ai\DataProviders;

use App\Contracts\AiDataProviderInterface;
use App\Enums\CompanyIndustry;
use App\Services\DriverRiskService;

class DriverPerformanceAiDataProvider implements AiDataProviderInterface
{
    public function __construct(protected DriverRiskService $driverRisk)
    {
    }

    public function key(): string
    {
        return 'driverPerformance';
    }

    public function label(): string
    {
        return 'Driver Performance';
    }

    public function requiredPermission(): ?string
    {
        return null;
    }

    public function appliesToIndustry(?string $industry): bool
    {
        return in_array($industry, [CompanyIndustry::Trucking->value, CompanyIndustry::MultiOperation->value], true);
    }

    public function summary(int $companyId, ?array $period = null): array
    {
        $limit = config('ai.max_records_per_provider', 40);
        $topRisks = $this->driverRisk->topRisks($companyId, $limit);

        return [
            'high_risk_drivers' => collect($topRisks)->map(fn ($r) => [
                'driver' => $r['driver_name'] ?? null,
                'risk_score' => $r['risk_score'] ?? null,
            ])->values()->all(),
        ];
    }

    public function tools(): array
    {
        return [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_driver_performance',
                    'description' => 'Get drivers ranked by operating risk (delayed trips, fuel anomalies).',
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
