<?php

namespace App\IntegrationEngine\Protocols;

use Tanaki\IntegrationContracts\Enum\ProtocolType;

class Rs232ProtocolAdapter extends AbstractUnimplementedProtocolAdapter
{
    public function getProtocol(): ProtocolType
    {
        return ProtocolType::from(ProtocolType::RS232);
    }

    public function getRequiredDependencies(): array
    {
        return [
            'a serial-port PHP extension (e.g. ext-dio) or a shell_exec bridge to a system serial tool — not installed',
            'a physical RS232 serial port/adapter on the host — not present in this environment',
        ];
    }
}
