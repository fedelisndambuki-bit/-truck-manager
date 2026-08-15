<?php

namespace App\IntegrationEngine\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Turns a standardized DTO into a dispatched domain event, queued so
 * ingestion never blocks on downstream processing. $tries/$backoff here is
 * the offline-buffering mechanism for this step (reuses Laravel's own
 * primitives rather than a bespoke buffer table, per the Phase 1 plan) —
 * the business-side listener that actually writes to a module's tables has
 * its own separate $tries/$backoff protecting the write itself.
 */
class ProcessDeviceReadingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public array $backoff = [5, 15, 30, 60, 120];

    /**
     * @param  class-string  $eventClass  one of the IntegrationEngine\Events\* classes, each taking exactly one DTO argument
     */
    public function __construct(
        protected string $eventClass,
        protected object $dto,
    ) {
        $this->onQueue(config('integration-engine.queue.queue_name', 'device-ingestion'));
    }

    public function handle(): void
    {
        event(new $this->eventClass($this->dto));
    }

    public function failed(?Throwable $exception): void
    {
        Log::channel('integration-engine')->error('ProcessDeviceReadingJob permanently failed after all retries', [
            'event_class' => $this->eventClass,
            'error' => $exception?->getMessage(),
        ]);
    }
}
