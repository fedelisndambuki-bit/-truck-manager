<?php

namespace Tanaki\IntegrationContracts\Contract\Category;

use Tanaki\IntegrationContracts\Contract\DeviceDriverInterface;
use Tanaki\IntegrationContracts\DTO\SensorReadingDTO;

interface IotSensorDriverInterface extends DeviceDriverInterface
{
    /**
     * @param  array<string, mixed>  $rawPayload
     */
    public function parseReading(array $rawPayload): SensorReadingDTO;
}
