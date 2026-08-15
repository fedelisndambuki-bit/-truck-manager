<?php

namespace App\Services\Quarry;

use App\Models\DispatchSetting;
use App\Models\Employee;
use App\Models\LoadingBay;
use App\Models\Truck;
use App\Models\Weighbridge;

/**
 * Real truck/driver fitness-checking and load-balancing recommendations for
 * Dispatch Planning & Scheduling — mirrors WeighbridgeAnalyticsService's
 * recommendWeighbridge() real-comparison approach (no fabricated AI/ML).
 *
 * Driver checks are limited to what Employee actually tracks (license_expiry).
 * Medical certificates, training records, and fatigue limits are not stored
 * anywhere in the system, so they are not checked here — surfacing a
 * fabricated pass/fail for data that doesn't exist would be dishonest.
 */
class DispatchSchedulingService
{
    protected function defaultDailyTripCap(?int $companyId): int
    {
        return $companyId ? (int) DispatchSetting::getSetting($companyId, 'daily_trip_cap_per_truck', 4) : 4;
    }

    /**
     * How many dispatches this truck has already been assigned to today,
     * matched by plate number since DispatchOrder stores vehicle_registration
     * as a plain string (trucks may belong to a third-party transport
     * company and never exist as a Truck record at all).
     */
    public function truckTripsToday(Truck $truck): int
    {
        return \App\Models\DispatchOrder::query()
            ->where('vehicle_registration', $truck->plate_number)
            ->whereDate('created_at', today())
            ->count();
    }

    public function driverTripsToday(Employee $employee): int
    {
        return \App\Models\DispatchOrder::query()
            ->where('driver_license_number', $employee->license_number)
            ->whereDate('created_at', today())
            ->count();
    }

    /**
     * Real pass/fail checks against actual Truck columns — no fabricated
     * telemetry. Keys map directly to what's shown in the UI.
     */
    public function truckFitnessCheck(Truck $truck, ?int $companyId = null): array
    {
        $cap = $this->defaultDailyTripCap($companyId ?? $truck->company_id);

        return [
            'available' => $truck->status?->value === 'Available',
            'insurance_valid' => (bool) $truck->insurance_expiry && now()->lt($truck->insurance_expiry),
            'road_license_valid' => (bool) $truck->road_license_expiry && now()->lt($truck->road_license_expiry),
            'not_overloaded' => $this->truckTripsToday($truck) < $cap,
        ];
    }

    /**
     * Real pass/fail checks against actual Employee columns only.
     */
    public function driverFitnessCheck(Employee $employee, ?int $companyId = null): array
    {
        $cap = $this->defaultDailyTripCap($companyId ?? $employee->company_id);

        return [
            'active' => $employee->status === 'Active',
            'license_valid' => (bool) $employee->license_expiry && now()->lt($employee->license_expiry),
            'not_overloaded' => $this->driverTripsToday($employee) < $cap,
        ];
    }

    public function truckPassesFitness(Truck $truck, ?int $companyId = null): bool
    {
        return ! in_array(false, $this->truckFitnessCheck($truck, $companyId), true);
    }

    public function driverPassesFitness(Employee $employee, ?int $companyId = null): bool
    {
        return ! in_array(false, $this->driverFitnessCheck($employee, $companyId), true);
    }

    /**
     * Recommends the least-loaded fit truck meeting the required capacity —
     * real load-balancing by today's trip count, not a fabricated optimizer.
     */
    public function recommendTruck(?int $companyId, float $requiredCapacity = 0): ?Truck
    {
        return Truck::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', 'Available')
            ->where('capacity_tons', '>=', $requiredCapacity)
            ->get()
            ->filter(fn (Truck $t) => $this->truckPassesFitness($t, $companyId))
            ->sortBy(fn (Truck $t) => $this->truckTripsToday($t))
            ->first();
    }

    public function recommendDriver(?int $companyId): ?Employee
    {
        return Employee::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', 'Active')
            ->get()
            ->filter(fn (Employee $e) => $this->driverPassesFitness($e, $companyId))
            ->sortBy(fn (Employee $e) => $this->driverTripsToday($e))
            ->first();
    }

    public function recommendLoadingBay(?int $companyId): ?LoadingBay
    {
        return LoadingBay::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', 'Available')
            ->get()
            ->sortBy(fn (LoadingBay $b) => $b->currentQueueCount())
            ->first();
    }

    public function recommendWeighbridge(?int $companyId): ?Weighbridge
    {
        return Weighbridge::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', 'Online')
            ->get()
            ->sortBy(fn (Weighbridge $w) => $w->currentQueueCount())
            ->first();
    }
}
