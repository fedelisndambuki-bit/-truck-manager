<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;

class SafetyNotificationRecipients
{
    /**
     * Users in a company who should receive safety alerts: Super Admins plus anyone
     * with investigation/approval responsibility over safety records.
     */
    public function forCompany(?int $companyId): Collection
    {
        return User::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->get()
            ->filter(fn (User $user) => $user->hasRole('Super Admin')
                || $user->can('safety.investigate')
                || $user->can('safety.manage'));
    }
}
