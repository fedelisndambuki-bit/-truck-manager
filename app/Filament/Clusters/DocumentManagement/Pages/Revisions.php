<?php

namespace App\Filament\Clusters\DocumentManagement\Pages;

use App\Filament\Clusters\DocumentManagement;
use App\Models\DocumentVersion;
use App\Traits\HasModuleAccess;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class Revisions extends Page
{
    use HasModuleAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'documents.view';
    }

    protected static ?string $cluster = DocumentManagement::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationLabel = 'Revisions';

    protected static ?int $navigationSort = 17;

    protected static ?string $title = 'Revisions';

    protected static string $view = 'filament.clusters.document-management.pages.revisions';

    public function getRows(): array
    {
        $companyId = Auth::user()?->company_id;

        return DocumentVersion::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->with(['versionable', 'uploadedBy'])
            ->latest('created_at')
            ->limit(100)
            ->get()
            ->map(function (DocumentVersion $version) {
                $entity = $version->versionable;

                return [
                    'version' => $version,
                    'entity_label' => class_basename($version->versionable_type),
                    'entity_title' => $entity?->title ?? $entity?->subject ?? '—',
                ];
            })
            ->all();
    }
}
