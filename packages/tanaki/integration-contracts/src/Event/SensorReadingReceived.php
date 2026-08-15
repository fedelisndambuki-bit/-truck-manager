<?php

namespace Tanaki\IntegrationContracts\Event;

use Tanaki\IntegrationContracts\DTO\SensorReadingDTO;

/**
 * A plain PHP value object — see WeighbridgeReadingCaptured for why the
 * Laravel event traits were removed (ADR-0006).
 */
final readonly class SensorReadingReceived
{
    public function __construct(public SensorReadingDTO $reading)
    {
    }
}
