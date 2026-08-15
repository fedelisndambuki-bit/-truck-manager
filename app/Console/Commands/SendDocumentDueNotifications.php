<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\EdmsDocument;
use App\Models\EdmsSetting;
use App\Models\Rfi;
use App\Notifications\DocumentNotification;
use App\Services\DocumentNotificationRecipients;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class SendDocumentDueNotifications extends Command
{
    protected $signature = 'documents:send-due-notifications';

    protected $description = 'Notify relevant users about RFIs due soon and contracts expiring soon';

    public function handle(DocumentNotificationRecipients $recipients): void
    {
        $sent = 0;

        Company::query()->where('industry', 'construction')->each(function (Company $company) use ($recipients, &$sent) {
            $companyId = $company->id;
            $users = $recipients->forCompany($companyId);

            if ($users->isEmpty()) {
                return;
            }

            $sent += $this->notifyRfisDue($companyId, $users);
            $sent += $this->notifyContractsExpiring($companyId, $users);
        });

        $this->info("Document due-notification sweep complete. Sent {$sent} notification batch(es).");
    }

    private function notifyRfisDue(int $companyId, $users): int
    {
        if (! EdmsSetting::getSetting($companyId, 'notify_rfi_due', true)) {
            return 0;
        }

        $days = (int) EdmsSetting::getSetting($companyId, 'rfi_due_alert_days', 2);

        $rfis = Rfi::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('status', 'Open')
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<=', now()->addDays($days))
            ->get();

        foreach ($rfis as $rfi) {
            Notification::send($users, new DocumentNotification('rfi_due', [
                'reference' => $rfi->rfi_number . ' — ' . $rfi->subject,
            ]));
        }

        return $rfis->count();
    }

    private function notifyContractsExpiring(int $companyId, $users): int
    {
        if (! EdmsSetting::getSetting($companyId, 'notify_contract_expiring', true)) {
            return 0;
        }

        $days = (int) EdmsSetting::getSetting($companyId, 'contract_expiry_alert_days', 30);

        $documents = EdmsDocument::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('category', 'Contracts')
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [now(), now()->addDays($days)])
            ->get();

        foreach ($documents as $document) {
            Notification::send($users, new DocumentNotification('contract_expiring', [
                'reference' => $document->document_number . ' — ' . $document->title,
            ]));
        }

        return $documents->count();
    }
}
