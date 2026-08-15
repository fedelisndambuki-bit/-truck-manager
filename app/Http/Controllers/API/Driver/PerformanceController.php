<?php

namespace App\Http\Controllers\API\Driver;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PerformanceController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $driver = $this->getDriver($request);

        return response()->json([
            'risk_score' => $driver->risk_score,
            'performance_score' => $driver->performance_score,
        ]);
    }

    private function getDriver(Request $request): Driver
    {
        return Driver::where('user_id', $request->user()->id)->firstOrFail();
    }
}
