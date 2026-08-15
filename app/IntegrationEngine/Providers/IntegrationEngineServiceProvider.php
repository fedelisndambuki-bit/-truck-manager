<?php

namespace App\IntegrationEngine\Providers;

use App\IntegrationEngine\Console\Commands\DeviceHealthCheckCommand;
use App\IntegrationEngine\Console\Commands\DiscoverDriversCommand;
use App\IntegrationEngine\Console\Commands\InstallDriverCommand;
use App\IntegrationEngine\Console\Commands\ListDriversCommand;
use App\IntegrationEngine\Console\Commands\RemoveDriverCommand;
use App\IntegrationEngine\Discovery\DriverDiscoveryService;
use App\IntegrationEngine\Drivers\AbstractDeviceDriver;
use App\IntegrationEngine\Factory\DeviceDriverFactory;
use App\IntegrationEngine\Registry\DeviceRegistry;
use Illuminate\Support\ServiceProvider;
use Tanaki\IntegrationContracts\Contract\DeviceRegistryInterface;
use Tanaki\IntegrationContracts\Contract\DriverFactoryInterface;
use Tanaki\IntegrationContracts\Contract\ProtocolAdapterInterface;
use Tanaki\IntegrationContracts\Enum\DeviceCategory;

class IntegrationEngineServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DeviceRegistryInterface::class, DeviceRegistry::class);
        $this->app->singleton(DriverFactoryInterface::class, DeviceDriverFactory::class);
        $this->app->singleton(DriverDiscoveryService::class);
    }

    public function boot(): void
    {
        $this->mergeConfigFrom(config_path('integration-engine.php'), 'integration-engine');

        if ($this->app->runningInConsole()) {
            $this->commands([
                DiscoverDriversCommand::class,
                ListDriversCommand::class,
                InstallDriverCommand::class,
                RemoveDriverCommand::class,
                DeviceHealthCheckCommand::class,
            ]);
        }

        $discovered = $this->resolveDiscoveredDrivers();

        /** @var DeviceRegistryInterface $registry */
        $registry = $this->app->make(DeviceRegistryInterface::class);

        foreach ($discovered as $vendorKey => $driver) {
            $registry->register($vendorKey, $driver['class'], DeviceCategory::from($driver['category']));
            $this->bindProtocolAdapterFor($driver['class']);
        }
    }

    /**
     * @return array<string, array{class: class-string, category: string, version: string}>
     */
    protected function resolveDiscoveredDrivers(): array
    {
        /** @var DriverDiscoveryService $discovery */
        $discovery = $this->app->make(DriverDiscoveryService::class);

        if (config('integration-engine.discovery.use_cache', true)) {
            $cached = $discovery->loadCached();

            if ($cached !== null) {
                return $cached;
            }
        }

        return $discovery->scan();
    }

    /**
     * ProtocolAdapterInterface is never bound to one concrete class globally
     * (multiple protocols implement it) — each discovered driver declares
     * which adapter it needs via its own protocolAdapterClass() static
     * method, and we register that as a contextual binding scoped to just
     * that driver class.
     */
    protected function bindProtocolAdapterFor(string $driverClass): void
    {
        if (! is_subclass_of($driverClass, AbstractDeviceDriver::class)) {
            return;
        }

        $this->app->when($driverClass)
            ->needs(ProtocolAdapterInterface::class)
            ->give(fn ($app) => $app->make($driverClass::protocolAdapterClass()));
    }
}
