<?php

namespace App\Filament\Widgets\UserManagement;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UserKPIOverview extends BaseWidget
{
    protected static bool $isLazy = false;

    protected int | string | array $columnSpan = 2;

    protected function getStats(): array
    {
        $user = auth()->user();
        $companyId = $user?->company_id;

        $userQuery = User::query();
        if ($companyId && !$user->hasRole('Super Admin')) {
            $userQuery->where('company_id', $companyId);
        }

        return [
            Stat::make('Active Users', $userQuery->clone()->where('status', 'active')->count())
                ->description('Total active users')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success'),
            Stat::make('Total Roles', Role::count())
                ->description('Defined system roles')
                ->descriptionIcon('heroicon-m-shield-check')
                ->color('info'),
            Stat::make('Total Permissions', Permission::count())
                ->description('System access points')
                ->descriptionIcon('heroicon-m-lock-closed')
                ->color('warning'),
        ];
    }
}
