<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait MultiTenantScope
{
    /**
     * Boot the trait
     */
    protected static function bootMultiTenantScope(): void
    {
        static::addGlobalScope('company', function (Builder $builder) {
            if (Auth::check() && !Auth::user()->hasRole('Super Admin')) {
                $builder->where('company_id', Auth::user()->company_id);
            }
        });

        static::creating(function (Model $model) {
            if (Auth::check() && !isset($model->company_id)) {
                $user = Auth::user();
                if ($user->company_id) {
                    $model->company_id = $user->company_id;
                }
            }
        });
    }

    /**
     * Get all records without company scope (for Super Admin)
     */
    public function scopeAllCompanies(Builder $builder): Builder
    {
        return $builder->withoutGlobalScope('company');
    }

    /**
     * Get records for a specific company
     */
    public function scopeForCompany(Builder $builder, int $companyId): Builder
    {
        return $builder->where('company_id', $companyId);
    }
}
