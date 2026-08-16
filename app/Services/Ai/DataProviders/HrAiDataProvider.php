<?php

namespace App\Services\Ai\DataProviders;

use App\Contracts\AiDataProviderInterface;
use App\Enums\CompanyIndustry;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\PayrollPeriod;
use App\Models\Staff;
use App\Services\Ai\AiComparisonEngine;

/**
 * HR/payroll — confirmed construction+quarry only (Staff/Attendance/
 * LeaveRequest/PayrollPeriod don't exist for the trucking industry, which
 * has its own separate Employee/Driver model instead). Gated behind
 * 'hr.payroll_access', matching PayrollPeriodResource's own gate, so a user
 * without HR/payroll permission is denied rather than silently answered.
 */
class HrAiDataProvider implements AiDataProviderInterface
{
    public function __construct(protected AiComparisonEngine $comparisons)
    {
    }

    public function key(): string
    {
        return 'hr';
    }

    public function label(): string
    {
        return 'HR / Payroll';
    }

    public function requiredPermission(): ?string
    {
        return 'hr.payroll_access';
    }

    public function appliesToIndustry(?string $industry): bool
    {
        return in_array($industry, [CompanyIndustry::Construction->value, CompanyIndustry::Quarry->value, CompanyIndustry::MultiOperation->value], true);
    }

    public function summary(int $companyId, ?array $period = null): array
    {
        $today = Attendance::where('company_id', $companyId)->whereDate('date', now()->toDateString())->get();
        $recentPayrolls = PayrollPeriod::where('company_id', $companyId)->latest('created_at')->take(2)->get();
        $latestPayroll = $recentPayrolls->first();
        $priorPayroll = $recentPayrolls->get(1);

        return [
            'total_staff' => Staff::where('company_id', $companyId)->where('status', 'Active')->count(),
            'attendance_today_present' => $today->where('status', 'Present')->count(),
            'attendance_today_total' => $today->count(),
            'pending_leave_requests' => LeaveRequest::where('company_id', $companyId)->where('status', 'Pending')->count(),
            'latest_payroll_period' => $latestPayroll ? [
                'status' => $latestPayroll->status,
                'total_gross' => (float) $latestPayroll->total_gross,
                'total_deductions' => (float) $latestPayroll->total_deductions,
                'total_net' => (float) $latestPayroll->total_net,
            ] : null,
            'payroll_cost_comparison' => $priorPayroll ? $this->comparisons->compare(
                'payroll_gross_cost',
                (float) $latestPayroll->total_gross,
                (float) $priorPayroll->total_gross,
                'KES',
                'latest payroll period',
                'previous payroll period'
            ) : null,
        ];
    }

    public function tools(): array
    {
        return [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_hr_summary',
                    'description' => 'Get staff count, today\'s attendance, pending leave requests, and the latest payroll period totals (gross/deductions/net pay, compared to the previous payroll period). Requires HR/payroll access.',
                    'parameters' => ['type' => 'object', 'properties' => new \stdClass(), 'required' => []],
                ],
            ],
        ];
    }

    public function callTool(string $name, array $arguments, int $companyId): array
    {
        return $this->summary($companyId);
    }
}
