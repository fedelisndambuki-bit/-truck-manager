<?php

namespace App\Filament\Clusters;

use App\Traits\HasIndustryAccess;
use Filament\Clusters\Cluster;

/**
 * Enterprise Laboratory & Quality Control (Quarry & Mining Industry) — a
 * full LIMS: samples, laboratory tests, test methods, quality standards,
 * quality holds/releases, non-conformance/CAPA, product certification,
 * laboratory equipment/calibration, and customer quality requirements. The
 * single source of truth for product quality — no product may proceed to
 * dispatch unless it has passed the required quality inspections, enforced
 * via a real gate wired into DispatchOrder::runDecisionEngine()/canExit().
 */
class LaboratoryQualityControl extends Cluster
{
    use HasIndustryAccess;

    public static function getRequiredIndustries(): array
    {
        return ['quarry'];
    }

    protected static ?string $navigationIcon = 'heroicon-o-beaker';

    protected static ?string $navigationLabel = 'Laboratory & Quality Control';

    protected static ?string $navigationGroup = 'Quarry';

    protected static ?int $navigationSort = 8;

    protected static ?string $slug = 'laboratory-quality-control';

    public static function canAccess(): bool
    {
        return static::passesIndustryGate();
    }
}
