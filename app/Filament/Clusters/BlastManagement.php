<?php

namespace App\Filament\Clusters;

use App\Traits\HasIndustryAccess;
use Filament\Clusters\Cluster;

/**
 * Blast Management (Quarry & Mining Industry) — the full blasting lifecycle
 * from planning through drilling, explosive allocation, safety zones,
 * execution, post-blast/fragmentation analysis, environmental monitoring,
 * costing, compliance and reporting. Industry-gated, sits alongside
 * Production Management in the same 'Quarry' nav group.
 */
class BlastManagement extends Cluster
{
    use HasIndustryAccess;

    public static function getRequiredIndustries(): array
    {
        return ['quarry'];
    }

    protected static ?string $navigationIcon = 'heroicon-o-fire';

    protected static ?string $navigationLabel = 'Blast Management';

    protected static ?string $navigationGroup = 'Quarry';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'blast-management';

    public static function canAccess(): bool
    {
        return static::passesIndustryGate();
    }
}
