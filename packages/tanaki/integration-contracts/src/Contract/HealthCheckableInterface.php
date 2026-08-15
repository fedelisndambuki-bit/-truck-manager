<?php

namespace Tanaki\IntegrationContracts\Contract;

use Tanaki\IntegrationContracts\DTO\DeviceHealthDTO;

interface HealthCheckableInterface
{
    /**
     * Fast, cheap check — safe to call frequently (e.g. hourly sweep).
     */
    public function healthCheck(): DeviceHealthDTO;
}
