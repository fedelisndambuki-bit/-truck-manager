<?php

namespace App\Filament\Clusters\BusinessIntelligence\Pages;

use App\Filament\Clusters\BusinessIntelligence;
use App\Traits\HasModuleAccess;
use Filament\Pages\Page;

class SafetyReports extends Page
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

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'Safety Reports';

    protected static ?int $navigationSort = 10;

    protected static ?string $title = 'Safety Reports';

    protected static string $view = 'filament.clusters.business-intelligence.pages.hub-page';

    public function getPageIntro(): string
    {
        return 'Incidents, near misses, hazards, risk assessments, PPE compliance, inspections, training and safety score — all already available in Safety & Incident Management.';
    }

    public function getPageIcon(): string
    {
        return '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.286z" />';
    }

    public function getHubCards(): array
    {
        return [
            [
                'label' => 'Open Safety Reports',
                'description' => 'Incident, Near Miss, Hazard Register, Risk Assessment, Inspection, Toolbox Talk, Safety Meeting, PPE, Medical, Compliance and Executive Safety Dashboard.',
                'icon' => 'heroicon-o-arrow-top-right-on-square',
                'url' => \App\Filament\Pages\SafetyReports::getUrl(),
            ],
        ];
    }
}
