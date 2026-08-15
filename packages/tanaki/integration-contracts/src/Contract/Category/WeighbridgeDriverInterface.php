<?php

namespace Tanaki\IntegrationContracts\Contract\Category;

use Tanaki\IntegrationContracts\Contract\DeviceDriverInterface;
use Tanaki\IntegrationContracts\DTO\WeighbridgeReadingDTO;

interface WeighbridgeDriverInterface extends DeviceDriverInterface
{
    /**
     * @param  array<string, mixed>  $rawPayload  vendor-native payload — the driver adapts it into the standardized DTO
     */
    public function parseReading(array $rawPayload): WeighbridgeReadingDTO;
}
