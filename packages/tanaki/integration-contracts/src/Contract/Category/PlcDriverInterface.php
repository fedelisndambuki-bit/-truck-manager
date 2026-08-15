<?php

namespace Tanaki\IntegrationContracts\Contract\Category;

use Tanaki\IntegrationContracts\Contract\DeviceDriverInterface;
use Tanaki\IntegrationContracts\DTO\PlcSignalDTO;

/**
 * PLCs are the one category that's legitimately bidirectional — control,
 * not just ingestion — so this is the only category interface that adds a
 * write capability alongside read.
 */
interface PlcDriverInterface extends DeviceDriverInterface
{
    public function readSignal(string $tag): PlcSignalDTO;

    public function writeSignal(string $tag, mixed $value): bool;
}
