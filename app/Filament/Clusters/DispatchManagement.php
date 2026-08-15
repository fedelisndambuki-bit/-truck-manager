<?php

namespace App\Filament\Clusters;

use App\Traits\HasIndustryAccess;
use Filament\Clusters\Cluster;

/**
 * Enterprise Dispatch Management (Quarry & Mining Industry) — the commercial
 * / planning / delivery view of the same physical process Smart Weighbridge
 * models from the weighbridge-operations angle: customer order -> inventory
 * reservation -> dispatch planning -> truck/driver assignment -> loading ->
 * weighbridge -> approval -> departure -> delivery -> proof of delivery ->
 * invoicing. DispatchOrder remains the single canonical record across both
 * clusters; this one adds the CustomerOrder upstream entity and the
 * delivery leg (En Route / Delivered / Delayed) on top of it.
 */
class DispatchManagement extends Cluster
{
    use HasIndustryAccess;

    public static function getRequiredIndustries(): array
    {
        return ['quarry'];
    }

    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static ?string $navigationLabel = 'Dispatch Management';

    protected static ?string $navigationGroup = 'Quarry';

    protected static ?int $navigationSort = 7;

    protected static ?string $slug = 'dispatch-management';

    public static function canAccess(): bool
    {
        return static::passesIndustryGate();
    }
}
