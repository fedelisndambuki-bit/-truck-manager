<?php

namespace App\Http\Controllers\API\Driver;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\DriverPod;
use App\Models\Trip;
use App\Enums\TripStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PodController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $driver = $this->getDriver($request);

        $query = DriverPod::query()
            ->where('driver_id', $driver->id)
            ->latest();

        if ($request->filled('trip_id')) {
            $query->where('trip_id', $request->integer('trip_id'));
        }

        $pods = $query->take(50)->get()->map(function (DriverPod $pod) {
            return [
                'id' => $pod->id,
                'trip_id' => $pod->trip_id,
                'status' => $pod->status ?? 'pending',
                'failure_reason' => $pod->failure_reason,
                'notes' => $pod->notes,
                'receiver_name' => $pod->receiver_name,
                'receiver_signature' => $pod->receiver_signature,
                'pickup_details' => $pod->pickup_details,
                'dropoff_details' => $pod->dropoff_details,
                'document_ocr_text' => $pod->document_ocr_text,
                'delivered_at' => optional($pod->delivered_at)->toISOString(),
                'image_url' => Storage::disk('public')->url($pod->image_path),
            ];
        })->values();

        return response()->json(['data' => $pods]);
    }

    public function store(Request $request): JsonResponse
    {
        $driver = $this->getDriver($request);

        $data = $request->validate([
            'trip_id' => 'required|integer|exists:trips,id',
            'notes' => 'nullable|string|max:1000',
            'receiver_name' => 'nullable|string|max:255',
            'receiver_signature' => 'nullable|string',
            'pickup_details' => 'nullable|string|max:1000',
            'dropoff_details' => 'nullable|string|max:1000',
            'document_ocr_text' => 'nullable|string|max:10000',
            'delivered_at' => 'nullable|date',
            'image' => 'required|file|image|max:5120',
        ]);

        // trip_id was only validated as `exists:trips,id` above — without an
        // ownership check here, any driver could POD-complete (and trigger
        // invoicing + commission payout for) any OTHER driver's trip just by
        // knowing/guessing its id.
        $trip = Trip::where('id', $data['trip_id'])
            ->where('employee_id', $driver->employee_id)
            ->firstOrFail();

        $path = $request->file('image')->store('driver/pods', 'public');

        $pod = DriverPod::create([
            'company_id' => $driver->company_id,
            'driver_id' => $driver->id,
            'trip_id' => $data['trip_id'],
            'image_path' => $path,
            'notes' => $data['notes'] ?? null,
            'receiver_name' => $data['receiver_name'] ?? null,
            'receiver_signature' => $data['receiver_signature'] ?? null,
            'pickup_details' => $data['pickup_details'] ?? null,
            'dropoff_details' => $data['dropoff_details'] ?? null,
            'document_ocr_text' => $data['document_ocr_text'] ?? null,
            'status' => 'pending',
            'delivered_at' => $data['delivered_at'] ?? now(),
        ]);

        // Auto-complete the trip
        $trip->status = TripStatus::Completed;
        $trip->actual_arrival = $pod->delivered_at ?? now();
        $trip->save();

        return response()->json([
            'message' => 'POD uploaded.',
            'pod' => $pod,
        ], 201);
    }

    private function getDriver(Request $request): Driver
    {
        return Driver::where('user_id', $request->user()->id)->firstOrFail();
    }
}
