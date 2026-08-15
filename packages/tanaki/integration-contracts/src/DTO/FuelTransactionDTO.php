<?php

namespace Tanaki\IntegrationContracts\DTO;

use Carbon\CarbonImmutable;

final readonly class FuelTransactionDTO
{
    /**
     * @param  array<string, mixed>  $rawVendorPayload
     */
    public function __construct(
        public int $companyId,
        public string $deviceId,
        public string $pumpIdentifier,
        public ?string $vehicleIdentifier,
        public float $litresDispensed,
        public ?string $fuelType,
        public ?float $unitPrice,
        public CarbonImmutable $transactionAt,
        public array $rawVendorPayload = [],
    ) {
    }
}
