<?php

namespace App\Observers;

use App\Models\DocumentShare;
use App\Models\EdmsSetting;
use App\Notifications\DocumentNotification;
use App\Services\DocumentNotificationRecipients;
use Illuminate\Support\Facades\Notification;

class DocumentShareObserver
{
    public function __construct(private readonly DocumentNotificationRecipients $recipients)
    {
    }

    public function created(DocumentShare $share): void
    {
        if (! EdmsSetting::getSetting($share->company_id, 'notify_document_shared', true)) {
            return;
        }

        $users = $this->recipients->forCompany($share->company_id);

        if ($users->isEmpty()) {
            return;
        }

        $entity = $share->shareable;

        Notification::send($users, new DocumentNotification('document_shared', [
            'reference' => $entity?->title ?? $entity?->subject ?? 'Item',
            'shared_with' => $share->shared_with_name ?: $share->shared_with_type,
        ]));
    }
}
