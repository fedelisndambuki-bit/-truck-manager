<?php

namespace Tanaki\IntegrationContracts\Contract\Category;

use Tanaki\IntegrationContracts\Contract\DeviceDriverInterface;
use Tanaki\IntegrationContracts\DTO\GpsPositionDTO;

interface GpsTrackerDriverInterface extends DeviceDriverInterface
{
    /**
     * @param  array<string, mixed>  $rawPayload
     */
    public function parsePosition(array $rawPayload): GpsPositionDTO;
}
