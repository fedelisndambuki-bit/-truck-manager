<?php

namespace Tanaki\IntegrationContracts\Event;

use Tanaki\IntegrationContracts\DTO\GpsPositionDTO;

/**
 * A plain PHP value object — see WeighbridgeReadingCaptured for why the
 * Laravel event traits were removed (ADR-0006).
 */
final readonly class GpsPositionReported
{
    public function __construct(public GpsPositionDTO $position)
    {
    }
}
