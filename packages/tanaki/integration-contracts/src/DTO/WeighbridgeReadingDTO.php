<?php

namespace Tanaki\IntegrationContracts\DTO;

use Carbon\CarbonImmutable;

/**
 * Standardized weighbridge reading — the ONLY shape any business module
 * (Weighbridge, Dispatch, Quarry) is allowed to depend on. rawVendorPayload
 * is carried for audit/support troubleshooting only, never for business logic.
 */
final readonly class WeighbridgeReadingDTO
{
    /**
     * @param  array<string, mixed>  $rawVendorPayload
     */
    public function __construct(
        public int $companyId,
        public string $deviceId,
        public string $weighbridgeCode,
        public float $grossWeight,
        public ?float $tareWeight,
        public ?float $netWeight,
        public string $unit,
        public ?string $vehicleIdentifier,
        public CarbonImmutable $capturedAt,
        public array $rawVendorPayload = [],
    ) {
    }
}
