<?php

namespace App\Http\Controllers\API\Driver;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\TripAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    public function accept(Request $request, TripAssignment $assignment): JsonResponse
    {
        $driver = $this->getDriver($request);

        if ($assignment->driver_id !== $driver->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $assignment->update([
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);

        // Sync with trip status
        $assignment->trip?->update(['status' => 'Scheduled']);

        return response()->json(['message' => 'Assignment accepted.']);
    }

    public function decline(Request $request, TripAssignment $assignment): JsonResponse
    {
        $driver = $this->getDriver($request);

        if ($assignment->driver_id !== $driver->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $data = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $assignment->update([
            'status' => 'declined',
            'decline_reason' => $data['reason'],
        ]);

        return response()->json(['message' => 'Assignment declined.']);
    }

    private function getDriver(Request $request): Driver
    {
        return Driver::where('user_id', $request->user()->id)->firstOrFail();
    }
}
