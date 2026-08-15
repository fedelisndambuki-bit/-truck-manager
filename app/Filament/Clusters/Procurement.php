<?php

namespace App\Filament\Clusters;

use App\Traits\HasIndustryAccess;
use Filament\Clusters\Cluster;

/**
 * Enterprise Source-to-Pay / Procure-to-Pay platform — purchase planning,
 * requisitions, RFQs, supplier quotations/comparison, purchase orders,
 * blanket orders, framework agreements, contracts, goods receiving,
 * returns, supplier management/portal/performance, replenishment, budget
 * control, three-way matching and spend analytics.
 *
 * Deliberately universal: no getRequiredIndustries() override — this works
 * across Construction, Quarry and general operations alike, matching the
 * established pattern for industry-agnostic Core modules (see
 * app/Filament/Clusters/WorkflowApprovals.php).
 */
class Procurement extends Cluster
{
    use HasIndustryAccess;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationLabel = 'Procurement';

    protected static ?string $navigationGroup = 'Procurement';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'procurement';

    public static function canAccess(): bool
    {
        return static::passesIndustryGate();
    }
}
