<?php

namespace Tanaki\IntegrationContracts\Exception;

use RuntimeException;

class DeviceOfflineException extends RuntimeException implements IntegrationExceptionInterface
{
    public static function forDevice(string $deviceId, ?string $reason = null): self
    {
        $message = "Device [{$deviceId}] is offline or unreachable.";

        return new self($reason ? "{$message} {$reason}" : $message);
    }
}
