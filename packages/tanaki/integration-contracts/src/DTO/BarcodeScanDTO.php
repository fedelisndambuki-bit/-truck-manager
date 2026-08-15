<?php

namespace Tanaki\IntegrationContracts\DTO;

use Carbon\CarbonImmutable;

final readonly class BarcodeScanDTO
{
    /**
     * @param  array<string, mixed>  $rawVendorPayload
     */
    public function __construct(
        public int $companyId,
        public string $deviceId,
        public string $code,
        public CarbonImmutable $scannedAt,
        public array $rawVendorPayload = [],
    ) {
    }
}
