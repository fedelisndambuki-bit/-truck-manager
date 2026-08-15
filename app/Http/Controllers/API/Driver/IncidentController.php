<?php

namespace App\Http\Controllers\API\Driver;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\DriverIncident;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class IncidentController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $driver = $this->getDriver($request);

        $data = $request->validate([
            // Unscoped exists:trips,id let a driver point this at another company's trip.
            'trip_id' => ['nullable', 'integer', Rule::exists('trips', 'id')->where('company_id', $driver->company_id)],
            'type' => 'required|string|max:255',
            'severity' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:2000',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'reported_at' => 'nullable|date',
            'photo' => 'nullable|file|image|max:5120',
        ]);

        $photoPath = $request->hasFile('photo')
            ? $request->file('photo')->store('driver/incidents', 'public')
            : null;

        $incident = DriverIncident::create([
            'driver_id' => $driver->id,
            'trip_id' => $data['trip_id'] ?? null,
            'type' => $data['type'],
            'severity' => $data['severity'] ?? null,
            'description' => $data['description'] ?? null,
            'photo_path' => $photoPath,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'reported_at' => $data['reported_at'] ?? now(),
        ]);

        return response()->json([
            'message' => 'Incident reported.',
            'incident' => $incident,
        ], 201);
    }

    private function getDriver(Request $request): Driver
    {
        return Driver::where('user_id', $request->user()->id)->firstOrFail();
    }
}
