<?php

namespace Tanaki\IntegrationContracts\Exception;

use RuntimeException;

class InvalidPayloadException extends RuntimeException implements IntegrationExceptionInterface
{
    public static function forVendor(string $vendorKey, string $reason): self
    {
        return new self("Payload from vendor [{$vendorKey}] could not be adapted into a standardized DTO: {$reason}");
    }
}
