<?php

namespace App\IntegrationEngine\Factory;

use Illuminate\Contracts\Container\Container;
use Tanaki\IntegrationContracts\Contract\DeviceDriverInterface;
use Tanaki\IntegrationContracts\Contract\DeviceRegistryInterface;
use Tanaki\IntegrationContracts\Contract\DriverFactoryInterface;

class DeviceDriverFactory implements DriverFactoryInterface
{
    public function __construct(
        protected DeviceRegistryInterface $registry,
        protected Container $container,
    ) {}

    public function make(string $vendorKey): DeviceDriverInterface
    {
        $class = $this->registry->driverClassFor($vendorKey);

        // Resolved out of the container (not `new $class`) so the driver's
        // own constructor dependencies — its ProtocolAdapterInterface,
        // config, etc — are still injected normally.
        return $this->container->make($class);
    }
}
