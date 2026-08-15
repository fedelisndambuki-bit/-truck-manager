<?php

namespace App\Filament\Clusters;

use App\Traits\HasIndustryAccess;
use Filament\Clusters\Cluster;

/**
 * Crushing Plant Management (Quarry & Mining Industry) — complete
 * operational control of crushing/screening plants: production lines,
 * crushers/screens/conveyors/feeders, production runs, downtime, product
 * output, energy monitoring, OEE and reporting. Industry-gated, sits
 * alongside Production Management and Blast Management in the same
 * 'Quarry' nav group.
 */
class CrushingPlantManagement extends Cluster
{
    use HasIndustryAccess;

    public static function getRequiredIndustries(): array
    {
        return ['quarry'];
    }

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Crushing Plant Management';

    protected static ?string $navigationGroup = 'Quarry';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'crushing-plant-management';

    public static function canAccess(): bool
    {
        return static::passesIndustryGate();
    }
}
