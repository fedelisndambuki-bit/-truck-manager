<?php

namespace App\IntegrationEngine\Console\Commands;

use Illuminate\Console\Command;
use Tanaki\IntegrationContracts\Contract\DeviceRegistryInterface;
use Tanaki\IntegrationContracts\Contract\DriverFactoryInterface;

class ListDriversCommand extends Command
{
    protected $signature = 'integration-engine:driver:list';

    protected $description = 'List every driver currently registered, with its metadata and capabilities';

    public function handle(DeviceRegistryInterface $registry, DriverFactoryInterface $factory): int
    {
        $all = $registry->all();

        if (empty($all)) {
            $this->warn('No drivers registered. Run `php artisan integration-engine:driver:discover` first.');

            return self::SUCCESS;
        }

        $rows = array_map(function (array $entry) use ($factory) {
            $driver = $factory->make($entry['vendor_key']);
            $metadata = $driver->getMetadata();

            return [
                $entry['vendor_key'],
                $metadata->displayName,
                $metadata->category->label(),
                $metadata->version,
                implode(', ', $metadata->capabilities),
            ];
        }, $all);

        $this->table(['Vendor Key', 'Name', 'Category', 'Version', 'Capabilities'], $rows);

        return self::SUCCESS;
    }
}
