<?php

namespace App\Filament\Clusters;

use App\Traits\HasIndustryAccess;
use Filament\Clusters\Cluster;

/**
 * Smart Weighbridge & Loading Management (Quarry & Mining Industry) — the
 * digital command center for the complete truck movement lifecycle: gate
 * check-in, driver/vehicle verification, tare weighing, automatic loading
 * instruction generation, QR-driven loader workflow, gross weighing,
 * tolerance validation, dispatch approval, exit, and automatic stock/finance
 * synchronization. Industry-gated, sits alongside Production Management,
 * Blast Management, Crushing Plant Management and Stockpile Management in
 * the same 'Quarry' nav group.
 */
class SmartWeighbridge extends Cluster
{
    use HasIndustryAccess;

    public static function getRequiredIndustries(): array
    {
        return ['quarry'];
    }

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationLabel = 'Smart Weighbridge';

    protected static ?string $navigationGroup = 'Quarry';

    protected static ?int $navigationSort = 6;

    protected static ?string $slug = 'smart-weighbridge';

    public static function canAccess(): bool
    {
        return static::passesIndustryGate();
    }
}
