<?php

namespace Tanaki\IntegrationContracts\Contract;

use Tanaki\IntegrationContracts\Enum\ProtocolType;

/**
 * How data physically moves (transport), kept deliberately separate from
 * how a vendor's payload is interpreted (that's the Driver's job — see
 * DeviceDriverInterface). A Driver is composed of one ProtocolAdapter plus
 * its own vendor-specific parsing logic (Strategy pattern: adapters are
 * interchangeable per protocol, independent of which vendor uses them).
 */
interface ProtocolAdapterInterface
{
    public function getProtocol(): ProtocolType;

    /**
     * @param  array<string, mixed>  $config
     */
    public function connect(array $config): bool;

    public function disconnect(): void;

    public function isConnected(): bool;

    /**
     * Outbound: send a command/payload to the device (PLC writes, MQTT
     * publish, HTTP POST). Not every protocol/device needs this — adapters
     * that are ingest-only may throw if called.
     *
     * @param  array<string, mixed>|string  $payload
     */
    public function send(array|string $payload): mixed;

    /**
     * Blocking/poll-style single read.
     */
    public function receive(): mixed;

    /**
     * Long-running push-style listening (TCP/UDP/WebSocket/MQTT). Not used
     * by poll-based adapters (e.g. plain HTTP).
     */
    public function listen(callable $onDataReceived): void;

    /**
     * Which PHP extensions/composer packages/system access this adapter
     * needs — returned honestly even for adapters that can't run in the
     * current environment (see ProtocolNotImplementedException).
     *
     * @return string[]
     */
    public function getRequiredDependencies(): array;
}
