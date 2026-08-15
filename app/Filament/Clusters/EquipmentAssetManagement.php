<?php

namespace App\Filament\Clusters;

use App\Traits\HasIndustryAccess;
use Filament\Clusters\Cluster;

/**
 * Equipment & Asset Management (Quarry & Mining Industry) — enterprise
 * lifecycle management of every physical asset: register, categories,
 * assignments, utilization, health/telematics, inspections, preventive and
 * corrective maintenance, breakdowns, work orders, workshop, service
 * history, spare parts, tyres, attachments, fuel, costs, depreciation,
 * warranty and documents. Industry-gated, sits alongside the other Quarry
 * clusters in the same 'Quarry' nav group.
 */
class EquipmentAssetManagement extends Cluster
{
    use HasIndustryAccess;

    public static function getRequiredIndustries(): array
    {
        return ['quarry'];
    }

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationLabel = 'Equipment & Assets';

    protected static ?string $navigationGroup = 'Quarry';

    protected static ?int $navigationSort = 9;

    protected static ?string $slug = 'equipment-asset-management';

    public static function canAccess(): bool
    {
        return static::passesIndustryGate();
    }
}
