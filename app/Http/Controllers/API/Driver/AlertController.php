<?php

namespace App\Http\Controllers\API\Driver;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $driver = $this->getDriver($request);
        $employee = $this->getEmployee($request, $driver);
        $truck = $employee?->truck;
        $alerts = [];

        $nextMaintenance = $truck?->maintenances()
            ->where('status', 'Pending')
            ->whereNotNull('next_maintenance_date')
            ->orderBy('next_maintenance_date')
            ->first();

        if ($nextMaintenance) {
            $upcomingService = \Carbon\Carbon::parse($nextMaintenance->next_maintenance_date);
            $alerts[] = [
                'type' => 'maintenance',
                'title' => 'Upcoming Service Date',
                'message' => 'Next service due on ' . $upcomingService->format('Y-m-d'),
                'severity' => now()->diffInDays($upcomingService, false) <= 7 ? 'warning' : 'info',
                'meta' => [
                    'next_service_date' => $upcomingService->format('Y-m-d'),
                ],
            ];
        } else {
            $lastServiceDate = $truck?->last_service_date ? \Carbon\Carbon::parse($truck->last_service_date) : null;
            if ($lastServiceDate) {
                $upcomingService = $lastServiceDate->copy()->addDays(30);
                $alerts[] = [
                    'type' => 'maintenance',
                    'title' => 'Upcoming Service Date',
                    'message' => 'Suggested next service on ' . $upcomingService->format('Y-m-d'),
                    'severity' => 'info',
                    'meta' => [
                        'next_service_date' => $upcomingService->format('Y-m-d'),
                    ],
                ];
            } else {
                $alerts[] = [
                    'type' => 'maintenance',
                    'title' => 'Upcoming Service Date',
                    'message' => 'No recent service date found. Schedule maintenance check.',
                    'severity' => 'warning',
                ];
            }
        }

        $alerts[] = [
            'type' => 'maintenance',
            'title' => 'Tire Pressure Alerts',
            'message' => 'Check tire pressure before departure and at major stops.',
            'severity' => 'info',
        ];

        $status = $truck?->status;
        $statusValue = $status instanceof \App\Enums\TruckStatus ? $status->value : (string) $status;
        $engineWarning = strtolower($statusValue ?? '');
        
        $alerts[] = [
            'type' => 'maintenance',
            'title' => 'Engine Warning',
            'message' => str_contains($engineWarning, 'maintenance')
                ? 'Truck is marked for maintenance. Inspect engine before trip.'
                : 'No critical engine warning reported.',
            'severity' => str_contains($engineWarning, 'maintenance') ? 'danger' : 'success',
        ];

        $alerts[] = [
            'type' => 'maintenance',
            'title' => 'Maintenance Checklist Before Trip',
            'message' => 'Brakes, lights, tires, and load security must be confirmed in pre-trip inspection.',
            'severity' => 'info',
        ];

        $hour = now()->hour;
        if ($hour >= 22 || $hour <= 4) {
            $alerts[] = [
                'type' => 'safety',
                'title' => 'Fatigue Alert Reminder',
                'message' => 'You are driving during high-fatigue hours. Take rest breaks.',
                'severity' => 'warning',
            ];
        }

        // Add Announcements
        $announcements = \App\Models\Announcement::where('is_active', true)
            ->where(function($q) use ($driver) {
                $q->whereNull('target_company_id')
                  ->orWhere('target_company_id', $driver->company_id);
            })
            ->where(function($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->latest()
            ->get();

        foreach ($announcements as $ann) {
            $alerts[] = [
                'type' => 'announcement',
                'title' => $ann->title,
                'message' => $ann->message,
                'severity' => 'info',
                'created_at' => $ann->created_at->toISOString(),
            ];
        }

        return response()->json([
            'driver_id' => $driver->id,
            'alerts' => $alerts,
        ]);
    }

    private function getDriver(Request $request): Driver
    {
        return Driver::where('user_id', $request->user()->id)->firstOrFail();
    }

    private function getEmployee(Request $request, Driver $driver): ?Employee
    {
        $employee = $driver->employee;
        $employee?->load('truck');

        return $employee;
    }
}
