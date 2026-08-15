<?php

namespace Tanaki\IntegrationContracts\Event;

use Carbon\CarbonImmutable;

/**
 * A plain PHP value object — see WeighbridgeReadingCaptured for why the
 * Laravel event traits were removed (ADR-0006). See DeviceCameOnline for why
 * $deviceId and $occurredAt were added, and why $deviceId is currently a
 * stand-in equal to $vendorKey.
 */
final readonly class DeviceWentOffline
{
    public function __construct(
        public string $vendorKey,
        public string $deviceId,
        public CarbonImmutable $occurredAt,
    ) {
    }
}
