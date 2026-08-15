<?php

namespace App\Filament\Resources\SiteResource\Pages;

use App\Filament\Resources\SiteResource;
use App\Filament\Widgets\SiteKpiWidget;
use Filament\Resources\Pages\ListRecords;

class ListSites extends ListRecords
{
    protected static string $resource = SiteResource::class;

    protected static string $view = 'filament.resources.site-resource.pages.list-sites';

    protected function getHeaderWidgets(): array
    {
        return [
            SiteKpiWidget::class,
        ];
    }

    /**
     * Filament's base page template auto-renders header widgets ABOVE the
     * whole page slot (before our custom Tanaki banner + table), with no way
     * to reposition that. We render getHeaderWidgets() ourselves further down
     * the custom blade view instead, so suppress the automatic render here to
     * avoid showing the KPI cards twice.
     */
    public function getVisibleHeaderWidgets(): array
    {
        return [];
    }
}
