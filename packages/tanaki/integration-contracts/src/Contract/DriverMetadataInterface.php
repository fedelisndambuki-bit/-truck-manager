<?php

namespace Tanaki\IntegrationContracts\Contract;

use Tanaki\IntegrationContracts\DTO\DriverMetadataDTO;

interface DriverMetadataInterface
{
    public function getMetadata(): DriverMetadataDTO;

    /**
     * @return string[] e.g. ['read', 'write', 'stream', 'health_check']
     */
    public function getCapabilities(): array;
}
