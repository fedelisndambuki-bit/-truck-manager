<?php

namespace App\Console\Commands;

use App\Models\AccessRequest;
use Illuminate\Console\Command;

class PurgeAccessRequestRetention extends Command
{
    protected $signature = 'access-requests:purge-retention';
    protected $description = 'Anonymize old rejected requests and purge never-verified abandoned requests, per the data retention policy';

    const REJECTED_RETENTION_MONTHS = 12;
    const ABANDONED_RETENTION_DAYS = 60;

    public function handle(): void
    {
        $anonymized = AccessRequest::where('status', AccessRequest::STATUS_REJECTED)
            ->where('updated_at', '<=', now()->subMonths(self::REJECTED_RETENTION_MONTHS))
            ->where('requester_email', 'not like', 'anonymized-%')
            ->get();

        foreach ($anonymized as $accessRequest) {
            $accessRequest->update([
                'requester_name' => 'Anonymized',
                'requester_email' => 'anonymized-' . $accessRequest->id . '@redacted.local',
                'requester_phone' => null,
                'operation_description' => null,
            ]);
            $accessRequest->delete(); // soft delete
        }

        $abandoned = AccessRequest::where('status', AccessRequest::STATUS_PENDING_EMAIL_VERIFICATION)
            ->where('created_at', '<=', now()->subDays(self::ABANDONED_RETENTION_DAYS))
            ->get();

        foreach ($abandoned as $accessRequest) {
            $accessRequest->forceDelete();
        }

        $this->info("Anonymized {$anonymized->count()} rejected request(s), purged {$abandoned->count()} abandoned request(s).");
    }
}
