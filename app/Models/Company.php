<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'status',
        'industry',
        'plan_id',
        'subscription_id',
        'trial_ends_at',
        'address',
        'tax_id',
        'is_active',
        'paystack_authorization_code',
        'paystack_authorization_reusable',
        'paystack_card_last4',
        'paystack_card_type',
        'owner_user_id',
        'country',
        'size',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'trial_ends_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::created(function ($company) {
            $types = [\App\Services\FinancialService::WALLET_REVENUE, \App\Services\FinancialService::WALLET_EXPENSE, \App\Services\FinancialService::WALLET_DRIVER];
            foreach ($types as $type) {
                \App\Models\Wallet::create([
                    'id' => (string) \Illuminate\Support\Str::uuid(),
                    'company_id' => $company->id,
                    'type' => $type,
                    'available_balance' => 0,
                    'ledger_balance' => 0,
                    'version' => 1
                ]);
            }
        });
    }

    /**
     * Custom module overrides for this company.

     */
    public function getNameAttribute($value)
    {
        return $value ?: 'Unnamed Company #' . $this->id;
    }

    public function customModules(): BelongsToMany
    {
        return $this->belongsToMany(Module::class, 'company_module')
            ->withPivot('is_enabled')
            ->withTimestamps();
    }

    /**
     * Get the final list of modules enabled for this company.
     * Priority: Company-specific overrides > Plan defaults.
     */
    public function getEffectiveModules(): Collection
    {
        // 1. Get default modules from the plan
        $planModules = $this->plan?->modules()->pluck('slug')->toArray() ?? [];

        // 2. Get custom overrides
        $overrides = $this->customModules()->get();
        $enabledOverrides = $overrides->where('pivot.is_enabled', true)->pluck('slug')->toArray();
        $disabledOverrides = $overrides->where('pivot.is_enabled', false)->pluck('slug')->toArray();

        // 3. Merge: (Plan Defaults - Disabled Overrides) + Enabled Overrides
        return collect($planModules)
            ->diff($disabledOverrides)
            ->merge($enabledOverrides)
            ->unique();
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function trucks(): HasMany
    {
        return $this->hasMany(Truck::class);
    }

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function aiLogs(): HasMany
    {
        return $this->hasMany(AiLog::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function currentSubscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class, 'subscription_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function subscriptionPayments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    public function aiUsageLogs(): HasMany
    {
        return $this->hasMany(AiUsageLog::class);
    }

    public function systemLogs(): HasMany
    {
        return $this->hasMany(SystemLog::class);
    }

    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }

    public function featureFlags(): HasMany
    {
        return $this->hasMany(FeatureFlag::class);
    }

    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class, 'target_company_id');
    }
}
