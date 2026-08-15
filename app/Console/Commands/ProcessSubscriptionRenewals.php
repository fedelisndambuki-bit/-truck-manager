<?php

namespace App\Console\Commands;

use App\Services\SubscriptionService;
use Illuminate\Console\Command;

class ProcessSubscriptionRenewals extends Command
{
    protected $signature   = 'subscription:process-renewals';
    protected $description = 'Auto-charge subscriptions due for renewal that have a saved card on file';

    public function handle(SubscriptionService $service): void
    {
        $this->info('Processing subscription renewals...');
        $service->processRenewals();
        $this->info('Done.');
    }
}
