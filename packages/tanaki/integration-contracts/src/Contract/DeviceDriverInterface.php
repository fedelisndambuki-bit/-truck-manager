<?php

namespace Tanaki\IntegrationContracts\Contract;

use Tanaki\IntegrationContracts\Enum\DeviceCategory;

/**
 * The common contract every hardware driver implements, regardless of
 * vendor or category. Deliberately composed of three smaller, focused
 * interfaces (Interface Segregation) rather than one flat interface — code
 * that only needs to health-check a device can type-hint just
 * HealthCheckableInterface instead of depending on the whole driver
 * contract. Every real driver still implements all of it.
 */
interface DeviceDriverInterface extends HealthCheckableInterface, DiagnosticsInterface, DriverMetadataInterface
{
    public function getVendorKey(): string;

    public function getCategory(): DeviceCategory;

    /**
     * @param  array<string, mixed>  $connectionConfig  e.g. host/port/api_key/serial_port — shape depends on the protocol
     */
    public function connect(array $connectionConfig): bool;

    public function disconnect(): void;

    public function isOnline(): bool;
}
