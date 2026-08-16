<?php

namespace App\Services\Ai\DataProviders;

use App\Contracts\AiDataProviderInterface;
use App\Enums\CompanyIndustry;
use App\Services\Ai\AiComparisonEngine;
use App\Services\Ai\AiTrendAnalyzer;
use App\Services\Quarry\BlastAnalyticsService;
use App\Services\Quarry\CrushingPlantAnalyticsService;
use App\Services\Quarry\DispatchAnalyticsService;
use App\Services\Quarry\QuarryAnalyticsService;
use App\Services\Quarry\StockpileAnalyticsService;

class QuarryAiDataProvider implements AiDataProviderInterface
{
    public function __construct(
        protected QuarryAnalyticsService $quarry,
        protected CrushingPlantAnalyticsService $crushingPlant,
        protected BlastAnalyticsService $blast,
        protected StockpileAnalyticsService $stockpile,
        protected DispatchAnalyticsService $dispatch,
        protected AiComparisonEngine $comparisons,
        protected AiTrendAnalyzer $trends,
    ) {
    }

    public function key(): string
    {
        return 'quarry';
    }

    public function label(): string
    {
        return 'Quarry Production';
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
        // plantThroughputTrend already returns a 14-day date=>tonnes series —
        // sliced into two 7-day halves this gives a real week-over-week
        // production comparison without a second bespoke query.
        $productionTrend = $this->crushingPlant->plantThroughputTrend($companyId, 14);
        $dates = array_keys($productionTrend);
        sort($dates);
        $thisWeekDates = array_slice($dates, -7);
        $lastWeekDates = array_slice($dates, -14, 7);
        $thisWeekTonnes = array_sum(array_intersect_key($productionTrend, array_flip($thisWeekDates)));
        $lastWeekTonnes = array_sum(array_intersect_key($productionTrend, array_flip($lastWeekDates)));

        return [
            'production_today_tonnes' => $this->quarry->productionToday($companyId),
            'production_week_tonnes' => $this->quarry->productionWeek($companyId),
            'production_week_comparison' => $this->comparisons->compare('production_tonnes', $thisWeekTonnes, $lastWeekTonnes, 'tonnes', 'this week', 'last week'),
            'production_month_tonnes' => $this->quarry->productionMonth($companyId),
            'production_trend_14d' => $this->trends->analyze($productionTrend),
            'target_achievement_percent' => $this->quarry->targetAchievementPercent($companyId),
            'crusher_utilization_percent' => $this->quarry->crusherUtilizationPercent($companyId),
            'plant_utilization_percent' => $this->crushingPlant->plantUtilizationPercent($companyId),
            'crusher_availability_percent' => $this->crushingPlant->crusherAvailabilityPercent($companyId),
            'oee_trend_14d' => $this->trends->analyze($this->crushingPlant->oeeTrend($companyId, 14)),
            'blast_success_rate_percent' => $this->blast->blastSuccessRatePercent($companyId),
            'blast_misfires' => $this->blast->misfiresCount($companyId),
            'low_stockpile_count' => $this->stockpile->lowStockCount($companyId),
            'stock_value' => $this->stockpile->stockValue($companyId),
            'stock_value_trend_14d' => $this->trends->analyze($this->stockpile->stockValueTrend($companyId, 14)),
            'delayed_dispatches' => $this->dispatch->delayedDispatchesCount($companyId),
            'trucks_en_route' => $this->dispatch->trucksEnRouteCount($companyId),
        ];
    }

    public function tools(): array
    {
        return [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_quarry_production',
                    'description' => 'Get quarry production tonnage (today/week/month, with a week-over-week comparison and 14-day trend), target achievement, plant/crusher utilization (with OEE trend), blast performance, stockpile status (with value trend) and dispatch status.',
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
