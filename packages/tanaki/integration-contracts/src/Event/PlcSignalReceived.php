<?php

namespace Tanaki\IntegrationContracts\Event;

use Tanaki\IntegrationContracts\DTO\PlcSignalDTO;

/**
 * A plain PHP value object — see WeighbridgeReadingCaptured for why the
 * Laravel event traits were removed (ADR-0006).
 */
final readonly class PlcSignalReceived
{
    public function __construct(public PlcSignalDTO $signal)
    {
    }
}
