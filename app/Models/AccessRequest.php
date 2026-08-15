<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccessRequest extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'operations_of_interest' => 'array',
        'terms_accepted_at' => 'datetime',
        'domain_is_risky' => 'boolean',
        'captcha_verified' => 'boolean',
        'email_verification_expires_at' => 'datetime',
        'email_verified_at' => 'datetime',
    ];

    // ── Status constants ────────────────────────────────────────
    const STATUS_PENDING_EMAIL_VERIFICATION = 'pending_email_verification';
    const STATUS_EMAIL_VERIFIED = 'email_verified';
    const STATUS_PENDING_REVIEW = 'pending_review';
    const STATUS_IN_REVIEW = 'in_review';
    const STATUS_ADDITIONAL_INFORMATION_REQUIRED = 'additional_information_required';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_CANCELLED = 'cancelled';

    const TERMINAL_STATUSES = [
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
        self::STATUS_CANCELLED,
    ];

    const STATUS_LABELS = [
        self::STATUS_PENDING_EMAIL_VERIFICATION => 'Pending Email Verification',
        self::STATUS_EMAIL_VERIFIED => 'Email Verified',
        self::STATUS_PENDING_REVIEW => 'Pending Review',
        self::STATUS_IN_REVIEW => 'In Review',
        self::STATUS_ADDITIONAL_INFORMATION_REQUIRED => 'Additional Information Required',
        self::STATUS_APPROVED => 'Approved',
        self::STATUS_REJECTED => 'Rejected',
        self::STATUS_CANCELLED => 'Cancelled',
    ];

    public function scopeNonTerminal($query)
    {
        return $query->whereNotIn('status', self::TERMINAL_STATUSES);
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, self::TERMINAL_STATUSES, true);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function informationThreads(): HasMany
    {
        return $this->hasMany(AccessRequestInformationThread::class)->orderBy('created_at');
    }
}
