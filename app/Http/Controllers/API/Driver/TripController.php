<?php

namespace App\Http\Controllers\API\Driver;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Trip;
use App\Models\TripAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TripController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $driver = $this->getDriver($request);
        $this->syncLegacyTripsToAssignments($request, $driver);

        $assignments = TripAssignment::where('driver_id', $driver->id)
            ->latest()
            ->get();

        $tripIds = $assignments->pluck('trip_id')->all();
        $trips = Trip::whereIn('id', $tripIds)->get()->keyBy('id');

        $data = $assignments->map(function ($assignment) use ($trips) {
            $trip = $trips->get($assignment->trip_id);
            $tripPayload = $trip ? $trip->toArray() : null;

            if (is_array($tripPayload)) {
                $tripPayload['route_from'] = $tripPayload['origin'] ?? $tripPayload['route_from'] ?? null;
                $tripPayload['route_to'] = $tripPayload['destination'] ?? $tripPayload['route_to'] ?? null;
                $tripPayload['pickup_details'] = $tripPayload['origin'] ?? null;
                $tripPayload['dropoff_details'] = $tripPayload['destination'] ?? null;
                $tripPayload['delay_prediction'] = $this->predictDelay($tripPayload);
            }

            return [
                'assignment_id' => $assignment->id,
                'status' => $assignment->status,
                'assigned_at' => optional($assignment->assigned_at)->toISOString(),
                'accepted_at' => optional($assignment->accepted_at)->toISOString(),
                'trip' => $tripPayload,
            ];
        });

        return response()->json(['data' => $data]);
    }

    public function show(Request $request, Trip $trip): JsonResponse
    {
        $driver = $this->getDriver($request);
        $this->syncLegacyTripsToAssignments($request, $driver);

        $assignment = TripAssignment::where('driver_id', $driver->id)
            ->where('trip_id', $trip->id)
            ->firstOrFail();

        return response()->json([
            'assignment' => $assignment,
            'trip' => array_merge($trip->toArray(), [
                'route_from' => $trip->origin,
                'route_to' => $trip->destination,
                'pickup_details' => $trip->origin,
                'dropoff_details' => $trip->destination,
                'delay_prediction' => $this->predictDelay($trip->toArray()),
            ]),
        ]);
    }

    private function getDriver(Request $request): Driver
    {
        return Driver::where('user_id', $request->user()->id)->firstOrFail();
    }

    private function syncLegacyTripsToAssignments(Request $request, Driver $driver): void
    {
        $employee = $driver->employee; // real FK bridge, replaces the old email match

        if (! $employee) {
            return;
        }

        $tripIds = Trip::query()
            ->where('employee_id', $employee->id)
            ->when($driver->company_id, fn ($q) => $q->where('company_id', $driver->company_id))
            // Closed trips don't need a fresh "pending" assignment — without
            // this, a driver keeps seeing every already-Completed/Cancelled
            // trip surfaced as new, unaddressed work forever.
            ->whereNotIn('status', [\App\Enums\TripStatus::Completed, \App\Enums\TripStatus::Cancelled])
            ->pluck('id');

        if ($tripIds->isEmpty()) {
            return;
        }

        $existingTripIds = TripAssignment::query()
            ->where('driver_id', $driver->id)
            ->whereIn('trip_id', $tripIds)
            ->pluck('trip_id')
            ->all();

        $missingTripIds = array_values(array_diff($tripIds->all(), $existingTripIds));
        foreach ($missingTripIds as $tripId) {
            TripAssignment::create([
                'driver_id' => $driver->id,
                'trip_id' => $tripId,
                'status' => 'pending',
                'assigned_at' => now(),
            ]);
        }
    }

    private function predictDelay(array $trip): array
    {
        $expectedArrivalRaw = $trip['expected_arrival'] ?? null;
        if (! $expectedArrivalRaw) {
            return [
                'risk' => 'unknown',
                'message' => 'Expected arrival time unavailable.',
            ];
        }

        $expectedArrival = \Carbon\Carbon::parse($expectedArrivalRaw);
        $now = now();
        $minutesToArrival = $now->diffInMinutes($expectedArrival, false);

        if ($minutesToArrival < -15) {
            return [
                'risk' => 'high',
                'message' => 'Likely delayed by ' . abs($minutesToArrival) . ' min.',
            ];
        }

        if ($minutesToArrival <= 30) {
            return [
                'risk' => 'medium',
                'message' => 'Possible delay risk from traffic. ETA window is tight.',
            ];
        }

        return [
            'risk' => 'low',
            'message' => 'On schedule based on current ETA.',
        ];
    }
}
