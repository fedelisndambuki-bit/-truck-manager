<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'price'          => 'decimal:2',
        'started_at'     => 'datetime',
        'expires_at'     => 'datetime',
        'auto_renewal'   => 'boolean',
        'reminders_sent' => 'array',
    ];

    public function getPlanNameAttribute($value)
    {
        return $value ?: ($this->plan?->name ?? 'Unnamed Plan');
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->expires_at?->isFuture();
    }

    public function daysUntilExpiry(): int
    {
        return (int) now()->diffInDays($this->expires_at, false);
    }

    public function reminderWasSent(string $key): bool
    {
        return in_array($key, $this->reminders_sent ?? []);
    }

    public function markReminderSent(string $key): void
    {
        $sent   = $this->reminders_sent ?? [];
        $sent[] = $key;
        $this->update(['reminders_sent' => array_unique($sent)]);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class);
    }
}
