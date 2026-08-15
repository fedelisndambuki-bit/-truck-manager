<?php

namespace Tanaki\IntegrationContracts\DTO;

use Tanaki\IntegrationContracts\Enum\DeviceStatus;
use Carbon\CarbonImmutable;

final readonly class DeviceHealthDTO
{
    public function __construct(
        public string $deviceId,
        public DeviceStatus $status,
        public bool $isOnline,
        public ?CarbonImmutable $lastSeenAt,
        public ?string $diagnosticMessage = null,
    ) {
    }
}
