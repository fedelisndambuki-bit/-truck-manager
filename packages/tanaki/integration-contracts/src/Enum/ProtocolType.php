<?php

namespace Tanaki\IntegrationContracts\Enum;

use Stringable;

/**
 * An open taxonomy, not a native PHP enum — same rationale as DeviceCategory.
 * A manufacturer may need to declare a proprietary transport a protocol
 * package hasn't been published for yet; ProtocolType::custom() covers that
 * without an integration-contracts release.
 *
 * WiFi and Ethernet are deliberately absent — they're link-layer transports
 * that Http/Tcp/Udp/WebSocket/Mqtt already run on top of transparently from
 * PHP's point of view, not distinct application protocols. See
 * ConnectionMedium for recording which physical medium a device uses.
 *
 * Deliberately zero framework dependency — see DeviceCategory for why this
 * class does not implement Laravel's Castable contract directly.
 */
final class ProtocolType implements Stringable
{
    public const HTTP = 'http';

    public const TCP = 'tcp';

    public const UDP = 'udp';

    public const WEBSOCKET = 'websocket';

    public const MQTT = 'mqtt';

    public const RS232 = 'rs232';

    public const RS485 = 'rs485';

    public const USB = 'usb';

    public const MODBUS = 'modbus';

    public const CAN_BUS = 'can_bus';

    public const BLE = 'ble';

    /**
     * Protocols with a real, working ProtocolAdapterInterface implementation
     * today vs ones that throw ProtocolNotImplementedException (no PHP
     * extension / OS access / hardware available in the reference
     * application's environment for the rest).
     */
    private const IMPLEMENTED = [self::HTTP, self::TCP, self::UDP, self::WEBSOCKET, self::MQTT];

    /** @var array<string, self> memoized built-in instances */
    private static array $builtIns = [];

    private function __construct(
        private readonly string $value,
        private readonly ?string $label,
    ) {
    }

    public static function from(string $value): self
    {
        return self::knownLabels()[$value] ?? self::custom($value);
    }

    public static function custom(string $value, ?string $label = null): self
    {
        return new self($value, $label);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function label(): string
    {
        return $this->label ?? strtoupper($this->value);
    }

    public function isImplemented(): bool
    {
        return in_array($this->value, self::IMPLEMENTED, true);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    /** @return array<string, self> */
    private static function knownLabels(): array
    {
        return [
            self::HTTP => self::$builtIns[self::HTTP] ??= new self(self::HTTP, 'HTTP/HTTPS'),
            self::TCP => self::$builtIns[self::TCP] ??= new self(self::TCP, 'TCP/IP'),
            self::UDP => self::$builtIns[self::UDP] ??= new self(self::UDP, 'UDP'),
            self::WEBSOCKET => self::$builtIns[self::WEBSOCKET] ??= new self(self::WEBSOCKET, 'WebSocket'),
            self::MQTT => self::$builtIns[self::MQTT] ??= new self(self::MQTT, 'MQTT'),
            self::RS232 => self::$builtIns[self::RS232] ??= new self(self::RS232, 'RS232'),
            self::RS485 => self::$builtIns[self::RS485] ??= new self(self::RS485, 'RS485'),
            self::USB => self::$builtIns[self::USB] ??= new self(self::USB, 'USB'),
            self::MODBUS => self::$builtIns[self::MODBUS] ??= new self(self::MODBUS, 'Modbus'),
            self::CAN_BUS => self::$builtIns[self::CAN_BUS] ??= new self(self::CAN_BUS, 'CAN Bus'),
            self::BLE => self::$builtIns[self::BLE] ??= new self(self::BLE, 'Bluetooth Low Energy'),
        ];
    }
}
