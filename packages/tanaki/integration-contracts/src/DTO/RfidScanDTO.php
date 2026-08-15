<?php

namespace Tanaki\IntegrationContracts\DTO;

use Carbon\CarbonImmutable;

final readonly class RfidScanDTO
{
    /**
     * @param  array<string, mixed>  $rawVendorPayload
     */
    public function __construct(
        public int $companyId,
        public string $deviceId,
        public string $tagId,
        public ?string $readerLocation,
        public ?string $direction,
        public CarbonImmutable $scannedAt,
        public array $rawVendorPayload = [],
    ) {
    }
}
