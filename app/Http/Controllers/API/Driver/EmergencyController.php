<?php

namespace App\Http\Controllers\API\Driver;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\DriverEmergencyAlert;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmergencyController extends Controller
{
    public function sos(Request $request): JsonResponse
    {
        $driver = $this->getDriver($request);

        $data = $request->validate([
            // Unscoped exists:trips,id let a driver point this at another company's trip.
            'trip_id' => ['nullable', 'integer', Rule::exists('trips', 'id')->where('company_id', $driver->company_id)],
            'message' => 'nullable|string|max:2000',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $alert = DriverEmergencyAlert::create([
            'driver_id' => $driver->id,
            'trip_id' => $data['trip_id'] ?? null,
            'type' => 'sos',
            'status' => 'open',
            'message' => $data['message'] ?? 'SOS triggered from mobile app.',
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'triggered_at' => now(),
        ]);

        return response()->json([
            'message' => 'SOS alert sent to control room.',
            'data' => $alert,
        ], 201);
    }

    private function getDriver(Request $request): Driver
    {
        return Driver::where('user_id', $request->user()->id)->firstOrFail();
    }
}
