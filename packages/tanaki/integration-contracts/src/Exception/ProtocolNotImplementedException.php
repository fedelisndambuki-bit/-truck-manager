<?php

namespace Tanaki\IntegrationContracts\Exception;

use RuntimeException;

/**
 * Thrown by protocol adapters that have a real interface implementation but
 * no working transport in this environment (RS232/RS485/USB/Modbus/CAN
 * Bus/BLE — each needs a PHP extension that isn't installed, OS-level
 * access PHP can't do directly, or hardware to shell out to). Thrown
 * deliberately instead of silently no-op-ing, so nothing in the codebase
 * pretends to support hardware it can't actually reach.
 */
class ProtocolNotImplementedException extends RuntimeException implements IntegrationExceptionInterface
{
    /**
     * @param  string[]  $requiredDependencies
     */
    public static function forProtocol(string $protocolName, array $requiredDependencies): self
    {
        $deps = implode(', ', $requiredDependencies);

        return new self("The {$protocolName} protocol adapter is not implemented in this environment — requires: {$deps}.");
    }
}
