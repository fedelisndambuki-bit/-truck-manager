<?php

namespace App\IntegrationEngine\Protocols;

use Tanaki\IntegrationContracts\Enum\ProtocolType;

class UsbProtocolAdapter extends AbstractUnimplementedProtocolAdapter
{
    public function getProtocol(): ProtocolType
    {
        return ProtocolType::from(ProtocolType::USB);
    }

    public function getRequiredDependencies(): array
    {
        return [
            'a USB HID/serial PHP extension (e.g. ext-libusb bindings) — not installed',
            'a physical USB device attached to the host running this code — not present in this environment',
        ];
    }
}
