<?php

namespace App\Filament\Clusters;

use App\Traits\HasIndustryAccess;
use Filament\Clusters\Cluster;

/**
 * Production Management (Quarry & Mining Industry) — the operational heart
 * of the Quarry ERP: extraction through crushing, product output, stockpile
 * crediting, planning, targets, downtime and reporting. Industry-gated
 * (unlike the universal WorkflowApprovals cluster) since it is specific to
 * Quarry companies.
 */
class ProductionManagement extends Cluster
{
    use HasIndustryAccess;

    public static function getRequiredIndustries(): array
    {
        return ['quarry'];
    }

    protected static ?string $navigationIcon = 'heroicon-o-cube-transparent';

    protected static ?string $navigationLabel = 'Production Management';

    protected static ?string $navigationGroup = 'Quarry';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'production-management';

    public static function canAccess(): bool
    {
        return static::passesIndustryGate();
    }
}
