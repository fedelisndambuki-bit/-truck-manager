<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
class User extends Authenticatable implements FilamentUser, HasAvatar
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected static function booted()
    {
        static::created(function ($model) {
            static::logActivity($model, 'Created');
        });

        static::updated(function ($model) {
            static::logActivity($model, 'Updated');
        });

        static::deleted(function ($model) {
            static::logActivity($model, 'Deleted');
        });
    }

    protected static function logActivity($model, $action)
    {
        $user = auth()->user();
        $description = "{$action} User: {$model->name} ({$model->email})";

        // deleted() fires after the row is actually gone from the DB — if
        // the acting user IS the user being deleted (self-service account
        // deletion, or deleting your own impersonated identity), referencing
        // that same now-nonexistent id violates system_logs' FK.
        $actingUserId = ($action === 'Deleted' && $user?->id === $model->id) ? null : $user?->id;

        \App\Models\SystemLog::create([
            'company_id' => $model->company_id ?? $user?->company_id,
            'user_id' => $actingUserId,
            'action' => "{$action} User",
            'description' => $description,
            'severity' => 'info',
            'metadata' => json_encode($model->getAttributes()),
        ]);
    }

    /**
     * The attributes that are mass assignable.
...
     * These allow the "make:filament-user" command to work.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'status', // 'active', 'suspended', 'dormant', 'on_leave'
        'avatar_path',
        'locked_until',
        'force_logout_at',
        'two_factor_enabled',
        'two_factor_method',
        'totp_secret',
        'totp_confirmed_at',
        'last_login_at',
        'company_id',
        'user_type', // internal, external
        'external_organization_id',
        'terms_accepted_at',
        'terms_version',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'last_login_at' => 'datetime',
        'locked_until' => 'datetime',
        'force_logout_at' => 'datetime',
        'two_factor_enabled' => 'boolean',
        'totp_secret' => 'encrypted',
        'totp_confirmed_at' => 'datetime',
        'terms_accepted_at' => 'datetime',
    ];

    /**
     * Allows the user to access the Filament admin panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if ($this->hasRole('Super Admin')) {
            return true;
        }

        return match ($panel->getId()) {
            'admin' => true,
            default => false,
        };
    }

    public function getNameAttribute($value)
    {
        return $value ?: 'Unnamed User #' . $this->id;
    }

    public function setEmailAttribute($value)
    {
        // SQLite compares strings case-sensitively by default, so a mixed-case
        // email stored here could fail to match a lowercased login attempt.
        // Normalise on write so lookups stay consistent everywhere.
        $this->attributes['email'] = $value ? strtolower(trim($value)) : $value;
    }

    /**
     * Get the company that owns the user.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * True for external Supplier Portal users — a regular User (same
     * MultiTenantScope company scoping as everyone else) additionally
     * linked to the Supplier record they represent, so Resources can add
     * row-level scoping on top of the normal company scope.
     */
    public function isSupplierPortalUser(): bool
    {
        return $this->supplier_id !== null;
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'email', 'email');
    }

    public function driver(): HasOne
    {
        return $this->hasOne(\App\Models\Driver::class);
    }

    public function externalOrganization(): BelongsTo
    {
        return $this->belongsTo(ExternalOrganization::class);
    }

    public function projectUsers(): HasMany
    {
        return $this->hasMany(ProjectUser::class);
    }

    public function assignedProjects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_users')
            ->withPivot(['role', 'status', 'start_date', 'end_date', 'can_download_documents', 'can_upload_documents', 'can_approve', 'can_comment', 'can_view_financials', 'can_view_drawings'])
            ->withTimestamps();
    }

    /**
     * The single blanket flag driving the project_access global scope —
     * external users are restricted by default; an internal user can also
     * be restricted explicitly via the platform.assigned_projects_only permission.
     *
     * Deliberately uses Spatie's hasPermissionTo() rather than can()/Gate —
     * AppServiceProvider registers a Gate::before granting every permission
     * to Super Admin/Company Admin/Fleet Manager/Accountant, which would
     * otherwise make can() always return true for those roles here too,
     * incorrectly restricting company-wide admins.
     */
    public function isProjectRestricted(): bool
    {
        if ($this->hasAnyRole(['Super Admin', 'Company Admin', 'Fleet Manager', 'Accountant'])) {
            return false;
        }

        return $this->user_type === 'external' || $this->hasPermissionTo('platform.assigned_projects_only');
    }

    /**
     * Checks a per-project capability toggle (e.g. can_approve). Unrestricted
     * (internal) users always pass. Restricted users with no assignment row
     * for the given project fail closed — absence of assignment already
     * means the project_access scope hid the record from them entirely.
     */
    public function canOnProject(?Project $project, string $capability): bool
    {
        if (! $this->isProjectRestricted()) {
            return true;
        }

        if (! $project) {
            return false;
        }

        $assignment = $this->projectUsers()->where('project_id', $project->id)->first();

        if (! $assignment || ! $assignment->isCurrentlyActive()) {
            return false;
        }

        return (bool) $assignment->{$capability};
    }

    public function getFilamentAvatarUrl(): ?string
    {
        if ($this->avatar_path) {
            return Storage::disk('public')->url($this->avatar_path);
        }

        if ($this->company_id) {
            $brand = BrandSetting::where('company_id', $this->company_id)->first();
            if ($brand) {
                if ($brand->logo_path) {
                    return Storage::disk('public')->url($brand->logo_path);
                }
                if ($brand->icon_path) {
                    return Storage::disk('public')->url($brand->icon_path);
                }
            }
        }
        return null;
    }
}
