<?php

namespace App\Observers;

use App\Filament\Clusters\DocumentManagement\Resources\DocumentResource;
use App\Models\EdmsDocument;
use App\Models\EdmsSetting;
use App\Notifications\DocumentNotification;
use App\Services\DocumentNotificationRecipients;
use Illuminate\Support\Facades\Notification;

class DocumentObserver
{
    public function __construct(private readonly DocumentNotificationRecipients $recipients)
    {
    }

    public function created(EdmsDocument $document): void
    {
        if (! EdmsSetting::getSetting($document->company_id, 'notify_document_uploaded', true)) {
            return;
        }

        $users = $this->recipients->forCompany($document->company_id);

        if ($users->isEmpty()) {
            return;
        }

        Notification::send($users, new DocumentNotification('document_uploaded', [
            'reference' => $document->document_number . ' — ' . $document->title,
            'url' => DocumentResource::getUrl('edit', ['record' => $document->id]),
        ]));
    }
}
