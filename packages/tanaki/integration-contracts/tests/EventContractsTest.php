<?php

namespace Tanaki\IntegrationContracts\Tests;

use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;
use Tanaki\IntegrationContracts\Event\DeviceCameOnline;
use Tanaki\IntegrationContracts\Event\WeighbridgeReadingCaptured;

class EventContractsTest extends TestCase
{
    public function test_events_are_final_and_readonly_with_no_framework_traits(): void
    {
        $reflection = new \ReflectionClass(WeighbridgeReadingCaptured::class);

        $this->assertTrue($reflection->isFinal());
        $this->assertTrue($reflection->isReadOnly());
        $this->assertFalse(method_exists(WeighbridgeReadingCaptured::class, 'dispatch'));
    }

    public function test_device_came_online_carries_device_id_and_occurred_at(): void
    {
        $event = new DeviceCameOnline('teltonika', 'device-42', CarbonImmutable::now());

        $this->assertSame('teltonika', $event->vendorKey);
        $this->assertSame('device-42', $event->deviceId);
        $this->assertInstanceOf(CarbonImmutable::class, $event->occurredAt);
    }
}
