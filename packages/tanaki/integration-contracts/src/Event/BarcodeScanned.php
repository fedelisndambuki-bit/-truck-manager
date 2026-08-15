<?php

namespace Tanaki\IntegrationContracts\Event;

use Tanaki\IntegrationContracts\DTO\BarcodeScanDTO;

/**
 * A plain PHP value object — see WeighbridgeReadingCaptured for why the
 * Laravel event traits were removed (ADR-0006).
 */
final readonly class BarcodeScanned
{
    public function __construct(public BarcodeScanDTO $scan)
    {
    }
}
