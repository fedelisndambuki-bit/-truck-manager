<?php

namespace App\Http\Controllers\API\Driver;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\DriverPreTripInspection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PreTripInspectionController extends Controller
{
    public function latest(Request $request): JsonResponse
    {
        $driver = $this->getDriver($request);

        $latest = DriverPreTripInspection::query()
            ->where('driver_id', $driver->id)
            ->latest('submitted_at')
            ->latest('id')
            ->first();

        return response()->json([
            'data' => $latest,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $driver = $this->getDriver($request);

        $data = $request->validate([
            // Unscoped exists:trips,id let a driver point this at another company's trip.
            'trip_id' => ['nullable', 'integer', Rule::exists('trips', 'id')->where('company_id', $driver->company_id)],
            'brakes' => 'required|boolean',
            'lights' => 'required|boolean',
            'tires' => 'required|boolean',
            'load_secured' => 'required|boolean',
            'notes' => 'nullable|string|max:2000',
        ]);

        $inspection = DriverPreTripInspection::create([
            'driver_id' => $driver->id,
            'trip_id' => $data['trip_id'] ?? null,
            'brakes' => $data['brakes'],
            'lights' => $data['lights'],
            'tires' => $data['tires'],
            'load_secured' => $data['load_secured'],
            'notes' => $data['notes'] ?? null,
            'submitted_at' => now(),
        ]);

        return response()->json([
            'message' => 'Pre-trip inspection submitted.',
            'data' => $inspection,
        ], 201);
    }

    private function getDriver(Request $request): Driver
    {
        return Driver::where('user_id', $request->user()->id)->firstOrFail();
    }
}
