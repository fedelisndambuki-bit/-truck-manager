<?php

namespace Tanaki\IntegrationContracts\Contract\Category;

use Tanaki\IntegrationContracts\Contract\DeviceDriverInterface;
use Tanaki\IntegrationContracts\DTO\QrScanDTO;

interface QrReaderDriverInterface extends DeviceDriverInterface
{
    /**
     * @param  array<string, mixed>  $rawPayload
     */
    public function parseScan(array $rawPayload): QrScanDTO;
}
