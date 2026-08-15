<?php

namespace Tanaki\IntegrationContracts\Event;

use Tanaki\IntegrationContracts\DTO\QrScanDTO;

/**
 * A plain PHP value object — see WeighbridgeReadingCaptured for why the
 * Laravel event traits were removed (ADR-0006).
 */
final readonly class QrCodeScanned
{
    public function __construct(public QrScanDTO $scan)
    {
    }
}
