<?php

namespace App\Filament\Clusters\BusinessIntelligence\Pages;

use App\Filament\Clusters\BusinessIntelligence;
use App\Traits\HasModuleAccess;
use Filament\Pages\Page;

class DocumentReports extends Page
{
    use HasModuleAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'bi.view';
    }

    protected static ?string $cluster = BusinessIntelligence::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Document Reports';

    protected static ?int $navigationSort = 12;

    protected static ?string $title = 'Document Reports';

    protected static string $view = 'filament.clusters.business-intelligence.pages.hub-page';

    public function getPageIntro(): string
    {
        return 'Document register, drawing register, RFI status, approvals, revisions and audit trail — all already available in Document Management.';
    }

    public function getPageIcon(): string
    {
        return '<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />';
    }

    public function getHubCards(): array
    {
        return [
            [
                'label' => 'Open Document Reports',
                'description' => 'Document Register, Drawing Register, RFI, Approval, Revision, Audit Trail, Expired Documents, Contract Register and Document Activity reports.',
                'icon' => 'heroicon-o-arrow-top-right-on-square',
                'url' => \App\Filament\Clusters\DocumentManagement\Pages\Reports::getUrl(),
            ],
        ];
    }
}
