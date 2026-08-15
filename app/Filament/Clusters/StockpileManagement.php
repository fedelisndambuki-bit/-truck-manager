<?php

namespace App\Filament\Clusters;

use App\Traits\HasIndustryAccess;
use Filament\Clusters\Cluster;

/**
 * Stockpile Management (Quarry & Mining Industry) — the single source of
 * truth for finished-product inventory across quarries, plants, stockyards
 * and depots: live inventory, stock movements, transfers, reservations,
 * adjustments, physical counts, quality status and reporting. Industry-gated,
 * sits alongside Production Management, Blast Management and Crushing Plant
 * Management in the same 'Quarry' nav group.
 */
class StockpileManagement extends Cluster
{
    use HasIndustryAccess;

    public static function getRequiredIndustries(): array
    {
        return ['quarry'];
    }

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationLabel = 'Stockpile Management';

    protected static ?string $navigationGroup = 'Quarry';

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'stockpile-management';

    public static function canAccess(): bool
    {
        return static::passesIndustryGate();
    }
}
