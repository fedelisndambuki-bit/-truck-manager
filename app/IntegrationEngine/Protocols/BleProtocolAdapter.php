<?php

namespace App\IntegrationEngine\Protocols;

use Tanaki\IntegrationContracts\Enum\ProtocolType;

class BleProtocolAdapter extends AbstractUnimplementedProtocolAdapter
{
    public function getProtocol(): ProtocolType
    {
        return ProtocolType::from(ProtocolType::BLE);
    }

    public function getRequiredDependencies(): array
    {
        return [
            'OS Bluetooth stack access — PHP has no native BLE support; this typically requires a companion service (e.g. a small Node/Python BLE bridge) that PHP talks to over HTTP/MQTT instead',
            'a Bluetooth radio and BLE peripheral to pair with — not present in this environment',
        ];
    }
}
