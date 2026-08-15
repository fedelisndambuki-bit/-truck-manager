<?php

namespace App\Filament\Widgets;

use App\Models\LeaveRequest;
use App\Models\Staff;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class StaffKpiWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $companyId = Auth::user()?->company_id;
        $staff = Staff::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId));

        $total = (clone $staff)->count();
        $active = (clone $staff)->where('status', 'Active')->count();
        $onLeave = (clone $staff)->where('status', 'On Leave')->count();
        $probation = (clone $staff)->where('employment_type', 'Probation')->count();
        $permanent = (clone $staff)->where('employment_type', 'Permanent')->count();
        $contract = (clone $staff)->where('employment_type', 'Contract')->count();
        $casual = (clone $staff)->where('employment_type', 'Casual')->count();

        $pendingLeave = Schema::hasTable('leave_requests')
            ? LeaveRequest::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->where('status', 'Pending')
                ->count()
            : 0;

        return [
            Stat::make('Total Staff', (string) $total)->color('info'),
            Stat::make('Active', (string) $active)->color('success'),
            Stat::make('On Leave', (string) $onLeave)->color($onLeave > 0 ? 'warning' : 'success'),
            Stat::make('Permanent', (string) $permanent)->color('success'),
            Stat::make('Contract', (string) $contract)->color('info'),
            Stat::make('Casual', (string) $casual)->color('gray'),
            Stat::make('On Probation', (string) $probation)->color($probation > 0 ? 'warning' : 'success'),
            Stat::make('Pending Leave Requests', (string) $pendingLeave)->color($pendingLeave > 0 ? 'danger' : 'success'),
        ];
    }
}
