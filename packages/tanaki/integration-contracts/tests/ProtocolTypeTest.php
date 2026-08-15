<?php

namespace Tanaki\IntegrationContracts\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tanaki\IntegrationContracts\Enum\ProtocolType;

class ProtocolTypeTest extends TestCase
{
    public function test_built_in_constant_resolves(): void
    {
        $protocol = ProtocolType::from(ProtocolType::MQTT);

        $this->assertSame('mqtt', $protocol->value());
        $this->assertSame('MQTT', $protocol->label());
    }

    #[DataProvider('implementedProtocolProvider')]
    public function test_is_implemented(string $constant, bool $expected): void
    {
        $this->assertSame($expected, ProtocolType::from($constant)->isImplemented());
    }

    public static function implementedProtocolProvider(): array
    {
        return [
            'http' => [ProtocolType::HTTP, true],
            'mqtt' => [ProtocolType::MQTT, true],
            'modbus (stub only)' => [ProtocolType::MODBUS, false],
            'ble (stub only)' => [ProtocolType::BLE, false],
        ];
    }

    public function test_a_custom_protocol_is_never_reported_as_implemented(): void
    {
        $this->assertFalse(ProtocolType::from('proprietary-vendor-bus')->isImplemented());
    }
}
