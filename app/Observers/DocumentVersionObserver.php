<?php

namespace App\Observers;

use App\Models\Drawing;
use App\Models\DocumentVersion;
use App\Models\EdmsSetting;
use App\Notifications\DocumentNotification;
use App\Services\DocumentNotificationRecipients;
use Illuminate\Support\Facades\Notification;

class DocumentVersionObserver
{
    public function __construct(private readonly DocumentNotificationRecipients $recipients)
    {
    }

    public function created(DocumentVersion $version): void
    {
        // The very first version (revision_number 1, from createVersion()'s
        // "max + 1" numbering) is the initial upload, already covered by
        // DocumentObserver's "document_uploaded" — only later versions
        // represent a genuine revision/update.
        if ($version->revision_number <= 1) {
            return;
        }

        $entity = $version->versionable;

        if (! $entity) {
            return;
        }

        $isDrawing = $version->versionable_type === Drawing::class;
        $type = $isDrawing ? 'drawing_updated' : 'revision_submitted';
        $settingKey = $isDrawing ? 'notify_drawing_updated' : 'notify_revision_submitted';

        if (! EdmsSetting::getSetting($version->company_id, $settingKey, true)) {
            return;
        }

        $users = $this->recipients->forCompany($version->company_id);

        if ($users->isEmpty()) {
            return;
        }

        Notification::send($users, new DocumentNotification($type, [
            'reference' => $entity->title ?? $entity->subject ?? 'Item',
        ]));
    }
}
