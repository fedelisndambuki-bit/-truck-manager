<?php

namespace Tanaki\IntegrationContracts\Event;

use Carbon\CarbonImmutable;

/**
 * A plain PHP value object — see WeighbridgeReadingCaptured for why the
 * Laravel event traits were removed (ADR-0006).
 *
 * $deviceId and $occurredAt were added pre-1.0 to close a design gap found
 * during the API review: $vendorKey alone identifies the driver TYPE, not
 * which specific device instance transitioned — a company running two
 * devices of the same vendor couldn't tell them apart from this event.
 * AbstractDeviceDriver is not yet instance-aware (it resolves per vendor
 * key, not per IntegratedDevice row), so today $deviceId is populated with
 * the same value as $vendorKey at the one call site — a known, documented
 * stand-in until driver instances become per-device, not a claim that the
 * two are conceptually the same.
 */
final readonly class DeviceCameOnline
{
    public function __construct(
        public string $vendorKey,
        public string $deviceId,
        public CarbonImmutable $occurredAt,
    ) {
    }
}
