<?php

namespace App\Console\Commands;

use App\Services\SubscriptionService;
use Illuminate\Console\Command;

class SendSubscriptionReminders extends Command
{
    protected $signature   = 'subscription:reminders';
    protected $description = 'Send trial expiry and renewal reminder notifications';

    public function handle(SubscriptionService $service): void
    {
        $this->info('Sending subscription reminders...');
        $service->sendDailyReminders();
        $this->info('Done.');
    }
}
