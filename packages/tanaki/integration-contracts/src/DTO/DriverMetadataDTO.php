<?php

namespace Tanaki\IntegrationContracts\DTO;

use Tanaki\IntegrationContracts\Enum\DeviceCategory;

final readonly class DriverMetadataDTO
{
    /**
     * @param  string[]  $capabilities  e.g. ['read', 'write', 'stream', 'health_check']
     */
    public function __construct(
        public string $vendorKey,
        public string $displayName,
        public DeviceCategory $category,
        public string $version,
        public array $capabilities,
        public ?string $description = null,
    ) {
    }
}
