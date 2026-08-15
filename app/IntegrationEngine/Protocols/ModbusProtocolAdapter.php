<?php

namespace App\IntegrationEngine\Protocols;

use Tanaki\IntegrationContracts\Enum\ProtocolType;

class ModbusProtocolAdapter extends AbstractUnimplementedProtocolAdapter
{
    public function getProtocol(): ProtocolType
    {
        return ProtocolType::from(ProtocolType::MODBUS);
    }

    public function getRequiredDependencies(): array
    {
        return [
            'a Modbus TCP/RTU composer library (none installed — this codebase has zero Modbus dependencies today)',
            'a reachable Modbus-speaking PLC/RTU device or simulator — not present in this environment',
        ];
    }
}
