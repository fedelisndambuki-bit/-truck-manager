<?php

namespace Tanaki\IntegrationContracts\DTO;

use Carbon\CarbonImmutable;

final readonly class GpsPositionDTO
{
    /**
     * @param  array<string, mixed>  $rawVendorPayload
     */
    public function __construct(
        public int $companyId,
        public string $deviceId,
        public string $assetIdentifier,
        public float $latitude,
        public float $longitude,
        public ?float $speedKph,
        public ?float $headingDegrees,
        public ?float $altitude,
        public ?float $engineHours,
        public ?float $fuelLevel,
        public CarbonImmutable $recordedAt,
        public array $rawVendorPayload = [],
    ) {
    }
}
