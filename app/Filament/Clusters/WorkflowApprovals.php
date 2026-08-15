<?php

namespace App\Filament\Clusters;

use App\Traits\HasIndustryAccess;
use Filament\Clusters\Cluster;

/**
 * Core Platform module — a generic, reusable no-code workflow/approval
 * engine used by every industry (Construction, Fleet, Quarry, ...) and every
 * business module (HR, Finance, Procurement, ...). Deliberately universal:
 * no getRequiredIndustries() override, matching the established pattern for
 * industry-agnostic modules (UserResource, RoleResource, NotificationSettingResource).
 */
class WorkflowApprovals extends Cluster
{
    use HasIndustryAccess;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path-rounded-square';

    protected static ?string $navigationLabel = 'Workflow & Approvals';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'workflow-approvals';

    public static function canAccess(): bool
    {
        return static::passesIndustryGate();
    }
}
