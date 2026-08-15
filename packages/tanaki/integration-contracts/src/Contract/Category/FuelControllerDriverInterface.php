<?php

namespace Tanaki\IntegrationContracts\Contract\Category;

use Tanaki\IntegrationContracts\Contract\DeviceDriverInterface;
use Tanaki\IntegrationContracts\DTO\FuelTransactionDTO;

interface FuelControllerDriverInterface extends DeviceDriverInterface
{
    /**
     * @param  array<string, mixed>  $rawPayload
     */
    public function parseTransaction(array $rawPayload): FuelTransactionDTO;
}
