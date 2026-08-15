<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;

class FinanceNotificationRecipients
{
    /**
     * Users in a company who should receive Project Finance alerts: Super Admins
     * plus anyone with approval/management responsibility over project finance.
     */
    public function forCompany(?int $companyId): Collection
    {
        return User::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->get()
            ->filter(fn (User $user) => $user->hasRole('Super Admin')
                || $user->can('project_finance.approve')
                || $user->can('project_finance.manage'));
    }
}
