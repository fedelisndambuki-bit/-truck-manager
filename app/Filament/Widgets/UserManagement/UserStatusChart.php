<?php

namespace App\Filament\Widgets\UserManagement;

use App\Models\User;
use Filament\Widgets\ChartWidget;

class UserStatusChart extends ChartWidget
{
    protected static bool $isLazy = false;

    protected static ?string $heading = 'User Status Distribution';

    protected int | string | array $columnSpan = 1;

    protected static ?string $maxHeight = '500px';

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
            'maintainAspectRatio' => false,
            'aspectRatio' => 0.8,
        ];
    }

    protected function getData(): array
    {
        $user = auth()->user();
        $companyId = $user?->company_id;

        $query = User::query();
        if ($companyId && !$user->hasRole('Super Admin')) {
            $query->where('company_id', $companyId);
        }

        $active = (clone $query)->where('status', 'active')->count();
        $suspended = (clone $query)->where('status', 'suspended')->count();
        $dormant = (clone $query)->where('status', 'dormant')->count();
        $onLeave = (clone $query)->where('status', 'on_leave')->count();

        return [
            'datasets' => [
                [
                    'label' => 'Users',
                    'data' => [$active, $suspended, $dormant, $onLeave],
                    'backgroundColor' => [
                        '#10b981', // success (active)
                        '#ef4444', // danger (suspended)
                        '#f59e0b', // warning (dormant)
                        '#3b82f6', // info (on leave)
                    ],
                ],
            ],
            'labels' => ['Active', 'Suspended', 'Dormant', 'On Leave'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
