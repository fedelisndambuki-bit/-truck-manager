<?php

namespace App\IntegrationEngine\Drivers\Concerns;

use Illuminate\Support\Facades\Log;

/**
 * Every driver logs through the same dedicated `integration-engine` channel
 * in a consistent format, rather than ad hoc Log::info() calls scattered
 * per driver — keeps device I/O logs separately filterable from
 * application logs.
 */
trait LogsDriverActivity
{
    protected function logDriverInfo(string $message, array $context = []): void
    {
        Log::channel('integration-engine')->info("[{$this->getVendorKey()}] {$message}", $context);
    }

    protected function logDriverWarning(string $message, array $context = []): void
    {
        Log::channel('integration-engine')->warning("[{$this->getVendorKey()}] {$message}", $context);
    }

    protected function logDriverError(string $message, array $context = []): void
    {
        Log::channel('integration-engine')->error("[{$this->getVendorKey()}] {$message}", $context);
    }
}
