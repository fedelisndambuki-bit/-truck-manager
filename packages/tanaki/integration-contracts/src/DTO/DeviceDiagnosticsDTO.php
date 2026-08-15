<?php

namespace Tanaki\IntegrationContracts\DTO;

use Carbon\CarbonImmutable;

/**
 * A deeper, on-demand report — distinct from DeviceHealthDTO, which is the
 * fast/cheap/frequent "is it online right now" check. Diagnostics are pulled
 * when someone is actively troubleshooting a device.
 */
final readonly class DeviceDiagnosticsDTO
{
    /**
     * @param  array<string, mixed>|null  $lastRawPayload
     */
    public function __construct(
        public string $deviceId,
        public string $vendorKey,
        public string $protocol,
        public bool $isConnected,
        public ?CarbonImmutable $connectedSince,
        public ?array $lastRawPayload,
        public ?string $lastError,
        public ?CarbonImmutable $lastErrorAt,
        public int $reconnectAttempts = 0,
    ) {
    }
}
