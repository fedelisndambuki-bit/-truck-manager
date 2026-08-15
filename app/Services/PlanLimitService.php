<?php

namespace App\Services;

use App\Models\AiLog;
use App\Models\Company;
use App\Models\Driver;
use App\Models\Truck;

class PlanLimitService
{
    public function getCompanyLimits(Company $company): array
    {
        $subscription = $company->currentSubscription;

        if ($subscription && in_array($subscription->status, ['active', 'trial'], true)) {
            return [
                'truck_limit' => (int) $subscription->truck_limit,
                'driver_limit' => (int) $subscription->driver_limit,
                'ai_limit' => (int) $subscription->ai_limit,
            ];
        }

        $plan = $company->plan;

        return [
            'truck_limit' => (int) ($plan?->truck_limit ?? 0),
            'driver_limit' => (int) ($plan?->driver_limit ?? 0),
            'ai_limit' => (int) ($plan?->ai_requests_limit ?? 0),
        ];
    }

    public function canCreateTruck(Company $company): bool
    {
        $limits = $this->getCompanyLimits($company);
        $limit = $limits['truck_limit'];

        if ($limit <= 0) {
            return true;
        }

        $count = Truck::where('company_id', $company->id)->count();

        return $count < $limit;
    }

    public function canCreateDriver(Company $company): bool
    {
        $limits = $this->getCompanyLimits($company);
        $limit = $limits['driver_limit'];

        if ($limit <= 0) {
            return true;
        }

        // driver_limit is meant to cap actual driver (mobile-app) accounts,
        // not HR employee headcount — it used to count Employee, which
        // over/under-counts once a company has employees who aren't drivers
        // or drivers who share an employee-less login.
        $count = Driver::where('company_id', $company->id)->count();

        return $count < $limit;
    }

    public function canUseAi(Company $company): bool
    {
        $limits = $this->getCompanyLimits($company);
        $limit = $limits['ai_limit'];

        if ($limit <= 0) {
            return true;
        }

        $used = AiLog::where('company_id', $company->id)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        return $used < $limit;
    }
}
