<?php

namespace App\Models;

use App\Notifications\DocumentNotification;
use App\Services\DocumentNotificationRecipients;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Notification;

class DocumentApproval extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'acted_at' => 'datetime',
    ];

    public function approvable(): MorphTo
    {
        return $this->morphTo();
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'approver_staff_id');
    }

    public function approve(?string $comments = null): void
    {
        $this->update([
            'status' => 'Approved',
            'comments' => $comments,
            'acted_at' => now(),
        ]);

        $next = $this->approvable->approvals()->where('status', 'Pending')->orderBy('step_order')->first();

        if ($next) {
            $next->notifyApprovalRequired();

            return;
        }

        if (method_exists($this->approvable, 'update')) {
            $this->approvable->update(['status' => 'Approved']);
        }

        $this->notifyRecipients('approval_completed', 'notify_approval_completed');
    }

    public function notifyApprovalRequired(): void
    {
        $this->notifyRecipients('approval_required', 'notify_approval_required');
    }

    protected function notifyRecipients(string $type, string $settingKey): void
    {
        if (! EdmsSetting::getSetting($this->company_id, $settingKey, true)) {
            return;
        }

        $users = app(DocumentNotificationRecipients::class)->forCompany($this->company_id);

        if ($users->isEmpty()) {
            return;
        }

        Notification::send($users, new DocumentNotification($type, [
            'reference' => $this->approvable->title ?? $this->approvable->subject ?? 'Item',
        ]));
    }

    public function reject(?string $comments = null): void
    {
        $this->update([
            'status' => 'Rejected',
            'comments' => $comments,
            'acted_at' => now(),
        ]);

        $this->approvable->update(['status' => 'Rejected']);
    }

    public function requestRevision(?string $comments = null): void
    {
        $this->update([
            'status' => 'Revision Required',
            'comments' => $comments,
            'acted_at' => now(),
        ]);

        $this->approvable->update(['status' => 'Revision Required']);
    }
}
