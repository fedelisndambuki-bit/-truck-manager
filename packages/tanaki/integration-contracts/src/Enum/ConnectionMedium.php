<?php

namespace Tanaki\IntegrationContracts\Enum;

/**
 * The physical link-layer medium a device uses to reach us — recorded as
 * metadata on IntegratedDevice for diagnostics/operations, independent of
 * which ProtocolType runs over it (a WiFi-connected device still speaks
 * Http/Tcp/Mqtt/etc — PHP never sees the link layer directly). A closed,
 * native enum: new physical media are rare, Tanaki-controlled additions,
 * not something a driver package needs to extend.
 *
 * No Filament\Support\Contracts\HasLabel — see DeviceStatus for the same
 * rationale.
 */
enum ConnectionMedium: string
{
    case WiFi = 'wifi';
    case Ethernet = 'ethernet';
    case Cellular = 'cellular';
    case Serial = 'serial';
    case Bluetooth = 'bluetooth';
    case Usb = 'usb';

    public function label(): string
    {
        return match ($this) {
            self::WiFi => 'WiFi',
            self::Ethernet => 'Ethernet',
            self::Cellular => 'Cellular',
            self::Serial => 'Serial Cable',
            self::Bluetooth => 'Bluetooth',
            self::Usb => 'USB',
        };
    }
}
