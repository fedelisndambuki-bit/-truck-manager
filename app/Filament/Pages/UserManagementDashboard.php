<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\UserManagement\UserKPIOverview;
use App\Filament\Widgets\UserManagement\UserStatusChart;
use App\Filament\Widgets\UserManagement\RecentUserActivity;
use Filament\Pages\Page;

class UserManagementDashboard extends Page
{
    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        if ($user->hasRole('Super Admin')) return true;
        return $user->can('user-management');
    }

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static ?string $navigationGroup = 'User Management';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?string $title = 'User Management Dashboard';

    protected static string $view = 'filament.pages.user-management-dashboard';

    protected static ?int $navigationSort = 1;

    public function getColumns(): int | string | array
    {
        return 2;
    }

    public function getCustomHeaderWidgets(): array
    {
        return [
            UserKPIOverview::class,
            UserStatusChart::class,
        ];
    }

    public function getCustomFooterWidgets(): array
    {
        return [
            RecentUserActivity::class,
        ];
    }
}
