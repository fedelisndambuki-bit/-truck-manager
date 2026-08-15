<?php

namespace App\Filament\Clusters\BusinessIntelligence\Pages;

use App\Filament\Clusters\BusinessIntelligence;
use App\Traits\HasModuleAccess;
use Filament\Pages\Page;

class SubcontractorReports extends Page
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

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Subcontractor Reports';

    protected static ?int $navigationSort = 9;

    protected static ?string $title = 'Subcontractor Reports';

    protected static string $view = 'filament.clusters.business-intelligence.pages.hub-page';

    public function getPageIntro(): string
    {
        return 'Contract performance, payments, compliance, ratings, work progress and variations — all already available in Subcontractor Management.';
    }

    public function getPageIcon(): string
    {
        return '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21" />';
    }

    public function getHubCards(): array
    {
        return [
            [
                'label' => 'Open Subcontractor Reports',
                'description' => 'Register, Contract Summary, Performance, Compliance, Payment, Variation, Scope Progress, Retention, Work Order and Project Subcontractor reports.',
                'icon' => 'heroicon-o-arrow-top-right-on-square',
                'url' => \App\Filament\Pages\SubcontractorReports::getUrl(),
            ],
        ];
    }
}
