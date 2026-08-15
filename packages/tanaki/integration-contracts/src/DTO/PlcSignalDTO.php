<?php

namespace Tanaki\IntegrationContracts\DTO;

use Carbon\CarbonImmutable;

final readonly class PlcSignalDTO
{
    /**
     * @param  array<string, mixed>  $rawVendorPayload
     */
    public function __construct(
        public int $companyId,
        public string $deviceId,
        public string $tag,
        public mixed $value,
        public string $dataType,
        public CarbonImmutable $readAt,
        public array $rawVendorPayload = [],
    ) {
    }
}
