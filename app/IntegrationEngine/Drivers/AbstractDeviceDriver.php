<?php

namespace App\IntegrationEngine\Drivers;

use App\IntegrationEngine\Drivers\Concerns\LogsDriverActivity;
use Carbon\CarbonImmutable;
use ReflectionClass;
use Tanaki\IntegrationContracts\Attribute\IntegrationDriver;
use Tanaki\IntegrationContracts\Contract\DeviceDriverInterface;
use Tanaki\IntegrationContracts\Contract\ProtocolAdapterInterface;
use Tanaki\IntegrationContracts\DTO\DeviceDiagnosticsDTO;
use Tanaki\IntegrationContracts\DTO\DeviceHealthDTO;
use Tanaki\IntegrationContracts\DTO\DriverMetadataDTO;
use Tanaki\IntegrationContracts\Enum\DeviceCategory;
use Tanaki\IntegrationContracts\Enum\DeviceStatus;
use Tanaki\IntegrationContracts\Event\DeviceCameOnline;
use Tanaki\IntegrationContracts\Event\DeviceWentOffline;
use Tanaki\IntegrationContracts\Exception\DeviceOfflineException;

/**
 * Base class every vendor driver extends. Concrete drivers only need to
 * implement getVendorKey(), getCategory(), and their category-specific
 * parse/capture method — connect/disconnect/health-check/diagnostics/
 * metadata/retry/reconnect/logging all come from here, built on top of
 * whichever ProtocolAdapterInterface is injected.
 */
abstract class AbstractDeviceDriver implements DeviceDriverInterface
{
    use LogsDriverActivity;

    protected ?CarbonImmutable $lastSeenAt = null;

    protected ?CarbonImmutable $connectedSince = null;

    protected ?string $lastError = null;

    protected ?CarbonImmutable $lastErrorAt = null;

    protected int $reconnectAttempts = 0;

    protected ?array $lastRawPayload = null;

    public function __construct(protected ProtocolAdapterInterface $protocol) {}

    /**
     * Which ProtocolAdapterInterface implementation this driver needs.
     * ProtocolAdapterInterface itself is never bound to one concrete class
     * in the container (multiple protocols implement it), so
     * IntegrationEngineServiceProvider reads this per discovered driver and
     * registers a contextual binding — the idiomatic Laravel answer to "this
     * interface resolves differently depending on which class is asking."
     *
     * @return class-string<ProtocolAdapterInterface>
     */
    abstract public static function protocolAdapterClass(): string;

    public function connect(array $connectionConfig): bool
    {
        try {
            $connected = retry(3, function () use ($connectionConfig) {
                if (! $this->protocol->connect($connectionConfig)) {
                    throw DeviceOfflineException::forDevice($this->getVendorKey(), 'Protocol adapter could not establish a connection.');
                }

                return true;
            }, 200);
        } catch (\Throwable $e) {
            $this->recordError($e->getMessage());

            return false;
        }

        if ($connected) {
            $this->connectedSince = CarbonImmutable::now();
            $this->reconnectAttempts = 0;
            $this->logDriverInfo('Connected');
        }

        return $connected;
    }

    public function disconnect(): void
    {
        $this->protocol->disconnect();
        $this->connectedSince = null;
        $this->logDriverInfo('Disconnected');
    }

    public function isOnline(): bool
    {
        return $this->protocol->isConnected();
    }

    public function healthCheck(): DeviceHealthDTO
    {
        $online = $this->isOnline();

        return new DeviceHealthDTO(
            deviceId: $this->getVendorKey(),
            status: $online ? DeviceStatus::Active : DeviceStatus::Offline,
            isOnline: $online,
            lastSeenAt: $this->lastSeenAt,
            diagnosticMessage: $online ? null : $this->lastError,
        );
    }

    public function runDiagnostics(): DeviceDiagnosticsDTO
    {
        return new DeviceDiagnosticsDTO(
            deviceId: $this->getVendorKey(),
            vendorKey: $this->getVendorKey(),
            protocol: $this->protocol->getProtocol()->value(),
            isConnected: $this->isOnline(),
            connectedSince: $this->connectedSince,
            lastRawPayload: $this->lastRawPayload,
            lastError: $this->lastError,
            lastErrorAt: $this->lastErrorAt,
            reconnectAttempts: $this->reconnectAttempts,
        );
    }

    /**
     * Reads metadata/capabilities off the driver's own #[IntegrationDriver]
     * attribute rather than requiring every concrete driver to redeclare
     * the same facts twice.
     */
    public function getMetadata(): DriverMetadataDTO
    {
        $attribute = $this->resolveAttribute();

        return new DriverMetadataDTO(
            vendorKey: $attribute->vendor,
            displayName: $attribute->displayName ?? $attribute->vendor,
            category: DeviceCategory::from($attribute->category),
            version: $attribute->version,
            capabilities: $attribute->capabilities,
            description: $attribute->description,
        );
    }

    public function getCapabilities(): array
    {
        return $this->resolveAttribute()->capabilities;
    }

    public function getCategory(): DeviceCategory
    {
        return DeviceCategory::from($this->resolveAttribute()->category);
    }

    public function getVendorKey(): string
    {
        return $this->resolveAttribute()->vendor;
    }

    /**
     * Wraps a receive/listen operation with automatic reconnect + backoff,
     * emitting DeviceCameOnline/DeviceWentOffline on state transitions.
     * Callers (category-specific listen loops) use this instead of calling
     * the protocol adapter directly.
     */
    protected function withAutoReconnect(callable $operation, array $connectionConfig, int $maxAttempts = 5): mixed
    {
        $wasOnline = $this->isOnline();

        try {
            $result = $operation();
            $this->lastSeenAt = CarbonImmutable::now();

            if (! $wasOnline) {
                // deviceId stands in as vendorKey until drivers become
                // per-instance rather than per-vendor — see DeviceCameOnline.
                event(new DeviceCameOnline($this->getVendorKey(), $this->getVendorKey(), CarbonImmutable::now()));
            }

            return $result;
        } catch (\Throwable $e) {
            $this->recordError($e->getMessage());

            if ($wasOnline) {
                event(new DeviceWentOffline($this->getVendorKey(), $this->getVendorKey(), CarbonImmutable::now()));
            }

            $this->reconnectAttempts++;

            if ($this->reconnectAttempts <= $maxAttempts) {
                $backoffMs = min(30000, 500 * (2 ** $this->reconnectAttempts));
                usleep($backoffMs * 1000);

                if ($this->connect($connectionConfig)) {
                    return $this->withAutoReconnect($operation, $connectionConfig, $maxAttempts);
                }
            }

            throw DeviceOfflineException::forDevice($this->getVendorKey(), $e->getMessage());
        }
    }

    protected function recordError(string $message): void
    {
        $this->lastError = $message;
        $this->lastErrorAt = CarbonImmutable::now();
        $this->logDriverError($message);
    }

    protected function recordRawPayload(array $payload): void
    {
        $this->lastRawPayload = $payload;
        $this->lastSeenAt = CarbonImmutable::now();
    }

    protected function resolveAttribute(): IntegrationDriver
    {
        $attributes = (new ReflectionClass(static::class))->getAttributes(IntegrationDriver::class);

        if (empty($attributes)) {
            throw new \RuntimeException(static::class.' must be tagged with #[IntegrationDriver] for auto-discovery and metadata to work.');
        }

        return $attributes[0]->newInstance();
    }
}
