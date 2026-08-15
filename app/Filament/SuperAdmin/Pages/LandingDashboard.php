<?php

namespace App\Filament\SuperAdmin\Pages;

use Filament\Pages\Page;

class LandingDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static string $view = 'filament.super-admin.pages.landing-dashboard';

    protected static ?string $navigationGroup = 'Landing Page Manager';

    protected static ?string $title = 'Landing Dashboard';

    protected static ?int $navigationSort = -1;
}
