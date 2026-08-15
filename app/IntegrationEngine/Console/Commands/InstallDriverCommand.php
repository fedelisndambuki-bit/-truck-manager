<?php

namespace App\IntegrationEngine\Console\Commands;

use App\Models\IntegratedDevice;
use Illuminate\Console\Command;
use Tanaki\IntegrationContracts\Contract\DeviceRegistryInterface;
use Tanaki\IntegrationContracts\Contract\DriverFactoryInterface;

class InstallDriverCommand extends Command
{
    protected $signature = 'integration-engine:driver:install
        {vendor : The vendor key of a discovered driver, e.g. generic-http}
        {name : A human-readable name for this device, e.g. "Main Gate Weighbridge"}
        {--company= : The company ID this device belongs to (required)}
        {--config={} : Connection config as a JSON string, e.g. {"extraction_site_id":1}}';

    protected $description = 'Install a discovered driver as a new integrated device for a company';

    public function handle(DriverFactoryInterface $factory, DeviceRegistryInterface $registry): int
    {
        $vendorKey = $this->argument('vendor');

        if (! $registry->isRegistered($vendorKey)) {
            $this->error("No driver is registered for vendor key [{$vendorKey}]. Run `php artisan integration-engine:driver:discover` first.");

            return self::FAILURE;
        }

        $companyId = $this->option('company');

        if (! $companyId) {
            $this->error('--company=<id> is required.');

            return self::FAILURE;
        }

        $config = json_decode((string) $this->option('config'), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('--config must be valid JSON.');

            return self::FAILURE;
        }

        $driver = $factory->make($vendorKey);
        $metadata = $driver->getMetadata();
        $diagnostics = $driver->runDiagnostics();

        $device = IntegratedDevice::create([
            'company_id' => $companyId,
            'name' => $this->argument('name'),
            'vendor_key' => $vendorKey,
            'category' => $metadata->category->value,
            'protocol' => $diagnostics->protocol,
            'connection_config' => $config,
            'status' => 'active',
        ]);

        $this->info("Installed device [{$device->id}] \"{$device->name}\" using driver [{$vendorKey}] for company [{$companyId}].");

        return self::SUCCESS;
    }
}
