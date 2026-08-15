<?php

namespace App\Services;

use App\Models\DepreciationEntry;
use App\Models\DepreciationSchedule;
use Carbon\Carbon;

/**
 * Computes one period's depreciation for a schedule and posts it — to the
 * unified cost ledger via the caller (EquipmentCostEntry, added by the
 * eam:post-depreciation command) and to Finance via PostingEngineService. Custom
 * method schedules are never auto-posted; there's no formula to compute
 * from, so those periods must be entered directly on the DepreciationEntry
 * relation manager.
 */
class DepreciationService
{
    public function postPeriod(DepreciationSchedule $schedule, Carbon $periodEnd): ?DepreciationEntry
    {
        if ($schedule->method === 'Custom') {
            return null;
        }

        // Normalize to plain Y-m-d strings — these are `date` (not datetime)
        // columns, and the uniqueness check must compare like-for-like with
        // what gets persisted, not a full datetime with a time component.
        $periodEndDate = $periodEnd->toDateString();
        $periodStartDate = $periodEnd->copy()->startOfMonth()->toDateString();

        if (DepreciationEntry::where('depreciation_schedule_id', $schedule->id)->where('period_end', $periodEndDate)->exists()) {
            return null;
        }

        $amount = $this->computePeriodAmount($schedule);

        if ($amount <= 0) {
            return null;
        }

        $bookValueAfter = max($schedule->residual_value, $schedule->currentBookValue() - $amount);

        $entry = DepreciationEntry::create([
            'company_id' => $schedule->company_id,
            'depreciation_schedule_id' => $schedule->id,
            'period_start' => $periodStartDate,
            'period_end' => $periodEndDate,
            'amount' => $amount,
            'book_value_after' => $bookValueAfter,
            'ledger_posted' => false,
        ]);

        app(\App\Services\PostingEngineService::class)->post('depreciation.posted', [
            'company_id' => $schedule->company_id,
            'reference_type' => 'depreciation_entry',
            'reference_id' => (string) $entry->id,
            'amount' => $amount,
            'description' => "Depreciation — {$schedule->equipment?->name} ({$schedule->method}), period ending {$periodEnd->format('Y-m-d')}",
        ]);

        $entry->update(['ledger_posted' => true]);

        \App\Models\EquipmentCostEntry::create([
            'company_id' => $schedule->company_id,
            'equipment_id' => $schedule->equipment_id,
            'cost_category' => 'Depreciation',
            'amount' => $amount,
            'incurred_on' => $periodEnd->toDateString(),
            'source_type' => DepreciationEntry::class,
            'source_id' => $entry->id,
            'description' => "Depreciation for period ending {$periodEnd->format('Y-m-d')}",
        ]);

        if ($bookValueAfter <= $schedule->residual_value) {
            $schedule->update(['status' => 'Completed']);
        }

        return $entry;
    }

    protected function computePeriodAmount(DepreciationSchedule $schedule): float
    {
        $depreciableBase = (float) $schedule->cost_basis - (float) $schedule->residual_value;

        return match ($schedule->method) {
            'StraightLine' => $schedule->useful_life_years
                ? round($depreciableBase / $schedule->useful_life_years / 12, 2)
                : 0.0,

            'ReducingBalance' => $schedule->rate_percent
                ? round($schedule->currentBookValue() * ((float) $schedule->rate_percent / 100) / 12, 2)
                : 0.0,

            'UnitsOfProduction' => $this->unitsOfProductionAmount($schedule, $depreciableBase),

            default => 0.0,
        };
    }

    protected function unitsOfProductionAmount(DepreciationSchedule $schedule, float $depreciableBase): float
    {
        if (! $schedule->total_expected_units || $schedule->total_expected_units <= 0) {
            return 0.0;
        }

        $unitsUsedTotal = match ($schedule->units_basis) {
            'engine_hours' => (float) $schedule->equipment?->engine_hours,
            default => 0.0,
        };

        $unitsAlreadyDepreciated = $schedule->accumulatedDepreciation() > 0
            ? ($schedule->accumulatedDepreciation() / $depreciableBase) * $schedule->total_expected_units
            : 0.0;

        $unitsThisPeriod = max(0, $unitsUsedTotal - $unitsAlreadyDepreciated);
        $perUnit = $depreciableBase / $schedule->total_expected_units;

        return round($unitsThisPeriod * $perUnit, 2);
    }
}
