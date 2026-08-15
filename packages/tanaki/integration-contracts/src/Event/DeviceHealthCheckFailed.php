<?php

namespace Tanaki\IntegrationContracts\Event;

use Tanaki\IntegrationContracts\DTO\DeviceHealthDTO;

/**
 * A plain PHP value object — see WeighbridgeReadingCaptured for why the
 * Laravel event traits were removed (ADR-0006).
 */
final readonly class DeviceHealthCheckFailed
{
    public function __construct(public string $vendorKey, public DeviceHealthDTO $health)
    {
    }
}
