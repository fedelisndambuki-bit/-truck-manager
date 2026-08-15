<?php

namespace App\Models;

use App\Notifications\DiaryNotification;
use App\Services\DiaryNotificationRecipients;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Notification;

class DailySiteDiary extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'date' => 'date',
        'temperature_high' => 'decimal:2',
        'temperature_low' => 'decimal:2',
        'rainfall_mm' => 'decimal:2',
        'wind_speed_kph' => 'decimal:2',
        'equipment_utilization_pct' => 'decimal:2',
        'progress_pct' => 'decimal:2',
        'weather_fetched_at' => 'datetime',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'locked_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (DailySiteDiary $diary) {
            if (empty($diary->diary_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $diary->diary_number = 'DSD-' . str_pad((string) ($lastId + 1), 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function loggedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'logged_by_id');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'submitted_by_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'reviewed_by_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'approved_by_id');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'rejected_by_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(DiaryActivity::class);
    }

    public function delays(): HasMany
    {
        return $this->hasMany(DiaryDelay::class);
    }

    public function diaryVisitors(): HasMany
    {
        return $this->hasMany(DiaryVisitor::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'daily_site_diary_id');
    }

    public function photos(): HasMany
    {
        return $this->documents()->where('category', 'Site Photos');
    }

    /**
     * Best-effort resolution of the acting Staff record for the current user,
     * matched by email since there is no direct User->Staff foreign key in this codebase.
     */
    public static function resolveActingStaffId(): ?int
    {
        $email = auth()->user()?->email;

        if (! $email) {
            return null;
        }

        return Staff::where('email', $email)->value('id');
    }

    public function submit(?int $staffId = null): void
    {
        $this->update([
            'status' => 'Submitted',
            'submitted_by_id' => $staffId ?? static::resolveActingStaffId() ?? $this->submitted_by_id,
            'submitted_at' => now(),
        ]);

        $this->notifyRecipients('diary_submitted', 'notify_submitted');
    }

    public function markReviewed(?int $staffId = null): void
    {
        $this->update([
            'status' => 'Reviewed',
            'reviewed_by_id' => $staffId ?? static::resolveActingStaffId() ?? $this->reviewed_by_id,
            'reviewed_at' => now(),
        ]);
    }

    public function approve(?int $staffId = null): void
    {
        $this->update([
            'status' => 'Approved',
            'approved_by_id' => $staffId ?? static::resolveActingStaffId() ?? $this->approved_by_id,
            'approved_at' => now(),
        ]);

        $this->notifyRecipients('diary_approved', 'notify_approved');
    }

    public function reject(string $reason, ?int $staffId = null): void
    {
        $this->update([
            'status' => 'Rejected',
            'rejected_by_id' => $staffId ?? static::resolveActingStaffId() ?? $this->rejected_by_id,
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);

        $this->notifyRecipients('diary_rejected', 'notify_rejected', ['reason' => $reason]);
    }

    public function lock(): void
    {
        $this->update([
            'status' => 'Locked',
            'locked_at' => now(),
        ]);
    }

    private function notifyRecipients(string $type, string $settingKey, array $extra = []): void
    {
        if (! DiarySetting::getSetting($this->company_id, $settingKey, true)) {
            return;
        }

        $users = app(DiaryNotificationRecipients::class)->forCompany($this->company_id);

        if ($users->isEmpty()) {
            return;
        }

        Notification::send($users, new DiaryNotification($type, array_merge([
            'diary_id' => $this->id,
            'diary_number' => $this->diary_number,
            'site_name' => $this->site?->name,
        ], $extra)));
    }
}
