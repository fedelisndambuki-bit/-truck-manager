<?php

namespace Tests\Unit\IntegrationEngine;

use PHPUnit\Framework\Attributes\DataProvider;
use Tanaki\IntegrationContracts\Enum\ProtocolType;
use Tests\TestCase;

/**
 * Covers ADR-0004 for ProtocolType — same open-value-object treatment as
 * DeviceCategory, for the same reason (a manufacturer may need a
 * proprietary transport no protocol package has been published for yet).
 */
class ProtocolTypeTest extends TestCase
{
    public function test_built_in_constant_resolves_with_its_label(): void
    {
        $protocol = ProtocolType::from(ProtocolType::MQTT);

        $this->assertSame('mqtt', $protocol->value());
        $this->assertSame('MQTT', $protocol->label());
    }

    public function test_an_unknown_value_becomes_a_valid_custom_protocol(): void
    {
        $protocol = ProtocolType::from('proprietary-vendor-bus');

        $this->assertSame('proprietary-vendor-bus', $protocol->value());
    }

    #[DataProvider('implementedProtocolProvider')]
    public function test_is_implemented_matches_the_real_five_working_adapters(string $constant, bool $expected): void
    {
        $this->assertSame($expected, ProtocolType::from($constant)->isImplemented());
    }

    public static function implementedProtocolProvider(): array
    {
        return [
            'http' => [ProtocolType::HTTP, true],
            'tcp' => [ProtocolType::TCP, true],
            'udp' => [ProtocolType::UDP, true],
            'websocket' => [ProtocolType::WEBSOCKET, true],
            'mqtt' => [ProtocolType::MQTT, true],
            'rs232 (stub only)' => [ProtocolType::RS232, false],
            'rs485 (stub only)' => [ProtocolType::RS485, false],
            'usb (stub only)' => [ProtocolType::USB, false],
            'modbus (stub only)' => [ProtocolType::MODBUS, false],
            'can_bus (stub only)' => [ProtocolType::CAN_BUS, false],
            'ble (stub only)' => [ProtocolType::BLE, false],
        ];
    }

    public function test_a_custom_protocol_is_never_reported_as_implemented(): void
    {
        $this->assertFalse(ProtocolType::from('proprietary-vendor-bus')->isImplemented());
    }
}
