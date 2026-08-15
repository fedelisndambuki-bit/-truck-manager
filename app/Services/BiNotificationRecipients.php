<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;

class BiNotificationRecipients
{
    /**
     * Users in a company who should receive Business Intelligence alerts:
     * Super Admins plus anyone with reporting/management responsibility.
     */
    public function forCompany(?int $companyId): Collection
    {
        return User::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->get()
            ->filter(fn (User $user) => $user->hasRole('Super Admin')
                || $user->can('bi.manage')
                || $user->can('bi.schedule'));
    }
}
