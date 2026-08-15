<?php

namespace App\IntegrationEngine\Protocols;

use Tanaki\IntegrationContracts\Enum\ProtocolType;

class Rs485ProtocolAdapter extends AbstractUnimplementedProtocolAdapter
{
    public function getProtocol(): ProtocolType
    {
        return ProtocolType::from(ProtocolType::RS485);
    }

    public function getRequiredDependencies(): array
    {
        return [
            'a serial-port PHP extension (e.g. ext-dio) or a shell_exec bridge to a system serial tool — not installed',
            'an RS485-to-USB/serial adapter with proper bus termination — not present in this environment',
        ];
    }
}
