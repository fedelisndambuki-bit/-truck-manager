<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;

class DriverNotificationRecipients
{
    /**
     * Users in a company who should be alerted about driver events: Super
     * Admins plus anyone with fleet/driver management responsibility.
     */
    public function forCompany(?int $companyId): Collection
    {
        return User::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->get()
            ->filter(fn (User $user) => $user->hasRole('Super Admin')
                || $user->can('driver-management')
                || $user->can('fleet-management'));
    }
}
