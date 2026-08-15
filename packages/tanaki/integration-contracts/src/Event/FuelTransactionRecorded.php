<?php

namespace Tanaki\IntegrationContracts\Event;

use Tanaki\IntegrationContracts\DTO\FuelTransactionDTO;

/**
 * A plain PHP value object — see WeighbridgeReadingCaptured for why the
 * Laravel event traits were removed (ADR-0006).
 */
final readonly class FuelTransactionRecorded
{
    public function __construct(public FuelTransactionDTO $transaction)
    {
    }
}
