<?php

namespace Tanaki\IntegrationContracts\Contract;

interface DriverFactoryInterface
{
    /**
     * Resolves and constructs the driver for a given vendor key out of the
     * container (so the driver's own constructor dependencies — its
     * ProtocolAdapter, config, etc — are still injected normally).
     */
    public function make(string $vendorKey): DeviceDriverInterface;
}
