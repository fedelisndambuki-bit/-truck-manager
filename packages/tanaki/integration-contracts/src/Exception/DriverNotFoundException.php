<?php

namespace Tanaki\IntegrationContracts\Exception;

use RuntimeException;

class DriverNotFoundException extends RuntimeException implements IntegrationExceptionInterface
{
    public static function forVendor(string $vendorKey): self
    {
        return new self("No driver is registered for vendor key [{$vendorKey}]. Run `php artisan integration-engine:driver:discover` if a new driver class was just added.");
    }
}
