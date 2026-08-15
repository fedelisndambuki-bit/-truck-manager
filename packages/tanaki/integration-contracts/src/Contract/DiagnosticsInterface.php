<?php

namespace Tanaki\IntegrationContracts\Contract;

use Tanaki\IntegrationContracts\DTO\DeviceDiagnosticsDTO;

interface DiagnosticsInterface
{
    /**
     * Deeper, on-demand report — pulled when actively troubleshooting a
     * device, not on a frequent schedule like healthCheck().
     */
    public function runDiagnostics(): DeviceDiagnosticsDTO;
}
