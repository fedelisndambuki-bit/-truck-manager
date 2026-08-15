<?php

namespace App\Http\Controllers\API\Driver;

use App\Http\Controllers\Controller;
use App\Models\BrandSetting;
use App\Models\Driver;
use App\Models\Employee;
use App\Models\Truck;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $data['email'])->first();
        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        if (($user->status ?? 'active') !== 'active') {
            return response()->json(['message' => 'This user account is inactive. Contact your administrator.'], 403);
        }

        $driver = $this->resolveDriverForUser($user);
        if (! $driver) {
            return response()->json(['message' => 'Driver profile not found. Ask dispatch to link your account.'], 403);
        }

        if (($driver->status ?? 'active') !== 'active') {
            return response()->json(['message' => 'Driver profile is inactive. Contact your dispatcher.'], 403);
        }

        $token = $user->createToken('driver-app')->plainTextToken;
        $employee = $driver->employee;
        $employee?->loadMissing('truck');
        $brand = BrandSetting::query()->where('company_id', $driver->company_id)->first();
        $profilePayload = $this->buildProfilePayload($user, $driver, $employee, $brand);

        return response()->json([
            'token' => $token,
            'driver' => [
                'id' => $driver->id,
                'company_id' => $driver->company_id,
                'risk_score' => $driver->risk_score,
                'performance_score' => $driver->performance_score,
                'status' => $driver->status,
            ],
            'profile' => $profilePayload,
        ]);
    }

    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();
        $driver = $this->resolveDriverForUser($user);

        if (! $driver) {
            return response()->json(['message' => 'Driver profile not found. Ask dispatch to link your account.'], 403);
        }

        $employee = $driver->employee;
        $employee?->loadMissing('truck');
        $brand = BrandSetting::query()->where('company_id', $driver->company_id)->first();

        return response()->json([
            'data' => [
                'profile' => $this->buildProfilePayload($user, $driver, $employee, $brand),
            ],
        ]);
    }

    private function resolveDriverForUser(User $user): ?Driver
    {
        $driver = Driver::where('user_id', $user->id)->first();
        if ($driver) {
            return $driver;
        }

        // Bridge legacy Truck Manager "employees" records into mobile "drivers".
        $employee = Employee::query()
            ->whereNotNull('email')
            ->whereRaw('LOWER(email) = ?', [strtolower($user->email)])
            ->when($user->company_id, fn ($q) => $q->where('company_id', $user->company_id))
            ->first();

        if (! $employee) {
            return null;
        }

        $driverCompanyId = $user->company_id ?: $employee->company_id;
        if (! $driverCompanyId) {
            return null;
        }

        $normalizedStatus = strtolower((string) ($employee->status ?? 'active'));
        $driverStatus = $normalizedStatus === 'active' ? 'active' : 'inactive';

        return Driver::create([
            'company_id' => $driverCompanyId,
            'user_id' => $user->id,
            'employee_id' => $employee->id,
            'license_number' => $employee->license_number,
            'risk_score' => 0,
            'performance_score' => 0,
            'status' => $driverStatus,
        ]);
    }

    private function buildProfilePayload(User $user, Driver $driver, ?Employee $employee, ?BrandSetting $brand): array
    {
        $truck = $employee?->truck;

        return [
            'driver_name' => $employee?->full_name ?: $user->name,
            'driver_profile_url' => $this->publicAssetUrl($employee?->profile_photo),
            'company_name' => $brand?->company_name ?: config('app.name'),
            'company_profile_url' => $this->publicAssetUrl($brand?->logo_path ?: $brand?->icon_path),
            'assigned_trucks' => $truck ? [$this->truckPayload($truck)] : [],
            'driver_details' => [
                'email' => $employee?->email ?: $user->email,
                'phone' => $employee?->phone_number ?: $user->phone,
                'national_id' => $employee?->national_id,
                'license_number' => $driver->license_number ?: $employee?->license_number,
                'license_expiry' => $employee?->license_expiry?->format('Y-m-d'),
                'date_hired' => $employee?->date_hired?->format('Y-m-d'),
                'status' => $employee?->status ?: $driver->status,
                'position' => $employee?->position ?: 'Driver',
            ],
            'driver' => [
                'id' => $driver->id,
                'status' => $driver->status,
                'risk_score' => $driver->risk_score,
                'performance_score' => $driver->performance_score,
            ],
        ];
    }

    private function truckPayload(Truck $truck): array
    {
        return [
            'id' => $truck->id,
            'plate_number' => $truck->plate_number,
            'truck_type' => $truck->truck_type,
            'status' => $truck->status,
            'brand' => $truck->brand,
            'model' => $truck->model,
            'year' => $truck->year,
            'current_mileage' => $truck->current_mileage,
            'capacity_tons' => $truck->capacity_tons,
            'fuel_type' => $truck->fuel_type,
            'truck_image_url' => $this->publicAssetUrl($truck->truck_image),
            'last_service_date' => optional($truck->last_service_date)?->format('Y-m-d'),
        ];
    }

    private function publicAssetUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        $normalized = ltrim($path, '/');
        if (Str::startsWith($normalized, 'storage/')) {
            $normalized = Str::after($normalized, 'storage/');
        }

        $url = Storage::disk('public')->url($normalized);
        if (Str::startsWith($url, ['http://', 'https://'])) {
            return $url;
        }

        return url($url);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out.']);
    }
}
