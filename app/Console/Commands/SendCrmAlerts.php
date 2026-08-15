<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Lead;
use App\Models\Quotation;
use App\Notifications\CrmNotification;
use App\Services\CrmNotificationRecipients;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class SendCrmAlerts extends Command
{
    protected $signature = 'crm:send-alerts';

    protected $description = 'Notify relevant users about newly assigned leads, quotations expiring soon and quotations accepted';

    public function handle(CrmNotificationRecipients $recipients): void
    {
        $sent = 0;

        Company::query()->each(function (Company $company) use ($recipients, &$sent) {
            $companyId = $company->id;
            $users = $recipients->forCompany($companyId);

            if ($users->isEmpty()) {
                return;
            }

            $sent += $this->notifyLeadAssigned($companyId, $users);
            $sent += $this->notifyQuotationExpiring($companyId, $users);
            $sent += $this->notifyQuotationAccepted($companyId, $users);
        });

        $this->info("CRM alert sweep complete. Sent {$sent} notification batch(es).");
    }

    private function notifyLeadAssigned(int $companyId, $users): int
    {
        $leads = Lead::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->whereNotNull('assigned_to_id')
            ->where('updated_at', '>=', now()->subHour())
            ->get();

        foreach ($leads as $lead) {
            Notification::send($users, new CrmNotification('lead_assigned', ['reference' => "{$lead->lead_number} ({$lead->company_name})"]));
        }

        return $leads->count();
    }

    private function notifyQuotationExpiring(int $companyId, $users): int
    {
        $quotations = Quotation::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->whereIn('status', ['Submitted', 'Approved'])
            ->whereNotNull('validity_date')
            ->whereBetween('validity_date', [now(), now()->addDays(3)])
            ->get();

        foreach ($quotations as $quotation) {
            Notification::send($users, new CrmNotification('quotation_expiring', ['reference' => $quotation->quotation_number]));
        }

        return $quotations->count();
    }

    private function notifyQuotationAccepted(int $companyId, $users): int
    {
        $quotations = Quotation::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('status', 'Accepted')
            ->where('updated_at', '>=', now()->subHour())
            ->get();

        foreach ($quotations as $quotation) {
            Notification::send($users, new CrmNotification('quotation_accepted', ['reference' => $quotation->quotation_number]));
        }

        return $quotations->count();
    }
}
