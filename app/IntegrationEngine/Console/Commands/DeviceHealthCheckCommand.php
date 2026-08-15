<?php

namespace App\IntegrationEngine\Console\Commands;

use App\Models\IntegratedDevice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Tanaki\IntegrationContracts\Contract\DriverFactoryInterface;
use Tanaki\IntegrationContracts\Event\DeviceHealthCheckFailed;

class DeviceHealthCheckCommand extends Command
{
    protected $signature = 'integration-engine:device:health-check';

    protected $description = 'Sweep active integrated devices, run each driver health check, and fire device-health events on failure';

    public function handle(DriverFactoryInterface $factory): int
    {
        $devices = IntegratedDevice::withoutGlobalScope('company')->where('status', '!=', 'inactive')->get();
        $checked = 0;

        foreach ($devices as $device) {
            try {
                $driver = $factory->make($device->vendor_key);

                if ($device->connection_config) {
                    $driver->connect($device->connection_config);
                }

                $health = $driver->healthCheck();

                $device->update([
                    'health_status' => $health->status->value,
                    'last_health_check_at' => now(),
                    'last_seen_at' => $health->lastSeenAt ?? $device->last_seen_at,
                    'status' => $health->isOnline ? 'active' : 'offline',
                ]);

                if (! $health->isOnline) {
                    event(new DeviceHealthCheckFailed($device->vendor_key, $health));
                }

                $checked++;
            } catch (\Throwable $e) {
                Log::channel('integration-engine')->error("Health check failed for device [{$device->id}]", [
                    'device' => $device->name,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Health-checked {$checked} device(s).");

        return self::SUCCESS;
    }
}
