<?php

namespace Tests\Unit\IntegrationEngine;

use Carbon\CarbonImmutable;
use Tanaki\IntegrationContracts\DTO\DeviceHealthDTO;
use Tanaki\IntegrationContracts\DTO\WeighbridgeReadingDTO;
use Tanaki\IntegrationContracts\Enum\DeviceStatus;
use Tanaki\IntegrationContracts\Event\DeviceCameOnline;
use Tanaki\IntegrationContracts\Event\DeviceHealthCheckFailed;
use Tanaki\IntegrationContracts\Event\DeviceWentOffline;
use Tanaki\IntegrationContracts\Event\WeighbridgeReadingCaptured;
use Tests\TestCase;

/**
 * Covers ADR-0006: event contracts are plain PHP value objects, not Laravel
 * event classes — Dispatchable/InteractsWithSockets/SerializesModels were
 * removed after verifying no call site used their behavior. Also covers the
 * deviceId/occurredAt addition to the two lifecycle events.
 */
class EventContractsTest extends TestCase
{
    public function test_weighbridge_reading_captured_no_longer_carries_the_dispatchable_trait(): void
    {
        // Dispatchable adds a static ::dispatch() method — its absence
        // proves the trait was actually removed, not just unused.
        $this->assertFalse(method_exists(WeighbridgeReadingCaptured::class, 'dispatch'));
    }

    public function test_weighbridge_reading_captured_is_final_and_readonly(): void
    {
        $reflection = new \ReflectionClass(WeighbridgeReadingCaptured::class);

        $this->assertTrue($reflection->isFinal());
        $this->assertTrue($reflection->isReadOnly());
    }

    public function test_it_can_still_be_dispatched_via_the_plain_event_helper(): void
    {
        $dto = new WeighbridgeReadingDTO(
            companyId: 1,
            deviceId: 'device-1',
            weighbridgeCode: 'WB-1',
            grossWeight: 100.0,
            tareWeight: 40.0,
            netWeight: 60.0,
            unit: 'kg',
            vehicleIdentifier: 'ABC 123',
            capturedAt: CarbonImmutable::now(),
        );

        $received = null;
        \Illuminate\Support\Facades\Event::listen(WeighbridgeReadingCaptured::class, function (WeighbridgeReadingCaptured $event) use (&$received) {
            $received = $event;
        });

        event(new WeighbridgeReadingCaptured($dto));

        $this->assertNotNull($received);
        $this->assertSame($dto, $received->reading);
    }

    public function test_device_came_online_carries_device_id_and_occurred_at(): void
    {
        $event = new DeviceCameOnline('teltonika', 'device-42', CarbonImmutable::now());

        $this->assertSame('teltonika', $event->vendorKey);
        $this->assertSame('device-42', $event->deviceId);
        $this->assertInstanceOf(CarbonImmutable::class, $event->occurredAt);
    }

    public function test_device_went_offline_carries_device_id_and_occurred_at(): void
    {
        $event = new DeviceWentOffline('teltonika', 'device-42', CarbonImmutable::now());

        $this->assertSame('device-42', $event->deviceId);
        $this->assertInstanceOf(CarbonImmutable::class, $event->occurredAt);
    }

    public function test_device_health_check_failed_is_unchanged_in_shape(): void
    {
        $health = new DeviceHealthDTO(
            deviceId: 'device-1',
            status: DeviceStatus::Offline,
            isOnline: false,
            lastSeenAt: null,
        );

        $event = new DeviceHealthCheckFailed('teltonika', $health);

        $this->assertSame('teltonika', $event->vendorKey);
        $this->assertSame($health, $event->health);
    }
}
