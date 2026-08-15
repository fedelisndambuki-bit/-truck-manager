<?php

namespace App\IntegrationEngine\Console\Commands;

use App\IntegrationEngine\Discovery\DriverDiscoveryService;
use Illuminate\Console\Command;

class DiscoverDriversCommand extends Command
{
    protected $signature = 'integration-engine:driver:discover';

    protected $description = 'Scan app/IntegrationEngine/Drivers for #[IntegrationDriver]-tagged classes and cache the result';

    public function handle(DriverDiscoveryService $discovery): int
    {
        $discovered = $discovery->cache();

        $this->info(count($discovered).' driver(s) discovered and cached.');

        foreach ($discovered as $vendorKey => $driver) {
            $this->line(" - {$vendorKey} ({$driver['category']}) v{$driver['version']} -> {$driver['class']}");
        }

        return self::SUCCESS;
    }
}
