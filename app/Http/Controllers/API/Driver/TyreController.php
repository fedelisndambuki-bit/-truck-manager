<?php

namespace App\Http\Controllers\API\Driver;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Truck;
use App\Models\Tyre;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TyreController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $driver = Driver::where('user_id', $user->id)->first();
        $employee = $driver?->employee;

        $trucks = Truck::where('company_id', $user->company_id)
            ->when($employee && $employee->truck_id, function($q) use ($employee) {
                return $q->where('id', $employee->truck_id);
            })
            ->get()
            ->map(function ($truck) {
                return [
                    'id' => $truck->id,
                    'plate_number' => $truck->plate_number,
                    'brand' => $truck->brand,
                    'model' => $truck->model,
                    'axle_config' => $truck->axle_config,
                    'tyre_count' => $truck->tyre_count,
                    'installed_tyres' => Tyre::where('truck_id', $truck->id)
                        ->where('status', 'Active')
                        ->get(['id', 'serial_number', 'brand', 'position', 'tread_depth', 'size', 'type', 'pressure', 'retread_count', 'purchase_date', 'purchase_cost'])
                ];
            });

        $availableTyres = Tyre::where('company_id', $user->company_id)
            ->where('status', 'Spare')
            ->get(['id', 'serial_number', 'brand', 'size', 'tread_depth', 'type']);

        return response()->json([
            'trucks' => $trucks,
            'available_tyres' => $availableTyres,
        ]);
    }

    public function install(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tyre_id' => 'required|exists:tyres,id',
            'truck_id' => 'required|exists:trucks,id',
            'position' => 'required|string',
        ]);

        $user = $request->user();
        $tyre = Tyre::where('id', $data['tyre_id'])
            ->where('company_id', $user->company_id)
            ->firstOrFail();

        // truck_id is only validated as `exists:trucks,id` above — without
        // this, a truck belonging to another company could be targeted,
        // both corrupting that company's tyre at this position below and
        // attaching this company's own tyre onto a truck it doesn't own.
        $truck = Truck::where('id', $data['truck_id'])
            ->where('company_id', $user->company_id)
            ->firstOrFail();

        if ($tyre->status !== 'Spare') {
            return response()->json(['message' => 'Tyre is not available for installation.'], 422);
        }

        // Remove existing tyre at this position if any
        Tyre::where('truck_id', $truck->id)
            ->where('company_id', $user->company_id)
            ->where('position', $data['position'])
            ->update([
                'truck_id' => null,
                'position' => null,
                'status' => 'Spare',
                'installation_date' => null,
                'removal_date' => now(),
            ]);

        $tyre->update([
            'truck_id' => $truck->id,
            'position' => $data['position'],
            'status' => 'Active',
            'installation_date' => now(),
            'removal_date' => null,
        ]);

        return response()->json(['message' => 'Tyre installed successfully.']);
    }

    public function remove(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tyre_id' => 'required|exists:tyres,id',
        ]);

        $user = $request->user();
        $tyre = Tyre::where('id', $data['tyre_id'])
            ->where('company_id', $user->company_id)
            ->firstOrFail();

        $tyre->update([
            'truck_id' => null,
            'position' => null,
            'status' => 'Spare',
            'installation_date' => null,
            'removal_date' => now(),
        ]);

        return response()->json(['message' => 'Tyre removed successfully.']);
    }

    public function discard(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tyre_id' => 'required|exists:tyres,id',
        ]);

        $user = $request->user();
        $tyre = Tyre::where('id', $data['tyre_id'])
            ->where('company_id', $user->company_id)
            ->firstOrFail();

        $tyre->update([
            'truck_id' => null,
            'position' => null,
            'status' => 'Discarded',
            'installation_date' => null,
            'removal_date' => now(),
        ]);

        return response()->json(['message' => 'Tyre marked as scrapped.']);
    }
}
