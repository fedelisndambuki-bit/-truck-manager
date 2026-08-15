<?php

namespace App\IntegrationEngine\Protocols;

use Tanaki\IntegrationContracts\Contract\ProtocolAdapterInterface;
use Tanaki\IntegrationContracts\Exception\ProtocolNotImplementedException;

/**
 * Shared base for protocols this environment genuinely cannot reach today
 * (RS232, RS485, USB, Modbus, CAN Bus, BLE — each needs a PHP extension
 * that isn't installed, OS-level access PHP can't do directly, or hardware
 * to shell out to). Every method throws ProtocolNotImplementedException
 * with the real, honest reason from getRequiredDependencies() — nothing
 * silently no-ops or pretends to succeed. Concrete subclasses only need to
 * supply getProtocol() and getRequiredDependencies().
 */
abstract class AbstractUnimplementedProtocolAdapter implements ProtocolAdapterInterface
{
    public function connect(array $config): bool
    {
        throw ProtocolNotImplementedException::forProtocol($this->getProtocol()->label(), $this->getRequiredDependencies());
    }

    public function disconnect(): void
    {
        throw ProtocolNotImplementedException::forProtocol($this->getProtocol()->label(), $this->getRequiredDependencies());
    }

    public function isConnected(): bool
    {
        return false;
    }

    public function send(array|string $payload): mixed
    {
        throw ProtocolNotImplementedException::forProtocol($this->getProtocol()->label(), $this->getRequiredDependencies());
    }

    public function receive(): mixed
    {
        throw ProtocolNotImplementedException::forProtocol($this->getProtocol()->label(), $this->getRequiredDependencies());
    }

    public function listen(callable $onDataReceived): void
    {
        throw ProtocolNotImplementedException::forProtocol($this->getProtocol()->label(), $this->getRequiredDependencies());
    }
}
