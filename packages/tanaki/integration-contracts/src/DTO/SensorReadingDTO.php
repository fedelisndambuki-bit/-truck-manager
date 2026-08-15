<?php

namespace Tanaki\IntegrationContracts\DTO;

use Carbon\CarbonImmutable;

final readonly class SensorReadingDTO
{
    /**
     * @param  array<string, mixed>  $rawVendorPayload
     */
    public function __construct(
        public int $companyId,
        public string $deviceId,
        public string $sensorType,
        public float $value,
        public string $unit,
        public CarbonImmutable $readAt,
        public array $rawVendorPayload = [],
    ) {
    }
}
