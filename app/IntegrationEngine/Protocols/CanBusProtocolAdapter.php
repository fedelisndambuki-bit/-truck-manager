<?php

namespace App\IntegrationEngine\Protocols;

use Tanaki\IntegrationContracts\Enum\ProtocolType;

class CanBusProtocolAdapter extends AbstractUnimplementedProtocolAdapter
{
    public function getProtocol(): ProtocolType
    {
        return ProtocolType::from(ProtocolType::CAN_BUS);
    }

    public function getRequiredDependencies(): array
    {
        return [
            'OS-level SocketCAN access (Linux-only) or a vendor CAN interface library — not available from PHP directly, and not present on this host',
            'a physical CAN interface/adapter — not present in this environment',
        ];
    }
}
