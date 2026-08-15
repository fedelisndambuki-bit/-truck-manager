<?php

namespace App\Filament\Clusters;

use App\Traits\HasIndustryAccess;
use Filament\Clusters\Cluster;

class BusinessIntelligence extends Cluster
{
    use HasIndustryAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction'];
    }

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static ?string $navigationLabel = 'Reports & Business Intelligence';

    protected static ?string $navigationGroup = 'Construction';

    protected static ?int $navigationSort = 17;

    protected static ?string $slug = 'business-intelligence';

    // Sidebar visibility is left to Cluster::shouldRegisterNavigation()
    // (true only if at least one clustered Resource/Page's own canAccess()
    // passes — which already combines the industry gate with the
    // bi.* permission check via HasModuleAccess). Only the direct-URL
    // industry gate is added here.
    public static function canAccess(): bool
    {
        return static::passesIndustryGate();
    }
}
