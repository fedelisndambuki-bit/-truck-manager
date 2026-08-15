<?php

namespace App\Http\Controllers\API\Driver;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\DriverFuelReceipt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class FuelReceiptController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $driver = $this->getDriver($request);

        $query = DriverFuelReceipt::query()
            ->where('driver_id', $driver->id)
            ->latest();

        if ($request->filled('trip_id')) {
            $query->where('trip_id', $request->integer('trip_id'));
        }

        $receipts = $query->take(50)->get()->map(function (DriverFuelReceipt $receipt) {
            return [
                'id' => $receipt->id,
                'trip_id' => $receipt->trip_id,
                'vendor' => $receipt->vendor,
                'litres' => $receipt->litres,
                'amount' => $receipt->amount,
                'status' => $receipt->status ?? 'pending',
                'failure_reason' => $receipt->failure_reason,
                'captured_at' => optional($receipt->captured_at)->toISOString(),
                'receipt_url' => Storage::disk('public')->url($receipt->receipt_path),
            ];
        })->values();

        return response()->json(['data' => $receipts]);
    }

    public function store(Request $request): JsonResponse
    {
        $driver = $this->getDriver($request);

        $data = $request->validate([
            // Unscoped exists:trips,id let a driver point this at another
            // company's trip — the reviewing resource then corrupts its
            // cost allocation trying to render a foreign-tenant trip.
            'trip_id' => ['nullable', 'integer', Rule::exists('trips', 'id')->where('company_id', $driver->company_id)],
            'vendor' => 'nullable|string|max:255',
            'litres' => 'nullable|numeric',
            'amount' => 'nullable|numeric',
            'captured_at' => 'nullable|date',
            'receipt' => 'required|file|image|max:5120',
        ]);

        $path = $request->file('receipt')->store('driver/receipts', 'public');

        $receipt = DriverFuelReceipt::create([
            'driver_id' => $driver->id,
            'trip_id' => $data['trip_id'] ?? null,
            'vendor' => $data['vendor'] ?? null,
            'litres' => $data['litres'] ?? null,
            'amount' => $data['amount'] ?? null,
            'receipt_path' => $path,
            'status' => 'pending',
            'captured_at' => $data['captured_at'] ?? now(),
        ]);

        return response()->json([
            'message' => 'Receipt uploaded.',
            'receipt' => $receipt,
        ], 201);
    }

    private function getDriver(Request $request): Driver
    {
        return Driver::where('user_id', $request->user()->id)->firstOrFail();
    }
}
