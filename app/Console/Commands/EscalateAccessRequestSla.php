<?php

namespace App\Console\Commands;

use App\Models\AccessRequest;
use App\Models\PlatformSetting;
use App\Models\SystemLog;
use App\Models\User;
use App\Notifications\AccessRequestNotification;
use Illuminate\Console\Command;

class EscalateAccessRequestSla extends Command
{
    protected $signature = 'access-requests:escalate-sla';
    protected $description = 'Notify Super Admins about access requests stuck in review past the SLA window';

    public function handle(): void
    {
        $slaHours = (int) PlatformSetting::getSetting('access_request_sla_hours', 48);

        $overdue = AccessRequest::whereIn('status', [
            AccessRequest::STATUS_PENDING_REVIEW,
            AccessRequest::STATUS_IN_REVIEW,
        ])->where('created_at', '<=', now()->subHours($slaHours))->get();

        if ($overdue->isEmpty()) {
            $this->info('No requests past the SLA window.');

            return;
        }

        $superAdmins = User::role('Super Admin')->get();

        foreach ($overdue as $accessRequest) {
            // Dedup: don't re-notify for the same request more than once a day.
            $alreadyEscalatedToday = SystemLog::where('action', 'access_request_sla_breach')
                ->where('metadata->access_request_id', $accessRequest->id)
                ->where('created_at', '>=', now()->subHours(23))
                ->exists();

            if ($alreadyEscalatedToday) {
                continue;
            }

            foreach ($superAdmins as $admin) {
                $admin->notify(new AccessRequestNotification('sla_breach', [
                    'organization_name' => $accessRequest->organization_name,
                    'sla_hours' => $slaHours,
                ]));
            }

            SystemLog::create([
                'action' => 'access_request_sla_breach',
                'description' => "Access request from {$accessRequest->organization_name} has been pending review for over {$slaHours}h",
                'severity' => 'warning',
                'metadata' => ['access_request_id' => $accessRequest->id],
            ]);
        }

        $this->info("Escalated {$overdue->count()} overdue request(s).");
    }
}
