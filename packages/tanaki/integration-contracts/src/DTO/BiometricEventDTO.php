<?php

namespace Tanaki\IntegrationContracts\DTO;

use Carbon\CarbonImmutable;

final readonly class BiometricEventDTO
{
    /**
     * @param  array<string, mixed>  $rawVendorPayload
     */
    public function __construct(
        public int $companyId,
        public string $deviceId,
        public string $employeeIdentifier,
        public string $eventType,
        public CarbonImmutable $verifiedAt,
        public array $rawVendorPayload = [],
    ) {
    }
}
