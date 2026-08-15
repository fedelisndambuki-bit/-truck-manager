<?php

namespace App\Http\Controllers\API\Driver;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiFeedbackController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $driver = $this->getDriver($request);

        return response()->json([
            'driver_id' => $driver->id,
            'message' => 'No AI feedback available yet.',
        ]);
    }

    private function getDriver(Request $request): Driver
    {
        return Driver::where('user_id', $request->user()->id)->firstOrFail();
    }
}
