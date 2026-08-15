<?php

namespace App\Filament\Clusters;

use App\Traits\HasIndustryAccess;
use Filament\Clusters\Cluster;

class DocumentManagement extends Cluster
{
    use HasIndustryAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction'];
    }

    protected static ?string $navigationIcon = 'heroicon-o-folder-open';

    protected static ?string $navigationLabel = 'Document Management';

    protected static ?string $navigationGroup = 'Construction';

    protected static ?int $navigationSort = 16;

    protected static ?string $slug = 'document-management';

    // Sidebar visibility is left to Cluster::shouldRegisterNavigation()
    // (true only if at least one clustered Resource/Page's own canAccess()
    // passes — which already combines the industry gate with the
    // documents.* permission check via HasModuleAccess). Only the
    // direct-URL industry gate is added here.
    public static function canAccess(): bool
    {
        return static::passesIndustryGate();
    }
}
