<?php

namespace App\Filament\Clusters;

use App\Traits\HasIndustryAccess;
use Filament\Clusters\Cluster;

class ProjectFinance extends Cluster
{
    use HasIndustryAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction'];
    }

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Project Finance';

    protected static ?string $navigationGroup = 'Construction';

    protected static ?int $navigationSort = 15;

    protected static ?string $slug = 'project-finance';

    // Sidebar visibility is left to Cluster::shouldRegisterNavigation()
    // (true only if at least one clustered Resource/Page's own canAccess()
    // passes — which already combines the industry gate with the
    // project_finance.* permission check via HasModuleAccess). Only the
    // direct-URL industry gate is added here.
    public static function canAccess(): bool
    {
        return static::passesIndustryGate();
    }
}
